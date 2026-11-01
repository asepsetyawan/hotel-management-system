# Stay OS

Hotel management and direct booking built with Laravel. Manage multiple properties, reservations, room availability, housekeeping, and payments from one staff workspace. Guests can search hotels and book against the same room inventory.

PHP 8.3+ · Laravel 13 · PostgreSQL 17 · Blade · CSS · JavaScript

## Features

| Area | Features |
| --- | --- |
| Properties | Multiple hotels, staff assignments, owner/manager/front-desk/housekeeping roles |
| Reservations | Booking search, check-in, checkout, cancellation, and seven-day availability |
| Rooms | Room types, nightly rates, capacity, housekeeping status, and maintenance holds |
| Billing | Manual payment records, balances, and printable invoices |
| Reports | Occupancy, booking sources, recorded payments, and reservation CSV export |
| Marketplace | Search by city or hotel name, stay dates, adults, children, and room count |
| Hotel pages | Property details, amenities, available rooms, and direct booking |
| Calendar feeds | Private, one-way iCalendar availability feeds for individual rooms |

Bookings reserve one room per transaction. Payments record money received outside the application. Calendar feeds export availability; they do not import OTA reservations or synchronize rates.

## Screenshots

### Staff workspace

![Staff dashboard with arrivals, room availability, housekeeping, and reservations](docs/screenshots/dashboard.jpg)

### Marketplace

![Hotel marketplace with destination, date, and guest search and ten hotel listings](docs/screenshots/marketplace.jpg)

### Product page

![Stay OS product page with a dashboard preview](docs/screenshots/product-home.jpg)

## Getting started

### Requirements

- PHP 8.3+ with Laravel's required extensions and PDO PostgreSQL
- Composer 2
- Docker with Compose

PHP runs on your machine; Docker runs PostgreSQL. The frontend requires no Node build step.

### Installation

For a new checkout:

```sh
git clone https://github.com/asepsetyawan/hotel-management-system.git
cd hotel-management-system
cp .env.example .env
```

Set `DB_PASSWORD` and `DEMO_ADMIN_PASSWORD` in `.env` before starting the database. The demo account password must contain at least 12 characters.

```sh
composer install
php artisan key:generate
docker compose up -d --wait postgres
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8000
```

Keep the server running, then open:

| Page | URL |
| --- | --- |
| Marketplace | [127.0.0.1:8000](http://127.0.0.1:8000) |
| Staff workspace | [127.0.0.1:8000/app](http://127.0.0.1:8000/app) |
| Product page | [127.0.0.1:8000/product](http://127.0.0.1:8000/product) |
| Hotel page | [The Aluna Ubud](http://127.0.0.1:8000/hotels/the-aluna-ubud) |

On macOS, `./start-local.command` also installs dependencies, starts PostgreSQL, applies migrations, seeds demo data, and runs the application. Configure `.env` first to use your own local passwords.

### Demo accounts

All demo accounts use the `DEMO_ADMIN_PASSWORD` configured in `.env`.

| Email | Role | Property access |
| --- | --- | --- |
| `admin@stayos.test` | Owner | All demo hotels |
| `desk@stayos.test` | Front desk | All demo hotels |
| `housekeeping@stayos.test` | Housekeeping | The Aluna Ubud |

The seed includes 10 hotels, 30 room types, 180 rooms, 140 reservations, and 140 payment records. Hotel and guest data are fictional. Hotel images are AI-generated; see the [asset notes](docs/marketplace-images.md).

Seeding runs only in local/testing environments. Re-running `php artisan db:seed` adds missing demo hotels and preserves existing records and staff passwords.

To start without demo data, replace `php artisan migrate --seed` with:

```sh
php artisan migrate
php artisan stayos:owner
```

The owner command creates an account and assigns a draft property. Sign in to configure the property, add room types and rooms, then publish it.

## Database

The local PostgreSQL service is defined in [compose.yaml](compose.yaml). Its settings match [.env.example](.env.example).

| Setting | Default |
| --- | --- |
| Image | `postgres:17` |
| Host | `127.0.0.1` |
| Published port | `5433` |
| Database / user | `stayos` / `stayos` |
| Password | `DB_PASSWORD` in `.env` |
| Persistent volume | `postgres_data` |

Start or stop the database without removing its data:

```sh
docker compose up -d --wait postgres
docker compose stop postgres
```

`docker compose down` also retains the volume. Adding `-v` deletes it. PostgreSQL initializes credentials when the volume is first created; changing `.env` alone does not change an existing database password. Keep `.env` out of version control.

Reservations allocate one row per occupied night in `room_nights`, with a unique `(room_id, night)` constraint. Booking transactions lock the room before allocating nights. Stay dates use `[check_in, check_out)`, allowing another guest to arrive on checkout day.

Amounts are stored as integer rupiah. Each reservation keeps its quoted rate and tax, so later rate changes apply only to new bookings. Checkout requires a zero balance and marks the room dirty; cancellation releases inventory while retaining the reservation and receipts.

See the [database reference](docs/DATABASE.md) for table definitions, relationships, indexes, and booking rules.

## Testing

The default suite uses an isolated, in-memory SQLite database:

```sh
php artisan config:clear
php artisan test
```

To run against PostgreSQL, create a separate test database once:

```sh
docker compose exec -T postgres createdb -U stayos stayos_testing
vendor/bin/phpunit --configuration phpunit.pgsql.xml
```

PostgreSQL tests use `stayos_testing` and may reset its schema. Keep this separate from the application database, `stayos`.

Additional checks:

```sh
php tests/domain-check.php
composer validate --no-check-publish
node --check public/assets/app.js
php artisan route:list --except-vendor
php artisan view:cache
```

The tests cover booking availability, price snapshots, payment replay, room readiness, staff access, marketplace search, and calendar-feed privacy. Concurrent booking behavior requires separate testing against the deployment database.

## Project structure

| Path | Purpose |
| --- | --- |
| `app/Domain/Stay.php` | Stay dates, price calculations, and reservation transitions |
| `app/Services/BookingService.php` | Inventory allocation, booking transactions, and audit records |
| `app/Http/Controllers/` | Public booking, staff operations, and authentication |
| `app/Models/` | Eloquent models and relationships |
| `database/` | Migrations and demo seeder |
| `resources/views/` | Blade layouts, pages, and components |
| `public/assets/` | Styles, browser interactions, fonts, and images |
| `tests/` | Domain and application tests |

Architecture and integration details are in [docs/IMPLEMENTATION.md](docs/IMPLEMENTATION.md). Repository conventions and business rules for coding agents are in [AGENTS.md](AGENTS.md).

## License

[MIT](LICENSE). Third-party dependencies retain their own licenses. The bundled DM Sans font uses the [SIL Open Font License](public/assets/fonts/OFL.txt).
