# APIFlow — Subscription Billing & Usage-Metering Platform

A multi-tenant backend that meters customer API usage against a subscription plan and produces accurate, prorated billing — built for the Mallow Technologies Senior Laravel Developer take-home.

**Demo merchant:** FinPay Technologies — a currency-exchange API where 1 API call = 1 usage unit.

## Functional Requirements Coverage

| # | Requirement | Where |
|---|---|---|
| 1 | Normalized, indexed schema for 50L+ rows | 11 tables, composite indexes on `usage_events` and `daily_usage` — see [Database Schema](#database-schema) |
| 2 | Idempotent `POST /usage` | `UsageService::recordUsage()` — unique `(merchant_id, event_key)` + `insertOrIgnore`; tested in `UsageEndpointTest` |
| 3 | Queued, chunked aggregation + proration billing | `AggregateUsageJob` (`chunkById(5000)`, transactional, overlap-locked) → `BillingService` + `ProrationService`; tested in `AggregateUsageJobTest`, `BillingServiceTest`, `ProrationSegmentsTest` |
| 4 | Cached plan lookups + invalidation strategy | `PlanPricingService::getCachedPlan()` (TTL) + `PlanObserver` (event-based invalidation); tested in `PlanPricingCacheTest` |
| 5 | `GET /merchants/{id}/dashboard` | `DashboardController` + `DashboardService` — current-cycle usage, top 5 customers, projected overage, churn risk, 30-day trend; tested in `DashboardTest` |
| 6 | Rate limiting | `RateLimiter::for('api-credential', ...)`, 120 req/min per API key; tested in `RateLimitTest` |
| 7 | Automated tests | 40 tests across unit + feature — see [Running Tests](#running-tests) |
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

**Multi-tenancy:** single database, every tenant-owned table carries `merchant_id`. Isolation is enforced in three layers: a global Eloquent scope (`MerchantScope`) filters queries automatically, `EnsureMerchantAccess` middleware blocks cross-tenant route access, and a `VerifiesTenantOwnership` trait adds an explicit ownership check on top for any route where Laravel resolves a route-model-bound record *before* that middleware runs (see [Key Architecture Decisions](#key-architecture-decisions)).

**Roles:** `merchant_admin` (runs the business — plans, customers, subscriptions, billing) and `merchant_staff` (read-only). There is no platform-wide super-admin — see [Assumptions](#assumptions-made) for why.

## Tech Stack

- **Laravel 12**, PHP 8.2+
- **MySQL 8** (application data), **SQLite in-memory** (test suite)
- Queue, cache, and session all use Laravel's **database** driver — see the Redis note below
- Plain session-based auth (no Sanctum/Breeze/Jetstream — hand-written, since this is what's being evaluated)
- Blade + Tailwind CSS v4 (no React/Vue/Livewire, per the assignment's own scope guidance)
- **Yajra DataTables** (server-side) for the Plans/Customers/Subscriptions/Invoices lists — search, sort, and pagination run as real SQL (`LIMIT`/`OFFSET`, indexed `WHERE`/`ORDER BY`) against each merchant-scoped query rather than shipping the full table to the browser. The only jQuery/JS dependency in the app; everything else is server-rendered Blade.

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

Queue/cache/session already default to the `database` driver, so no further changes are needed there.

```bash
php artisan migrate --seed
npm install && npm run build
php artisan serve
```

Visit `http://127.0.0.1:8000` and log in (see [Demo Credentials](#demo-credentials)).

**To process queued jobs** (aggregation, invoice generation), run a worker in a separate terminal:

```bash
php artisan queue:work
```

Or trigger them synchronously for a quick demo:

```bash
php artisan usage:aggregate --sync
php artisan billing:generate-invoices
```

## Database Schema

11 tables, in dependency order: `merchants`, `users`, `plans`, `customers`, `subscriptions`, `subscription_plan_changes`, `api_credentials`, `usage_events`, `daily_usage`, `invoices`, `invoice_items`. Every column's meaning is documented directly in the migrations via `->comment()` (visible in `SHOW FULL COLUMNS`, not just in code).

**Scaling `usage_events` to 50L+ (5M+) rows:**
- Composite indexes already in place for the query patterns that matter: `(merchant_id, customer_id, recorded_date, is_aggregated)` for the aggregation sweep, `(subscription_id, recorded_date)` for billing lookups, `(created_at)` for future partitioning.
- The `is_aggregated` flag means `AggregateUsageJob` only ever touches unprocessed rows — re-running it is always safe, and the table never needs a second full scan.
- `chunkById(5000)` instead of `chunk()`/`get()` avoids the offset-scan cost that degrades badly past a few million rows; it pages on a cursor (`id > lastId`), so rows leaving the filtered set as `is_aggregated` flips mid-run never shift the window.
- **Overlap-safe by two layers:** `AggregateUsageJob` runs both hourly (scheduled) and on-demand (`usage:aggregate`) — a `WithoutOverlapping` job-middleware lock (backed by the cache store) stops a manual run from ever executing concurrently with the scheduled one, and `Schedule::job(...)->withoutOverlapping()` guards the scheduler itself. Without this, two runs could both read the same `is_aggregated = false` rows and double-count them.
- **Transactional per chunk:** each chunk's `daily_usage` totals and its `is_aggregated` flag update commit together inside one `DB::transaction()`. If the process dies mid-chunk, both roll back together — that's what makes "safe to rerun" actually true, rather than just usually true.
- **Beyond ~10M rows:** partition `usage_events` by `recorded_date` (monthly RANGE partitioning), archive partitions older than 6 months to cold storage, and point dashboard/reporting queries at a read replica. Note the trade-off: MySQL requires every unique key on a partitioned table to include the partition column, so the idempotency constraint would need to become `(merchant_id, event_key, recorded_date)` — idempotency per calendar day instead of forever. Acceptable here since `event_key`s aren't reused across days in practice, but worth stating explicitly.
- `daily_usage` deliberately stays small (one row per customer per day, regardless of how many raw events fed into it) — every billing and dashboard query reads from here, never from `usage_events` directly, which is what keeps them fast independent of how large the raw table grows.

**Money:** every amount is an integer cents column (`base_price_cents`, `total_amount_cents`, etc.) — never a float or decimal, to avoid floating-point rounding errors in billing math.

## Key Architecture Decisions

1. **Idempotency, two mechanisms:** `POST /usage` relies on the unique `(merchant_id, event_key)` constraint plus `insertOrIgnore` — the insert count itself (not a separate exists-check) tells the caller created-vs-duplicate, so concurrent retries can't race. Invoices use an `idempotency_key` (`sub_{id}_period_{start}_{end}`) so `BillingService::generateInvoice()` is safe to call repeatedly.
2. **Calendar-aligned billing periods, not anniversary-based.** A subscription's period runs to the end of the current calendar month, not "one month from signup." This was a deliberate correction made mid-build: an anniversary model (period = signup date + 1 month) makes it structurally impossible for a subscription to ever start mid-cycle relative to its own period, which would have made the brief's required "prorate a mid-cycle start" scenario unsatisfiable.
3. **Proration denominator is the nominal full cycle length, not the billed period's own length.** Dividing a segment by itself always returns a 100% ratio when the period is already short (a mid-cycle start) — this is easy to get wrong because it coincidentally works for mid-cycle *plan changes* (whose segments sum to a full period). Fixed by deriving the nominal cycle length from the plan's billing cycle and the period's end date, which is always a true calendar boundary by construction.
4. **Tenant isolation has a defense-in-depth layer beyond the global scope.** Laravel's `SubstituteBindings` (which resolves route-model-bound parameters like `{plan}`, `{customer}`, `{subscription}`) runs *before* custom middleware like `EnsureMerchantAccess`, regardless of the order they're declared on a route. That means a route-bound model can be resolved unscoped before tenant context is ever set. `VerifiesTenantOwnership` closes this with an explicit `merchant_id` check in the controller — this is how a genuine cross-tenant mutation (a merchant admin toggling another tenant's plan) was caught and fixed during review, before it shipped.
5. **Cache invalidation is TTL + event-based, not either alone.** `PlanPricingService::getCachedPlan()` caches for 10 minutes; `PlanObserver` clears the cache immediately on update/delete. Using the database cache store rather than Redis (see below) doesn't change this strategy — only the backing store.
6. **Rate limiting is keyed off the raw request header, not a request attribute.** The obvious approach — read the resolved API credential that `auth.apikey` middleware attaches to the request — breaks because Laravel's internal middleware priority list runs `ThrottleRequests` *before* custom aliases regardless of route-declared order, so the attribute isn't set yet when the limiter's closure runs. Fixed by hashing the raw `Authorization`/`X-API-Key` header directly.
7. **`/api/*` always renders errors as JSON**, via `shouldRenderJsonWhen()` in `bootstrap/app.php`, rather than relying on the caller sending the right `Accept` header. Laravel's default behavior decides JSON-vs-HTML-redirect from request headers, not from which route group a request hit — worth being explicit about for a pure JSON API.

## Assumptions Made

- **1 API request = 1 usage unit.**
- **Currency defaults to INR**; each plan carries its own currency code.
- **Rate limit: 120 requests/minute per API credential** (not per IP).
- **No public customer self-registration.** Customers are registered by the merchant's own admin from the dashboard. A self-service registration flow was actually built and tested, then deliberately removed: the brief frames customers as the merchant's own client base (not people who sign themselves up on the platform), and the brief explicitly says to skip "consumer portals."
- **No platform-wide super-admin role.** An earlier version of this app had a `super_admin` role with its own tenant-management CRUD (create/suspend merchants), modeled after a typical SaaS platform-owner layer. It was removed after re-reading the brief: the assignment's scope is a *single merchant's* billing/usage system, not a platform admin tool for onboarding multiple tenants — that layer was scope the take-home never asked for. `MerchantSeeder` creates the one demo tenant instead.
- **A customer has at most one active subscription at a time.** Changing plans mid-cycle updates the existing subscription (recorded in `subscription_plan_changes`) rather than creating a second, parallel one.

## Trade-offs Under Time Pressure

- **Redis/Horizon were not installed.** Queue, cache, and session all use Laravel's `database` driver instead. The brief explicitly allows this ("Redis or array cache is fine for the exercise"), and the caching/queueing *code* is driver-agnostic — swapping to Redis later is purely a `.env` change (`QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`), no application code would need to change.
- **No Stripe integration.** Not part of the brief's 8 functional requirements — it only appeared in a separate planning document, not the graded assignment.
- **No pagination yet** on customer/plan lists or dashboard queries. Fine at the current demo scale (one merchant, a handful of customers); would need `->paginate()` before this scaled to hundreds of customers.
- **A simple `role` string column** instead of a permissions package — sufficient for two roles, avoids a dependency the brief explicitly says isn't needed.
- **Money is formatted via a small static helper** (`App\Support\Money::format()`) rather than a full value-object wrapper around integer cents.

## What I'd Do Differently With More Time

- Add pagination to list/dashboard queries once data volume actually warrants it.
- Extract the repeated "merchant-scoped uniqueness/exists" validation pattern out of the Form Requests into a shared helper.
- Add a proper `Money` value object instead of raw integer cents plus a formatter function.
- Swap to Redis + Horizon for real queue observability, retries, and worker metrics in production.
- Partition `usage_events` by `recorded_date` before it's actually needed, rather than as a "when it hurts" migration.
- Add OpenAPI/Swagger documentation and API versioning for the public endpoints.

## Running Tests

```bash
php artisan test
```

Tests run against an in-memory SQLite database (configured in `phpunit.xml`), so `php artisan test` never touches — or wipes — the real MySQL dev database. 40 tests, all passing:

| File | Covers |
|---|---|
| `Unit/BillingCycleTest` | Calendar-aligned period boundaries (monthly/quarterly/yearly), the `diffInDays()` float-precision fix |
| `Unit/ProrationServiceTest` | The prorate formula itself: full-cycle, partial-cycle, zero-division guard |
| `Feature/ProrationSegmentsTest` | Splitting a billing period into per-plan segments around a mid-cycle plan change |
| `Feature/BillingServiceTest` | End-to-end invoice generation: full-period billing, mid-cycle-start proration, overage, a mid-cycle plan change billed across two segments, and invoice idempotency (same period never double-invoiced) |
| `Feature/PlanPricingCacheTest` | Plan cache populates on first read and is invalidated immediately on update/delete |
| `Feature/Api/UsageEndpointTest` | `POST /usage` auth, validation, and idempotency (a replayed `event_key` is a no-op, not a duplicate row) |
| `Feature/Api/RateLimitTest` | 120 req/min per API credential; the 121st request is throttled; two credentials are limited independently |
| `Feature/AggregateUsageJobTest` | Chunked aggregation: correct sums, safe to rerun, multiple merchants in one pass, overlap-locked against concurrent runs |
| `Feature/DashboardTest` | Dashboard renders correct usage totals; cross-tenant access is blocked; unauthenticated access redirects to login |
| `Feature/AuthAndAccessTest` | Login success/failure/deactivated-account cases; `merchant_staff` blocked from write routes; cross-tenant plan mutation blocked even with a valid route-bound model |

Stripe isn't covered since it isn't part of this build (see [Trade-offs](#trade-offs-under-time-pressure)).

## Demo Credentials

| Role | Email | Password |
|---|---|---|
| Merchant Admin (FinPay) | admin@finpay.com | password |
| Merchant Staff (FinPay) | staff@finpay.com | password |

Seeded customer API keys are printed to the console once, during `php artisan db:seed` (via `ApiCredentialSeeder`) — they're SHA-256-hashed in the database and cannot be retrieved again afterward, so re-seed if you need a fresh set.

## API Endpoints

All API routes require `Authorization: Bearer <api_key>` (or `X-API-Key: <api_key>`) and are rate-limited to 120 req/min per key.

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/v1/exchange-rate?from=USD&to=INR` | Demo currency conversion; records 1 usage unit on success |
| POST | `/api/v1/usage` | Records a usage event directly (`event_key`, `units`, `recorded_date`); idempotent |

Dashboard routes are session-authenticated and merchant-scoped under `/merchants/{merchant}/...`: `dashboard`, `plans`, `customers`, `subscriptions`, `invoices` (each with the create/edit/toggle actions a `merchant_admin` needs; `merchant_staff` gets read-only views). Subscriptions also expose **Change Plan** (records the mid-cycle change and switches the subscription immediately) and **Generate Invoice** (bills the current period on demand, splitting it into per-plan segments if it contains a plan change).

`php artisan billing:generate-invoices` is the cycle-end counterpart: it finds every active subscription whose period has already ended, dispatches `GenerateInvoiceJob` for the period that just closed, and rolls the subscription into its next period. Intended to run daily via the scheduler alongside the hourly `usage:aggregate`.

## Prompt Log

AI-assisted development prompts are documented as screenshots in the `/prompts` folder (or embedded above, per submission format).
