"""Notification API routes — query logs, trigger manual re-sends."""

from datetime import datetime
from typing import Optional

from fastapi import APIRouter, Depends, Query
from sqlalchemy.orm import Session

from app.database import get_db
from app.models import NotificationLog

router = APIRouter(tags=["Notifications"])


@router.get("/logs")
def list_notification_logs(
    user_id: Optional[int] = Query(None),
    type: Optional[str] = Query(None),
    status: Optional[str] = Query(None),
    limit: int = Query(50, le=200),
    offset: int = Query(0),
    db: Session = Depends(get_db),
):
    """List notification logs from own database."""
    query = db.query(NotificationLog).order_by(NotificationLog.created_at.desc())

    if user_id is not None:
        query = query.filter(NotificationLog.user_id == user_id)
    if type is not None:
        query = query.filter(NotificationLog.type == type)
    if status is not None:
        query = query.filter(NotificationLog.status == status.upper())

    total = query.count()
    items = query.offset(offset).limit(limit).all()

    return {
        "total": total,
        "items": [
            {
                "id": item.id,
                "user_id": item.user_id,
                "type": item.type,
                "channel": item.channel,
                "status": item.status,
                "error_message": item.error_message,
                "created_at": item.created_at.isoformat() if item.created_at else None,
                "sent_at": item.sent_at.isoformat() if item.sent_at else None,
            }
            for item in items
        ],
    }


@router.get("/stats")
def notification_stats(db: Session = Depends(get_db)):
    """Simple statistics about notifications."""
    from sqlalchemy import func

    rows = (
        db.query(
            NotificationLog.status,
            func.count(NotificationLog.id),
        )
        .group_by(NotificationLog.status)
        .all()
    )

    stats = {status: count for status, count in rows}
    stats["total"] = sum(stats.values())
    return stats
