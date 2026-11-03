# Deploy Subnexa API on Render (Free Web Service)

Dockerized Laravel 13 API for [Render](https://render.com) Free Web Service ($0/month).

Related frontend: [subnexa-web](https://github.com/rohansadhukhan-codeclouds/subnexa-web)

## What this setup assumes

- Repo: `rohansadhukhan-codeclouds/subnexa-api`
- Branch: `main`
- Runtime: **Docker** (PHP is not a native Render language)
- Instance: **Free**
- HTTP server: **FrankenPHP** (Caddy + PHP 8.4) serving `public/`
- HTTP port inside the container: Render `PORT` (image default `10000`)
- Database: **external** MySQL (or Postgres). Not SQLite. Not Render paid disks.
- No Redis. Queue driver: `sync`.
- Migrations/seeds are **manual** (or a one-shot `RUN_MIGRATIONS=true`)

## 1. External database (required)

Render Free web services have an **ephemeral filesystem**. Do not use SQLite for real data.

Recommended free MySQL-compatible options:

- [TiDB Cloud Serverless](https://tidbcloud.com)
- [Aiven MySQL free trial / free tier](https://aiven.io) (check current limits)
- Any MySQL 8 host you already have

Create a database (for example `subnexa`) and collect host, port, user, password.

PostgreSQL needs `pdo_pgsql` in the image. The default Dockerfile installs MySQL (`pdo_mysql`) only — add `install-php-extensions pdo_pgsql` if you switch to Postgres.

> Render Free Postgres expires after 30 days. Prefer an external DB you control.

## 2. Generate `APP_KEY`

On your machine (from this repo):

```bash
php artisan key:generate --show
```

Copy the `base64:...` value into Render. Do not reuse a key from a public gist or screenshot.

## 3. Create the Render Web Service

1. Open [Render Dashboard](https://dashboard.render.com) → **New** → **Web Service**
2. Connect GitHub and select **`rohansadhukhan-codeclouds/subnexa-api`**
3. Settings:

| Field | Value |
| --- | --- |
| Name | `subnexa-api` (or any name) |
| Region | Closest to you / your DB |
| Branch | `main` |
| Root Directory | _(leave empty)_ |
| Runtime | **Docker** |
| Dockerfile Path | `./Dockerfile` |
| Docker Build Context Directory | `.` |
| Instance type | **Free** |

4. Health check path: `/up`
5. Do **not** add a persistent disk on Free (not available / not needed for this API).

## 4. Environment variables

In **Environment**, paste values from `.env.render.example`.

Minimum required:

```
APP_NAME=Subnexa
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:YOUR_KEY
APP_URL=https://YOUR-SERVICE.onrender.com
FRONTEND_URL=https://YOUR-FRONTEND.vercel.app
LOG_CHANNEL=stderr
LOG_LEVEL=info
DB_CONNECTION=mysql
DB_HOST=...
DB_PORT=3306
DB_DATABASE=subnexa
DB_USERNAME=...
DB_PASSWORD=...
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=sync
CACHE_CONFIG=true
RUN_MIGRATIONS=false
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=a-strong-unique-password
```

Notes:

- Set `APP_URL` to the final `https://….onrender.com` URL after the first deploy (or use your custom domain).
- Set `FRONTEND_URL` to the Vercel URL of `subnexa-web`.
- Keep `APP_DEBUG=false`.
- Change `ADMIN_*` before seeding. Do not ship `admin@subnexa.test` / `admin123` to production.
- `PORT` is injected by Render; you normally do not set it.

## 5. First deploy

Click **Create Web Service** and wait for the Docker build.

When live:

- Health: `GET https://YOUR-SERVICE.onrender.com/up` → `200`
- Status: `GET https://YOUR-SERVICE.onrender.com/api/status`

`/api/status` returns `503` with `"health":"degraded"` until the database is reachable and migrated.

## 6. Migrate and seed (manual, safe)

### Option A — Render Shell (preferred)

1. Open the service → **Shell**
2. Run:

```bash
php artisan migrate --force
php artisan db:seed --force
```

`db:seed` creates the admin from `ADMIN_NAME` / `ADMIN_EMAIL` / `ADMIN_PASSWORD`.

### Option B — one-shot migrate on boot

1. Set `RUN_MIGRATIONS=true`
2. Redeploy once
3. Set `RUN_MIGRATIONS=false` again

Seeding is **never** automatic.

## 7. Wire the frontend

In the Vercel project for `subnexa-web`:

```
LARAVEL_API_URL=https://YOUR-SERVICE.onrender.com
```

No trailing slash.

Redeploy the frontend after changing it.

## 8. Free tier behaviour

| Behaviour | Detail |
| --- | --- |
| Spin down | After ~15 minutes idle the service sleeps |
| Cold start | ~1 minute on the next request |
| Hours | 750 Free instance-hours / workspace / month |
| Disk | Ephemeral — uploads and local SQLite will disappear |
| Workers | No background worker on Free — use `QUEUE_CONNECTION=sync` |

This is fine for demos. Not ideal for a live pharmacy checkout.

## 9. Local Docker smoke test

```bash
# from the backend repo root
export APP_KEY="$(php artisan key:generate --show)"   # or paste an existing key

docker build -t subnexa-api:local .
docker compose up --build
```

Then:

```bash
curl -i http://localhost:10000/up
curl -i http://localhost:10000/api/status
```

Migrate inside the running app container:

```bash
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
```

## 10. Common errors

| Symptom | Fix |
| --- | --- |
| Deploy crash: `APP_KEY is not set` | Add `APP_KEY` in Render env |
| `/api/status` → database disconnected | Check `DB_*`, allowlist Render egress IPs if your DB requires it, run migrations |
| 500 after env change with `CACHE_CONFIG=true` | Clear cache: Shell → `php artisan config:clear` then redeploy |
| Build fails on `composer install` | Ensure `composer.lock` is committed |
| Connection refused / bad port | Confirm health check uses `/up`; Render sets `PORT` automatically; image defaults to `10000` |
| Admin login fails | Reseed with current `ADMIN_*`, confirm `users.is_admin = 1` |
| Frontend CORS / wrong host | Align `FRONTEND_URL` and Vercel `LARAVEL_API_URL` |

## 11. What not to do

- Do not commit `.env` or real passwords
- Do not enable `APP_DEBUG=true` on Render
- Do not rely on container disk for prescriptions, uploads, or SQLite
- Do not leave `RUN_MIGRATIONS=true` forever
- Do not expect Free tier to stay warm 24/7 without consuming monthly hours
