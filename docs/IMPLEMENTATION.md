# Implementation notes

## Architecture

Stay OS is a Laravel 13 modular monolith. The public website and staff workspace share server-side availability and booking services. Blade renders the screens, standard Laravel sessions authenticate staff, and CSRF protection covers mutations. No separate SPA, JWT system or duplicated frontend pricing logic is required.

The current build uses a default SQLite database for easy development. Move to PostgreSQL for production, with normal database migrations and no Docker requirement for PHP. If desired, run only PostgreSQL in a development container and set `DB_CONNECTION=pgsql` with the usual host/database credentials.

Room-night allocation is the central inventory constraint. The application takes a row lock on the room and reserves each night inside a transaction. The unique nightly constraint prevents duplicate allocations even if another writer bypasses the service. Payment recording separately locks the reservation and uses unique receipt idempotency keys. The framework feature tests are single-process correctness tests; a production rollout also needs concurrent requests against its target database.

## Data boundaries

Properties own room types, rooms, reservations and audit events. Users receive property assignments through a pivot table. Every staff read/mutation resolves through the assigned selected property. Housekeeping cannot operate reservations or payments. Owners and managers can edit rates/content/settings; only owners can create properties and staff accounts.

Guest contact details are held per reservation. The guest directory is a grouped view, not a separate CRM model. Hotel listings deliberately expose only public property and room information. Private confirmation pages use random UUID bearer tokens and return `no-store`/`noindex` headers; calendar feeds use separate per-room random tokens and omit names and contact details. Add token rotation and confirmation email verification before broader public use.

Staff pages return private no-store responses. CSV exports omit guest contact data and neutralize formula-leading cells. Session cookies are HTTP-only, SameSite Lax, and secure by default in production. Login and booking endpoints have rate limits.

## Honest integration boundaries

**Channel manager:** The current source exports one-way iCalendar room availability. Remote consumers decide how often they poll. iCalendar cannot deliver dependable real-time rate/availability/reservation sync. Do not connect live distribution until inbound booking ingestion and reconciliation exist. A full connector should implement mapping for properties, room types and rate plans; signed/verified provider webhooks; idempotent external reservation IDs; transactional inventory allocation; an outbox for rate/availability events; queued retries and failure alerts; and scheduled reconciliation. Provider agreements and credentials are prerequisites.

**Payments:** Only manual received-money records are supported. A gateway integration should create a payment intent from a server-calculated booking quote, verify webhook signatures, enforce amount/currency matching, record unique provider events, reconcile failures and add refunds. Never trust browser payment-success redirects. Cancellation currently does not refund money or alter historical receipts. Before production, add a cancellation/refund ledger and explicit policy enforcement.

**Mobile:** The manifest is a browser shortcut foundation for the responsive application. No service worker caches private pages. Installation behavior differs by browser. It is not an App Store or Play Store release. Native packaging, token lifecycle, device permissions, push and offline conflict resolution are separate work.

**Marketplace:** This version is a directory and shared booking surface operated by one platform owner. It has no vendor payouts, commission ledger, property-owner onboarding or reviews. Do not treat it as a finished commercial marketplace.

## Next implementation stages

1. Resolve dependency installation, boot the application, run all feature tests, cache Blade views and inspect desktop/mobile flows in a browser. Add targeted fixes before accepting the first release. Record the dependency lockfile.
2. Complete account lifecycle: staff listing/edit/deactivation, property assignment changes, password reset and email verification. Add owner/manager authorization policies with explicit policy tests as resource scope grows.
3. Complete hotel operations: reservation edits and room moves with atomic reallocation; guest counts and room-type pricing; seasonal/date-specific rates; cancellation and refund accounting; no-shows, early checkout settlement, night audit and configurable check-in/out times. Current early checkout retains its original invoice total.
4. Add property photo upload/validation, hotel content pages, booking terms, transactional email, and configurable brand assets. Decorative illustrations should be replaced by actual licensed hotel photography.
5. Implement a selected payment provider and validate webhook replay, partial payment, refund and reconciliation behavior in sandbox mode.
6. Implement one OTA integration with approved access; prove inbound/outbound synchronization before extending to more providers. Avoid selling the iCalendar export as a real-time channel manager.
7. Extend the marketplace with property owner onboarding, publication review, commission/payout accounting and policies. Build native mobile apps only after their workflows and API requirements are agreed.

## Verification performed in this environment

- Composer manifest validation: passed.
- PHP syntax checks for application/config/migrations/seeder/routes/tests: passed.
- JavaScript syntax check: passed.
- 22 standalone domain checks: passed.
- Composer dependencies: installed locally (Laravel 13.39.0; PHP 8.5.8).
- Laravel suite: 25 tests passed, 81 assertions, including rollback and retry after a simulated demo-seed failure.
- Route discovery and Blade compilation: passed.
- Browser runtime checks: marketplace, hotel page, reservation dialog, booking confirmation, staff sign-in and dashboard passed. One fictional `Local Browser Test` reservation remains in the local demo database.
- Startup fixes: property defaults are available immediately after creation; demo seeding is transactional. The incomplete database created by the first failed startup was preserved under `.runtime/failed-first-start/` before initializing the working demo database.

No deployment has been made. Sites hosting uses a JavaScript Worker runtime and cannot host this PHP application directly. A PHP-capable host, VPS or Laravel-compatible platform is required.
