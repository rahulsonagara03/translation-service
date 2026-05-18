# Translation Management Service

Laravel 12 API service for storing, searching, tagging, and exporting translations at scale.

## Features

- Sanctum bearer-token authentication.
- Create, update, view, and search translations by locale, group, key, content, and tags.
- Normalized tag schema for indexed tag filtering.
- Streaming JSON export endpoint for frontend clients such as Vue.js.
- ETag and revalidation headers for CDN-friendly exports that still return current data.
- Bulk seed command for 100k+ translation records.
- Feature, unit, and performance smoke tests.
- OpenAPI definition in `docs/openapi.yaml`.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

The default seeded user is `test@example.com` with password `password`.

## Docker

```bash
docker compose up --build
docker compose exec app cp .env.example .env
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

The API will be available at `http://localhost:8000/api`.

## Authentication

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password","device_name":"local"}'
```

Use the returned token as `Authorization: Bearer <token>`.

## API Examples

Create a translation:

```bash
curl -X POST http://localhost:8000/api/translations \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"locale":"en","group":"auth","key":"login.button","value":"Log in","tags":["web","mobile"]}'
```

Search translations:

```bash
curl "http://localhost:8000/api/translations?locale=en&tags=web&key=login" \
  -H "Authorization: Bearer <token>"
```

Export translations:

```bash
curl "http://localhost:8000/api/translations/export?locale=en&tags=web" \
  -H "Authorization: Bearer <token>"
```

The export shape is:

```json
{
  "meta": {
    "locale": "en",
    "tags": ["web"],
    "count": 1,
    "updated_at": "2026-05-18 08:00:00"
  },
  "data": {
    "en": {
      "auth": {
        "login.button": "Log in"
      }
    }
  }
}
```

## Scalability Data

Generate 100k records:

```bash
php artisan translations:seed --count=100000 --fresh
```

The export endpoint uses the query builder and streams sorted rows directly to the response, avoiding Eloquent hydration and large in-memory payload construction. Tag filters use an indexed pivot table and `where exists` clauses so multiple tag filters remain composable.

## Tests

```bash
php artisan test
```

For coverage:

```bash
XDEBUG_MODE=coverage php artisan test --coverage --min=95
```

## Design Notes

- `translations` has a unique `(locale, group, key)` constraint for data integrity and fast exact lookups.
- Tags are normalized into `tags` and `translation_tag` instead of JSON columns so filtering is database-portable and indexable.
- CRUD writes are handled through `TranslationManager`; search and export are separate services to keep controller actions thin.
- CDN support is provided with `ETag`, `Cache-Control: public, max-age=0, must-revalidate`, and `Surrogate-Key` headers. Clients and CDNs can revalidate cheaply without serving stale translations after updates.