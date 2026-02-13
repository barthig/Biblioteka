"""Tests for the notification-service health endpoint."""

from unittest.mock import MagicMock, patch


class TestHealthEndpoint:
    """Test suite for /health endpoint."""

    def test_health_returns_200(self, client):
        """Health endpoint should return 200 with service info."""
        response = client.get("/health")
        assert response.status_code == 200
        data = response.json()
        assert "status" in data
        assert "checks" in data
        assert "service" in data
        assert data["service"] == "notification-service"

    def test_health_ok_when_all_checks_pass(self, client):
        """Health should report 'ok' when DB and RabbitMQ are reachable."""
        response = client.get("/health")
        data = response.json()
        assert data["status"] == "ok"
        assert data["checks"]["database"] == "ok"
        assert data["checks"]["rabbitmq"] == "ok"

    def test_health_degraded_when_db_fails(self, client, mock_engine):
        """Health should report 'degraded' when database is unavailable."""
        with patch("app.routes.health.engine") as eng:
            eng.connect.side_effect = Exception("connection refused")
            response = client.get("/health")
            data = response.json()
            assert data["status"] == "degraded"
            assert "error" in data["checks"]["database"]

    def test_health_degraded_when_rabbitmq_fails(self, client, mock_rabbitmq):
        """Health should report 'degraded' when RabbitMQ is unavailable."""
        with patch("app.routes.health.pika") as pika_mock:
            pika_mock.PlainCredentials.return_value = MagicMock()
            pika_mock.ConnectionParameters.return_value = MagicMock()
            pika_mock.BlockingConnection.side_effect = Exception("connection refused")
            response = client.get("/health")
            data = response.json()
            assert data["status"] == "degraded"
            assert "error" in data["checks"]["rabbitmq"]
