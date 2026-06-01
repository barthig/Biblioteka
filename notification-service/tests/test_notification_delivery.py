"""Tests for notification delivery helpers."""

from datetime import datetime, timedelta
from unittest.mock import MagicMock, patch

from app import handlers


class TestEmailDelivery:
    """Test SMTP delivery without opening a network connection."""

    def test_send_email_returns_sent_when_smtp_accepts_message(self):
        smtp = MagicMock()
        smtp.__enter__.return_value = smtp

        with patch("app.handlers.smtplib.SMTP", return_value=smtp) as smtp_cls:
            result = handlers._send_email(
                "reader@example.com",
                "Reminder",
                "Plain text body",
                "<p>HTML body</p>",
            )

        assert result == {"status": "sent"}
        smtp_cls.assert_called_once_with(
            handlers.settings.smtp_host,
            handlers.settings.smtp_port,
        )
        smtp.send_message.assert_called_once()

    def test_send_email_returns_failed_when_smtp_raises(self):
        with patch("app.handlers.smtplib.SMTP", side_effect=OSError("smtp down")):
            result = handlers._send_email(
                "reader@example.com",
                "Reminder",
                "Plain text body",
            )

        assert result["status"] == "failed"
        assert "smtp down" in result["error"]


class TestNotificationDeduplication:
    """Test notification duplicate detection."""

    def test_is_duplicate_returns_true_when_recent_log_exists(self):
        db = MagicMock()
        query = db.query.return_value
        query.filter.return_value.first.return_value = object()

        assert handlers._is_duplicate(db, "loan_due_42_2026-05-29") is True

    def test_is_duplicate_returns_false_when_no_log_exists(self):
        db = MagicMock()
        query = db.query.return_value
        query.filter.return_value.first.return_value = None

        assert handlers._is_duplicate(db, "loan_due_42_2026-05-29") is False


class TestNotificationLogging:
    """Test persistence contract for notification log entries."""

    def test_log_notification_persists_sent_status_and_timestamp(self):
        db = MagicMock()
        before = datetime.utcnow() - timedelta(seconds=1)

        handlers._log_notification(
            db=db,
            user_id=7,
            event_type="loan_due",
            channel="email",
            fingerprint="loan_due_1_2026-05-29",
            payload={"loan_id": 1},
            result={"status": "sent"},
        )

        log_entry = db.add.call_args.args[0]
        assert log_entry.user_id == 7
        assert log_entry.type == "loan_due"
        assert log_entry.status == "SENT"
        assert log_entry.sent_at >= before
        db.commit.assert_called_once()

    def test_log_notification_persists_error_message_on_failure(self):
        db = MagicMock()

        handlers._log_notification(
            db=db,
            user_id=7,
            event_type="loan_due",
            channel="email",
            fingerprint="loan_due_1_2026-05-29",
            payload={"loan_id": 1},
            result={"status": "failed", "error": "smtp down"},
        )

        log_entry = db.add.call_args.args[0]
        assert log_entry.status == "FAILED"
        assert log_entry.error_message == "smtp down"
        assert log_entry.sent_at is None
        db.commit.assert_called_once()
