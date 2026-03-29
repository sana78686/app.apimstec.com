# Running compressedPDF-cms (Laravel + Vue)

## Why you saw ERR_CONNECTION_REFUSED

This app needs **two processes** at the same time:

1. **Laravel** – serves the HTML at `http://localhost:8000`
2. **Vite** – serves the Vue/JS assets (`app.js`, `Welcome.vue`, etc.)

If only Laravel is running, the browser asks the Vite dev server for those files and gets **ERR_CONNECTION_REFUSED** because Vite is not running.

---

## One-command run (recommended)

From the `compressedPDF-cms` folder:

```bash
npm run dev:all
```

This starts both Laravel (port 8000) and Vite (port 5173). Then open **http://localhost:8000**.

---

## Or run in two terminals

**Terminal 1 – Laravel:**
```bash
cd compressedPDF-cms
php artisan serve
```

**Terminal 2 – Vite:**
```bash
cd compressedPDF-cms
npm run dev
```

Then open **http://localhost:8000**.

---

## First-time setup (if needed)

- `composer install` – PHP dependencies  
- `cp .env.example .env` and `php artisan key:generate` – if no `.env`  
- `npm install` – Node dependencies  
