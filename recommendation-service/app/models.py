"""Models — book embeddings and interaction data stored locally."""

from datetime import datetime
from sqlalchemy import Column, Integer, String, Float, DateTime, Text
from pgvector.sqlalchemy import Vector

from app.config import settings
from app.database import Base


class BookEmbedding(Base):
    """Local copy of book embeddings for similarity search."""
    __tablename__ = "book_embedding"

    id = Column(Integer, primary_key=True)  # same ID as in catalog-service
    title = Column(String(500), nullable=False)
    author = Column(String(500), nullable=True)
    category = Column(String(255), nullable=True)
    description = Column(Text, nullable=True)
    embedding = Column(Vector(settings.embedding_dimensions), nullable=True)
    updated_at = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)

    def __repr__(self):
        return f"<BookEmbedding(id={self.id}, title={self.title})>"


class UserInteraction(Base):
    """Local copy of user-book interactions for collaborative filtering."""
    __tablename__ = "user_interaction"

    id = Column(Integer, primary_key=True, autoincrement=True)
    user_id = Column(Integer, nullable=False, index=True)
    book_id = Column(Integer, nullable=False, index=True)
    interaction_type = Column(String(30), nullable=False)  # borrow, return, rate, favorite
    rating = Column(Float, nullable=True)
    created_at = Column(DateTime, default=datetime.utcnow, nullable=False)
