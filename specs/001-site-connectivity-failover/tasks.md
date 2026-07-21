---
description: "Task list — Feature 001: Реєстрація сайтів та моніторинг статусу підключення"
---

# Tasks: Реєстрація сайтів та моніторинг статусу підключення

**Input**: Design documents from `specs/001-site-connectivity-failover/`

**Prerequisites**: plan.md ✅, spec.md ✅, research.md ✅, data-model.md ✅, contracts/ ✅, quickstart.md ✅

**Tests**: включені — Конституція v2.0.1 («Робочий процес і контроль якості») **вимагає** автотести
критичних шляхів перед злиттям (HMAC/час, replay, per-site ізоляція, повторна верифікація на
бекенді, миттєве відкликання, ідемпотентність upsert, детектор офлайну, дубль домену).

**Organization**: задачі згруповані за user story (US1..US4, усі P1) для незалежної реалізації й
тестування. Джерело wire-format — `contracts/ingest-contract.md` §1 (байт-у-байт).

## Format: `[ID] [P?] [Story] Description`

- **[P]**: можна виконувати паралельно (різні файли, без залежностей)
- **[Story]**: US1..US4 (лише у фазах user story)
- Кожна задача містить точний шлях до файлу

## Path Conventions (з plan.md, Принцип I — монорепо)

- CRM (Laravel 13): `crm/app/`, `crm/database/`, `crm/resources/`, `crm/routes/`, `crm/config/`, `crm/tests/`
- Плагін (vanilla WP PHP): `plugin/data-site.php`, `plugin/includes/`

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: ініціалізація монорепо та базова структура

- [x] T001 Створити структуру монорепо: каталоги `crm/` (Laravel) і `plugin/` (WordPress) у корені (Принцип I)
- [x] T002 Ініціалізувати Laravel 13.x у `crm/` (PHP 8.3+); налаштувати `crm/.env` під PostgreSQL 16 + Redis
- [ ] T003 [P] Додати й налаштувати Laravel Horizon (Redis-черга) у `crm/config/horizon.php`
- [ ] T004 [P] Налаштувати Pest у `crm/` (`crm/tests/Pest.php`, `crm/phpunit.xml`)
- [ ] T005 [P] Налаштувати Vite + npm із **локальними** ассетами (шрифти/Tailwind/Alpine self-hosted, без CDN — Принцип III) у `crm/package.json`, `crm/vite.config.js`
- [x] T006 [P] Створити `crm/config/databridge.php` з параметрами: `offline_window` (~300s), `timestamp_tolerance` (±300s), `nonce_ttl` (600s), `heartbeat_interval` (~60s), rate-limits (proxy ~10/min, backend ~6/min), `max_body` (8 KiB)
- [x] T007 [P] Скелет WordPress-плагіна: `plugin/data-site.php` (нейтральна ідентичність «Данные сайта», префікс `sd_`/`SD_`, заголовок плагіна) + `plugin/readme.txt`
- [ ] T008 [P] Лінтери/стандарти: Laravel Pint у `crm/`; PHPCS + WordPress Coding Standards у `plugin/` (`plugin/phpcs.xml`)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: ядро, що ПОВИННО бути готове до будь-якої user story

**⚠️ CRITICAL**: жодна user story не починається, доки ця фаза не завершена

- [x] T009 Міграції PostgreSQL у `crm/database/migrations/`: `sites`, `site_statuses` (1:1), `site_credentials`, `groups`+`site_group`, `event_log_entries`, `users`+`user_site_access` — з констрейнтами (UNIQUE `domain`, UNIQUE `site_identifier`, CHECK-enum статусів/станів) та індексами (частковий `(last_seen_at) WHERE status='online'`, `(status)`, частковий UNIQUE `(site_id) WHERE state='active'`) за `data-model.md`
- [x] T010 [P] Eloquent-моделі у `crm/app/Models/`: `Site`, `SiteStatus`, `SiteCredential`, `Group`, `EventLogEntry`, `User` — зв'язки, enum-касти, **encrypted-cast** для секрету (encrypted-at-rest, конституція v2.0.1)
- [x] T011 [P] `crm/app/Support/CanonicalRequest.php` — побудова канонічного рядка **байт-у-байт** за `contracts/ingest-contract.md` §1.2 (7 полів, включно з `site-id`)
- [x] T012 [P] `crm/app/Services/HmacVerifier.php` — переобчислення HMAC-SHA256 і **constant-time** порівняння (`hash_equals`)
- [x] T013 [P] `crm/app/Services/NonceStore.php` — Redis `SET NX EX 600` (анти-replay) + перевірка timestamp-вікна (±300s)
- [x] T014 [P] `crm/app/Services/EventLogger.php` — append-only запис у `event_log_entries` (хто/коли/тип/було→стало; **секрети ніколи не логуються**)
- [x] T015 [P] `crm/app/Services/CredentialService.php` — генерація публічного `site_identifier` (opaque high-entropy) + секрету (256-bit CSPRNG), encrypted-at-rest; методи issue/revoke/reissue (спільні для US1 і US4)
- [x] T016 Гейт автентифікації адмінки + обмеження **admin-only** (A-5) у `crm/app/Http/Middleware/EnsureAdmin.php` та `crm/routes/web.php` (стандартний логін, дизайн §11)
- [ ] T017 [P] Нейтральні відповіді про помилки (contract §2.4 — без топології/існування інших сайтів) у `crm/app/Exceptions/Handler.php`
- [x] T018 [P] Pest-фабрики у `crm/database/factories/` для `Site`, `SiteCredential`, `User`

**Checkpoint**: фундамент готовий — user stories можна починати (паралельно, якщо є ресурс)

---

## Phase 3: User Story 1 — Реєстрація сайту та видача токена (Priority: P1) 🎯 MVP

**Goal**: оператор реєструє сайт (назва+домен) і одноразово отримує публічний `site-id` + секрет.

**Independent Test**: `POST /admin/sites` → `201` з `site-id` + `signing_secret` (показ **один раз**), статус `pending`; повторний домен → `409` без дубля; повторний перегляд секрету → лише маскований.

### Tests for User Story 1 ⚠️

- [x] T019 [P] [US1] Feature-тест реєстрації (`201`, site-id + secret, статус `pending`) у `crm/tests/Feature/RegisterSiteTest.php`
- [x] T020 [P] [US1] Feature-тест дубля домену (`409`, без дубля — FR-006) у `crm/tests/Feature/RegisterSiteDuplicateTest.php`
- [x] T021 [P] [US1] Тест «секрет показується один раз» (далі маскований — FR-004) у `crm/tests/Feature/SecretShownOnceTest.php`

### Implementation for User Story 1

- [x] T022 [P] [US1] `crm/app/Services/DomainNormalizer.php` — нормалізація домену (lowercase, зняття схеми/порту/слеша) перед UNIQUE-перевіркою (FR-006)
- [x] T023 [US1] `SiteController@register` (`POST /admin/sites`) у `crm/app/Http/Controllers/Admin/SiteController.php` — створює `site` (status `pending`), викликає `CredentialService::issue`, повертає `201` з `credentials` (секрет один раз) за contract §3.1 (залежить від T015, T022)
- [x] T024 [US1] Емісія подій `site_registered` + `token_issued` через `EventLogger` у `crm/app/Http/Controllers/Admin/SiteController.php` (register, FR-021)
- [ ] T025 [P] [US1] Blade-форма «Додати сайт» + показ одноразового секрету у `crm/resources/views/sites/create.blade.php`
- [x] T026 [US1] Маршрут реєстрації у `crm/routes/web.php` (під `EnsureAdmin`)

**Checkpoint**: US1 повністю функціональна й тестована незалежно

---

## Phase 4: User Story 2 — Автентифікація плагіна та heartbeat чергою (Priority: P1)

**Goal**: плагін підписує й надсилає heartbeat через проксі; CRM авторитетно верифікує та обробляє асинхронно; офлайн виявляється за тишею.

**Independent Test**: валідний підписаний heartbeat → `202` + сайт `online` ≤~1 хв; без токена/невалідний підпис/невідомий id/відкликаний/replay → відхилено, статус без змін; після ~5 хв тиші → `offline`.

### Tests for User Story 2 ⚠️

- [ ] T027 [P] [US2] Feature-тест приймання валідного heartbeat (`202`, upsert `online`) у `crm/tests/Feature/HeartbeatAcceptTest.php`
- [ ] T028 [P] [US2] Тест відхилення неавтентифікованих/невалідний підпис/невідомий site-id/відкликаний (`401/403`, статус без змін — SC-004) у `crm/tests/Feature/HeartbeatRejectTest.php`
- [ ] T029 [P] [US2] Тест анти-replay (повтор nonce / timestamp поза вікном → відхилено — SC-005) у `crm/tests/Feature/HeartbeatReplayTest.php`
- [ ] T030 [P] [US2] Unit-тест `HmacVerifier` + `CanonicalRequest` (constant-time; байт-точний канонік) у `crm/tests/Unit/HmacVerifierTest.php`
- [ ] T031 [P] [US2] Тест ідемпотентності upsert (повтори/гонки → latest-wins без дублів) у `crm/tests/Feature/HeartbeatIdempotentTest.php`
- [ ] T032 [P] [US2] Тест детектора офлайну (перехід `online→offline` рівно після вікна від `last_seen_at` — SC-003) у `crm/tests/Feature/DetectOfflineTest.php`

### Implementation for User Story 2

- [ ] T033 [US2] `HeartbeatController` (`POST /v1/heartbeat`) у `crm/app/Http/Controllers/HeartbeatController.php` — рівень-2 верифікація (lookup `site-id` → `active` → HMAC → timestamp → nonce) → dispatch job → `202` (FR-007/010/011/028; залежить від T011–T013)
- [ ] T034 [US2] `ProcessHeartbeat` job у `crm/app/Jobs/ProcessHeartbeat.php` — ідемпотентний upsert `site_statuses` (`online`, `last_seen_at`), оновлення `credential.last_used_at`, емісія `status_changed` на транзиції
- [ ] T035 [US2] Команда `sites:detect-offline` у `crm/app/Console/Commands/DetectOffline.php` — set-based UPDATE `online→offline` (`last_seen_at < now-offline_window`) + емісія подій (FR-014)
- [ ] T036 [US2] Планувальник `->command('sites:detect-offline')->everyMinute()->withoutOverlapping()` у `crm/routes/console.php`
- [ ] T037 [US2] Backend rate-limit (2-й контур) per `site-id` у `crm/app/Http/Middleware/ThrottleBySiteId.php` (FR-026)
- [ ] T038 [US2] Маршрут heartbeat + throttle у `crm/routes/api.php`
- [x] T039 [P] [US2] Плагін: `plugin/includes/class-sd-signer.php` — канонічний рядок (**дзеркало** contract §1) + `hash_hmac('sha256', …)`, nonce `random_bytes` (base64url)
- [x] T040 [P] [US2] Плагін: `plugin/includes/class-sd-settings.php` — Options API (`sd_site_id`, `sd_signing_secret` [autoload=no, показ один раз], `sd_endpoint_url`); санітизація/ескейпінг/нонси; жодних захардкоджених адрес (FR-024)
- [x] T041 [US2] Плагін: `plugin/includes/class-sd-heartbeat.php` — WP-Cron ~60s → підпис → `wp_remote_post` на `sd_endpoint_url`; читає лише статус-код (залежить від T039, T040)
- [x] T042 [P] [US2] Плагін: вкладка «Подключение» (статус, показ/сховати токен, «Проверить соединение» — **нейтральні** повідомлення) + документація системного cron у `plugin/includes/class-sd-admin.php`

**Checkpoint**: US1 і US2 працюють незалежно; сайти звітують і бачаться online/offline

---

## Phase 5: User Story 3 — Список сайтів зі статусом і часом оновлення (Priority: P1)

**Goal**: оператор бачить список сайтів із поточним статусом і часом останнього оновлення, фільтрує за статусом із лічильником «N із M».

**Independent Test**: `GET /admin/sites` → рядки зі статусом + `last_seen_at`; фільтр `status` → лічильник «N із M»; єдина легенда кольорів (зелений/синій/червоний/сірий).

### Tests for User Story 3 ⚠️

- [ ] T043 [P] [US3] Feature-тест списку (статус, `last_seen_at`, легенда — FR-015/016) у `crm/tests/Feature/SiteListTest.php`
- [ ] T044 [P] [US3] Тест фільтра за статусом + counts «N із M» (FR-018) у `crm/tests/Feature/SiteListFilterTest.php`

### Implementation for User Story 3

- [ ] T045 [US3] `SiteController@index` (`GET /admin/sites?status=…`) у `crm/app/Http/Controllers/Admin/SiteController.php` — вибірка з `site_statuses`, `counts` (total/filtered/by_status) за contract §3.3
- [ ] T046 [US3] `SiteController@deactivate`/`@reactivate` (`POST /admin/sites/{id}:deactivate|:reactivate`) у `crm/app/Http/Controllers/Admin/SiteController.php` — робить статус `inactive` досяжним (FR-013/016, contract §3.4) + події `site_deactivated`/`site_reactivated`
- [ ] T047 [P] [US3] Blade-в'ю списку у `crm/resources/views/sites/index.blade.php` — єдина легенда статусів, фільтр, «N із M»; **локальні** ассети (Принцип III), Alpine опційно (не real-time — FR-017)
- [ ] T048 [US3] Маршрути списку/деактивації у `crm/routes/web.php`

**Checkpoint**: усі три статусні US працюють; список відображає реальні стани

---

## Phase 6: User Story 4 — Відкликання та перевипуск токена (Priority: P1)

**Goal**: оператор відкликає секрет (діє майже одразу) і перевипускає новий; `site-id` **не змінюється**.

**Independent Test**: revoke → heartbeat зі старим секретом відхиляється ≤~1 хв (SC-006); reissue → новий секрет приймається, `site-id` той самий (A-4), показ один раз.

### Tests for User Story 4 ⚠️

- [ ] T049 [P] [US4] Тест відкликання (старий секрет → `401/403` у межах ≤1 хв — SC-006) у `crm/tests/Feature/RevokeCredentialTest.php`
- [ ] T050 [P] [US4] Тест перевипуску (новий секрет працює; `site-id` незмінний — A-4; показ один раз) у `crm/tests/Feature/ReissueCredentialTest.php`

### Implementation for User Story 4

- [ ] T051 [US4] `SiteController@revokeCredential` + `@reissueCredential` (`POST /admin/sites/{id}/credentials:revoke|:reissue`) у `crm/app/Http/Controllers/Admin/SiteController.php` — через `CredentialService` (revoke + новий `active`); `site_identifier` не змінюється (contract §3.2)
- [ ] T052 [US4] Емісія подій `token_revoked` / `token_reissued` через `EventLogger` у `crm/app/Http/Controllers/Admin/SiteController.php` (FR-021)
- [ ] T053 [US4] Оптимістичний контроль конкурентних дій двох операторів над одним сайтом (edge case) у `crm/app/Services/CredentialService.php`
- [ ] T054 [P] [US4] Blade-UI відкликання/перевипуску в огляді сайту + показ нового секрету один раз у `crm/resources/views/sites/credentials.blade.php`

**Checkpoint**: усі чотири P1-історії функціональні й незалежно тестовані

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: наскрізні гейти й закріплення якості

- [ ] T055 [P] CI-гейт: відхилення зовнішніх CDN/`<script>`/`<link>` у зібраних в'ю (Принцип III) — `crm/.github/workflows/assets-gate.yml` або npm-скрипт
- [ ] T056 [P] CI-гейт: перевірка `plugin/` на відсутність Laravel-залежностей і захардкоджених адрес CRM (Принципи I/II) — `.github/workflows/plugin-gate.yml`
- [ ] T057 [P] Кореляція аудиту: correlation-id (`X-DB-Nonce`/`X-DB-Request-Id`) у логах бекенду ↔ edge-лог проксі (FR-033) у `crm/app/Services/EventLogger.php`
- [ ] T058 [P] Документація деплою проксі/ingress (продукт-агностично): TLS на **обох** ділянках, rate-limit за `site-id`, приховування бекенду, критерії відбору (contract Додаток C) у `docs/deploy-proxy.md`
- [ ] T059 [P] Перевірка керування ключем шифрування секретів (env/KMS, поза БД/репо — конституція v2.0.1) у `docs/secrets.md`
- [ ] T060 Навантажувальна перевірка приймання: 500+ сайтів × ~1/хв + бурст (SC-007) через Horizon-метрики
- [ ] T061 [P] README для `crm/` і `plugin/` (запуск, черга, планувальник, конфіг) — `crm/README.md`, `plugin/README.md`
- [ ] T062 Прогін `quickstart.md` — наскрізна валідація всіх US

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: без залежностей — старт одразу
- **Foundational (Phase 2)**: після Setup — **БЛОКУЄ** усі user stories
- **User Stories (Phase 3–6)**: усі після Foundational; далі можуть іти паралельно або послідовно (US1→US2→US3→US4)
- **Polish (Phase 7)**: після потрібних user stories

### User Story Dependencies

- **US1 (P1)**: після Foundational — фундамент видачі токена (використовує `CredentialService`)
- **US2 (P1)**: після Foundational — потребує зареєстрованого сайту з US1 для e2e, але верифікація/детектор тестуються незалежно (фабрики)
- **US3 (P1)**: після Foundational — читає `site_statuses`; повноцінно демонструється разом із US2, але список тестується на фабриках незалежно
- **US4 (P1)**: після Foundational — переважно перевикористовує `CredentialService` з US1

### Within Each User Story

- Тести пишуться першими й мають **впасти** до реалізації (критичні шляхи — вимога конституції)
- Моделі → сервіси → ендпоінти → інтеграція/UI
- Історія завершена до переходу до наступної

### Parallel Opportunities

- Усі `[P]` у Setup — паралельно
- Усі `[P]` у Foundational (T010–T015, T017, T018) — паралельно (різні файли)
- Після Foundational — різні US можуть вести різні розробники
- Усі `[P]`-тести в межах однієї US — паралельно
- Плагін (T039–T042) і бекенд-частина US2 (T033–T038) — переважно паралельні гілки, що сходяться на спільному контракті `contracts/ingest-contract.md`

---

## Parallel Example: User Story 2

```bash
# Тести US2 разом (мають впасти до реалізації):
Task: "T027 Heartbeat accept test — crm/tests/Feature/HeartbeatAcceptTest.php"
Task: "T028 Heartbeat reject test — crm/tests/Feature/HeartbeatRejectTest.php"
Task: "T029 Replay test — crm/tests/Feature/HeartbeatReplayTest.php"
Task: "T030 HMAC unit test — crm/tests/Unit/HmacVerifierTest.php"

# Паралельні гілки реалізації US2 (спільний контракт §1):
Task: "T039 Plugin signer — plugin/includes/class-sd-signer.php"
Task: "T033 Backend HeartbeatController — crm/app/Http/Controllers/HeartbeatController.php"
```

---

## Implementation Strategy

### MVP First

1. Phase 1 Setup → Phase 2 Foundational (**критично** — блокує все)
2. **US1** (реєстрація+токен) → STOP & VALIDATE (незалежний тест) — мінімальний демо-зріз
3. **US2** (heartbeat) — тепер сайти реально звітують; STOP & VALIDATE
4. **US3** (список) — оператор бачить стан мережі → **зв'язний перший реліз = US1+US2+US3**

### Incremental Delivery

- Setup+Foundational → фундамент
- +US1 → +US2 → +US3 → +US4 (кожна додає цінність, не ламаючи попередні)
- **US4 (відкликання)** — безпеково-критична; рекомендовано включити в перший реліз попри окремий phase

### Suggested MVP scope

- **Мінімум**: US1 (незалежно тестований фундамент видачі облікових даних)
- **Зв'язний перший реліз**: US1 + US2 + US3 (реєстрація → heartbeat → список)
- **Безпековий must перед проду**: + US4 (миттєве відкликання per-site)

---

## Notes

- `[P]` = різні файли, без залежностей
- `[Story]` мапить задачу на US для трасовності
- Єдине джерело істини wire-format — `contracts/ingest-contract.md` §1; `crm/app/Support/CanonicalRequest.php` і `plugin/includes/class-sd-signer.php` мають давати **ідентичний** канонічний рядок байт-у-байт (Принцип I)
- Секрети підпису — encrypted-at-rest (конституція v2.0.1), НІКОЛИ не в репозиторії/логах
- Перевіряти, що тести падають до реалізації; комітити після кожної задачі/логічної групи
- Уникати: розпливчастих задач, конфліктів на одному файлі, крос-story залежностей, що ламають незалежність
