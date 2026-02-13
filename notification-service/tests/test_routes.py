"""Tests for the notification-service API routes."""

from unittest.mock import MagicMock, patch


class TestNotificationRoutes:
    """Test suite for /api/notifications/* endpoints."""

    def test_logs_endpoint_returns_200(self, client):
        """GET /api/notifications/logs should return 200."""
        with patch("app.routes.notifications.get_db") as mock_get_db:
            mock_session = MagicMock()
            mock_query = MagicMock()
            mock_query.order_by.return_value = mock_query
            mock_query.count.return_value = 0
            mock_query.offset.return_value = mock_query
            mock_query.limit.return_value = mock_query
            mock_query.all.return_value = []
            mock_session.query.return_value = mock_query
            mock_get_db.return_value = iter([mock_session])

            response = client.get("/api/notifications/logs")
            assert response.status_code == 200
            data = response.json()
            assert "total" in data
            assert "items" in data
            assert data["total"] == 0

    def test_stats_endpoint_returns_200(self, client):
        """GET /api/notifications/stats should return stats dict."""
        with patch("app.routes.notifications.get_db") as mock_get_db:
            mock_session = MagicMock()
            mock_query = MagicMock()
            mock_query.group_by.return_value = mock_query
            mock_query.all.return_value = [("SENT", 5), ("FAILED", 1)]
            mock_session.query.return_value = mock_query
            mock_get_db.return_value = iter([mock_session])

            response = client.get("/api/notifications/stats")
            assert response.status_code == 200
            data = response.json()
            assert "total" in data


class TestNotificationConfig:
    """Test suite for notification configuration."""

    def test_settings_defaults(self):
        """Settings should have sensible defaults."""
        from app.config import Settings
        s = Settings()
        assert s.service_name == "notification-service"
        assert s.service_port == 8001
        assert s.smtp_port == 1025
        assert s.max_retries == 3

    def test_settings_rabbitmq_defaults(self):
        """RabbitMQ settings should have defaults."""
        from app.config import Settings
        s = Settings()
        assert s.rabbitmq_host == "rabbitmq"
        assert s.rabbitmq_port == 5672
        assert s.rabbitmq_exchange == "biblioteka.events"
        assert s.rabbitmq_queue == "notification-service.events"
