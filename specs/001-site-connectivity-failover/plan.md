# Implementation Plan: Реєстрація сайтів та моніторинг статусу підключення

**Branch**: `001-site-connectivity-failover` | **Date**: 2026-07-21 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `specs/001-site-connectivity-failover/spec.md`

## Summary

MVP сполучного каркасу DataBridge: оператор реєструє сайт у CRM і отримує **публічний site-id +
одноразовий секретний ключ підпису** (US1); WordPress-плагін підписує та надсилає heartbeat статусу
підключення (~1/хв) через **проміжний проксі/ingress** (US2); CRM показує список сайтів із поточним
статусом і часом останнього оновлення (US3); оператор може відкликати/перевипустити секрет (US4).
Технічний підхід: Laravel 13 бекенд (PostgreSQL + Redis/Horizon-черга) за єдиною публічною
точкою приймання (проксі), який робить лише **несекретні** edge-перевірки; авторитетна
HMAC-верифікація й per-site секрети — виключно на бекенді (defense-in-depth). Плагін — vanilla PHP
на WordPress Plugin API, без Laravel. Деталі — у [research.md](./research.md),
[data-model.md](./data-model.md), [contracts/ingest-contract.md](./contracts/ingest-contract.md),
[quickstart.md](./quickstart.md).

## Technical Context

**Language/Version**: PHP 8.3+ (CRM — Laravel 13.x; `plugin/` — vanilla PHP, WordPress Plugin API)

**Primary Dependencies**: Laravel 13.x, Blade, Vite + npm (усі фронтенд-ассети локально, без CDN),
Redis (черга + nonce-store + rate-limit), Laravel Horizon (моніторинг воркерів), Pest (тести).
Плагін: WP Plugin API, вбудовані `hash_hmac`/`random_bytes` (ext-hash) — **без** зовнішніх
залежностей і **без** Laravel.

**Storage**: PostgreSQL 16+ (CRM — first-class сутності); Redis (ефемерне: черга, nonce-store,
rate-limit buckets); плагін тримає site-id/секрет/endpoint у WordPress Options API (autoload=no).

**Testing**: Pest (поверх PHPUnit). Автотести критичних шляхів **обов'язкові** перед злиттям
(гейт конституції): HMAC-верифікація + вікно часу, анти-replay, per-site ізоляція, повторна
верифікація на бекенді (defense-in-depth), миттєве відкликання секрету, ідемпотентність
heartbeat-upsert, детектор офлайну (перехід рівно після вікна), заборона дублю домену.

**Target Platform**: Linux-сервер для CRM-бекенду (у приватній мережі за проксі/ingress);
WordPress-сайти (плагін) на довільному, часто шаред-, хостингу.

**Project Type**: web — **двокомпонентний монорепозиторій** `crm/` (Laravel) + `plugin/`
(WordPress), єдиний канал взаємодії — задокументований підписаний HTTP-контракт (Принцип I).

**Performance Goals**: приймання ~8–9 запитів/с усталено (500 сайтів × ~1/хв) + бурсти до ~500
одночасних (SC-007); поява «онлайн» ≤ ~1 хв (SC-002); перехід «офлайн» ≤ ~5 хв / 3 пропущені
звіти (SC-003); відкликання секрету діє ≤ ~1 хв (SC-006).

**Constraints**: однонаправлена довіра, плагін знає лише публічний ендпоінт проксі (Принцип II);
нуль зовнішніх frontend-runtime-залежностей / без CDN (Принцип III); рутина асинхронно через
чергу, без гарантованого real-time (Принцип IV); TLS на **обох** ділянках (плагін↔проксі,
проксі↔бекенд); per-site HMAC-секрети лишаються на бекенді, проксі їх не тримає; 100%
незаавтентифікованих/replay-запитів відхиляються (SC-004/SC-005).

**Scale/Scope**: 500+ сайтів. Обсяг MVP — US1–US4 (реєстрація+токен, heartbeat, список,
відкликання) + проксі/ingress (обов'язковий, продукт-агностичний). Поза межами: контактні дані
й ланцюг резервних **номерів** (A-1), повна матриця доступу менеджерів (A-5), піддомени (A-6).

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

Результат адверсаріальної перевірки дизайну проти Конституції **v2.0.0** — **PASS** (жодного
порушення). Зведення гейтів:

| Принцип / секція | Гейт | Статус |
|---|---|---|
| I. Розділення компонентів | `crm/` Laravel + `plugin/` vanilla WP PHP; без Laravel у плагіні; взаємодія лише HTTP-контрактом | ✅ PASS |
| II. Однонаправлена довіра | плагін знає лише проксі; per-site секрет невивідний; бекенд відхиляє неавтентифіковане; blast radius = 1 сайт | ✅ PASS |
| III. Нуль frontend-runtime-залежностей | Blade + Vite + npm, ассети локальні, без CDN; CI-гейт на зовнішні `<script>`/`<link>` | ✅ PASS |
| IV. Асинхронність за замовчуванням (черга) | heartbeat → Redis-черга (Horizon), 202-accept; без real-time; без воскресіння failover | ✅ PASS |
| V. Дисципліна стеку | PHP 8.3+, Laravel 13.x (як «стабільна LTS»), Blade, WP Coding Standards | ✅ PASS |
| Вимоги безпеки | TLS на обох ділянках; defense-in-depth; per-site секрети лише на бекенді; аудит; секрети не в репозиторії | ✅ PASS |

**Follow-ups (не блокери, перенесено з verify-пасу):**

1. **Уточнення формулювання конституції** «Токени зберігаються хешованими на боці CRM» → для
   **HMAC-секрету** односторонній хеш неможливий (бекенд потребує оригінал для переобчислення
   підпису). Коректно — **encrypted-at-rest у відновлюваній формі** (Laravel encrypter / envelope-KMS,
   ключ поза БД); суть вимоги («неможливо прочитати у відкриту», «секрети не в репозиторії»)
   задовольняється. Паролі операторів лишаються односторонньо-хешованими (bcrypt/argon2).
   → Рекомендовано PATCH конституції (секція «Вимоги безпеки») для розрізнення цих двох випадків.
2. **Канонічний рядок підпису** зафіксовано байт-у-байт у `contracts/ingest-contract.md` §1
   (єдине джерело істини; `data-model.md` §5 дзеркалить). Критично для Принципу I.
3. **CI-гейти**: відхилення зовнішніх CDN/`<script>`/`<link>` (III); перевірка `plugin/` на
   відсутність Laravel-залежностей і захардкоджених адрес CRM (I/II).
4. **Спільний атомарний Redis** для nonce-store (`SET NX EX 600` = 2× вікна) і rate-limit за
   кількох інстансів бекенду.
5. **Надійність heartbeat** на низькотрафічних сайтах: документувати системний cron замість
   лінивого WP-Cron (уникнення хибних офлайнів проти SC-003) — задача UX/докам.
6. **Ре-рішення стеку**: якщо команда обере MySQL/database-queue замість PostgreSQL/Redis — це
   допустимо без порушення конституції, фіксується в Complexity Tracking.

## Project Structure

### Documentation (this feature)

```text
specs/001-site-connectivity-failover/
├── spec.md              # специфікація (вхід)
├── plan.md              # цей файл
├── research.md          # Phase 0 — рішення (Laravel 13, PG, Redis, HMAC-схема, детектор офлайну)
├── data-model.md        # Phase 1 — сутності (sites, site_statuses, site_credentials, event_log, users)
├── contracts/
│   └── ingest-contract.md  # Phase 1 — підписаний запит + heartbeat + admin-операції (source of truth для wire-format)
├── quickstart.md        # Phase 1 — сценарії наскрізної валідації
└── checklists/
    └── requirements.md  # якість спеки (16/16)
```

### Source Code (repository root)

Двокомпонентний монорепозиторій (Принцип I): `crm/` (Laravel) і `plugin/` (WordPress), без
взаємопроникнення; взаємодія лише через підписаний HTTP-контракт.

```text
crm/                                  # Laravel 13 (адмінка, ingest API, черги, БД)
├── app/
│   ├── Models/                       # Site, SiteStatus, SiteCredential, Group, EventLogEntry, User
│   ├── Http/Controllers/
│   │   ├── HeartbeatController.php    # POST /v1/heartbeat — авторитетна верифікація → чергу, 202
│   │   └── Admin/SiteController.php   # register / revoke / reissue / list / deactivate / reactivate
│   ├── Jobs/ProcessHeartbeat.php      # ідемпотентний latest-wins upsert у site_statuses
│   ├── Console/Commands/DetectOffline.php   # sites:detect-offline (щохвилини, set-based UPDATE)
│   ├── Services/                     # HmacVerifier, CredentialService (видача/ротація), NonceStore
│   └── Support/CanonicalRequest.php  # канонічний рядок (дзеркало contract §1, байт-у-байт)
├── database/migrations/
├── resources/views/sites/            # Blade — екран списку сайтів (легенда статусів, фільтр N із M)
├── resources/{js,css}/               # Vite — локальні ассети (Принцип III), Alpine опційно
├── routes/{web,api}.php
├── config/databridge.php             # offline_window, timestamp_tolerance, nonce_ttl, rate-limits
└── tests/{Feature,Unit}/             # Pest — критичні шляхи

plugin/                               # WordPress-плагін, vanilla PHP (без Laravel)
├── data-site.php                     # головний файл; нейтральна ідентичність «Данные сайта»
├── includes/
│   ├── class-sd-heartbeat.php        # WP-Cron ~60 c → підпис (hash_hmac) → wp_remote_post
│   ├── class-sd-signer.php           # канонічний рядок + HMAC-SHA256 (дзеркало contract §1)
│   └── class-sd-settings.php         # Options API: sd_site_id / sd_signing_secret / sd_endpoint_url
└── readme.txt

# Проксі/Ingress — інфраструктура, НЕ артефакт репозиторію (продукт-агностичний, вибір на деплої;
# критерії відбору — contracts/ingest-contract.md Додаток C). Конфігурується при розгортанні.
```

**Structure Decision**: обрано двокомпонентний монорепозиторій згідно з Принципом I. `crm/` —
Laravel-застосунок (адмінка + ingest API + Redis-черга + PostgreSQL); `plugin/` — легкий vanilla-PHP
WordPress-плагін без жодної Laravel-залежності. Єдиний канал взаємодії — підписаний HTTP-контракт
(`contracts/ingest-contract.md`), який обидва компоненти реалізують байт-у-байт. Проксі/ingress —
обов'язковий інфраструктурний шар перед `crm/`, не збирається в репозиторії (продукт-агностичний).

## Complexity Tracking

> Порушень Конституції немає — таблиця фіксує лише свідомі рішення/уточнення, що потребують
> governance-уваги (не відхилення від принципів).

| Рішення | Чому потрібне | Простіша альтернатива й чому відхилена / умова |
|---|---|---|
| Секрет HMAC — **encrypted-at-rest**, не «hashed» | HMAC-верифікація потребує оригінал секрету; односторонній хеш унеможливив би переобчислення підпису | Bcrypt-хеш секрету — неможливий для HMAC. Суть вимоги («не читається у відкриту», «не в репозиторії») задовольняється шифруванням із ключем поза БД. Потребує PATCH-уточнення формулювання конституції. |
| PostgreSQL + Redis/Horizon | MVCC для конкурентних upsert/sweep; чистий `ON CONFLICT`; часткові індекси; низьколатентна черга + nonce/rate-limit store | MySQL 8 + database-queue — прийнятні; відхилено як основні через слабші часткові індекси/семантику upsert і polling-затримку черги. Ре-рішення допустиме — записати сюди, якщо обрано. |
| Проксі/ingress як обов'язковий компонент | Приховання бекенду, edge rate-limit, TLS-термінація (FR-023..FR-033, OQ-4) | Пряме приймання на бекенді — відхилено: порушує Принцип II (топологія) і рішення OQ-4. Продукт лишається агностичним (вибір на деплої). |
| §3.4 deactivate/reactivate у контракті | Статус `inactive` (FR-013/FR-016) інакше недосяжний | Звузити enum до pending/online/offline — відхилено: суперечить FR-013/FR-016, які прямо перелічують `inactive`. Операція мінімальна й імпліцитна з цих FR. |
