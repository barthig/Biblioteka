"""Embedding service — calls OpenAI for vector generation."""

import logging
from typing import Optional

import httpx
from tenacity import retry, stop_after_attempt, wait_exponential
from prometheus_client import Counter, Histogram

from app.config import settings

logger = logging.getLogger(__name__)

EMBEDDING_REQUESTS = Counter("recommendation_embedding_requests_total", "Total embedding API calls")
EMBEDDING_ERRORS = Counter("recommendation_embedding_errors_total", "Failed embedding API calls")
EMBEDDING_LATENCY = Histogram("recommendation_embedding_latency_seconds", "Embedding API latency")


@retry(stop=stop_after_attempt(3), wait=wait_exponential(multiplier=1, min=1, max=10))
def get_embedding(text: str) -> Optional[list[float]]:
    """Get embedding vector from OpenAI API."""
    if settings.openai_api_key == "change_me_openai_key":
        logger.warning("OpenAI API key not configured — returning None")
        return None

    EMBEDDING_REQUESTS.inc()
    with EMBEDDING_LATENCY.time():
        try:
            response = httpx.post(
                "https://api.openai.com/v1/embeddings",
                headers={
                    "Authorization": f"Bearer {settings.openai_api_key}",
                    "Content-Type": "application/json",
                },
                json={
                    "input": text[:8000],
                    "model": settings.openai_model,
                },
                timeout=30.0,
            )
            response.raise_for_status()
            data = response.json()
            return data["data"][0]["embedding"]
        except Exception as exc:
            EMBEDDING_ERRORS.inc()
            logger.error("Embedding API error: %s", exc)
            raise
