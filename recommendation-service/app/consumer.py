"""RabbitMQ consumer — listens for embedding update and interaction events."""

import json
import logging
import time

import pika
from pika.exceptions import AMQPConnectionError
from tenacity import retry, stop_after_attempt, wait_exponential

from app.config import settings
from app.database import SessionLocal
from app.models import BookEmbedding, UserInteraction
from app.embedding import get_embedding
from prometheus_client import Counter

logger = logging.getLogger(__name__)

EVENTS_RECEIVED = Counter(
    "recommendation_events_received_total", "Events received", ["event_type"]
)


class EmbeddingConsumer:
    """Consumes book-related events to keep embeddings in sync."""

    def __init__(self):
        self._connection = None
        self._channel = None
        self._running = False

    @retry(stop=stop_after_attempt(10), wait=wait_exponential(multiplier=1, min=2, max=30))
    def _connect(self):
        credentials = pika.PlainCredentials(settings.rabbitmq_user, settings.rabbitmq_password)
        params = pika.ConnectionParameters(
            host=settings.rabbitmq_host,
            port=settings.rabbitmq_port,
            virtual_host=settings.rabbitmq_vhost,
            credentials=credentials,
            heartbeat=60,
        )
        self._connection = pika.BlockingConnection(params)
        self._channel = self._connection.channel()

        self._channel.exchange_declare(
            exchange=settings.rabbitmq_exchange, exchange_type="topic", durable=True
        )
        self._channel.queue_declare(queue=settings.rabbitmq_queue, durable=True)

        # Bind to relevant events
        for key in [
            "book.created",
            "book.updated",
            "book.deleted",
            "book.embedding_updated",
            "loan.borrowed",
            "loan.returned",
            "rating.created",
            "favorite.added",
        ]:
            self._channel.queue_bind(
                queue=settings.rabbitmq_queue,
                exchange=settings.rabbitmq_exchange,
                routing_key=key,
            )

        self._channel.basic_qos(prefetch_count=1)
        logger.info("Connected to RabbitMQ for embedding events.")

    def _on_message(self, channel, method, properties, body):
        event_type = method.routing_key
        EVENTS_RECEIVED.labels(event_type=event_type).inc()

        try:
            payload = json.loads(body)
            logger.info("Event: %s", event_type)

            if event_type in ("book.created", "book.updated", "book.embedding_updated"):
                self._handle_book_upsert(payload)
            elif event_type == "book.deleted":
                self._handle_book_deleted(payload)
            elif event_type in ("loan.borrowed", "loan.returned", "rating.created", "favorite.added"):
                self._handle_user_interaction(event_type, payload)

            channel.basic_ack(delivery_tag=method.delivery_tag)
        except Exception as exc:
            logger.exception("Failed: %s", exc)
            channel.basic_nack(delivery_tag=method.delivery_tag, requeue=False)

    def _handle_book_upsert(self, payload: dict):
        db = SessionLocal()
        try:
            book_id = payload["book_id"]
            title = payload.get("title", "")
            description = payload.get("description", "")

            existing = db.query(BookEmbedding).filter_by(id=book_id).first()
            if not existing:
                existing = BookEmbedding(id=book_id)
                db.add(existing)

            existing.title = title
            existing.author = payload.get("author", "")
            existing.category = payload.get("category", "")
            existing.description = description

            # Generate embedding if we have text
            text = f"{title}\n\n{description}".strip()
            if text:
                vector = get_embedding(text)
                if vector:
                    existing.embedding = vector

            db.commit()
            logger.info("Book embedding upserted: %d", book_id)
        finally:
            db.close()

    def _handle_book_deleted(self, payload: dict):
        db = SessionLocal()
        try:
            book_id = payload["book_id"]
            db.query(BookEmbedding).filter_by(id=book_id).delete()
            db.commit()
        finally:
            db.close()

    def _handle_user_interaction(self, event_type: str, payload: dict):
        db = SessionLocal()
        try:
            interaction_map = {
                "loan.borrowed": "borrow",
                "loan.returned": "return",
                "rating.created": "rate",
                "favorite.added": "favorite",
            }
            interaction = UserInteraction(
                user_id=payload["user_id"],
                book_id=payload["book_id"],
                interaction_type=interaction_map.get(event_type, event_type),
                rating=payload.get("rating"),
            )
            db.add(interaction)
            db.commit()
        finally:
            db.close()

    def start(self):
        self._running = True
        while self._running:
            try:
                self._connect()
                self._channel.basic_consume(
                    queue=settings.rabbitmq_queue,
                    on_message_callback=self._on_message,
                    auto_ack=False,
                )
                self._channel.start_consuming()
            except AMQPConnectionError:
                logger.warning("RabbitMQ connection lost. Reconnecting...")
                time.sleep(5)
            except Exception as exc:
                logger.exception("Consumer error: %s", exc)
                time.sleep(5)

    def stop(self):
        self._running = False
        try:
            self._channel and self._channel.stop_consuming()
        except Exception:
            pass
