# Notification Architecture

_Last updated: 2025-11-21_

## Goals

- Automate transactional reminders for upcoming due dates, overdue loans, and reservations ready for pickup.
- Reuse Symfony Messenger for asynchronous fan-out and resilience (RabbitMQ in Docker, sync transport in tests).
- Provide channel-agnostic messages so we can plug in email (Symfony Mailer) and optional SMS/push later.
- Keep notifications idempotent and auditable so readers are not spammed and librarians can trace deliveries.

## High-level flow

1. **Scheduler command** (cron / worker) queries Doctrine repositories for eligible events (due soon, overdue, ready reservations).
2. The command emits strongly typed Messenger messages (`LoanDueReminderMessage`, etc.) with normalized payloads.
3. An async consumer handles each message by loading fresh entities, rendering templates, and dispatching channel-specific jobs.
4. Channel dispatchers send emails via Mailer and (optionally) SMS via Symfony Notifier; both write to a `notification_log` table for auditing/deduplication.

```
+---------+       +-----------------+       +----------------------+       +--------------------+
| cron    |  -->  | DispatchCommand |  -->  | Messenger transport  |  -->  | NotificationHandler |
| (5min)  |       |  (per scenario) |       |  (async RabbitMQ)    |       |  + Email/SMS send   |
+---------+       +-----------------+       +----------------------+       +--------------------+
```

## Notification scenarios

| Scenario | Trigger window | Repository contract | Message class | Channels |
| --- | --- | --- | --- | --- |
| Loan due reminder | Due date in **N days** (configurable, default 2) | `LoanRepository::findDueBetween(
    \DateTimeImmutable $from,
    \DateTimeImmutable $to,
    bool $returned = false
): Loan[]` | `LoanDueReminderMessage` (loanId, userId, dueAt) | Email mandatory, SMS optional |
| Loan overdue warning | Due date **< now** and not returned, limit once per day | `LoanRepository::findOverdueSince(\DateTimeImmutable $threshold)` | `LoanOverdueMessage` (loanId, daysLate) | Email + SMS if number available |
| Reservation ready | Reservation assigned a copy and `status=ACTIVE`, expires within pickup window | `ReservationRepository::findReadyForPickup()` | `ReservationReadyMessage` (reservationId, pickupDeadline) | Email + SMS |
| Reservation expiring reminder (optional stretch) | `expiresAt` within next 12h | `ReservationRepository::findExpiringSoon()` | `ReservationExpiringMessage` | Email |

Each repository method returns lightweight DTOs or entities; handlers always reload by ID when processing to avoid stale data.

## Message contracts

All messages live under `App\Message` and implement a shared `NotificationMessageInterface` (exposes `getUserId()` and `getContext()` for logging). Example:

```php
final class LoanDueReminderMessage implements NotificationMessageInterface
{
    public function __construct(
        private int $loanId,
        private int $userId,
        private string $dueAtIso
    ) {}

    public function getUserId(): int { return $this->userId; }
    public function getType(): string { return NotificationMessageInterface::TYPE_LOAN_DUE; }
    public function getFingerprint(): string { return sprintf('loan_due_%d_%s', $this->loanId, $this->dueAtIso); }
    public function getPayload(): array { return ['loanId' => $this->loanId, 'dueAt' => $this->dueAtIso]; }
}
```

`config/packages/messenger.yaml` routes all new messages (`LoanDueReminderMessage`, `LoanOverdueMessage`, `ReservationReadyMessage`) to the existing `async` transport. Functional tests set the DSN to `sync://` via `ApiTestCase`, dzięki czemu handler wykonuje się w tym samym procesie i zapisuje logi.

## Notification handler

`App\MessageHandler\NotificationMessageHandler` otrzymuje dowolny `NotificationMessageInterface` i wykonuje następujące kroki:

1. Ponownie ładuje użytkownika oraz powiązany byt (`Loan`, `Reservation`) z repozytoriów.
2. Buduje treść kanałów przy pomocy `NotificationContentBuilder` (plain text + HTML – obecnie generowane w kodzie, bez osobnych szablonów Twig).
3. Wysyła wiadomości poprzez `NotificationSender`, który korzysta z Symfony Mailer (DSN domyślnie `null://null`) oraz symuluje SMS-y wpisem do logów dopóki nie podłączymy realnego transportu.
4. Zapisuje wpis w tabeli `notification_log` (encja `NotificationLog`) zawierający typ, kanał, fingerprint i status (`SENT`, `FAILED`, `SKIPPED`).
5. Handler pilnuje deduplikacji w oknie 6 godzin – jeśli istnieje wpis o tym samym odcisku i kanale, komunikat jest pomijany.

## Scheduling & CLI commands

Console commands live under `App\Command` and can be wired to cron or Windows Task Scheduler:

| Command | Purpose | Suggested schedule |
| --- | --- | --- |
| `notifications:dispatch-due-reminders --days=2` | Emit reminders for loans due in N days | Every morning at 08:00 |
| `notifications:dispatch-overdue-warnings --threshold=1` | Notify patrons whose loans are X days late | Every day at 09:00 |
| `notifications:dispatch-reservation-ready` | Inform patrons when a reserved copy becomes available | Run every 10 minutes |
|
Each command uses the corresponding repository method, filters out users who already received the same notification (via `NotificationLog`), and dispatches messages to Messenger. Commands accept `--dry-run` to print counts without sending.

## Configuration surface

- `.env` / `.env.local` musi definiować `MAILER_DSN` (domyślnie `null://null`, czyli transport „/dev/null”) oraz opcjonalnie `app.notifications.from` w `config/services.yaml`.
- Włączenie realnego transportu SMS będzie wymagało pliku `config/packages/notifier.yaml` i ustawienia `SMS_TRANSPORT_DSN`; obecnie SMS-y są tylko logowane (nie wysyłamy żadnego żądania do zewnętrznego API).
- Harmonogram uruchamia polecenia CLI opisane wyżej. Jeżeli jakaś instancja jest zatrzymana, powiadomienia zostaną ponownie wysłane po wznowieniu, dzięki czemu nie zgubimy okna czasowego.

## Data additions

- New table/entity `NotificationLog` (id, user, type enum, channel enum, fingerprint hash, payload JSON, sentAt, status, error).
- Optional `NotificationTemplate` entity if librarians need to edit copy via UI; otherwise Twig templates suffice for now.

## Testing

- `tests/Functional/Command/NotificationCommandsTest.php` pokrywa trzy scenariusze: 
    1. wysyłkę przypomnienia o zbliżającym się terminie,
    2. wysyłkę rezerwacji gotowych do odbioru,
    3. działanie trybu `--dry-run` dla ostrzeżeń o zaległościach.
- Testy korzystają z sqlite w katalogu `var/test.db`, dlatego przed uruchomieniem nie potrzeba żadnych dodatkowych usług.
- Dodatkowe przypadki (np. notyfikacje SMS albo przypomnienia o wygasaniu rezerwacji) można dopisać na bazie tego samego wzorca, mockując ewentualnie `NotificationSender`.

## Rollout checklist (zrealizowane)

- [x] Dodanie Mailera/Notifiera i ustawienie `MAILER_DSN`.
- [x] Migracja `notification_log` oraz repozytoria (`LoanRepository`, `ReservationRepository`).
- [x] Klasy wiadomości, handler, builder treści i sender.
- [x] Komendy CLI + opis w README / niniejszym dokumencie.
- [x] Testy funkcjonalne komend.
