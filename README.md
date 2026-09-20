# Picnic Island

A resort island booking and management system, built as the group coursework for
**UFCF7S-30-2 System Development** (UWE Bristol / Villa College).

Visitors browse the island, register an account and book across four services from one
place. Staff manage their own service area, and administrators oversee users, promotions
and reporting across all of them.

📷 **[Screenshots of every screen, by role](docs/screenshots/)** — the whole system
captured from a running instance, including the refusals that evidence its business rules,
if you would rather look than install.

## Modules

The system is divided into five modules, one per group member.

| # | Module | Scope | Owner |
|---|---|---|---|
| 1 | Auth, Roles & Admin , Reporting | Registration, login, the five-role permission model, user administration, reporting | Mohamed Faain |
| 2 | Hotel | Rooms, availability search, bookings, payments, staff desk | Ahmed Raafil. |
| 3 | Ferry | Routes, schedules, ticketing, boarding manifests, ticket validation | Ali Naayif |
| 4 | Theme Park & Beach | Activities, events, gate sales, capacity limits, ticket validation | Ahmed Malaaz Mohamed |
| 5 | Content, Map  | Public home page, island map, promotions | Ahmed Safhaan |

Routes are split one file per module under `routes/modules/`, so five people can work
without competing for a single route file.

## Stack

- **Laravel 13** on **PHP 8.4.15**
- **Blade** templating with **Bootstrap 5.3.3**
- **MySQL 8.4** (InnoDB, `utf8mb4_unicode_ci`)

There is **no front-end build step** — no Node, no npm, no Vite. Bootstrap is committed
under `public/css` and `public/js`, so the application runs without an internet connection.

## Running it locally

Requires PHP 8.4.x, Composer and a MySQL server. Create an empty database named
`picnic_island` with collation `utf8mb4_unicode_ci`, then:

```bash
git clone https://github.com/12HCL/Picnic-Island.git
cd Picnic-Island

composer install
cp .env.example .env        # copy .env.example .env  on Windows
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

The application is then at <http://127.0.0.1:8000>.

`--seed` is **not optional**. Migrations create the tables but do not populate the `roles`
table, and registering an account against an unseeded database fails with a 404 raised from
inside the controller rather than the router.

### Demo accounts

Seeding creates one account per role. Every password is `password`. This is local
demonstration data only and carries no real credentials.

| Role | Email | Use it to see |
|---|---|---|
| Admin | `admin@picnic.test` | Users, reports, promotions, map locations |
| Hotel staff | `hotel.staff@picnic.test` | Rooms, bookings, hotel reports |
| Ferry operator | `demo.operator@example.com` | Timetable, counter issuance, validation, manifest, trip reports |
| Park staff | `park.staff@picnic.test` | Activities, events, gate sales, capacity, park reports |
| Visitor with a hotel booking | `demo.allowed@example.com` | BR-01 allowing a ferry ticket |
| Visitor without a hotel booking | `demo.blocked@example.com` | BR-01 refusing a ferry ticket |
| Visitor | `hotel.visitor@picnic.test` | Booking a hotel stay |
| Visitor | `park.visitor@picnic.test` | Park tickets and events |

### After pulling

```bash
composer install     # if anyone added a package
php artisan migrate  # if anyone added a migration
```

`php artisan migrate:status` shows where your database stands; every row should read `Ran`.

## Repository layout

This repository contains the application only. The project documentation — requirements
analysis, the master database schema, design models, Scrum records and the final report —
is maintained separately by the group.

This repository is the editable standalone submission application. It was initially split
from the combined group repository on 19 September 2026, but direct post-split UI,
presentation and submission fixes are now maintained here. Do not replace its history with
an automatic subtree refresh without first reconciling and preserving these direct changes.

```
app/Http/Controllers/   one subdirectory per module
routes/modules/         one route file per module
database/migrations/    schema, built to the agreed master design
resources/views/        Blade templates; shared layout and components at the top level
docs/screenshots/       every screen, by role, captured from a running instance
```

## Authors

Mohamed Faain · Ahmed Raafil · Ali Naayif · Ahmed Malaaz Mohamed · Ahmed Safhaan..
