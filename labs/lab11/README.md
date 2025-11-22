# Lab 11 – RabbitMQ / AMQP Demo

Ten katalog zawiera prosty przykład z brokerem RabbitMQ pokazujący wysyłanie zadań (sender) i asynchroniczne
przetwarzanie (worker).

## 1. Uruchomienie RabbitMQ (Docker)
```powershell
# Windows PowerShell
docker run -d --hostname my-rabbit --name rabbitmq `
  -p 5672:5672 -p 15672:15672 rabbitmq:3-management
```
Po kilku sekundach interfejs będzie dostępny pod `http://localhost:15672` (login/hasło: `guest`/`guest`).

## 2. Konfiguracja projektu
```powershell
cd labs/lab11
copy .env.example .env  # ustaw AMQP_URL, jeśli broker jest gdzie indziej
npm install
```
Domyślne wartości w `.env`:
```
AMQP_URL=amqp://guest:guest@localhost
QUEUE_NAME=task_queue
```

## 3. Uruchomienie
Wymagane są dwa terminale:

1. Worker (konsument):
   ```powershell
   npm run worker
   ```
2. Sender (producent):
   ```powershell
   npm run sender -- "Hello...." "Drugie zadanie"
   ```
   Jeśli przekażesz wiele argumentów, zostaną złączone w jedną wiadomość.

## 4. Jak to działa
- `sender.js` łączy się z RabbitMQ, zakłada trwałą kolejkę i wysyła wiadomość z flagą `persistent`.
- `worker.js` ustawia `prefetch(1)` i potwierdza (`ack`) każdą wiadomość po przetworzeniu.
- Liczba kropek w treści wiadomości symuluje czas pracy (1 s na kropkę).
- Dzięki temu można łatwo obserwować asynchroniczne przetwarzanie i trwałość zadań.

## 5. Typowe polecenia pomocnicze
```powershell
docker logs -f rabbitmq          # obserwowanie logów brokera
npm run sender -- "Task..."      # szybkie wysłanie testowego zadania
docker stop rabbitmq; docker rm rabbitmq  # sprzątanie po demo
```

Po wykonaniu powyższych kroków masz kompletne demo do zaprezentowania w ramach lab 11.
