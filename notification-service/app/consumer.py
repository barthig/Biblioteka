"""RabbitMQ consumer — listens for integration events from the main backend."""

import json
import logging
import time

import pika
from pika.exceptions import AMQPConnectionError
from tenacity import retry, stop_after_attempt, wait_exponential

from app.config import settings
from app.handlers import handle_event
from prometheus_client import Counter

logger = logging.getLogger(__name__)

EVENTS_RECEIVED = Counter(
    "notification_events_received_total",
    "Total integration events received from RabbitMQ",
    ["event_type"],
)
EVENTS_PROCESSED = Counter(
    "notification_events_processed_total",
    "Total integration events successfully processed",
    ["event_type"],
)
EVENTS_FAILED = Counter(
    "notification_events_failed_total",
    "Total integration events that failed processing",
    ["event_type"],
)


class RabbitMQConsumer:
    """Consumes integration events from RabbitMQ exchange."""

    def __init__(self):
        self._connection = None
        self._channel = None
        self._running = False

    @retry(
        stop=stop_after_attempt(10),
        wait=wait_exponential(multiplier=1, min=2, max=30),
        reraise=True,
    )
    def _connect(self):
        """Connect to RabbitMQ with retry."""
        credentials = pika.PlainCredentials(
            settings.rabbitmq_user,
            settings.rabbitmq_password,
        )
        params = pika.ConnectionParameters(
            host=settings.rabbitmq_host,
            port=settings.rabbitmq_port,
            virtual_host=settings.rabbitmq_vhost,
            credentials=credentials,
            heartbeat=60,
            blocked_connection_timeout=300,
        )
        self._connection = pika.BlockingConnection(params)
        self._channel = self._connection.channel()

        # Declare exchange (topic exchange for event routing)
        self._channel.exchange_declare(
            exchange=settings.rabbitmq_exchange,
            exchange_type="topic",
            durable=True,
        )

        # Declare our queue
        self._channel.queue_declare(
            queue=settings.rabbitmq_queue,
            durable=True,
            arguments={
                "x-dead-letter-exchange": f"{settings.rabbitmq_exchange}.dlx",
                "x-dead-letter-routing-key": "notification.dead",
                "x-message-ttl": 86400000,  # 24h
            },
        )

        # Dead-letter exchange/queue
        self._channel.exchange_declare(
            exchange=f"{settings.rabbitmq_exchange}.dlx",
            exchange_type="direct",
            durable=True,
        )
        self._channel.queue_declare(
            queue=f"{settings.rabbitmq_queue}.dlq",
            durable=True,
        )
        self._channel.queue_bind(
            queue=f"{settings.rabbitmq_queue}.dlq",
            exchange=f"{settings.rabbitmq_exchange}.dlx",
            routing_key="notification.dead",
        )

        # Bind to relevant routing keys
        for routing_key in [
            "loan.borrowed",
            "loan.returned",
            "loan.overdue",
            "loan.due_reminder",
            "reservation.created",
            "reservation.fulfilled",
            "reservation.expired",
            "fine.created",
            "user.blocked",
        ]:
            self._channel.queue_bind(
                queue=settings.rabbitmq_queue,
                exchange=settings.rabbitmq_exchange,
                routing_key=routing_key,
            )

        # Prefetch 1 message at a time
        self._channel.basic_qos(prefetch_count=1)
        logger.info(
            "Connected to RabbitMQ: exchange=%s queue=%s",
            settings.rabbitmq_exchange,
            settings.rabbitmq_queue,
        )

    def _on_message(self, channel, method, properties, body):
        """Callback for each received message."""
        event_type = method.routing_key
        EVENTS_RECEIVED.labels(event_type=event_type).inc()

        try:
            payload = json.loads(body)
            logger.info("Received event: %s payload=%s", event_type, payload)
            handle_event(event_type, payload)
            channel.basic_ack(delivery_tag=method.delivery_tag)
            EVENTS_PROCESSED.labels(event_type=event_type).inc()
        except Exception as exc:
            logger.exception("Failed to process event %s: %s", event_type, exc)
            EVENTS_FAILED.labels(event_type=event_type).inc()
            # Reject & send to DLQ (no requeue)
            channel.basic_nack(delivery_tag=method.delivery_tag, requeue=False)

    def start(self):
        """Start consuming (blocking call — run in a thread)."""
        self._running = True
        while self._running:
            try:
                self._connect()
                self._channel.basic_consume(
                    queue=settings.rabbitmq_queue,
                    on_message_callback=self._on_message,
                    auto_ack=False,
                )
                logger.info("Notification consumer started. Waiting for events...")
                self._channel.start_consuming()
            except AMQPConnectionError:
                logger.warning("RabbitMQ connection lost. Reconnecting in 5s...")
                time.sleep(5)
            except Exception as exc:
                logger.exception("Consumer error: %s. Restarting in 5s...", exc)
                time.sleep(5)

    def stop(self):
        """Stop consuming."""
        self._running = False
        if self._channel:
            try:
                self._channel.stop_consuming()
            except Exception:
                pass
        if self._connection:
            try:
                self._connection.close()
            except Exception:
                pass
