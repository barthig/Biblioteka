"""Health check route."""

import logging

import pika
from fastapi import APIRouter
from sqlalchemy import text

from app.config import settings
from app.database import engine

logger = logging.getLogger(__name__)
router = APIRouter(tags=["Health"])


@router.get("/health")
def health_check():
    checks = {}

    try:
        with engine.connect() as conn:
            conn.execute(text("SELECT 1"))
        checks["database"] = "ok"
    except Exception as exc:
        checks["database"] = f"error: {exc}"

    try:
        credentials = pika.PlainCredentials(settings.rabbitmq_user, settings.rabbitmq_password)
        params = pika.ConnectionParameters(
            host=settings.rabbitmq_host,
            port=settings.rabbitmq_port,
            virtual_host=settings.rabbitmq_vhost,
            credentials=credentials,
            connection_attempts=1,
            socket_timeout=3,
        )
        connection = pika.BlockingConnection(params)
        connection.close()
        checks["rabbitmq"] = "ok"
    except Exception as exc:
        checks["rabbitmq"] = f"error: {exc}"

    all_ok = all(v == "ok" for v in checks.values())
    return {
        "service": settings.service_name,
        "status": "ok" if all_ok else "degraded",
        "checks": checks,
    }
