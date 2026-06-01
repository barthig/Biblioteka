"""Tests for the recommendation-service API routes and embedding."""

from unittest.mock import MagicMock, patch

from app.database import get_db


class TestRecommendationConfig:
    """Test suite for recommendation configuration."""

    def test_settings_defaults(self):
        """Settings should have sensible defaults."""
        from app.config import Settings
        s = Settings()
        assert s.service_name == "recommendation-service"
        assert s.service_port == 8002
        assert s.embedding_dimensions == 1536
        assert s.default_limit == 10
        assert s.similarity_threshold == 0.3

    def test_settings_rabbitmq_defaults(self):
        """RabbitMQ settings should have defaults."""
        from app.config import Settings
        s = Settings()
        assert s.rabbitmq_host == "rabbitmq"
        assert s.rabbitmq_port == 5672
        assert s.rabbitmq_exchange == "biblioteka.events"
        assert s.rabbitmq_queue == "recommendation-service.events"


class TestEmbedding:
    """Test suite for the embedding module."""

    def test_get_embedding_returns_none_with_default_key(self):
        """get_embedding should return None if API key is not configured."""
        from app.embedding import get_embedding
        result = get_embedding("test text")
        assert result is None

    def test_get_embedding_calls_openai_with_valid_key(self):
        """get_embedding should call OpenAI API when key is configured."""
        with patch("app.embedding.settings") as mock_settings:
            mock_settings.openai_api_key = "sk-test-valid-key"
            mock_settings.openai_model = "text-embedding-3-small"

            mock_response = MagicMock()
            mock_response.status_code = 200
            mock_response.json.return_value = {
                "data": [{"embedding": [0.1] * 1536}]
            }
            mock_response.raise_for_status = MagicMock()

            with patch("app.embedding.httpx.post", return_value=mock_response):
                from app.embedding import get_embedding
                result = get_embedding("test text about books")
                assert result is not None
                assert len(result) == 1536

    def test_get_embedding_raises_when_openai_request_fails(self):
        """get_embedding should surface provider errors after retry attempts."""
        with patch("app.embedding.settings") as mock_settings:
            mock_settings.openai_api_key = "sk-test-valid-key"
            mock_settings.openai_model = "text-embedding-3-small"

            with patch("app.embedding.httpx.post", side_effect=RuntimeError("api down")):
                from app.embedding import get_embedding
                from tenacity import RetryError, wait_none

                fast_get_embedding = get_embedding.retry_with(wait=wait_none())

                try:
                    fast_get_embedding("test text about books")
                except RetryError as exc:
                    assert "api down" in str(exc.last_attempt.exception())
                else:
                    raise AssertionError("Expected RetryError")


class TestSimilarBooksEndpoint:
    """Test suite for /api/recommendations/similar/{book_id}."""

    def test_similar_returns_404_when_no_embedding(self, client):
        """Should return 404 when book has no embedding."""
        mock_session = MagicMock()
        mock_query = MagicMock()
        mock_query.filter_by.return_value = mock_query
        mock_query.first.return_value = None
        mock_session.query.return_value = mock_query
        client.app.dependency_overrides[get_db] = lambda: mock_session

        try:
            response = client.get("/api/recommendations/similar/999")
        finally:
            client.app.dependency_overrides.pop(get_db, None)

        assert response.status_code == 404

    def test_search_returns_503_when_embedding_service_unavailable(self, client):
        """Semantic search should report 503 when embeddings are unavailable."""
        with patch("app.routes.recommendations.get_embedding", return_value=None):
            response = client.get("/api/recommendations/search?q=history")

        assert response.status_code == 503
