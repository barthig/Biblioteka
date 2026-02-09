"""Configuration via environment variables."""

from pydantic_settings import BaseSettings


class Settings(BaseSettings):
    # Service
    service_name: str = "recommendation-service"
    service_port: int = 8002
    debug: bool = False

    # Own database (pgvector)
    database_url: str = "postgresql://recommendation:recommendation@recommendation-db:5432/recommendation_db"

    # RabbitMQ
    rabbitmq_host: str = "rabbitmq"
    rabbitmq_port: int = 5672
    rabbitmq_user: str = "app"
    rabbitmq_password: str = "app"
    rabbitmq_vhost: str = "/"
    rabbitmq_exchange: str = "biblioteka.events"
    rabbitmq_queue: str = "recommendation-service.events"

    # OpenAI
    openai_api_key: str = "change_me_openai_key"
    openai_model: str = "text-embedding-3-small"
    embedding_dimensions: int = 1536

    # Recommendation defaults
    default_limit: int = 10
    similarity_threshold: float = 0.3

    # Jaeger tracing
    jaeger_host: str = "jaeger"
    jaeger_port: int = 6831

    class Config:
        env_prefix = "RECOMMENDATION_"
        env_file = ".env"


settings = Settings()
