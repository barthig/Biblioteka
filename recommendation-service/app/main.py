"""
Recommendation Service — standalone microservice for AI-powered book recommendations.

Uses pgvector for vector similarity search (cosine distance).
Consumes UpdateBookEmbedding events from RabbitMQ.
Has its own PostgreSQL+pgvector database.
Exposes REST API for recommendations.
"""

import logging
import threading
from contextlib import asynccontextmanager

from fastapi import FastAPI
from prometheus_client import make_asgi_app

from app.config import settings
from app.consumer import EmbeddingConsumer
from app.database import engine, Base
from app.routes import health, recommendations

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(name)s: %(message)s",
)
logger = logging.getLogger(__name__)


@asynccontextmanager
async def lifespan(application: FastAPI):
    """Startup / shutdown lifecycle."""
    # Ensure pgvector extension and tables
    from sqlalchemy import text
    with engine.connect() as conn:
        conn.execute(text("CREATE EXTENSION IF NOT EXISTS vector"))
        conn.commit()
    Base.metadata.create_all(bind=engine)
    logger.info("Database tables ensured (pgvector enabled).")

    # Start RabbitMQ consumer for embedding updates
    consumer = EmbeddingConsumer()
    consumer_thread = threading.Thread(target=consumer.start, daemon=True)
    consumer_thread.start()
    logger.info("Embedding consumer started.")

    yield

    consumer.stop()
    logger.info("Recommendation service shutting down.")


app = FastAPI(
    title="Recommendation Service",
    description="AI-powered book recommendations using vector similarity (pgvector).",
    version="1.0.0",
    lifespan=lifespan,
)

# Prometheus metrics
metrics_app = make_asgi_app()
app.mount("/metrics", metrics_app)

# Routers
app.include_router(health.router)
app.include_router(recommendations.router, prefix="/api/recommendations")
