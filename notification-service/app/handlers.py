"""Event handlers — process integration events and send notifications."""

import logging
import smtplib
from datetime import datetime, timedelta
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText

from sqlalchemy import and_

from app.config import settings
from app.database import SessionLocal
from app.models import NotificationLog
from prometheus_client import Histogram

logger = logging.getLogger(__name__)

NOTIFICATION_DURATION = Histogram(
    "notification_processing_seconds",
    "Time spent processing a notification event",
    ["event_type"],
)


def handle_event(event_type: str, payload: dict):
    """Route an integration event to the appropriate handler."""
    with NOTIFICATION_DURATION.labels(event_type=event_type).time():
        handler = EVENT_HANDLERS.get(event_type)
        if handler:
            handler(payload)
        else:
            logger.warning("No handler for event type: %s", event_type)


# ─── Helpers ────────────────────────────────────────────────────────

def _is_duplicate(db, fingerprint: str) -> bool:
    """Check deduplication window."""
    since = datetime.utcnow() - timedelta(hours=settings.dedup_window_hours)
    existing = (
        db.query(NotificationLog)
        .filter(
            and_(
                NotificationLog.fingerprint == fingerprint,
                NotificationLog.created_at >= since,
                NotificationLog.status.in_(["SENT", "PENDING"]),
            )
        )
        .first()
    )
    return existing is not None


def _send_email(to_email: str, subject: str, text_body: str, html_body: str | None = None) -> dict:
    """Send an email via SMTP."""
    try:
        msg = MIMEMultipart("alternative")
        msg["From"] = settings.smtp_from
        msg["To"] = to_email
        msg["Subject"] = subject
        msg.attach(MIMEText(text_body, "plain", "utf-8"))
        if html_body:
            msg.attach(MIMEText(html_body, "html", "utf-8"))

        with smtplib.SMTP(settings.smtp_host, settings.smtp_port) as server:
            if settings.smtp_tls:
                server.starttls()
            if settings.smtp_user:
                server.login(settings.smtp_user, settings.smtp_password)
            server.send_message(msg)

        logger.info("Email sent to %s: %s", to_email, subject)
        return {"status": "sent"}
    except Exception as exc:
        logger.error("Email send failed to %s: %s", to_email, exc)
        return {"status": "failed", "error": str(exc)}


def _log_notification(
    db,
    user_id: int,
    event_type: str,
    channel: str,
    fingerprint: str,
    payload: dict,
    result: dict,
):
    """Persist notification log to own database."""
    log_entry = NotificationLog(
        user_id=user_id,
        type=event_type,
        channel=channel,
        fingerprint=fingerprint,
        payload=payload,
        status=result["status"].upper(),
        error_message=result.get("error"),
        sent_at=datetime.utcnow() if result["status"] == "sent" else None,
    )
    db.add(log_entry)
    db.commit()


# ─── Event Handlers ────────────────────────────────────────────────

def _handle_loan_due_reminder(payload: dict):
    """Handle loan.due_reminder event."""
    db = SessionLocal()
    try:
        user_id = payload["user_id"]
        loan_id = payload["loan_id"]
        user_email = payload.get("user_email", "")
        user_name = payload.get("user_name", "Czytelniku")
        book_title = payload.get("book_title", "")
        due_date = payload.get("due_date", "")

        fingerprint = f"loan_due_{loan_id}_{due_date}"
        if _is_duplicate(db, fingerprint):
            logger.info("Duplicate skipped: %s", fingerprint)
            return

        subject = f'Przypomnienie: zwrot "{book_title}" do {due_date}'
        text = (
            f"Cześć {user_name}!\n\n"
            f'Przypominamy o terminie zwrotu książki "{book_title}". '
            f"Termin mija {due_date}.\n\n"
            f"Pozdrawiamy,\nTwoja Biblioteka"
        )

        result = _send_email(user_email, subject, text)
        _log_notification(db, user_id, "loan_due", "email", fingerprint, payload, result)
    finally:
        db.close()


def _handle_loan_overdue(payload: dict):
    """Handle loan.overdue event."""
    db = SessionLocal()
    try:
        user_id = payload["user_id"]
        loan_id = payload["loan_id"]
        user_email = payload.get("user_email", "")
        user_name = payload.get("user_name", "Czytelniku")
        book_title = payload.get("book_title", "")
        due_date = payload.get("due_date", "")
        days_late = payload.get("days_late", 1)

        fingerprint = f"loan_overdue_{loan_id}_{days_late}"
        if _is_duplicate(db, fingerprint):
            return

        subject = f'Pilne: przeterminowane wypożyczenie "{book_title}"'
        text = (
            f"Cześć {user_name}!\n\n"
            f'Wypożyczona książka "{book_title}" powinna zostać zwrócona {due_date} '
            f"i jest spóźniona o {days_late} dni.\n\n"
            f"Pozdrawiamy,\nTwoja Biblioteka"
        )

        result = _send_email(user_email, subject, text)
        _log_notification(db, user_id, "loan_overdue", "email", fingerprint, payload, result)
    finally:
        db.close()


def _handle_reservation_created(payload: dict):
    """Handle reservation.created event."""
    db = SessionLocal()
    try:
        user_id = payload["user_id"]
        reservation_id = payload["reservation_id"]
        user_email = payload.get("user_email", "")
        user_name = payload.get("user_name", "Czytelniku")
        book_title = payload.get("book_title", "")

        fingerprint = f"reservation_created_{reservation_id}"
        if _is_duplicate(db, fingerprint):
            return

        subject = f'Potwierdzenie rezerwacji: "{book_title}"'
        text = (
            f"Cześć {user_name}!\n\n"
            f'Twoja rezerwacja książki "{book_title}" została przyjęta. '
            f"Powiadomimy Cię, gdy egzemplarz będzie gotowy do odbioru.\n\n"
            f"Pozdrawiamy,\nTwoja Biblioteka"
        )

        result = _send_email(user_email, subject, text)
        _log_notification(db, user_id, "reservation_created", "email", fingerprint, payload, result)
    finally:
        db.close()


def _handle_reservation_fulfilled(payload: dict):
    """Handle reservation.fulfilled — book ready for pickup."""
    db = SessionLocal()
    try:
        user_id = payload["user_id"]
        reservation_id = payload["reservation_id"]
        user_email = payload.get("user_email", "")
        user_name = payload.get("user_name", "Czytelniku")
        book_title = payload.get("book_title", "")
        expires_at = payload.get("expires_at", "")

        fingerprint = f"reservation_ready_{reservation_id}"
        if _is_duplicate(db, fingerprint):
            return

        subject = f'Rezerwacja "{book_title}" gotowa do odbioru'
        text = (
            f"Cześć {user_name}!\n\n"
            f'Twoja rezerwacja książki "{book_title}" jest gotowa do odbioru. '
            f"Odbierz egzemplarz przed {expires_at}.\n\n"
            f"Pozdrawiamy,\nTwoja Biblioteka"
        )

        result = _send_email(user_email, subject, text)
        _log_notification(db, user_id, "reservation_ready", "email", fingerprint, payload, result)
    finally:
        db.close()


def _handle_fine_created(payload: dict):
    """Handle fine.created event."""
    db = SessionLocal()
    try:
        user_id = payload["user_id"]
        fine_id = payload.get("fine_id", 0)
        user_email = payload.get("user_email", "")
        user_name = payload.get("user_name", "Czytelniku")
        amount = payload.get("amount", "0.00")
        reason = payload.get("reason", "kara biblioteczna")

        fingerprint = f"fine_created_{fine_id}"
        if _is_duplicate(db, fingerprint):
            return

        subject = f"Nowa kara: {amount} zł — {reason}"
        text = (
            f"Cześć {user_name}!\n\n"
            f"Została naliczona kara w wysokości {amount} zł.\n"
            f"Powód: {reason}\n\n"
            f"Prosimy o uregulowanie należności.\n\n"
            f"Pozdrawiamy,\nTwoja Biblioteka"
        )

        result = _send_email(user_email, subject, text)
        _log_notification(db, user_id, "fine_created", "email", fingerprint, payload, result)
    finally:
        db.close()


def _handle_loan_borrowed(payload: dict):
    """Handle loan.borrowed event — confirmation."""
    db = SessionLocal()
    try:
        user_id = payload["user_id"]
        loan_id = payload["loan_id"]
        user_email = payload.get("user_email", "")
        user_name = payload.get("user_name", "Czytelniku")
        book_title = payload.get("book_title", "")
        due_date = payload.get("due_date", "")

        fingerprint = f"loan_borrowed_{loan_id}"
        if _is_duplicate(db, fingerprint):
            return

        subject = f'Potwierdzenie wypożyczenia: "{book_title}"'
        text = (
            f"Cześć {user_name}!\n\n"
            f'Wypożyczyłeś/aś książkę "{book_title}". '
            f"Termin zwrotu: {due_date}.\n\n"
            f"Pozdrawiamy,\nTwoja Biblioteka"
        )

        result = _send_email(user_email, subject, text)
        _log_notification(db, user_id, "loan_borrowed", "email", fingerprint, payload, result)
    finally:
        db.close()


def _handle_loan_returned(payload: dict):
    """Handle loan.returned event — confirmation."""
    db = SessionLocal()
    try:
        user_id = payload["user_id"]
        loan_id = payload["loan_id"]
        user_email = payload.get("user_email", "")
        user_name = payload.get("user_name", "Czytelniku")
        book_title = payload.get("book_title", "")

        fingerprint = f"loan_returned_{loan_id}"
        if _is_duplicate(db, fingerprint):
            return

        subject = f'Potwierdzenie zwrotu: "{book_title}"'
        text = (
            f"Cześć {user_name}!\n\n"
            f'Książka "{book_title}" została zwrócona. Dziękujemy!\n\n'
            f"Pozdrawiamy,\nTwoja Biblioteka"
        )

        result = _send_email(user_email, subject, text)
        _log_notification(db, user_id, "loan_returned", "email", fingerprint, payload, result)
    finally:
        db.close()


def _handle_reservation_expired(payload: dict):
    """Handle reservation.expired event — inform user."""
    db = SessionLocal()
    try:
        user_id = payload["user_id"]
        reservation_id = payload.get("reservation_id", 0)
        user_email = payload.get("user_email", "")
        user_name = payload.get("user_name", "Czytelniku")
        book_title = payload.get("book_title", "")

        fingerprint = f"reservation_expired_{reservation_id}"
        if _is_duplicate(db, fingerprint):
            return

        subject = f'Rezerwacja wygasła: "{book_title}"'
        text = (
            f"Cześć {user_name}!\n\n"
            f'Twoja rezerwacja książki "{book_title}" wygasła, '
            f"ponieważ nie została odebrana w wyznaczonym terminie.\n"
            f"Możesz ponownie złożyć rezerwację.\n\n"
            f"Pozdrawiamy,\nTwoja Biblioteka"
        )

        result = _send_email(user_email, subject, text)
        _log_notification(db, user_id, "reservation_expired", "email", fingerprint, payload, result)
    finally:
        db.close()


def _handle_user_blocked(payload: dict):
    """Handle user.blocked event — notify user about account block."""
    db = SessionLocal()
    try:
        user_id = payload["user_id"]
        user_email = payload.get("user_email", "")
        reason = payload.get("reason", "naruszenie regulaminu")

        fingerprint = f"user_blocked_{user_id}"
        if _is_duplicate(db, fingerprint):
            return

        subject = "Twoje konto zostało zablokowane"
        text = (
            f"Informujemy, że Twoje konto w systemie bibliotecznym zostało zablokowane.\n\n"
            f"Powód: {reason}\n\n"
            f"W celu wyjaśnienia prosimy o kontakt z biblioteką.\n\n"
            f"Pozdrawiamy,\nTwoja Biblioteka"
        )

        result = _send_email(user_email, subject, text)
        _log_notification(db, user_id, "user_blocked", "email", fingerprint, payload, result)
    finally:
        db.close()


# ─── Routing table ──────────────────────────────────────────────────

EVENT_HANDLERS = {
    "loan.borrowed": _handle_loan_borrowed,
    "loan.returned": _handle_loan_returned,
    "loan.overdue": _handle_loan_overdue,
    "loan.due_reminder": _handle_loan_due_reminder,
    "reservation.created": _handle_reservation_created,
    "reservation.fulfilled": _handle_reservation_fulfilled,
    "reservation.expired": _handle_reservation_expired,
    "fine.created": _handle_fine_created,
    "user.blocked": _handle_user_blocked,
}
