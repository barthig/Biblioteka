"""Tests for recommendation event consumer handlers."""

from unittest.mock import MagicMock, patch

from app.consumer import EmbeddingConsumer


class TestEmbeddingConsumerMessageHandling:
    """Test RabbitMQ message acknowledgement behaviour."""

    def test_on_message_acks_after_successful_book_event(self):
        consumer = EmbeddingConsumer()
        channel = MagicMock()
        method = MagicMock()
        method.routing_key = "book.created"
        method.delivery_tag = "tag-1"

        with patch.object(consumer, "_handle_book_upsert") as handler:
            consumer._on_message(
                channel,
                method,
                None,
                b'{"book_id": 10, "title": "Clean Code"}',
            )

        handler.assert_called_once_with({"book_id": 10, "title": "Clean Code"})
        channel.basic_ack.assert_called_once_with(delivery_tag="tag-1")
        channel.basic_nack.assert_not_called()

    def test_on_message_nacks_invalid_json_without_requeue(self):
        consumer = EmbeddingConsumer()
        channel = MagicMock()
        method = MagicMock()
        method.routing_key = "book.created"
        method.delivery_tag = "tag-1"

        consumer._on_message(channel, method, None, b"not-json")

        channel.basic_ack.assert_not_called()
        channel.basic_nack.assert_called_once_with(
            delivery_tag="tag-1",
            requeue=False,
        )


class TestEmbeddingConsumerPersistence:
    """Test persistence contracts for consumer handlers."""

    def test_book_deleted_removes_embedding_and_commits(self):
        db = MagicMock()

        with patch("app.consumer.SessionLocal", return_value=db):
            EmbeddingConsumer()._handle_book_deleted({"book_id": 42})

        db.query.return_value.filter_by.assert_called_once_with(id=42)
        db.query.return_value.filter_by.return_value.delete.assert_called_once()
        db.commit.assert_called_once()
        db.close.assert_called_once()

    def test_user_interaction_maps_rating_event(self):
        db = MagicMock()

        with patch("app.consumer.SessionLocal", return_value=db):
            EmbeddingConsumer()._handle_user_interaction(
                "rating.created",
                {"user_id": 7, "book_id": 42, "rating": 5},
            )

        interaction = db.add.call_args.args[0]
        assert interaction.user_id == 7
        assert interaction.book_id == 42
        assert interaction.interaction_type == "rate"
        assert interaction.rating == 5
        db.commit.assert_called_once()
        db.close.assert_called_once()
