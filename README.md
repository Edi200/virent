# VIRENT

[![Tests](https://github.com/Edi200/virent/actions/workflows/tests.yml/badge.svg)](https://github.com/Edi200/virent/actions/workflows/tests.yml)
[![Linter](https://github.com/Edi200/virent/actions/workflows/lint.yml/badge.svg)](https://github.com/Edi200/virent/actions/workflows/lint.yml)
[![PHP](https://img.shields.io/badge/PHP-8.4%2F8.5-777BB4?logo=php&logoColor=white)](#)
[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)](#)
[![Vue](https://img.shields.io/badge/Vue-3-4FC08D?logo=vuedotjs&logoColor=white)](#)
[![Filament](https://img.shields.io/badge/Filament-v5-FDAE4B)](#)

A mixed-fleet vehicle rental platform — passenger cars, vans, pickups, excavators, loaders, and motorcycles booked through a single system. Built as a portfolio piece to demonstrate full-stack Laravel + Inertia engineering depth, from domain modeling through real-time UX.

> Built by [Viredsoft](https://viredsoft.com) as a full-stack capability showcase. Not a production rental business — a demonstration of how one would be built correctly.

---

## What this demonstrates

ViRent isn't a CRUD tutorial app. It's deliberately built to surface the kind of decisions that come up in real client work:

- **Schema design under uncertainty** — a single rental platform needs to model a sedan's transmission type and an excavator's bucket capacity without hardcoding either. Categories and their attributes are fully dynamic (EAV-style), driven entirely from the admin panel, with no vehicle "type" enum anywhere in the code.
- **Correct concurrency handling** — two customers attempting to book the same vehicle for overlapping dates is a race condition, not an edge case. Booking creation uses row-level locking (`lockForUpdate`) inside a transaction, with buffer-aware overlap math (so a vehicle isn't double-booked even when bookings are calendar-day adjacent, accounting for category-specific cleaning/inspection time).
- **Real-time UX backed by real data, not just appearances** — while a customer has dates selected on the booking page, a temporary hold is broadcast over WebSockets (Laravel Reverb) so other viewers see those dates go live-blocked. The hold is a real, queryable database row with the same overlap logic as a confirmed booking — not a cosmetic-only signal.
- **Honest state representation** — no feature implies something that hasn't happened. Booking confirmations never claim a deposit was charged when it wasn't; a cancelled booking's email never mentions payment language; the rental agreement PDF labels the deposit as "Due," "Paid," or "Outstanding" based on actual database state, not optimistic copy.

---

## Features

**Public site**
- Live, server-side filtered fleet listing — category, price range, and dynamic per-category attributes (make, fuel type, engine displacement, horsepower, body type, etc.), with debounced search and faceted filter options that narrow based on currently active filters
- Vehicle detail pages with an image gallery (PhotoSwipe lightbox), full spec breakdown, and pricing
- Calendar-based date selection showing real blocked dates (bookings, maintenance windows, and other customers' active holds) before a date is ever picked — not discovered after submission

**Booking**
- Tiered pricing engine (daily / weekly / monthly rates with correct remainder handling — a 35-day rental bills one month plus 7 days, never a re-applied weekly rate)
- Live price preview as dates/extras/operator selection change
- Real-time booking holds via Laravel Reverb — see other customers' in-progress selections live, with automatic release on navigation away or successful booking
- Full booking lifecycle with an enforced state machine (`pending → confirmed → active → completed`, with `cancelled` reachable from any non-terminal state) — invalid transitions are rejected at the service layer, not just hidden in the UI
- Transactional email at every status change, with a formal PDF rental agreement generated via DomPDF and attached on confirmation

**Admin (Filament v5)**
- Dynamic category/attribute management — admins define what specs a vehicle category needs without a code deploy
- Two-level vehicle grouping (e.g. Cars → Car/Pickup, Machinery → Excavator/Loader) for browsing and filtering
- Booking management with role-appropriate status transition actions
- Media management per vehicle (Spatie Media Library)

**Platform**
- Role-based access (admin/staff/customer) on a single `users` table, with Filament panel access gated purely by a model method — no separate guard complexity
- PHPStan level 7 static analysis, Pint code style, and a Pest test suite covering domain logic (pricing, availability, transitions), HTTP flows, and Filament resources

---

## Tech stack

| Layer | Technology |
|---|---|
| Backend | Laravel 13, PHP 8.4/8.5 |
| Admin panel | Filament v5 |
| Frontend | Vue 3 (Composition API) + TypeScript, Inertia 3 |
| UI | shadcn-vue, Tailwind CSS v4 |
| Real-time | Laravel Reverb |
| Media | Spatie Media Library |
| PDF | barryvdh/laravel-dompdf |
| Testing | Pest, PHPStan (level 7), Pint |
| CI | GitHub Actions |

---

## Local setup

Requires PHP 8.4+, Composer, Node 22+, and MySQL.

```bash
git clone https://github.com/Edi200/virent.git
cd virent

composer install
npm install

cp .env.example .env
php artisan key:generate

# configure your database in .env, then:
php artisan migrate --seed
php artisan storage:link

composer run dev   # runs serve + queue + vite + reverb concurrently
```

Seeded accounts:

| Role | Email | Password |
|---|---|---|
| Admin | `admin@example.com` | `password` |
| Staff | `staff@example.com` | `password` |
| Customer | `test@example.com` | `password` |

Visit `/admin` for the Filament panel, or `/` for the public site.

---

## Testing & quality

```bash
composer test         # Pint + PHPStan (level 7) + Pest, all in one gate
npm run types:check   # TypeScript
npm run lint          # ESLint
```

CI runs the full PHP suite against PHP 8.4 and 8.5 on every push and pull request.

---

## Deliberately out of scope

A few things were intentionally left out, with the reasoning noted rather than left ambiguous:

- **Stripe/payment processing** — the booking flow is fully functional up to deposit *notification*; actual payment capture was scoped out to keep focus on the booking/availability engineering, not payment integration plumbing.
- **Full-text search at scale** — name search currently uses `LIKE`, which is sufficient at this catalog size. The upgrade path (MySQL `FULLTEXT` or Laravel Scout + Meilisearch) is noted in code rather than pre-built for a problem the dataset doesn't have yet.
- **Staff vs. admin permission split** — both roles currently share identical Filament access. The `staff` role exists and is enforced at the panel-access layer; granular per-action policies (e.g. staff can't delete vehicles) are a natural next increment, not yet built.

---

## License

This is a portfolio/demonstration project. Feel free to read the code; please don't deploy it as a real commercial rental platform without a security and legal review (it's a demo, not legal advice — see the rental agreement template's own disclaimer).

---

Built by **[Edi · Viredsoft](https://viredsoft.com)** — full-stack Laravel/Vue developer available for remote/contractor work.
