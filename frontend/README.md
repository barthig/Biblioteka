Frontend (React + Vite)

This is a minimal React frontend for the Biblioteka API. It demonstrates login, books listing and a small dashboard.

How to run locally:

1. From `d:\Biblioteka-1\frontend` install dependencies:

   npm install

2. Start dev server:

   npm run dev

The frontend expects the Symfony backend to be available at the same origin (e.g., run backend locally at http://localhost:8000). If the backend runs on a different host/port you can configure a Vite proxy in `vite.config.js` (not included by default).

Vite proxy (recommended)
--
I added `vite.config.js` which proxies requests starting with `/api` to `http://localhost:8000`. That means you can run the frontend dev server and it will forward API calls to the Symfony backend automatically.

Notes:
- The app includes a left-sidebar navigation, a dashboard, books list and a simple login flow.
- Login stores a JWT in `localStorage` as `token` and the app sends it as `Authorization: Bearer <token>` when calling the API.
