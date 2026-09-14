# APIFlow — Subscription Billing & Usage-Metering Platform

A multi-tenant backend that meters customer API usage against a subscription plan and produces accurate, prorated billing — built for the Mallow Technologies Senior Laravel Developer take-home.

**Demo merchants** (3, so tenant isolation is something you click through, not just take on faith):

| Merchant | Slug | Business | Theme | Seed data |
|---|---|---|---|---|
| FinPay Technologies | `finpay` | Currency Exchange API | Indigo → fuchsia (default) | 5 customers, a mid-cycle plan change, an overage customer, a churn-risk customer |
| GeoLocate Pro | `geolocate` | Location & Geocoding API | Emerald → teal | 3 customers, one overage, one churn-risk |
| WeatherCloud | `weathercloud` | Weather Forecast API | Sky → cyan | 2 customers, one overage |

1 API call = 1 usage unit for all three. Seed data is defined once in `Database\Seeders\Support\DemoData::merchants()` — every seeder loops over it instead of hardcoding "finpay", so a 4th merchant is a data change, not a code change.

## Functional Requirements Coverage

| # | Requirement | Where |
|---|---|---|
| 1 | Normalized, indexed schema for 50L+ rows | 11 tables, composite indexes on `usage_events` and `daily_usage` — see [Database Schema](#database-schema) |
| 2 | Idempotent `POST /usage` | `UsageService::recordUsage()` — unique `(merchant_id, event_key)` + `insertOrIgnore`; tested in `UsageEndpointTest` |
| 3 | Queued, chunked aggregation + proration billing | `AggregateUsageJob` (`chunkById(5000)`, transactional, overlap-locked) → `BillingService` + `ProrationService`; tested in `AggregateUsageJobTest`, `BillingServiceTest`, `ProrationSegmentsTest` |
| 4 | Cached plan lookups + invalidation strategy | `PlanPricingService::getCachedPlan()` (TTL) + `PlanObserver` (event-based invalidation); tested in `PlanPricingCacheTest` |
| 5 | `GET /merchants/{id}/dashboard` | `DashboardController` + `DashboardService` — current-cycle usage, top 5 customers, projected overage, churn risk, 30-day trend; tested in `DashboardTest` |
| 6 | Rate limiting | `RateLimiter::for('api-credential', ...)`, 120 req/min per API key; tested in `RateLimitTest` |
| 7 | Automated tests | 60 tests across unit + feature — see [Running Tests](#running-tests) |
| 8 | Mid-cycle plan change handling | `SubscriptionController::changePlan()` records a `subscription_plan_changes` row; `ProrationService::calculateSegments()` splits the period at the effective date so the next invoice bills each plan's share separately; tested in `ProrationSegmentsTest` and `BillingServiceTest` |

## Architecture Overview

**Request flow, end to end:**

```
Customer registration (by merchant admin)
        │
        ▼
API key generated (SHA-256 hash stored, plaintext shown once)
        │
        ▼
Customer's app calls the API  ── Authorization: Bearer <key>
        │
        ▼
AuthenticateApiKey middleware ──► resolves merchant + customer, sets tenant context
        │
        ▼
Rate limiter (120 req/min per API credential)
        │
        ▼
GET /exchange-rate  or  POST /usage
        │                       │
        ▼                       ▼
External rate lookup    Idempotent insert (event_key unique per merchant)
        │                       │
        └──────────┬────────────┘
                    ▼
           usage_events (raw, high-volume)
                    │
                    ▼  (hourly, chunked, queued)
           AggregateUsageJob → daily_usage (1 row / customer / day)
                    │
                    ▼  (on demand or cycle-end)
           BillingService → proration + overage → Invoice + InvoiceItems
                    │
                    ▼
           Merchant Dashboard (top 5, projected overage, churn risk)
```

**Multi-tenancy:** single database, every tenant-owned table carries `merchant_id`.
- `MerchantScope` — global Eloquent scope, filters queries automatically.
- `EnsureMerchantAccess` middleware — blocks cross-tenant route access.
- `VerifiesTenantOwnership` trait — explicit ownership check for routes where a model binds *before* that middleware runs (see [Key Architecture Decisions](#key-architecture-decisions)).

**Roles:** one role, `merchant_admin` — full access to plans, customers, subscriptions, billing, team management. A read-only `merchant_staff` tier existed earlier and was removed: the brief never asked for role differentiation, and it was pure branching to maintain for no real capability. `UserRole` stays an enum, not a dropped column, in case a lighter tier is needed later. No platform-wide super-admin — see [Assumptions](#assumptions-made).

**Two separate login systems, two separate URL spaces:**

| | Merchant admin | Customer |
|---|---|---|
| Guard | `web` (`App\Models\User`) | `customer` (`App\Models\Customer`) |
| URL space | `/admin/...` | `/` (unprefixed) |
| Route names | `admin.*` | unprefixed (`login`, `dashboard`, `plans.choose`, …) |
| Route file | `routes/admin.php` (+ `routes/admin-customers.php`) | `routes/customer-portal.php` |
| Login | `/admin/login` | `/login` |

Each guard has its own auth-flow middleware (`AuthenticateAdmin`/`RedirectIfAdminAuthenticated`, `AuthenticateCustomer`/`RedirectIfCustomerAuthenticated`) instead of Laravel's built-in `auth`/`guest` aliases, which hardcode a redirect to `route('login')` regardless of guard — that breaks the moment two login systems both need their own target. `/` redirects to `/login`, `/admin` redirects to `/admin/login`.

**Customer portal (self-service login + self-registration):**
- A merchant admin can issue/reset a customer's portal password (plaintext shown once, only the hash stored — same pattern as API keys), or a customer can self-register at `/register/{merchant:slug}` or the `/register` landing page.
- Registration doesn't log the customer in — it redirects to `/login` so they confirm the password they just set actually works.
- First login with no active subscription routes to `/plans/choose`; picking a plan calls the same `SubscriptionService::subscribe()` the admin side uses.
- The portal itself is read-only: current-cycle usage vs. allowance, a 30-day usage chart, invoice history with PDF download — every query scoped to `customer_id = auth('customer')->id()`.
- Controllers/views still live under `Portal`/`portal/` — internal naming, unrelated to the public URLs above.

**Per-merchant branding:** each merchant has `theme_from`/`theme_to` hex colors (`Merchant::themeFrom()`/`themeTo()`, defaulting to indigo/fuchsia). These can't be Tailwind classes — Tailwind only generates CSS for class names visible in source at build time, not colors read from the database at request time — so they're applied as CSS custom properties (`--brand-from`/`--brand-to`) set inline per request.

## Tech Stack

- **Laravel 12**, PHP 8.2+
- **MySQL 8** (application data), **SQLite in-memory** (test suite)
- Queue, cache, and session all use Laravel's **database** driver — see the Redis note below
- Plain session-based auth (no Sanctum/Breeze/Jetstream — hand-written, since this is what's being evaluated)
- Blade + Tailwind CSS v4 (no React/Vue/Livewire, per the assignment's own scope guidance)
- **Yajra DataTables** (server-side) for the Plans/Customers/Subscriptions/Invoices lists — search, sort, and pagination run as real SQL against each merchant-scoped query rather than shipping the full table to the browser. The only jQuery/JS dependency in the app; everything else is server-rendered Blade.

## Setup Instructions

**Prerequisites:** PHP 8.2+, MySQL 8, Composer, Node.js.

```bash
git clone <repo-url> apiflow && cd apiflow
composer install
cp .env.example .env
php artisan key:generate
```

Edit `.env` — `.env.example` defaults to SQLite, so switch it to MySQL:

```
DB_CONNECTION=mysql
DB_DATABASE=apiflow
DB_USERNAME=root
DB_PASSWORD=
```

Queue/cache/session already default to the `database` driver, no further changes needed.

```bash
php artisan migrate --seed
npm install && npm run build
php artisan serve
```

Visit `http://127.0.0.1:8000` (see [Demo Credentials](#demo-credentials)).

**To process queued jobs**, run a worker in a separate terminal:

```bash
php artisan queue:work
```

Or trigger them synchronously for a quick demo:

```bash
php artisan usage:aggregate --sync
php artisan billing:generate-invoices
```

## Database Schema

11 tables, in dependency order: `merchants`, `users`, `plans`, `customers`, `subscriptions`, `subscription_plan_changes`, `api_credentials`, `usage_events`, `daily_usage`, `invoices`, `invoice_items`. Every column's meaning is documented directly in the migrations via `->comment()`.

**Scaling `usage_events` to 50L+ (5M+) rows:**
- Composite indexes for the query patterns that matter: `(merchant_id, customer_id, recorded_date, is_aggregated)` for the aggregation sweep, `(subscription_id, recorded_date)` for billing lookups, `(created_at)` for future partitioning.
- `is_aggregated` flag — `AggregateUsageJob` only touches unprocessed rows, so re-running it is always safe.
- `chunkById(5000)` instead of `chunk()`/`get()` — avoids offset-scan cost past a few million rows.
- **Overlap-safe by two layers:** hourly schedule + on-demand (`usage:aggregate`) command both go through a `WithoutOverlapping` job-middleware lock, so a manual run can never race the scheduled one.
- **Transactional per chunk:** each chunk's `daily_usage` totals and its `is_aggregated` flag commit together — a mid-chunk crash rolls both back, so a rerun never double-counts.
- **Beyond ~10M rows:** partition by `recorded_date` (monthly RANGE), archive old partitions, read-replica for dashboard queries. Trade-off: a partitioned table's unique key must include the partition column, so idempotency would become per-calendar-day (`merchant_id, event_key, recorded_date`) instead of forever — acceptable since `event_key`s aren't reused across days in practice.
- `daily_usage` stays small (one row per customer per day) — billing/dashboard queries read from here, never from `usage_events` directly.

**Money:** every amount is an integer cents column — never float/decimal, to avoid rounding errors in billing math.

## Key Architecture Decisions

1. **Idempotency, two mechanisms:** `POST /usage` relies on the unique `(merchant_id, event_key)` constraint + `insertOrIgnore` — the insert count (not a separate exists-check) tells the caller created-vs-duplicate, so concurrent retries can't race. Invoices use an `idempotency_key` (`sub_{id}_period_{start}_{end}`) so `generateInvoice()` is safe to call repeatedly.
2. **Calendar-aligned billing periods, not anniversary-based.** A period runs to the end of the calendar month, not "one month from signup" — an anniversary model would make a mid-cycle start structurally impossible, which the brief requires.
3. **Proration divides by the nominal full cycle length, not the billed period's own length** — dividing a short period by itself always gives 100%. Fixed by deriving cycle length from the plan's billing cycle and the period's end date (always a true calendar boundary).
4. **Tenant isolation has defense-in-depth beyond the global scope.** `SubstituteBindings` resolves route-model params like `{plan}` *before* `EnsureMerchantAccess` runs, regardless of declared order — so a bound model can be unscoped before tenant context exists. `VerifiesTenantOwnership` adds an explicit `merchant_id` check in the controller; this caught a real cross-tenant mutation bug during review.
5. **Cache invalidation is TTL + event-based, not either alone.** `PlanPricingService` caches 10 minutes; `PlanObserver` clears immediately on update/delete.
6. **Rate limiting is keyed off the raw request header, not a request attribute** — `ThrottleRequests` runs before custom middleware aliases regardless of route order, so an attribute set by `auth.apikey` isn't available yet. Fixed by hashing the raw `Authorization`/`X-API-Key` header directly.
7. **`/api/*` always renders errors as JSON**, via `shouldRenderJsonWhen()`, rather than trusting the caller's `Accept` header.
8. **Seed data lives in one place** (`DemoData::merchants()`); every seeder loops over it instead of hardcoding a merchant lookup. `ApiCredentialSeeder`/`InvoiceSeeder` needed no loop at all — they already operate on "every customer" / "every active subscription".

## Subscription Lifecycle Guardrails

Business rules enforced in the service layer so the system can't drift into an inconsistent state:

- **Plan seat limit + 90% alert:** a plan can optionally set `max_subscribers`. `Plan::isNearSubscriberLimit()` flags it once active subscriptions reach 90% — shown as a banner on the Plans page and a highlighted count in the list.
- **Plan edits can't strand existing subscribers:** `PlanService::update()` rejects lowering `max_subscribers` below the plan's current active subscriber count.
- **Plans with any subscription (active or past) can't be deleted** — `PlanService::delete()` refuses so billing history/invoice line items never point at a deleted plan. Deactivate instead.
- **Deleting a customer auto-cancels their active subscription first**, then deletes; if cancellation fails, the delete is refused rather than leaving an orphaned subscription.
- **Deactivating a customer** (`is_active`) blocks portal login and API key use immediately, without touching their subscription record — reactivating restores access as-is.
- **`SubscriptionService::cancel()`** is the single cancellation path: validates the subscription is active, updates status locally, and carries a `// TODO: Need to cancel subscription in Stripe` marker for when real payment-provider cancellation is added — no Stripe logic today.

## Assumptions Made

- **1 API request = 1 usage unit.**
- **Currency defaults to INR**; each plan carries its own currency code.
- **Rate limit: 120 requests/minute per API credential** (not per IP).
- **Customers can self-register**, beyond the brief's 8 core requirements — either an admin creates the account and issues a password, or the customer signs up at a merchant-scoped link (`/register/{merchant:slug}`). No platform-wide signup with no merchant context, since every customer belongs to exactly one merchant.
- **No platform-wide super-admin role.** Out of scope — the brief is a single merchant's billing system, not a platform tool for onboarding tenants. `MerchantSeeder` seeds three demo tenants instead so isolation can be demonstrated directly.
- **A customer has at most one active subscription at a time.** A mid-cycle plan change updates the existing subscription (recorded in `subscription_plan_changes`) rather than creating a second one.

## Trade-offs Under Time Pressure

- **Redis/Horizon not installed** — queue/cache/session use Laravel's `database` driver (brief allows this explicitly). The code is driver-agnostic; switching later is a `.env` change only.
- **No Stripe integration** — not part of the brief's 8 functional requirements. `SubscriptionService::cancel()` handles cancellation locally with a `TODO` marker for where Stripe's cancel call would go.
- **No pagination yet** on customer/plan lists or dashboard queries — fine at demo scale, would need `->paginate()` at hundreds of customers.
- **A simple `role` string column** instead of a permissions package — kept even after simplifying to one role, since it costs nothing to leave in place.
- **Money uses a small static helper** (`App\Support\Money::format()`) rather than a full value-object.

## What I'd Do Differently With More Time

- Add pagination to list/dashboard queries.
- Extract the repeated merchant-scoped uniqueness validation out of the Form Requests into a shared helper.
- Add a proper `Money` value object instead of raw integer cents + a formatter.
- Swap to Redis + Horizon for real queue observability in production.
- Partition `usage_events` proactively rather than as a "when it hurts" migration.
- Add OpenAPI/Swagger documentation and API versioning.

## Running Tests

```bash
php artisan test
```

Runs against an in-memory SQLite database (`phpunit.xml`) — never touches the real MySQL dev database. 60 tests, all passing:

| File | Covers |
|---|---|
| `Unit/BillingCycleTest` | Calendar-aligned period boundaries, the `diffInDays()` float-precision fix |
| `Unit/ProrationServiceTest` | The prorate formula: full-cycle, partial-cycle, zero-division guard |
| `Feature/ProrationSegmentsTest` | Splitting a billing period into per-plan segments around a mid-cycle plan change |
| `Feature/BillingServiceTest` | End-to-end invoice generation: proration, overage, plan-change segments, idempotency |
| `Feature/PlanPricingCacheTest` | Plan cache populates on first read, invalidated on update/delete |
| `Feature/Api/UsageEndpointTest` | `POST /usage` auth, validation, and idempotency |
| `Feature/Api/RateLimitTest` | 120 req/min per API credential; independent limits per credential |
| `Feature/AggregateUsageJobTest` | Chunked aggregation: correct sums, safe to rerun, multi-merchant, overlap-locked |
| `Feature/DashboardTest` | Correct usage totals; cross-tenant access blocked; unauthenticated redirect |
| `Feature/AuthAndAccessTest` | Login success/failure/deactivated cases; write-route access; cross-tenant mutation blocked |
| `Feature/Portal/CustomerPortalTest` | Portal login cases; correct guard redirect; own-data-only access; admin-issues-password flow |
| `Feature/Portal/CustomerRegistrationTest` | Landing page + theme rendering; self-registration flow; duplicate/merchant-scoped email rules; plan-choose flow |

Stripe isn't covered — not part of this build (see [Trade-offs](#trade-offs-under-time-pressure)).

## Demo Credentials

| Role | Email | Password |
|---|---|---|
| Merchant User (FinPay Technologies), at `/admin/login` | admin@finpay.com | password |
| Merchant User (GeoLocate Pro), at `/admin/login` | admin@geolocate.com | password |
| Merchant User (WeatherCloud), at `/admin/login` | admin@weathercloud.com | password |
| Customer Portal (ABC Forex Pvt Ltd), at `/login` | billing@abcforex.test | password |

Log into two different merchants back to back to see tenant isolation directly — different customers, plans, usage; neither can reach the other's `/admin/merchants/{id}/...` URLs (403).

Seeded API keys print once to the console during `db:seed` — SHA-256-hashed afterward, re-seed for a fresh set. Only ABC Forex has a portal password pre-set; other customers need `Enable Portal Access` from their admin page first.

To try self-registration instead, visit `/register`, pick a merchant, and use a fresh email — no seeded credentials needed.

## API Endpoints

All API routes require `Authorization: Bearer <api_key>` (or `X-API-Key: <api_key>`), rate-limited to 120 req/min per key.

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/exchange-rate?from=USD&to=INR` | **FinPay only.** Real conversion via open.er-api.com; records 1 usage unit |
| GET | `/api/v1/geocode?address=Bengaluru` | **GeoLocate Pro only.** Real lookup via OpenStreetMap Nominatim; records 1 usage unit |
| GET | `/api/v1/weather?lat=12.97&lon=77.59` | **WeatherCloud only.** Real current conditions via Open-Meteo; records 1 usage unit |
| POST | `/api/v1/usage` | Records a usage event (`event_key`, `units`, `recorded_date`); idempotent — every merchant's customers use this one |
| GET | `/api/v1/invoices` | Lists the authenticated customer's own invoices |
| GET | `/api/v1/invoices/{invoice}/download` | Downloads that invoice as a PDF |

The first three are each gated to one merchant (`$customer->merchant->slug` check, before the external call) — a customer of another merchant gets 403. Each calls a real, keyless, free external service. Covered by `MerchantBusinessEndpointTest`.

**Invoice PDFs, three ways:** merchant dashboard (`/admin/merchants/{merchant}/invoices/{invoice}/download`), the API routes above (own key), or the customer portal (`/invoices/{invoice}/download`) — all three render through the same `InvoicePdfService`, and both non-admin routes check `invoice->customer_id` against the caller before rendering.

**Customer routes** (`routes/customer-portal.php`, guard `customer`, no URL prefix): `/login`, `/logout`, `/dashboard`, `/plans/choose`, `/invoices/{invoice}` (+ `/download`), `/register`, `/register/{merchant:slug}`.

**Admin routes** (`routes/admin.php`, guard `web`, prefix `/admin` + name prefix `admin.`): `/admin/login`, `/admin/logout`, then everything merchant-scoped under `/admin/merchants/{merchant}/...` — `dashboard`, `plans`, `customers`, `subscriptions`, `invoices`, `users` (Team).

Every merchant user has full create/edit/toggle access on Plans/Customers/Subscriptions. Subscriptions also expose **Change Plan** and **Generate Invoice** (on-demand billing, split into segments if it spans a plan change). Invoices stay read-only — they're a record of what was billed, not something to edit after the fact.

The Team module lets a merchant user manage teammates. Guards: a user can't deactivate their own account, and the last active user for a merchant can't be deactivated while no other exists.

`php artisan billing:generate-invoices` is the cycle-end counterpart to the hourly aggregation: finds every subscription whose period ended, generates its invoice, rolls it to the next period. Meant to run daily via the scheduler.

## Prompt Log

AI-assisted development prompts are documented as screenshots in the `/prompts` folder (or embedded above, per submission format).
