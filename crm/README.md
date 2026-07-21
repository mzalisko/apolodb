# DataBridge CRM

Laravel 13 бекенд: реєстрація сайтів, токени, приймання heartbeat, моніторинг статусу
підключення (фіча `001-site-connectivity-failover`).

**Стек**: PHP 8.3+ · Laravel 13 · PostgreSQL 16 · Redis (черга/кеш/nonce) · Blade + Vite (локальні
ассети, без CDN).

## Запуск (Docker, з кореня монорепо)

```sh
docker compose build app
docker compose run --rm app php artisan migrate --force
docker compose run --rm app php artisan test
```

Для роботи в проді потрібні (окремі процеси):

```sh
php artisan queue:work        # обробка ProcessHeartbeat (async, FR-010)
php artisan schedule:work     # sites:detect-offline щохвилини (FR-014)
```

## Конфігурація

- `config/databridge.php` — інтервал heartbeat, вікно офлайну, вікно timestamp, nonce TTL,
  rate-limits, максимальний розмір тіла (усе через env).
- `APP_KEY` (`.env`) — ключ шифрування per-site секретів (див. `../docs/secrets.md`).

## Ендпоінти

- `POST /v1/heartbeat` — публічний ingest (підпис HMAC; api-група, без сесії/CSRF).
- `POST /admin/sites`, `GET /admin/sites`, `POST /admin/sites/{id}/deactivate|reactivate`,
  `POST /admin/sites/{id}/credentials/revoke|reissue` — операторські (session + EnsureAdmin).
- `GET /admin` — Blade-список сайтів; `/admin/sites/create` — форма реєстрації.

Контракт wire-format — `../specs/001-site-connectivity-failover/contracts/ingest-contract.md`.

## Тести

`php artisan test` (PHPUnit). Тестова БД — `databridge_test` (PostgreSQL), драйвери форсуються у
`phpunit.xml` + `tests/TestCase.php` (sync-queue, array-cache).
