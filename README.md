# APIFlow — Subscription Billing & Usage-Metering Platform

A multi-tenant backend that meters customer API usage against a subscription plan and produces accurate, prorated billing.

> 📄 For architecture, business rules, and full technical flow, see **[Technical Documentation](docs/APIFlow-Technical-Documentation.pdf)**. This README covers setup and everyday use only.

## Overview

APIFlow lets a **merchant** (tenant) define subscription **plans**, sign up **customers** against those plans, meter their API usage per day, and automatically generate prorated invoices at the end of each billing cycle — including correct handling of mid-cycle plan changes and overage charges.

Each merchant's admin gets a dashboard (plans, customers, subscriptions, invoices, usage trends). Each customer gets a self-service portal (subscribe, view usage, change plan, pay invoices).

## Key Technical Flow

```
Merchant defines Plans (price, billing cycle, included units, overage rate)
        │
        ▼
Customer registers & subscribes to a Plan
        │
        ▼
Customer's app calls the API (Bearer API key) → usage recorded per day
        │
        ▼
Hourly job aggregates raw usage into daily totals
        │
        ▼
At cycle end (or on demand): invoice generated — base price + overage,
prorated across any mid-cycle plan change
        │
        ▼
Customer views/pays invoice · Merchant sees usage & revenue on their dashboard
```

## Technologies Used

- **Laravel 12**, PHP 8.2+
- **MySQL 8** (app data) — SQLite in-memory for the test suite
- Blade + **Tailwind CSS v4**
- **Yajra DataTables** (server-side listing tables)
- **barryvdh/laravel-dompdf** (invoice PDFs)
- Laravel's built-in **queue / cache / session** (database driver)

## Prerequisites

Install these before setup:

- PHP **8.2+** with the extensions Laravel 12 needs (`pdo`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`)
- **Composer** 2.x
- **MySQL 8** (or MariaDB equivalent)
- **Node.js 18+** and npm (for building CSS/JS)
- Git

## Installation & Setup

```bash
git clone <repo-url> apiflow
cd apiflow

composer install
npm install

cp .env.example .env
php artisan key:generate
```

### Environment Configuration

Open `.env` and point it at MySQL (it defaults to SQLite):

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=apiflow
DB_USERNAME=root
DB_PASSWORD=
```

Queue, cache, and session already default to the `database` driver — no extra setup needed for this exercise.

### Database Setup

Create the `apiflow` database in MySQL, then run:

```bash
php artisan migrate --seed
```

This creates all tables and seeds 3 demo merchants with plans, customers, subscriptions, usage, and invoices already in place.

### Build Frontend Assets

```bash
npm run build
```

(Use `npm run dev` instead during active development for hot-reload.)

## Running the Project

```bash
php artisan serve
```

Visit **http://127.0.0.1:8000**.

**Background jobs** (usage aggregation, cycle-end billing) run on Laravel's scheduler/queue. For a quick local demo you don't need a worker running — trigger them manually instead:

```bash
php artisan usage:aggregate --sync       # roll up usage into daily totals
php artisan billing:generate-invoices    # bill any subscription whose cycle ended
```

To run them continuously like production would, run these in separate terminals:

```bash
php artisan queue:work
php artisan schedule:work
```

### Running Tests

```bash
php artisan test
```

Runs against an isolated in-memory SQLite database — never touches your MySQL dev data.

## How to Access & Use the Application

| Area | URL | Login |
|---|---|---|
| Merchant Admin | `/admin/login` | see credentials below |
| Customer Portal | `/login` | see credentials below |
| Customer self-registration | `/register` | pick a merchant, use any new email |

**Demo credentials** (seeded by `migrate --seed`):

| Role | Email | Password |
|---|---|---|
| Admin — FinPay Technologies | admin@finpay.com | password |
| Admin — GeoLocate Pro | admin@geolocate.com | password |
| Admin — WeatherCloud | admin@weathercloud.com | password |
| Customer — ABC Forex Pvt Ltd | billing@abcforex.test | password |

Log into two different merchant admins back to back to see tenant isolation directly — separate customers, plans, and usage, with no access to each other's data.

Other seeded customers don't have a portal password yet — from their admin page, click **Enable Portal Access** to issue one, or just self-register a new customer at `/register`.

## Prompt Log

AI-assisted development prompts are documented as screenshots in the `/prompts` folder.
