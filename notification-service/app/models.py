"""Notification log model — own table in notification-service database."""

from datetime import datetime
from sqlalchemy import Column, Integer, String, DateTime, JSON, Text
from app.database import Base


class NotificationLog(Base):
    __tablename__ = "notification_log"

    id = Column(Integer, primary_key=True, autoincrement=True)
    user_id = Column(Integer, nullable=False, index=True)
    type = Column(String(50), nullable=False, index=True)  # loan_due, loan_overdue, reservation_ready
    channel = Column(String(20), nullable=False)  # email, sms
    fingerprint = Column(String(255), nullable=False, index=True)
    payload = Column(JSON, nullable=True)
    status = Column(String(20), nullable=False, default="PENDING")  # PENDING, SENT, FAILED, SKIPPED
    error_message = Column(Text, nullable=True)
    created_at = Column(DateTime, default=datetime.utcnow, nullable=False)
    sent_at = Column(DateTime, nullable=True)

    def __repr__(self):
        return f"<NotificationLog(id={self.id}, type={self.type}, status={self.status})>"
