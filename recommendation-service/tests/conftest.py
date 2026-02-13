"""Pytest configuration for recommendation-service tests."""

import pytest
from fastapi.testclient import TestClient
from unittest.mock import MagicMock, patch


@pytest.fixture
def mock_engine():
    """Mock SQLAlchemy engine to avoid real DB connection."""
    with patch("app.database.engine") as mock_eng:
        mock_conn = MagicMock()
        mock_conn.execute = MagicMock()
        mock_conn.commit = MagicMock()
        mock_eng.connect.return_value.__enter__ = MagicMock(return_value=mock_conn)
        mock_eng.connect.return_value.__exit__ = MagicMock(return_value=False)
        yield mock_eng


@pytest.fixture
def mock_rabbitmq():
    """Mock pika to avoid real RabbitMQ connection."""
    with patch("app.routes.health.pika") as mock_pika:
        mock_connection = MagicMock()
        mock_pika.BlockingConnection.return_value = mock_connection
        mock_pika.PlainCredentials.return_value = MagicMock()
        mock_pika.ConnectionParameters.return_value = MagicMock()
        yield mock_pika


@pytest.fixture
def client(mock_engine, mock_rabbitmq):
    """Create a test client with mocked dependencies."""
    with patch("app.main.EmbeddingConsumer"):
        with patch("app.database.Base.metadata.create_all"):
            from app.main import app
            with TestClient(app) as c:
                yield c
