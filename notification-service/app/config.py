"""Configuration via environment variables."""

from pydantic_settings import BaseSettings


class Settings(BaseSettings):
    # Service
    service_name: str = "notification-service"
    service_port: int = 8001
    debug: bool = False

    # Database (own DB for notification logs)
    database_url: str = "postgresql://notification:notification@notification-db:5432/notification_db"

    # RabbitMQ
    rabbitmq_host: str = "rabbitmq"
    rabbitmq_port: int = 5672
    rabbitmq_user: str = "app"
    rabbitmq_password: str = "app"
    rabbitmq_vhost: str = "/"
    rabbitmq_exchange: str = "biblioteka.events"
    rabbitmq_queue: str = "notification-service.events"

    # Email (SMTP)
    smtp_host: str = "mailpit"
    smtp_port: int = 1025
    smtp_user: str = ""
    smtp_password: str = ""
    smtp_from: str = "no-reply@biblioteka.local"
    smtp_tls: bool = False

    # Retry
    max_retries: int = 3
    retry_delay_seconds: float = 1.0

    # Deduplication window (hours)
    dedup_window_hours: int = 6

    # Jaeger tracing
    jaeger_host: str = "jaeger"
    jaeger_port: int = 6831

    class Config:
        env_prefix = "NOTIFICATION_"
        env_file = ".env"


settings = Settings()
