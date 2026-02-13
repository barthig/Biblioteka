"""Tests for event handlers."""

from unittest.mock import MagicMock, patch

from app.handlers import handle_event


class TestEventHandlers:
    """Test suite for the notification event handler dispatch."""

    def test_handle_unknown_event_does_not_crash(self):
        """Unknown event types should be silently ignored."""
        # Should not raise
        handle_event("unknown.event.type", {"data": "test"})

    def test_handle_event_dispatches_to_correct_handler(self):
        """Known event types should be dispatched to the correct handler."""
        with patch("app.handlers.EVENT_HANDLERS", {"test.event": MagicMock()}) as mock_handlers:
            handle_event("test.event", {"key": "value"})
            mock_handlers["test.event"].assert_called_once_with({"key": "value"})
