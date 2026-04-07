# Local CMS development (MySQL registry + tenant)

Use this when you do **not** want to point the CMS at live production databases. You get two logical databases on one local MySQL server:

| Database           | Purpose |
|--------------------|---------|
| `cms_registry`     | Laravel users, sessions, roles, `domains` table |
| `cms_tenant_demo`  | Pages, blogs, content manager settings, SEO tables, etc. |

## 1. Start MySQL (Docker)

From **`app.apimstec.com`** (this folder):

```bash
docker compose up -d
```

Default: MySQL listens on **host port `3307`** (avoids clashes with XAMPP/other MySQL on 3306).  
Override: `LOCAL_MYSQL_PORT=3306 docker compose up -d`

Root password default: `root` (override with `LOCAL_MYSQL_ROOT_PASSWORD` in `.env` next to `docker-compose.yml` or export in shell).

Wait until healthy: `docker compose ps`

## 2. Configure Laravel `.env`

Copy `.env.example` → `.env`, then set at least:

```env
APP_KEY=base64:...          # php artisan key:generate
# Must match the URL you use in the browser (host + port). Wrong APP_URL breaks Inertia redirects & Ziggy routes.
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=cms_registry
DB_USERNAME=root
DB_PASSWORD=root

SESSION_DRIVER=database
SESSION_CONNECTION=mysql

QUEUE_CONNECTION=database
CACHE_STORE=database

CMS_TENANT_HOST=127.0.0.1
CMS_TENANT_PORT=3307
CMS_TENANT_DATABASE=cms_tenant_demo
CMS_TENANT_USERNAME=root
CMS_TENANT_PASSWORD=root

# Demo site row in `domains` + CORS / public API host match
LOCAL_DEMO_DOMAIN_SITE=compresspdf.local
LOCAL_DEMO_FRONTEND_URL=http://localhost:2000
```

`CMS_TENANT_*` must match the **same** host/port/user/password as the tenant database (`cms_tenant_demo`).

## 3. Bootstrap migrations + demo domain

```bash
php artisan key:generate
php artisan cms:local-bootstrap --seed
```

This will:

1. Migrate the **registry** (`database/migrations`).
2. With `--seed`: create admin user (**admin@gmail.com** / **Test@123**) and roles.
3. Migrate the **tenant** (`database/migrations/tenant`) on `cms_tenant_demo`.
4. Insert a **Domain** row (if missing) pointing at `cms_tenant_demo` (see `LocalDemoDomainSeeder`).

Run without seeding users: `php artisan cms:local-bootstrap`  
Skip tenant: `php artisan cms:local-bootstrap --skip-tenant`  
Skip domain row: `php artisan cms:local-bootstrap --skip-domain`

## 4. Run the CMS

```bash
php artisan serve --port=3000
```

Set `APP_URL=http://localhost:3000` to match. Open that URL, log in (if you used `--seed`), pick **Local demo (UI)** when asked for a site.

## 5. Point the React app at this CMS (optional)

React Vite is set to **port 2000** in the project root `vite.config.js`. For `npm run dev`, the repo includes **`.env.development`** with `VITE_API_URL=http://localhost:3000` and `VITE_SITE_DOMAIN=compresspdf.local`. Override in **`.env.local`** if needed.

If you already seeded the demo domain with an old `frontend_url`, update **Domains → Local demo → Frontend URL** to `http://localhost:2000` (or clear CORS cache: `php artisan cache:clear`) so the public API allows your dev origin.

`VITE_SITE_DOMAIN` must match `LOCAL_DEMO_DOMAIN_SITE` so `X-Domain` / path-based public API resolves the right tenant.

## 6. Reset from scratch

```bash
docker compose down -v
docker compose up -d
```

Then repeat steps 2–3 (new empty volumes recreate both databases).

## XAMPP / native MySQL instead of Docker

Create databases `cms_registry` and `cms_tenant_demo` yourself, set `DB_PORT=3306` (or your port), omit Docker, and run `php artisan cms:local-bootstrap --seed` with the same env shape as above.
