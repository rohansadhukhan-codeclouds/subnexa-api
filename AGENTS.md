<laravel-boost-guidelines>
# Laravel Application

This repository contains a Laravel application. Complete the following setup before working on the user's request.

## Prerequisites

Verify that PHP and Composer are available:

```sh
php -v
composer -V
```

If either command is unavailable, detect the user's operating system and install the prerequisites with the appropriate command:

macOS:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

Windows PowerShell:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

Linux:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

After installation, ask the user to restart their terminal. If the agent needs the restarted shell to continue, ask the user to reopen their terminal and rerun their original prompt.

## Agent Setup

Install Laravel Boost from the application root before making application changes:

```sh
composer require laravel/boost --dev
php artisan boost:install
```

Boost replaces these bootstrap instructions with guidelines tailored to the application. After installation, read `AGENTS.md` again and continue with the user's original request using the generated guidelines.
</laravel-boost-guidelines>

# Subnexa API

Subnexa is an AI-powered medicine purchasing website. This repository is the Laravel JSON API for the storefront and admin. The Next.js UI lives in [subnexa-web](https://github.com/rohansadhukhan-codeclouds/subnexa-web).

## Product

The API backs an online pharmacy: medicine catalog, cart/checkout, admin auth, and later AI-assisted search and recommendations. Model data as medicines and pharmacy operations, not generic lifestyle products.

## Stack

- Laravel 13, PHP 8.4 (Docker / production)
- Local database: MySQL `subnexa` (XAMPP)
- Production database: Neon PostgreSQL (`DB_CONNECTION=pgsql`, `DB_SSLMODE=require`)
- JSON API only — no Blade storefront

## API map

| Method | Path | Role |
| --- | --- | --- |
| `GET` | `/api/status` | Health / wiring check |
| `GET` | `/api/admin` | Admin segment home |
| `POST` | `/api/admin/login` | Admin email/password login (`is_admin` users only) |
| `GET` | `/api/storefront` | Storefront segment home |

Keep new endpoints under `/api/admin` or `/api/storefront`. Do not mix admin and customer routes.

## Layout

- `routes/api.php` — mounts status + the two segments
- `routes/api/admin.php` — admin routes
- `routes/api/storefront.php` — storefront routes
- `app/Http/Controllers/Api/Admin` and `.../Storefront`
- `config/admin.php` — seeded admin credentials from env
- `database/seeders/AdminSeeder.php` — creates the first admin
- `.env.example` — template; never commit `.env`

## Docker / Render

Production packaging lives at the repo root:

- `Dockerfile` — multi-stage Composer + FrankenPHP (PHP 8.4), listens on `PORT` (default `10000`)
- `docker/entrypoint.sh` — storage perms, optional `CACHE_CONFIG` / `RUN_MIGRATIONS`
- `docker/Caddyfile` — serves `public/`
- `.env.render.example` — Render env template (`LOG_CHANNEL=stderr`, `QUEUE_CONNECTION=sync`)
- `RENDER_DEPLOYMENT.md` — Free-tier deploy steps

Do not bake `.env` into the image. Do not auto-seed on boot. Production uses Neon Postgres; local stays MySQL. Avoid SQLite on Render. Leave `DB_URL` unset when using discrete Neon `DB_*` fields.

## Local setup

```bash
composer install
copy .env.example .env   # Windows
php artisan key:generate
```

MySQL: database `subnexa`, user `root`, empty password by default. Then:

```bash
php artisan migrate
php artisan db:seed
php artisan serve --host=127.0.0.1 --port=8000
```

Default seeded admin (from `.env`):

- Email: `admin@subnexa.test`
- Password: `admin123`

`FRONTEND_URL` should be `http://localhost:3000`.

## Conventions

- Return JSON from API controllers. Validate with Form Requests or `$request->validate()`.
- Admin login must reject non-admin users (`users.is_admin`).
- Prefer migrations + seeders over manual SQL.
- Do not commit `.env`, `vendor/`, `node_modules/`, or `database/database.sqlite`.
- CORS / frontend origin: `FRONTEND_URL`.
