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
- Production database: **Neon PostgreSQL**
- Local development: MySQL (XAMPP) remains supported via `.env`
- No Redis. Queue driver: `sync`
- Migrations/seeds are **manual** (or a one-shot `RUN_MIGRATIONS=true`)

## 1. Neon PostgreSQL

Neon project connection (password only in Render env — never in Git):

| Field | Value |
| --- | --- |
| Host | `ep-round-glade-bbx5cb6e.c-15.us-east-1.aws.neon.tech` |
| Port | `5432` |
| Database | `neondb` |
| User | `neondb_owner` |
| SSL | `require` |
| Channel binding | `require` (when libpq supports it) |

### TLS / channel_binding

- `DB_SSLMODE=require` is enforced through Laravel's pgsql DSN (`sslmode=require`). TLS is not disabled.
- `DB_CHANNEL_BINDING=require` is forwarded by `App\Database\Connectors\PostgresConnector`.
- If the image's libpq is too old to understand `channel_binding`, PDO may reject the DSN. In that case keep `DB_SSLMODE=require` and temporarily omit `DB_CHANNEL_BINDING` — do **not** set `sslmode=disable`. Report the PDO error and upgrade the base image.

### Do not leave a stale `DB_URL`

When using discrete `DB_*` fields, leave `DB_URL` **empty/unset** on Render. A leftover `mysql://…` URL overrides host/database and breaks Neon.

## 2. Generate `APP_KEY`

```bash
php artisan key:generate --show
```

## 3. Create the Render Web Service

| Field | Value |
| --- | --- |
| Branch | `main` |
| Runtime | **Docker** |
| Dockerfile Path | `./Dockerfile` |
| Instance type | **Free** |
| Health check path | `/up` |

## 4. Environment variables

Use `.env.render.example` as the checklist. Required:

```
APP_NAME=Subnexa
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:YOUR_KEY
APP_URL=https://YOUR-SERVICE.onrender.com
FRONTEND_URL=https://YOUR-FRONTEND.vercel.app
LOG_CHANNEL=stderr
LOG_LEVEL=info
DB_CONNECTION=pgsql
DB_HOST=ep-round-glade-bbx5cb6e.c-15.us-east-1.aws.neon.tech
DB_PORT=5432
DB_DATABASE=neondb
DB_USERNAME=neondb_owner
DB_PASSWORD=<from Neon console — never commit>
DB_SSLMODE=require
DB_CHANNEL_BINDING=require
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=sync
CACHE_CONFIG=true
RUN_MIGRATIONS=false
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=<strong unique password>
```

Unset / do not set: `DB_URL` (unless you intentionally use a full Neon URI instead of discrete fields).

## 5. First deploy + migrate

1. Deploy the service.
2. Confirm `GET /up` → `200`.
3. Open **Shell** and run:

```bash
php artisan migrate --force
php artisan db:seed --force
```

Or one-shot: set `RUN_MIGRATIONS=true`, redeploy once, then set it back to `false`. Seeding is never automatic.

4. Confirm status:

```bash
curl -s https://YOUR-SERVICE.onrender.com/api/status
```

Expected (healthy):

```json
{
  "name": "Subnexa",
  "status": "ok",
  "health": "ok",
  "connections": {
    "database": {
      "name": "pgsql",
      "driver": "pgsql",
      "status": "connected"
    }
  }
}
```

Host and database names are intentionally omitted from the public payload.

## 6. Wire the frontend

```
LARAVEL_API_URL=https://YOUR-SERVICE.onrender.com
```

## 7. Local MySQL (unchanged)

Keep local `.env` on MySQL:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=subnexa
DB_USERNAME=root
DB_PASSWORD=
```

Docker Compose still uses MySQL for local smoke tests.

## 8. Common errors

| Symptom | Fix |
| --- | --- |
| `frankenphp: Operation not permitted` (126) | Capability strip in Dockerfile — redeploy latest image |
| `/api/status` disconnected | Check Neon password, `DB_SSLMODE=require`, unset stale `DB_URL`, run migrations |
| `channel_binding` PDO error | Remove `DB_CHANNEL_BINDING` only; keep `sslmode=require` |
| Stale MySQL settings on Render | Set `DB_CONNECTION=pgsql` and Neon `DB_*`; clear `DB_URL` |
| Admin login fails | Reseed with current `ADMIN_*` |

## 9. What not to do

- Do not commit `.env` or Neon passwords
- Do not set `APP_DEBUG=true` on Render
- Do not use SQLite on Render
- Do not leave `RUN_MIGRATIONS=true` forever
