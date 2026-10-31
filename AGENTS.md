# Agent guide for Stay OS

This guide applies to the entire repository. Check changes against the actual implementation; documentation does not replace code, configuration, migrations, or test results.

## Project context

Stay OS is a multi-property hotel operations and direct-booking application. It is a Laravel monolith with server-rendered pages. The marketplace and staff workspace share the same database and booking service.

- PHP `^8.3`, Laravel `^13.0`, Composer 2. Installed dependency versions follow `composer.lock`.
- The UI uses Blade, plain CSS, and JavaScript. There is no Node build, SPA, React, or frontend component library.
- PostgreSQL 17 runs in Docker for local development; PHP/Laravel runs on the host.
- Staff authentication uses Laravel sessions. Mutations use web routes with CSRF protection.
- Local defaults: file-based sessions/cache, synchronous queues, and email written to logs.
- The application timezone follows `APP_TIMEZONE`, with `Asia/Jakarta` as the local default.

## References

| Need | Source |
| --- | --- |
| Setup, features, database, and testing | [README.md](README.md) |
| Architecture and integration boundaries | [docs/IMPLEMENTATION.md](docs/IMPLEMENTATION.md) |
| Visual direction and UI references | [DESIGN.md](DESIGN.md) |
| Images mapped to each hotel | [config/marketplace.php](config/marketplace.php) |
| AI image provenance and prompts | [docs/marketplace-images.md](docs/marketplace-images.md) |
| Database structure and constraints | [Platform migration](database/migrations/2026_10_31_000001_create_hotel_platform.php) |

## Architecture and responsibilities

| Location | Responsibility |
| --- | --- |
| `bootstrap/app.php` | Routing, `/up` health endpoint, `staff` middleware alias, authentication redirects |
| `routes/web.php` | Public routes, login/logout, `/app` staff routes, and throttling |
| `routes/console.php` | The `stayos:owner` command for creating an owner and draft property without demo data |
| `app/Domain/Stay.php` | Date rules, price/tax calculations, and status transitions; independent of Laravel |
| `app/Services/BookingService.php` | Room availability, night allocation, reservation transactions, status transitions, and auditing |
| `app/Http/Controllers/PublicController.php` | Marketplace search, hotel pages, public bookings, confirmations, and calendar feeds |
| `app/Http/Controllers/HotelController.php` | Staff workspace, property selection, operation authorization, manual payments, rooms/rates, reports, and exports |
| `app/Http/Controllers/AuthController.php` | Login/logout and session regeneration/invalidation |
| `app/Http/Middleware/StaffAccess.php` | Staff role restrictions and private response headers |
| `app/Models/` | Eloquent relationships, casting, and derived values such as paid/balance |
| `resources/views/` | Public/staff layouts, Blade components, and pages |
| `public/assets/app.css` | Theme tokens, layouts, components, and responsive rules |
| `public/assets/app.js` | Theme, navigation, dialog, form, and search-picker interactions |
| `database/seeders/DatabaseSeeder.php` | Transactional demo data for local/testing environments |
| `tests/Feature/`, `tests/Unit/` | HTTP/database behavior and domain rules |

Booking flow: route → controller/validation → `BookingService` → `Stay` for dates and quotes → Eloquent/PostgreSQL within a transaction → Blade response/redirect.

Keep date and money rules in the domain, booking coordination in the service, and request validation and authorization at the HTTP boundary. Manual payments are currently handled in `HotelController::payment()`. Do not allocate inventory directly from views or JavaScript. Do not introduce repository layers, microservices, separate APIs, or frontend frameworks without a concrete task requirement.

## Data model and property boundaries

- Properties own room types, rooms, reservations, and audit events.
- `property_user` determines which properties staff may access. Roles are global to the user, rather than defined per property on the pivot.
- Resolve the active property through `user()->properties()` and the session's `property_id`. Verify request/session IDs against the user's property assignments.
- Look up staff resources through the active property's relationships. Do not replace scoped queries with `Model::find($id)` without equivalent ownership checks.
- Validate that the selected room type and room belong to the same property. Existing foreign keys do not enforce every cross-table `property_id` invariant.
- The guest directory groups reservations by email; it is not a separate CRM table.
- Use new migrations for schema changes. Do not edit an already-applied migration instead of adding a change migration.

## Business rules to preserve

### Inventory and reservations

- Stays use the interval `[check_in, check_out)`: checkout day may be the next guest's check-in day.
- Stays last 1–90 nights. Validate room capacity and `out_of_service` status on the server.
- `BookingService::create()` locks the room row, checks availability, then creates the reservation and `room_nights` within one transaction.
- The unique `(room_id, night)` constraint is the final guard against duplicate night allocations. Preserve constraints, transactions, and locking when changing bookings.
- Transition flows lock the room first, then the reservation. Avoid reversing the lock order.
- Supported transitions: `confirmed → checked_in`, `confirmed → cancelled`, and `checked_in → checked_out`.
- Check-in requires a clean, operational room, no other active occupant, and stay dates that include today.
- Checkout requires a zero balance, releases night allocations, and marks the room `dirty`.
- Cancellation releases night allocations while retaining the reservation and receipts. It does not automatically issue a refund.
- Rooms with upcoming reservations or active occupants cannot be taken out of service until those reservations are moved/cancelled.

### Money and payments

- Store all amounts as integer whole Indonesian rupiah; do not use floating-point money.
- Property tax uses basis points; use `Stay::quote()` to keep rounding consistent.
- Reservations snapshot `nightly_rate`, `subtotal`, `tax`, and `total`. Rate changes must not reprice existing reservations.
- Paid is the sum of receipts; balance is `max(0, total - paid)`. Use the existing model accessors.
- Manual receipts record money already received; the application does not charge cards.
- Preserve reservation locking, balance limits, rejection of payments on cancelled bookings, and unique `idempotency_key` values. Replays must not create a second receipt.

### Access and privacy

- Owners/managers may manage rates, content, and settings. Only owners may create properties and staff.
- Front-desk operations permit owner, manager, and frontdesk roles. Housekeeping is restricted to room readiness and the pages allowed by the implementation.
- Hidden buttons are not authorization; preserve server-side checks for every mutation.
- Booking confirmations use UUID bearer tokens; calendar feeds use random per-room tokens. Keep tokens and private URLs out of screenshots, public logs, and documentation.
- iCalendar feeds must not contain guest names/contact details. Preserve private headers on staff/confirmation pages and formula sanitization in CSV exports.
- Do not read or display the entire `.env` when troubleshooting. Inspect only the necessary settings; never commit credentials, keys, cookies, database dumps, or local SQLite files.

## Marketplace and UI

- `/` is the marketplace, `/product` is the product landing page, `/app` is the staff workspace, and `/hotels/{slug}` is a hotel page.
- Search matches cities/hotel names case-insensitively. Filters must preserve publication checks, inventory availability throughout the stay, room count, and adult + child capacity.
- Require at least one adult per room. Children count toward capacity. Pass date and group parameters through to hotel pages.
- Multi-room support currently covers capacity search; reservations are made one room per transaction. Do not imply group checkout is available.
- Follow `DESIGN.md`: local DM Sans, white surfaces, blue accents, purposeful borders/shadows, and the existing responsive layouts and staff themes.
- Preserve field labels, keyboard navigation, focus indicators, dialogs, and empty/error/loading states. Avoid double outlines on inputs inside controls that already use `:focus-within`.
- The product preview fade uses `mask-image` on `.product-preview` so the image, borders, and shadows fade together.
- Map property images by slug, rather than search-result position. Preserve distinct images for all ten demo hotels and a fallback for new properties.
- Demo hotel images are AI-generated, not photographs of real hotels. Record prompts/assets in `docs/marketplace-images.md`; store final assets in `public/assets/images/` and provide appropriate alt text.
- When a documented screen changes, update the relevant screenshot in `docs/screenshots/` after checking the actual page.

## Running locally

Run commands from the repository root. Prerequisites: PHP 8.3+, PDO PostgreSQL, Composer 2, and Docker Desktop running. See README for first-time installation and `.env` configuration.

Existing launcher:

```sh
./start-local.command
```

For a checkout with dependencies and `.env` already configured:

```sh
docker compose up -d --wait postgres
php artisan config:clear
php artisan migrate --seed --no-interaction
php artisan serve --host=127.0.0.1 --port=8000
```

- Application PostgreSQL: `127.0.0.1:5433`, default database/user `stayos`; the password comes from `.env`.
- Compose runs PostgreSQL only. PHP runs on the host; do not use the `postgres` service hostname for host-side PHP connections.
- The `postgres_data` volume persists data. `docker compose stop postgres` stops the service without deleting data.
- Do not use `migrate:fresh`, drop the application database, or run `docker compose down -v` for routine setup troubleshooting.
- Do not replace an existing `APP_KEY` or overwrite `.env`. Changing the password in `.env` does not change credentials in an initialized PostgreSQL volume.
- Seeding is restricted to `local/testing`, requires a `DEMO_ADMIN_PASSWORD` of at least 12 characters, and adds missing demo slugs without overwriting existing properties/staff credentials.
- A complete seed contains 10 hotels, 30 room types, 180 rooms, 140 reservations, and 140 manual payments. Do not reset the application database just to restore these counts.
- `composer setup` does not start Docker and still touches the SQLite file; use the launcher or README workflow for PostgreSQL.

## Verification by change type

Choose relevant checks. Documentation changes do not require the entire suite. Small UI changes need visual/interaction checks on the affected page; booking, payment, and tenant-scope changes need behavioral tests.

```sh
php tests/domain-check.php
php artisan config:clear
php artisan test
node --check public/assets/app.js
php artisan view:cache
php artisan route:list --except-vendor
composer validate --no-check-publish
```

- `phpunit.xml` forces an in-memory SQLite database, separate from application data.
- For query, transaction, or locking changes, also verify against PostgreSQL. Create the following test database only if it does not already exist:

```sh
docker compose exec -T postgres createdb -U stayos stayos_testing
vendor/bin/phpunit --configuration phpunit.pgsql.xml
```

- PostgreSQL tests use `stayos_testing`; `RefreshDatabase` may reset its schema. Never point the test suite at `stayos`.
- `HotelWorkflowTest` covers bookings, inventory, payments, lifecycle, and staff access; `MarketplaceSearchTest` covers search/availability; `DemoSetupTest` covers seeder rollback/retry; `RedesignTest` covers product/dashboard pages; `StayTest` covers domain rules.
- Sequential HTTP tests do not prove concurrent-request safety. Do not claim concurrency is verified without parallel tests against the target engine.
- Report the checks actually run and their limitations. Do not record new test counts/benchmarks without verification output.

## Feature boundaries and working practices

- There is no payment gateway, active confirmation-email delivery, two-way OTA integration, refund ledger, native mobile app, marketplace payout system, or multi-room checkout.
- iCalendar exports availability in one direction; it is not real-time channel synchronization. The web manifest does not provide a native app or offline support.
- Change only what the task requires. Follow the affected files' structure and conventions; avoid mass formatting or cross-module refactoring for small bug fixes.
- Do not modify `vendor/`, dependency lockfiles, or add packages without an implementation need.
- Keep fixes separate from existing user changes. Inspect `git status`/diff before editing and before committing.
- Commit, push, or deploy according to the user's request. Do not force-push or delete data as a shortcut to resolving conflicts.
- At handoff, explain changed behavior, key files, verification, and remaining work. Update README/implementation notes when contracts, setup, or architecture change.
