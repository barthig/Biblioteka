# Distributed Routing Contract

This document defines public API ownership in distributed mode when requests pass through Traefik.

## Rule of thumb

Frontend talks only to the gateway.
Each public path has exactly one owner behind the gateway.
Backend acts as BFF for user-facing endpoints that aggregate domain logic or enforce auth flows.
Microservices expose only their own read models or specialized APIs.

## Public path ownership

### Backend-owned paths

These paths are routed by Traefik to the Symfony backend:
- `/api/notifications`
- `/api/notifications/test`
- `/api/recommendations/personal`
- all remaining `/api/**` paths that are not explicitly delegated to a microservice
- `/health` and `/health/distributed`

Rationale:
- they depend on backend auth/session context or CQRS handlers
- they are part of the public BFF contract used by the frontend

### Notification-service paths

These paths are routed by Traefik directly to notification-service:
- `/api/notifications/logs`
- `/api/notifications/stats`

Rationale:
- they are notification-service read models backed by its own database
- they should not shadow the backend BFF endpoint `/api/notifications`

### Recommendation-service paths

These paths are routed by Traefik directly to recommendation-service:
- `/api/recommendations/similar/{bookId}`
- `/api/recommendations/for-user/{userId}`
- `/api/recommendations/search`

Rationale:
- they are recommendation-service specialized APIs
- frontend-facing personalized recommendations remain exposed through backend BFF as `/api/recommendations/personal`

## Frontend contract

Frontend code should use only public paths guaranteed by the gateway contract.
Current user-facing paths include:
- `/api/notifications`
- `/api/notifications/test`
- `/api/recommendations/personal`
- `/api/recommendations/feedback`
- `/api/recommendations/feedback/{bookId}`

If a frontend feature needs data from a microservice-only path, prefer adding a backend adapter first instead of coupling the UI directly to an internal service contract.

## Regression checks

Use these scripts after changing Traefik rules or service paths:
- `tests/integration/test_cross_service.sh`
- `tests/integration/test_gateway_routing.sh`

The gateway smoke test is focused on collision-prone paths and verifies that:
- backend-owned endpoints do not get swallowed by microservice prefix routing
- microservice-owned paths still resolve through Traefik
- wrong-owner 404s do not reappear after config changes