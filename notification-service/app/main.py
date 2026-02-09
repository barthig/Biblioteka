"""
Notification Service — standalone microservice for handling notifications.

Consumes events from RabbitMQ (integration events published by the main backend),
processes them and sends notifications via email/SMS.
Has its own PostgreSQL database for notification logs.
"""

import asyncio
import logging
import threading
from contextlib import asynccontextmanager

from fastapi import FastAPI
from prometheus_client import make_asgi_app

from app.config import settings
from app.consumer import RabbitMQConsumer
from app.database import engine, Base
from app.routes import health, notifications

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(name)s: %(message)s",
)
logger = logging.getLogger(__name__)


@asynccontextmanager
async def lifespan(application: FastAPI):
    """Startup / shutdown lifecycle."""
    # Create tables
    Base.metadata.create_all(bind=engine)
    logger.info("Database tables ensured.")

    # Start RabbitMQ consumer in background thread
    consumer = RabbitMQConsumer()
    consumer_thread = threading.Thread(target=consumer.start, daemon=True)
    consumer_thread.start()
    logger.info("RabbitMQ consumer started in background thread.")

    yield

    # Shutdown
    consumer.stop()
    logger.info("Notification service shutting down.")


app = FastAPI(
    title="Notification Service",
    description="Microservice responsible for sending notifications (email, SMS, push).",
    version="1.0.0",
    lifespan=lifespan,
)

# Mount Prometheus metrics endpoint
metrics_app = make_asgi_app()
app.mount("/metrics", metrics_app)

# Include routers
app.include_router(health.router)
app.include_router(notifications.router, prefix="/api/notifications")
