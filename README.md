# Subnexa API

Laravel API for Subnexa, an AI-powered medicine purchasing website.

This service exposes JSON endpoints for the customer storefront and the admin panel. The Next.js frontend is [subnexa-web](https://github.com/rohansadhukhan-codeclouds/subnexa-web).

## Stack

- Laravel 13
- PHP 8.3+
- MySQL

## API

| Method | Path | Purpose |
| --- | --- | --- |
| `GET` | `/api/status` | API health |
| `GET` | `/api/admin` | Admin segment |
| `POST` | `/api/admin/login` | Admin login |
| `GET` | `/api/storefront` | Storefront segment |

## Setup

```bash
composer install
copy .env.example .env
php artisan key:generate
```

Create a MySQL database named `subnexa`. Default local connection in `.env.example`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=subnexa
DB_USERNAME=root
DB_PASSWORD=
```

Then:

```bash
php artisan migrate
php artisan db:seed
php artisan serve --host=127.0.0.1 --port=8000
```

API: [http://127.0.0.1:8000](http://127.0.0.1:8000)

Seeded admin:

- Email: `admin@subnexa.test`
- Password: `admin123`

Set `FRONTEND_URL=http://localhost:3000` so the web app can call this API.

## Docker / Render

Production image and Free-tier Render instructions:

- `Dockerfile` — multi-stage PHP 8.4 + FrankenPHP
- `docker-compose.yml` — local app + MySQL smoke test
- `.env.render.example` — env template for Render
- [RENDER_DEPLOYMENT.md](./RENDER_DEPLOYMENT.md) — full deploy guide

```bash
docker build -t subnexa-api:local .
```

## Related repo

- Web: [subnexa-web](https://github.com/rohansadhukhan-codeclouds/subnexa-web)
