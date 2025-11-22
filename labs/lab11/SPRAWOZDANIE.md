# Sprawozdanie – Lab 11 (RabbitMQ / AMQP)

## Cel
Celem laboratorium było skonfigurowanie brokera wiadomości zgodnego z AMQP oraz przygotowanie
prostej aplikacji Node.js wykorzystującej RabbitMQ do asynchronicznego przetwarzania zadań
(podział na producenta i konsumenta).

## Konfiguracja środowiska
1. Uruchomienie RabbitMQ z panelem zarządzania:
   ```powershell
   docker run -d --hostname my-rabbit --name rabbitmq \
     -p 5672:5672 -p 15672:15672 rabbitmq:3-management
   ```
   W repozytorium projektowym działa już kontener `biblioteka-1-rabbitmq-1` (prekonfigurowany w
   `docker-compose.yml`) z użytkownikiem `app/app`.
2. W katalogu `labs/lab11` przygotowano projekt Node:
   ```powershell
   cd labs/lab11
   copy .env.example .env   # ustawiono AMQP_URL=amqp://app:app@localhost
   npm install
   ```
3. Zależności: `amqplib` (komunikacja AMQP) oraz `dotenv` (konfiguracja środowiskowa).

## Implementacja
- `sender.js` – tworzy połączenie i kanał, zakłada trwałą kolejkę (`durable: true`) i wysyła wiadomość
  z flagą `persistent: true`. Wiadomość przekazywana jest przez argumenty CLI.
- `worker.js` – łączy się z tą samą kolejką, ustawia `prefetch(1)` i konsumuje wiadomości z ręcznym
  potwierdzeniem (`channel.ack`). Liczba kropek w treści symuluje czas pracy (1 s na kropkę).
- Zmienna `QUEUE_NAME` (`task_queue`) i adres `AMQP_URL` pobierane są z `.env`, dzięki czemu kod
  łatwo wskazać na inny broker.

## Testy / Demonstracja
1. W terminalu A uruchomiono konsumenta:
   ```powershell
   npm run worker
   ```
   Log: `[x] Oczekiwanie na wiadomości w kolejce task_queue...`
2. W terminalu B wysłano dwie wiadomości:
   ```powershell
   npm run sender -- "Hello..."
   npm run sender -- "Work....load"
   ```
   Sender potwierdził: `[x] Wysłano do task_queue: '...'`.
3. Konsument odebrał i przetworzył oba zadania:
   ```
   [.] Otrzymano: 'Hello...'
   [v] Skończono: 'Hello...'
   [.] Otrzymano: 'Work....load'
   [v] Skończono: 'Work....load'
   ```
   Dłuższa liczba kropek w drugim komunikacie spowodowała odpowiednio dłuższe oczekiwanie
   (symulacja obciążenia CPU).
4. Panel RabbitMQ dostępny był pod `http://localhost:15672` (login `app`, hasło `app`), co pozwala
   obserwować kolejkę `task_queue` i statystyki ruchu.

## Wnioski
- Zadanie spełnia wymagania labu: jest skonfigurowany broker AMQP, aplikacja potrafi wysyłać i
  odbierać wiadomości, a worker przetwarza je asynchronicznie z potwierdzeniem.
- Projekt można łatwo rozszerzyć o wielu workerów (skalowanie horyzontalne) lub inne kolejki.
- Kluczowe parametry niezależne od kodu (URL, nazwa kolejki) są w `.env`, co ułatwia wdrożenie w
  innym środowisku (np. w chmurze lub w Kubernetesie).
