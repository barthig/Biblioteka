"""Recommendation API routes."""

from typing import Optional

from fastapi import APIRouter, Depends, Query, HTTPException
from sqlalchemy import text
from sqlalchemy.orm import Session

from app.config import settings
from app.database import get_db
from app.embedding import get_embedding
from app.models import BookEmbedding, UserInteraction
from prometheus_client import Histogram

router = APIRouter(tags=["Recommendations"])

RECOMMENDATION_LATENCY = Histogram(
    "recommendation_request_latency_seconds",
    "Latency of recommendation requests",
    ["endpoint"],
)


@router.get("/similar/{book_id}")
def get_similar_books(
    book_id: int,
    limit: int = Query(10, le=50),
    db: Session = Depends(get_db),
):
    """Find books similar to a given book using cosine distance."""
    with RECOMMENDATION_LATENCY.labels(endpoint="similar").time():
        source = db.query(BookEmbedding).filter_by(id=book_id).first()
        if not source or source.embedding is None:
            raise HTTPException(status_code=404, detail="Book embedding not found")

        # pgvector cosine distance query
        results = db.execute(
            text("""
                SELECT id, title, author, category,
                       1 - (embedding <=> :embedding) AS similarity
                FROM book_embedding
                WHERE id != :book_id AND embedding IS NOT NULL
                ORDER BY embedding <=> :embedding
                LIMIT :limit
            """),
            {
                "embedding": str(source.embedding),
                "book_id": book_id,
                "limit": limit,
            },
        ).fetchall()

        return {
            "source_book_id": book_id,
            "recommendations": [
                {
                    "book_id": row.id,
                    "title": row.title,
                    "author": row.author,
                    "category": row.category,
                    "similarity": round(float(row.similarity), 4),
                }
                for row in results
                if float(row.similarity) >= settings.similarity_threshold
            ],
        }


@router.get("/for-user/{user_id}")
def get_user_recommendations(
    user_id: int,
    limit: int = Query(10, le=50),
    db: Session = Depends(get_db),
):
    """Get personalized recommendations based on user interaction history."""
    with RECOMMENDATION_LATENCY.labels(endpoint="for_user").time():
        # Get books the user has interacted with (borrowed, rated, favorited)
        interacted_book_ids = (
            db.query(UserInteraction.book_id)
            .filter(UserInteraction.user_id == user_id)
            .distinct()
            .all()
        )

        if not interacted_book_ids:
            # Cold start — return popular books
            popular = db.execute(
                text("""
                    SELECT be.id, be.title, be.author, be.category,
                           COUNT(ui.id) AS interaction_count
                    FROM book_embedding be
                    LEFT JOIN user_interaction ui ON ui.book_id = be.id
                    GROUP BY be.id, be.title, be.author, be.category
                    ORDER BY interaction_count DESC
                    LIMIT :limit
                """),
                {"limit": limit},
            ).fetchall()

            return {
                "user_id": user_id,
                "strategy": "popular",
                "recommendations": [
                    {
                        "book_id": row.id,
                        "title": row.title,
                        "author": row.author,
                        "score": int(row.interaction_count),
                    }
                    for row in popular
                ],
            }

        book_ids = [r[0] for r in interacted_book_ids]

        # Average embedding of interacted books → user "taste profile"
        embeddings = (
            db.query(BookEmbedding)
            .filter(BookEmbedding.id.in_(book_ids), BookEmbedding.embedding.isnot(None))
            .all()
        )

        if not embeddings:
            raise HTTPException(404, "No embeddings for user's books")

        # Compute centroid in Python (simple average)
        import numpy as np

        vectors = [e.embedding for e in embeddings if e.embedding is not None]
        if not vectors:
            raise HTTPException(404, "No embeddings available")

        centroid = np.mean(vectors, axis=0).tolist()

        # Find closest books to centroid that user hasn't seen
        placeholders = ", ".join(str(bid) for bid in book_ids)
        results = db.execute(
            text(f"""
                SELECT id, title, author, category,
                       1 - (embedding <=> :centroid) AS similarity
                FROM book_embedding
                WHERE id NOT IN ({placeholders})
                  AND embedding IS NOT NULL
                ORDER BY embedding <=> :centroid
                LIMIT :limit
            """),
            {"centroid": str(centroid), "limit": limit},
        ).fetchall()

        return {
            "user_id": user_id,
            "strategy": "content_based",
            "books_used": len(vectors),
            "recommendations": [
                {
                    "book_id": row.id,
                    "title": row.title,
                    "author": row.author,
                    "similarity": round(float(row.similarity), 4),
                }
                for row in results
                if float(row.similarity) >= settings.similarity_threshold
            ],
        }


@router.get("/search")
def semantic_search(
    q: str = Query(..., min_length=2, description="Search query"),
    limit: int = Query(10, le=50),
    db: Session = Depends(get_db),
):
    """Semantic search — find books by meaning, not just keywords."""
    with RECOMMENDATION_LATENCY.labels(endpoint="semantic_search").time():
        vector = get_embedding(q)
        if vector is None:
            raise HTTPException(503, "Embedding service unavailable")

        results = db.execute(
            text("""
                SELECT id, title, author, category, description,
                       1 - (embedding <=> :query_vec) AS similarity
                FROM book_embedding
                WHERE embedding IS NOT NULL
                ORDER BY embedding <=> :query_vec
                LIMIT :limit
            """),
            {"query_vec": str(vector), "limit": limit},
        ).fetchall()

        return {
            "query": q,
            "results": [
                {
                    "book_id": row.id,
                    "title": row.title,
                    "author": row.author,
                    "category": row.category,
                    "similarity": round(float(row.similarity), 4),
                }
                for row in results
                if float(row.similarity) >= settings.similarity_threshold
            ],
        }
