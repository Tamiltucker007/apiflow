<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 70px 55px 60px 55px; }
    body { font-family: 'Helvetica', Arial, sans-serif; font-size: 10.5pt; color: #1f2430; line-height: 1.5; }

    .cover { text-align: center; padding-top: 220px; }
    .cover h1 { font-size: 26pt; color: #4338ca; margin-bottom: 6px; }
    .cover h2 { font-size: 13pt; font-weight: normal; color: #555; margin-bottom: 40px; }
    .cover .meta { font-size: 10pt; color: #888; margin-top: 60px; }
    .cover .badge { display: inline-block; margin-top: 30px; padding: 6px 16px; border: 1px solid #4338ca; color: #4338ca; border-radius: 20px; font-size: 9pt; }

    .toc { page-break-after: always; }
    .toc h1 { color: #4338ca; }
    .toc ol { font-size: 11pt; line-height: 2; padding-left: 20px; }
    .toc ol li { color: #333; }

    h1.section { font-size: 16pt; color: #4338ca; border-bottom: 2px solid #4338ca; padding-bottom: 6px; margin-top: 0; page-break-before: always; }
    h2 { font-size: 12.5pt; color: #1f2430; margin-top: 22px; margin-bottom: 8px; border-left: 4px solid #a5b4fc; padding-left: 8px; }
    h3 { font-size: 11pt; color: #333; margin-top: 14px; margin-bottom: 4px; }

    p { margin: 6px 0; }
    ul, ol { margin: 6px 0 10px 0; padding-left: 20px; }
    li { margin-bottom: 4px; }
    code, .code { font-family: 'Courier New', monospace; background: #f2f2f7; padding: 1px 5px; border-radius: 3px; font-size: 9.5pt; color: #b3261e; }
    pre { background: #1f2430; color: #e6e6e6; padding: 12px 14px; border-radius: 6px; font-size: 9pt; font-family: 'Courier New', monospace; white-space: pre-wrap; line-height: 1.4; }

    table { width: 100%; border-collapse: collapse; margin: 10px 0 16px 0; font-size: 9.5pt; }
    th { background: #4338ca; color: white; text-align: left; padding: 6px 8px; }
    td { border-bottom: 1px solid #e2e2ea; padding: 6px 8px; vertical-align: top; }
    tr:nth-child(even) td { background: #f9f9fc; }

    .callout { background: #f5f6ff; border-left: 4px solid #4338ca; padding: 8px 12px; margin: 10px 0; font-size: 9.5pt; }
    .callout.warn { background: #fff8ec; border-left-color: #d97706; }

    .flow { background: #1f2430; color: #d4d4e0; font-family: 'Courier New', monospace; font-size: 8.5pt; padding: 14px; border-radius: 6px; white-space: pre; line-height: 1.35; }

    .footer-note { font-size: 8.5pt; color: #999; margin-top: 30px; }
</style>
</head>
<body>

<div class="cover">
    <h1>APIFlow</h1>
    <h2>Subscription Billing &amp; Usage-Metering Platform</h2>
    <div class="badge">TECHNICAL DOCUMENTATION</div>
    <div class="meta">
        Laravel 12 &middot; PHP 8.2+ &middot; MySQL 8<br>
        Version 1.0 &middot; {{ now()->format('F Y') }}
    </div>
</div>

<div class="toc">
    <h1 class="section" style="page-break-before: avoid;">Table of Contents</h1>
    <ol>
        <li>Project Overview</li>
        <li>Architecture</li>
        <li>Tech Stack</li>
        <li>Important Modules</li>
        <li>Authentication Flow</li>
        <li>Multi-Tenant Flow</li>
        <li>Subscription / Plan Flow</li>
        <li>Usage Metering Flow</li>
        <li>Billing / Proration Flow</li>
        <li>API Flow</li>
        <li>Database &amp; Relationship Overview</li>
        <li>Important Business Rules</li>
        <li>Validation &amp; Restrictions</li>
        <li>Customer / Admin Flow</li>
        <li>Subscription Cancellation Flow</li>
        <li>Usage Limit / Alert Flow</li>
        <li>Important Implementation Details</li>
        <li>Overall End-to-End Project Flow</li>
    </ol>
</div>

<!-- 1. PROJECT OVERVIEW -->
<h1 class="section">1. Project Overview</h1>

<p>APIFlow is a multi-tenant backend that meters customer API usage against a subscription plan and produces accurate, prorated billing.</p>

<h2>1.1 What it does</h2>
<ul>
    <li>A <strong>merchant</strong> (tenant) defines one or more <strong>plans</strong> — name, base price, billing cycle, included usage units, overage rate per unit.</li>
    <li><strong>Customers</strong> subscribe to a merchant's plan through a self-service portal.</li>
    <li>Every API call a customer makes is recorded as a <strong>usage event</strong>, aggregated daily.</li>
    <li>At the end of each billing cycle, the system generates an <strong>invoice</strong>: base price + overage beyond the included allowance, correctly prorated if the subscription started mid-cycle or the plan changed mid-cycle.</li>
</ul>

<h2>1.2 Who uses it</h2>
<table>
    <tr><th>Role</th><th>Access</th></tr>
    <tr><td>Merchant Admin</td><td>Manages plans, customers, subscriptions (view/change/cancel), invoices, and their own merchant dashboard.</td></tr>
    <tr><td>Customer</td><td>Registers, subscribes, views usage/billing, changes plan, pays invoices — entirely self-service.</td></tr>
</table>

<h2>1.3 Demo tenants</h2>
<p>Three demo merchants ship with the seed data, each with its own real (free, keyless) external API integration — this makes multi-tenancy something you can click through, not just take on faith:</p>
<table>
    <tr><th>Merchant</th><th>Business</th><th>API</th></tr>
    <tr><td>FinPay Technologies</td><td>Currency exchange</td><td><span class="code">open.er-api.com</span></td></tr>
    <tr><td>GeoLocate Pro</td><td>Geocoding</td><td><span class="code">nominatim.openstreetmap.org</span></td></tr>
    <tr><td>WeatherCloud</td><td>Weather forecasts</td><td><span class="code">api.open-meteo.com</span></td></tr>
</table>

<!-- 2. ARCHITECTURE -->
<h1 class="section">2. Architecture</h1>

<h2>2.1 Pattern</h2>
<p>Standard Laravel MVC with a dedicated <strong>service layer</strong> — controllers stay thin (auth, validation, delegate, respond); all business logic (billing math, proration, tenant checks, caching) lives in single-purpose service classes. This keeps the same logic reusable across the admin UI, the customer portal, and the public API without duplication.</p>

<h2>2.2 High-level request flow</h2>
<div class="flow">Customer registers & subscribes (portal)
        |
        v
API key generated (SHA-256 hash stored, plaintext shown once)
        |
        v
Customer's app calls the API -- Authorization: Bearer &lt;key&gt;
        |
        v
AuthenticateApiKey middleware --> resolves merchant + customer, sets tenant context
        |
        v
Rate limiter (120 req/min per API credential)
        |
        v
Business endpoint (exchange-rate / geocode / weather) or POST /usage
        |
        v
usage_events (raw, high-volume, idempotent insert)
        |
        v   (hourly, chunked, queued)
AggregateUsageJob --> daily_usage (1 row / customer / day)
        |
        v   (on demand or cycle-end)
BillingService --> proration + overage --> Invoice + InvoiceItems
        |
        v
Customer pays (PaymentService, simulated) | Merchant Dashboard (usage, revenue, churn risk)</div>

<h2>2.3 Two separate applications, one codebase</h2>
<table>
    <tr><th></th><th>Merchant Admin</th><th>Customer Portal</th></tr>
    <tr><td>Guard</td><td><span class="code">web</span> (App\Models\User)</td><td><span class="code">customer</span> (App\Models\Customer)</td></tr>
    <tr><td>URL space</td><td><span class="code">/admin/...</span></td><td><span class="code">/</span> (unprefixed)</td></tr>
    <tr><td>Route file</td><td><span class="code">routes/admin.php</span></td><td><span class="code">routes/customer-portal.php</span></td></tr>
    <tr><td>Login</td><td><span class="code">/admin/login</span></td><td><span class="code">/login</span></td></tr>
</table>
<p>Each guard has its own auth middleware (<span class="code">AuthenticateAdmin</span>/<span class="code">RedirectIfAdminAuthenticated</span>, <span class="code">AuthenticateCustomer</span>/<span class="code">RedirectIfCustomerAuthenticated</span>) rather than Laravel's built-in <span class="code">auth</span>/<span class="code">guest</span> aliases — those hardcode a redirect to <span class="code">route('login')</span> regardless of guard, which breaks the moment two login systems both need their own target.</p>

<h2>2.4 Per-merchant branding</h2>
<p>Each merchant carries <span class="code">theme_from</span>/<span class="code">theme_to</span> hex colors, applied as CSS custom properties (<span class="code">--brand-from</span>/<span class="code">--brand-to</span>) set inline per request — not Tailwind classes, since Tailwind only generates CSS for class names visible in source at build time, not colors read from the database at request time.</p>

<!-- 3. TECH STACK -->
<h1 class="section">3. Tech Stack</h1>
<table>
    <tr><th>Layer</th><th>Choice</th></tr>
    <tr><td>Framework</td><td>Laravel 12, PHP 8.2+</td></tr>
    <tr><td>Database</td><td>MySQL 8 (app data); SQLite in-memory (test suite)</td></tr>
    <tr><td>Queue / Cache / Session</td><td>Laravel's <span class="code">database</span> driver (Redis-ready — driver-agnostic code, swap via <span class="code">.env</span>)</td></tr>
    <tr><td>Auth</td><td>Hand-written session-based auth, two guards — no Sanctum/Breeze/Jetstream</td></tr>
    <tr><td>Frontend</td><td>Blade + Tailwind CSS v4 (no React/Vue/Livewire, per assignment scope)</td></tr>
    <tr><td>Admin list tables</td><td>Yajra DataTables — server-side search/sort/pagination against merchant-scoped SQL</td></tr>
    <tr><td>PDF generation</td><td>barryvdh/laravel-dompdf — invoices, rendered identically for admin/API/portal</td></tr>
    <tr><td>Testing</td><td>PHPUnit (Laravel's testing layer), 100 tests, unit + feature</td></tr>
</table>

<!-- 4. IMPORTANT MODULES -->
<h1 class="section">4. Important Modules</h1>

<h2>4.1 Services (business logic)</h2>
<table>
    <tr><th>Service</th><th>Responsibility</th></tr>
    <tr><td><span class="code">SubscriptionService</span></td><td>Subscribe, change plan, cancel (with final prorated billing), advance to next period</td></tr>
    <tr><td><span class="code">BillingService</span></td><td>Generates invoices — proration, overage, idempotency</td></tr>
    <tr><td><span class="code">ProrationService</span></td><td>Splits a billing period into per-plan segments around a mid-cycle change; prorates any amount by day-fraction</td></tr>
    <tr><td><span class="code">UsageService</span></td><td>Idempotent usage recording; demo usage simulation</td></tr>
    <tr><td><span class="code">PlanService</span></td><td>Create/update/delete plans with guardrails (seat limits, in-use protection)</td></tr>
    <tr><td><span class="code">PlanPricingService</span></td><td>Cached plan lookups (TTL + event-based invalidation)</td></tr>
    <tr><td><span class="code">CustomerService</span></td><td>Create/update/delete customers; portal password issuance; self-registration</td></tr>
    <tr><td><span class="code">SubscriptionUsageSnapshot</span></td><td>Single source of truth for "usage this cycle / overage / % used / 30-day trend" — shared by every customer-portal page that shows it</td></tr>
    <tr><td><span class="code">DashboardService</span></td><td>Merchant dashboard aggregates — top customers, projected overage revenue, churn risk, usage trend</td></tr>
    <tr><td><span class="code">ApiCredentialService</span></td><td>Issues/resolves/revokes API keys (SHA-256 hashed, prefix shown for identification)</td></tr>
    <tr><td><span class="code">InvoicePdfService</span></td><td>Renders an invoice to PDF — shared by admin, API, and portal download routes</td></tr>
    <tr><td><span class="code">PaymentService</span></td><td>Simulates a payment-gateway charge (stands in for Stripe) — real request/response shape, TODO marker for the real integration</td></tr>
</table>

<h2>4.2 Jobs &amp; scheduled commands</h2>
<table>
    <tr><th>Name</th><th>Type</th><th>Purpose</th></tr>
    <tr><td><span class="code">AggregateUsageJob</span></td><td>Queued job, hourly + on-demand</td><td>Rolls raw <span class="code">usage_events</span> into <span class="code">daily_usage</span>, chunked (5,000/batch), transactional, overlap-locked</td></tr>
    <tr><td><span class="code">GenerateInvoiceJob</span></td><td>Queued job</td><td>Generates one subscription's invoice for a specific (already-ended) period</td></tr>
    <tr><td><span class="code">billing:generate-invoices</span></td><td>Scheduled command, daily 01:00</td><td>Finds every active subscription whose period ended, dispatches its invoice job, advances it to the next period</td></tr>
    <tr><td><span class="code">usage:aggregate</span></td><td>Manual/scheduled command</td><td>CLI trigger for <span class="code">AggregateUsageJob</span> (<span class="code">--sync</span> to run inline for demos)</td></tr>
</table>

<h2>4.3 Middleware</h2>
<ul>
    <li><span class="code">AuthenticateAdmin</span> / <span class="code">AuthenticateCustomer</span> — per-guard login enforcement</li>
    <li><span class="code">EnsureMerchantAccess</span> — blocks an admin from reaching another merchant's <span class="code">{merchant}</span>-scoped routes</li>
    <li><span class="code">AuthenticateApiKey</span> — resolves the Bearer/X-API-Key credential, sets tenant context, rejects deactivated customers</li>
    <li><span class="code">throttle:api-credential</span> — 120 req/min per API key</li>
</ul>

<!-- 5. AUTHENTICATION FLOW -->
<h1 class="section">5. Authentication Flow</h1>

<h2>5.1 Merchant Admin</h2>
<ol>
    <li>Admin visits <span class="code">/admin/login</span>, submits email/password.</li>
    <li><span class="code">Auth::attempt()</span> against the <span class="code">web</span> guard (<span class="code">App\Models\User</span>).</li>
    <li>Deactivated users (<span class="code">is_active = false</span>) are logged straight back out with an error, even with correct credentials.</li>
    <li>On success, redirected to <span class="code">/admin/merchants/{merchant}/dashboard</span> for their own merchant.</li>
</ol>

<h2>5.2 Customer</h2>
<ol>
    <li>Self-registration at <span class="code">/register/{merchant:slug}</span> (or the <span class="code">/register</span> landing page to pick a merchant first) — email + password + confirm only.</li>
    <li>Or, a merchant admin issues/resets a portal password from the customer's admin page (plaintext shown once, only the hash stored).</li>
    <li>Registration does <em>not</em> auto-login — it redirects to <span class="code">/login</span> so the customer confirms the password actually works.</li>
    <li><span class="code">Auth::guard('customer')->attempt()</span> — deactivated customers are rejected the same way as deactivated admins.</li>
    <li>First login with no active subscription routes to <span class="code">/plans/choose</span>.</li>
</ol>

<h2>5.3 API (machine-to-machine)</h2>
<ol>
    <li>Admin generates an API key for a customer (<span class="code">ApiCredentialService::generateKey()</span>) — plaintext shown once, SHA-256 hash stored, a short prefix kept for identification in the UI.</li>
    <li>Caller sends <span class="code">Authorization: Bearer &lt;key&gt;</span> (or <span class="code">X-API-Key</span>).</li>
    <li><span class="code">AuthenticateApiKey</span> middleware resolves the credential, checks the customer is active, sets tenant context for the rest of the request.</li>
    <li>Invalid/revoked key &rarr; <span class="code">401</span>. Deactivated customer &rarr; <span class="code">403</span>.</li>
</ol>

<!-- 6. MULTI-TENANT FLOW -->
<h1 class="section">6. Multi-Tenant Flow</h1>

<h2>6.1 Model: single database, shared schema</h2>
<p>Every tenant-owned table carries a <span class="code">merchant_id</span> column. Isolation is enforced in three layers (defense-in-depth, not just one):</p>

<h3>Layer 1 — Global Eloquent Scope</h3>
<p><span class="code">MerchantScope</span> automatically filters every query on a merchant-scoped model to the current tenant context. Applied via the <span class="code">BelongsToMerchant</span> trait.</p>

<h3>Layer 2 — Route Middleware</h3>
<p><span class="code">EnsureMerchantAccess</span> blocks an admin from navigating to another merchant's <span class="code">{merchant}</span>-prefixed routes at all.</p>

<h3>Layer 3 — Explicit Controller Check</h3>
<p><span class="code">VerifiesTenantOwnership</span> trait re-checks <span class="code">merchant_id</span> explicitly inside the controller. This exists because of a real bug found during development: Laravel's <span class="code">SubstituteBindings</span> resolves route-model-bound params (like <span class="code">{plan}</span>) <em>before</em> <span class="code">EnsureMerchantAccess</span> runs, regardless of declared middleware order — so a bound model can be resolved (unscoped) before tenant context even exists. Layer 3 closes that gap.</p>

<div class="callout warn"><strong>Lesson:</strong> never rely on a single isolation mechanism for multi-tenant data — middleware order is not guaranteed the way it looks in the route file.</div>

<h2>6.2 Roles</h2>
<p>One role today: <span class="code">merchant_admin</span> — full access to plans, customers, subscriptions, and billing for their own merchant. A read-only "staff" tier existed earlier and was deliberately removed — the brief never asked for role differentiation, and it was pure branching logic with no real capability behind it. <span class="code">UserRole</span> stays an enum (not a dropped column) in case a lighter tier is needed later.</p>
<p>There is no platform-wide super-admin — this is a single merchant's billing system, not a tool for onboarding tenants; the 3 demo merchants are seeded directly for that reason.</p>

<!-- 7. SUBSCRIPTION / PLAN FLOW -->
<h1 class="section">7. Subscription / Plan Flow</h1>

<h2>7.1 Plan attributes</h2>
<ul>
    <li>Name, base price (integer cents), currency</li>
    <li>Billing cycle — Monthly / Quarterly / Yearly</li>
    <li>Included units (usage allowance per cycle) and overage rate per unit beyond it</li>
    <li>Optional <span class="code">max_subscribers</span> seat cap</li>
    <li><span class="code">is_active</span> — deactivating stops new signups without affecting existing subscribers</li>
</ul>

<h2>7.2 Subscribing</h2>
<ol>
    <li>Customer picks a plan at <span class="code">/plans/choose</span> (only their own merchant's active plans are offered).</li>
    <li><span class="code">SubscriptionService::subscribe()</span> refuses if the customer already has an active subscription (one-at-a-time rule).</li>
    <li>Period starts today, ends at the calendar boundary for that plan's billing cycle (see Section 9).</li>
</ol>

<h2>7.3 Changing plan (upgrade/downgrade)</h2>
<ol>
    <li>Customer (their own subscription) or admin (any subscription in their merchant) can switch to another active plan.</li>
    <li>Restricted to plans with the <strong>same billing cycle</strong> as the current one — see Section 12 for why.</li>
    <li>A <span class="code">subscription_plan_changes</span> row records old plan, new plan, and effective date (today).</li>
    <li>Period dates are untouched — the next invoice splits the period into per-plan segments (Section 9).</li>
    <li>Switching back to a previously-held plan is fully supported — there is no "one-way" restriction; each segment simply bills at whatever plan was active during it.</li>
</ol>

<h2>7.4 Subscription History</h2>
<p>The customer portal's History page lists every subscription the customer has ever had (active, cancelled, or expired) plus the plan-change events within each — full audit trail, read-only.</p>

<!-- 8. USAGE METERING FLOW -->
<h1 class="section">8. Usage Metering Flow</h1>

<h2>8.1 Recording usage</h2>
<ol>
    <li>Every API call — the generic <span class="code">POST /usage</span> or a merchant's own business endpoint (exchange-rate/geocode/weather) — calls <span class="code">UsageService::recordUsage()</span>.</li>
    <li>Assumption: <strong>1 API request = 1 usage unit.</strong></li>
    <li>Insert is idempotent: unique <span class="code">(merchant_id, event_key)</span> constraint + <span class="code">insertOrIgnore</span>. The insert count (not a separate exists-check) tells the caller created-vs-duplicate, so concurrent retries can never double-count.</li>
    <li>Row lands in <span class="code">usage_events</span> — the raw, high-volume log — flagged <span class="code">is_aggregated = false</span>.</li>
</ol>

<h2>8.2 Aggregation</h2>
<ol>
    <li><span class="code">AggregateUsageJob</span> runs hourly (and on-demand via <span class="code">usage:aggregate</span>).</li>
    <li>Processes only <span class="code">is_aggregated = false</span> rows, in chunks of 5,000 (<span class="code">chunkById</span>, not offset-based <span class="code">chunk()</span> — avoids the offset-scan cost past a few million rows).</li>
    <li>Each chunk's totals update and <span class="code">is_aggregated</span> flag commit together in one transaction — a mid-chunk crash rolls both back, so a rerun never double-counts.</li>
    <li>A <span class="code">WithoutOverlapping</span> job-lock means the hourly schedule and a manual run can never race each other.</li>
    <li>Result lands in <span class="code">daily_usage</span> — one row per customer per subscription per day. All billing and dashboard queries read from here, never from <span class="code">usage_events</span> directly.</li>
</ol>

<h2>8.3 Merchant-gated business endpoints</h2>
<p>Each demo merchant's own API (exchange-rate/geocode/weather) is gated to that merchant's own customers before the external call is even made — a customer of another merchant gets <span class="code">403</span>. This is what makes the multi-tenant story concrete: three merchants don't just look different, each genuinely exposes a different real, working API.</p>

<!-- 9. BILLING / PRORATION FLOW -->
<h1 class="section">9. Billing / Proration Flow</h1>

<h2>9.1 Calendar-aligned billing periods</h2>
<p>A period runs to the end of the calendar month/quarter/year — not "one month from signup." An anniversary model would make a mid-cycle start structurally impossible to reason about, which the brief explicitly requires supporting.</p>

<h2>9.2 Splitting a period around a plan change</h2>
<p><span class="code">ProrationService::calculateSegments()</span> splits <span class="code">[period_start, period_end]</span> into one segment per plan that was active during it, at each recorded change's effective date. No change &rarr; one segment for the whole period.</p>

<h2>9.3 The proration formula</h2>
<pre>prorated_amount = full_amount &times; (segment_days / total_cycle_days)
overage_units   = max(0, actual_usage_in_segment - prorated_included_units)
overage_cost    = overage_units &times; overage_rate_per_unit</pre>
<p><strong>total_cycle_days is the nominal full cycle length</strong> (e.g. all 30 days of the calendar month), not the segment's own length — dividing a short segment by itself would always give 100%, over-billing it. Both the base price <em>and</em> the included-units allowance are prorated the same way for each segment, so a customer only pays for, and only gets quota for, the time actually spent on each plan.</p>

<h2>9.4 Worked example</h2>
<p>Starter (₹999/mo, 10,000 units, ₹0.15/unit overage) &rarr; Growth (₹4,999/mo, 50,000 units, ₹0.10/unit overage), switched on day 15 of a 30-day month:</p>
<table>
    <tr><th>Segment</th><th>Days</th><th>Base (prorated)</th><th>Included (prorated)</th></tr>
    <tr><td>1&ndash;14 (Starter)</td><td>14</td><td>999 &times; 14/30 = ₹466</td><td>10,000 &times; 14/30 = 4,667</td></tr>
    <tr><td>15&ndash;30 (Growth)</td><td>16</td><td>4,999 &times; 16/30 = ₹2,666</td><td>50,000 &times; 16/30 = 26,667</td></tr>
</table>
<p>Each segment's actual usage is compared against <em>its own</em> prorated quota to compute that segment's overage. Both segments' base + overage are added into a single invoice with separate line items — nothing is "subtracted"; each segment is billed fairly for the plan that was active during it.</p>

<h2>9.5 Invoice generation</h2>
<ul>
    <li>Triggered by <span class="code">billing:generate-invoices</span> (daily, for any subscription whose period ended) or on-demand (admin's "Generate Invoice" button, or a subscription cancellation).</li>
    <li>Idempotent via <span class="code">idempotency_key</span> (<span class="code">sub_{id}_period_{start}_{end}</span>) — safe to call repeatedly for the same period.</li>
    <li>After billing, the subscription is advanced to its next period using its (possibly just-changed) current plan's cycle length.</li>
</ul>

<!-- 10. API FLOW -->
<h1 class="section">10. API Flow</h1>

<p>All API routes require <span class="code">Authorization: Bearer &lt;api_key&gt;</span> (or <span class="code">X-API-Key</span>), rate-limited to 120 req/min per key.</p>

<table>
    <tr><th>Method</th><th>Endpoint</th><th>Notes</th></tr>
    <tr><td>GET</td><td><span class="code">/api/v1/exchange-rate</span></td><td>FinPay only — real conversion via open.er-api.com</td></tr>
    <tr><td>GET</td><td><span class="code">/api/v1/geocode</span></td><td>GeoLocate Pro only — real lookup via OpenStreetMap Nominatim</td></tr>
    <tr><td>GET</td><td><span class="code">/api/v1/weather</span></td><td>WeatherCloud only — real current conditions via Open-Meteo</td></tr>
    <tr><td>POST</td><td><span class="code">/api/v1/usage</span></td><td>Generic usage event — every merchant's customers can use this one</td></tr>
    <tr><td>GET</td><td><span class="code">/api/v1/invoices</span></td><td>Lists the authenticated customer's own invoices</td></tr>
    <tr><td>GET</td><td><span class="code">/api/v1/invoices/{invoice}/download</span></td><td>Downloads that invoice as a PDF</td></tr>
</table>

<h2>10.1 Error handling</h2>
<p><span class="code">/api/*</span> always renders errors as JSON, via <span class="code">shouldRenderJsonWhen()</span>, rather than trusting the caller's <span class="code">Accept</span> header — a misconfigured client never gets an HTML error page back.</p>

<h2>10.2 Rate limiting</h2>
<p>Keyed off the <em>raw</em> <span class="code">Authorization</span>/<span class="code">X-API-Key</span> header, not a request attribute set by earlier middleware — <span class="code">ThrottleRequests</span> runs before custom middleware aliases regardless of route declaration order, so an attribute set by the auth middleware isn't available yet at that point.</p>

<!-- 11. DATABASE & RELATIONSHIP OVERVIEW -->
<h1 class="section">11. Database &amp; Relationship Overview</h1>

<h2>11.1 Tables</h2>
<table>
    <tr><th>Table</th><th>Purpose</th><th>Key relationships</th></tr>
    <tr><td><span class="code">merchants</span></td><td>Tenants</td><td>has many plans, customers, users</td></tr>
    <tr><td><span class="code">users</span></td><td>Merchant admin logins</td><td>belongs to merchant</td></tr>
    <tr><td><span class="code">plans</span></td><td>Pricing/allowance definitions</td><td>belongs to merchant; has many subscriptions</td></tr>
    <tr><td><span class="code">customers</span></td><td>Merchant's end customers</td><td>belongs to merchant; has many subscriptions, invoices, api_credentials</td></tr>
    <tr><td><span class="code">subscriptions</span></td><td>A customer's plan enrollment</td><td>belongs to customer &amp; plan; has many plan_changes, invoices</td></tr>
    <tr><td><span class="code">subscription_plan_changes</span></td><td>Audit trail of plan switches</td><td>belongs to subscription; references old/new plan</td></tr>
    <tr><td><span class="code">api_credentials</span></td><td>Hashed API keys</td><td>belongs to customer</td></tr>
    <tr><td><span class="code">usage_events</span></td><td>Raw per-call usage log (high volume)</td><td>belongs to customer &amp; subscription</td></tr>
    <tr><td><span class="code">daily_usage</span></td><td>Aggregated daily totals</td><td>belongs to customer &amp; subscription</td></tr>
    <tr><td><span class="code">invoices</span></td><td>Generated bills</td><td>belongs to customer &amp; subscription; has many invoice_items</td></tr>
    <tr><td><span class="code">invoice_items</span></td><td>Line items (base charge / overage / proration)</td><td>belongs to invoice</td></tr>
</table>

<h2>11.2 Scaling <span class="code">usage_events</span> to 50L+ (5M+) rows</h2>
<ul>
    <li>Composite indexes for the query patterns that matter: <span class="code">(merchant_id, customer_id, recorded_date, is_aggregated)</span> for the aggregation sweep, <span class="code">(subscription_id, recorded_date)</span> for billing lookups, <span class="code">(created_at)</span> for future partitioning.</li>
    <li><span class="code">is_aggregated</span> flag means a rerun only ever touches unprocessed rows.</li>
    <li><span class="code">chunkById(5000)</span> instead of offset-based <span class="code">chunk()</span>/<span class="code">get()</span>.</li>
    <li><strong>Beyond ~10M rows:</strong> partition by <span class="code">recorded_date</span> (monthly RANGE), archive old partitions, read-replica for dashboard queries. Trade-off: a partitioned table's unique key must include the partition column, so idempotency would become per-calendar-day instead of forever — acceptable since event keys aren't reused across days in practice.</li>
    <li><span class="code">daily_usage</span> stays small (one row per customer per day) — every billing/dashboard query reads from here, never from <span class="code">usage_events</span> directly.</li>
</ul>

<h2>11.3 Money</h2>
<p>Every amount is an integer cents column — never float/decimal, to avoid rounding errors in billing math.</p>

<!-- 12. IMPORTANT BUSINESS RULES -->
<h1 class="section">12. Important Business Rules</h1>
<p>Enforced in the service layer so the system can never drift into an inconsistent state:</p>
<ol>
    <li><strong>Plan seat limit + 90% alert</strong> — a plan can optionally set <span class="code">max_subscribers</span>; <span class="code">Plan::isNearSubscriberLimit()</span> flags it once active subscriptions reach 90%, shown as a banner on the admin Plans page.</li>
    <li><strong>Plan edits can't strand existing subscribers</strong> — lowering <span class="code">max_subscribers</span> below the plan's current active subscriber count is rejected.</li>
    <li><strong>Plans with any subscription (active or past) can't be deleted</strong> — so billing history/invoice line items never point at a deleted plan. Deactivate instead, which only blocks new signups.</li>
    <li><strong>Deleting a customer auto-cancels their active subscription first</strong> (billing a final invoice, see Section 15), then deletes; if cancellation fails, the delete is refused rather than leaving an orphaned subscription.</li>
    <li><strong>Deactivating a customer</strong> blocks portal login and API key use immediately, without touching their subscription record — reactivating restores access exactly as it was.</li>
    <li><strong>Plan changes are restricted to the same billing interval</strong> — switching monthly&rarr;quarterly mid-cycle isn't supported, since proration assumes the period's own cycle length throughout. Both the plan picker and server-side validation only offer/allow same-<span class="code">billing_cycle</span> plans; a full cycle-length change takes effect at the next renewal instead.</li>
    <li><strong>A customer has at most one active subscription at a time</strong> — a plan change updates the existing subscription rather than creating a second one.</li>
</ol>

<!-- 13. VALIDATION & RESTRICTIONS -->
<h1 class="section">13. Validation &amp; Restrictions</h1>
<ul>
    <li><strong>Merchant-scoped uniqueness</strong> — a customer's email only has to be unique <em>per merchant</em>, not globally; the same person can be a customer of two different merchants.</li>
    <li><strong>Plan-change target validation</strong> — the target plan must belong to the same merchant, be active, and share the current plan's billing cycle (Section 12.6). Enforced both in the UI (dropdown only offers valid options) and server-side (Form Request rule), so tampering with the request can't bypass it.</li>
    <li><strong>Cross-tenant mutation is blocked at three layers</strong> — see Section 6.1.</li>
    <li><strong>Deactivated-customer requests are rejected at the middleware layer</strong> — before any controller logic runs, for both portal login and API key auth.</li>
    <li><strong>Idempotency is enforced at the database level</strong>, not just in application logic — unique constraints on <span class="code">(merchant_id, event_key)</span> for usage, and on the invoice <span class="code">idempotency_key</span>, so even a race between two concurrent requests can't create a duplicate.</li>
</ul>

<!-- 14. CUSTOMER / ADMIN FLOW -->
<h1 class="section">14. Customer / Admin Flow</h1>

<h2>14.1 Admin capabilities</h2>
<ul>
    <li>Full create/edit/toggle access on Plans and Customers.</li>
    <li>Subscriptions are admin-<em>managed</em> but not admin-<em>created</em> — customers subscribe themselves via the portal. The admin side can view, <strong>Change Plan</strong>, <strong>Cancel</strong>, or trigger <strong>Generate Invoice</strong> on-demand.</li>
    <li>Invoices are read-only from the admin side — a record of what was billed, not something to edit after the fact.</li>
    <li>Merchant dashboard: current-cycle usage, top 5 customers, projected overage revenue, churn-risk customers (usage down &gt;50% month-over-month), 30-day usage trend.</li>
</ul>

<h2>14.2 Customer capabilities (self-service portal)</h2>
<table>
    <tr><th>Page</th><th>Purpose</th></tr>
    <tr><td>Dashboard</td><td>Quick overview — active plan, usage this cycle, overage, 30-day trend, recent invoices</td></tr>
    <tr><td>Subscription</td><td>Full plan/billing details; Change Plan (same-cycle only)</td></tr>
    <tr><td>History</td><td>Every subscription ever held, plus plan-change events</td></tr>
    <tr><td>Usage Details</td><td>Full usage breakdown + 90%/100% alerts + 30-day trend</td></tr>
    <tr><td>Invoices</td><td>Full invoice list, status badges, download PDF, Pay Now</td></tr>
    <tr><td>Profile</td><td>Account details, change password</td></tr>
</table>
<p>Every customer-portal query is scoped to <span class="code">customer_id = auth('customer')->id()</span> — a customer can never reach another customer's data, even by guessing an invoice ID (404, not 403, so ID enumeration can't distinguish "not yours" from "doesn't exist").</p>

<!-- 15. SUBSCRIPTION CANCELLATION FLOW -->
<h1 class="section">15. Subscription Cancellation Flow</h1>

<p>Cancellation is currently <strong>admin-initiated</strong> (from the Subscriptions page) or happens automatically as a side effect of deleting a customer. There is no direct customer-facing "cancel my subscription" button in the current build.</p>

<h2>15.1 What happens on cancel</h2>
<ol>
    <li><span class="code">SubscriptionService::cancel()</span> validates the subscription is currently active.</li>
    <li><strong>Bills a final invoice</strong> for the days actually used this cycle — <span class="code">period_start</span> through <span class="code">min(today, period_end)</span> — using the same proration engine as a normal cycle-end invoice.</li>
    <li>Skips that final invoice if the current cycle was <em>already</em> invoiced (e.g. an admin generated it on-demand earlier) — a <span class="code">whereDate()</span> check against existing invoices for the same period prevents ever double-billing an overlapping range.</li>
    <li>Marks the subscription <span class="code">Cancelled</span> with a <span class="code">cancelled_at</span> timestamp.</li>
    <li>Carries a <span class="code">// TODO: Need to cancel subscription in Stripe</span> marker for when a real payment provider is wired in.</li>
</ol>

<h2>15.2 Why there's no refund logic</h2>
<p>There is nothing to refund: the customer was only ever billed for days already used (Section 15.1, step 2) — never for the unused remainder of the cycle. This mirrors how usage-based metered billing works generally (AWS, Twilio): you're billed for what you consumed, not a flat advance fee.</p>

<!-- 16. USAGE LIMIT / ALERT FLOW -->
<h1 class="section">16. Usage Limit / Alert Flow</h1>

<p>There are two distinct "limit" concepts in the system — easy to conflate, worth being precise about:</p>

<h2>16.1 Plan seat limit (merchant-facing)</h2>
<p><span class="code">max_subscribers</span> caps how many active subscriptions a <em>plan</em> can carry. <span class="code">Plan::isNearSubscriberLimit()</span> returns true at 90% of that cap — shown as a banner on the admin Plans page. This is optional per plan; <span class="code">null</span> means unlimited.</p>

<h2>16.2 Usage allowance (customer-facing)</h2>
<p>Each subscription has an included-units allowance from its plan. <span class="code">SubscriptionUsageSnapshot</span> computes, for the current cycle:</p>
<ul>
    <li><span class="code">usedUnits</span> — sum of <span class="code">daily_usage.total_units</span> within the current period</li>
    <li><span class="code">overageUnits</span> — <span class="code">max(0, usedUnits - included_units)</span></li>
    <li><span class="code">percentage</span> — <span class="code">round(usedUnits / included_units * 100)</span>, capped at 100</li>
</ul>
<p>This single service backs the Dashboard, Subscription, and Usage Details pages — they can never disagree on the numbers. At <strong>90%</strong>, an amber warning banner appears; at <strong>100%</strong>, a red banner explains that further usage is now billed at the overage rate.</p>

<!-- 17. IMPORTANT IMPLEMENTATION DETAILS -->
<h1 class="section">17. Important Implementation Details</h1>
<ol>
    <li><strong>Idempotency, two mechanisms</strong> — usage events via a unique constraint + <span class="code">insertOrIgnore</span>; invoices via an <span class="code">idempotency_key</span> string. Both make the operation safe to retry without a separate exists-check race.</li>
    <li><strong>Calendar-aligned billing periods</strong>, not anniversary-based — required for a mid-cycle-start scenario to even make sense.</li>
    <li><strong>Proration divides by the nominal full cycle length</strong>, not the billed period's own length — otherwise a short period always computes as 100% of itself.</li>
    <li><strong>Tenant isolation has defense-in-depth</strong> beyond the global scope — see Section 6.1's <span class="code">SubstituteBindings</span> ordering story.</li>
    <li><strong>Cache invalidation is TTL + event-based, not either alone</strong> — <span class="code">PlanPricingService</span> caches for 10 minutes; a model observer clears it immediately on update/delete, so staleness in practice is bounded by the observer, not the TTL.</li>
    <li><strong>Rate limiting is keyed off the raw request header</strong>, not a request attribute — see Section 10.2.</li>
    <li><strong><span class="code">/api/*</span> always renders JSON errors</strong>, regardless of the caller's <span class="code">Accept</span> header.</li>
    <li><strong>A date-cast column can round-trip with a time component on some database drivers</strong> (observed on SQLite) — an exact string comparison against <span class="code">toDateString()</span> can silently never match. Fixed by using <span class="code">whereDate()</span> everywhere a date-only comparison is needed, which normalizes across drivers.</li>
    <li><strong>Seed data lives in one place</strong> (<span class="code">DemoData::merchants()</span>) — every seeder loops over it instead of hardcoding a merchant lookup, so a 4th demo merchant is a data change, not a code change.</li>
</ol>

<!-- 18. OVERALL END-TO-END PROJECT FLOW -->
<h1 class="section">18. Overall End-to-End Project Flow</h1>
<ol>
    <li>Merchant admin logs in, creates one or more <strong>plans</strong> (price, cycle, included units, overage rate).</li>
    <li>A customer registers (self-service or admin-created) and lands on <strong>Choose a Plan</strong>.</li>
    <li>Customer subscribes — a <strong>Subscription</strong> is created with a calendar-aligned period.</li>
    <li>Admin issues the customer an <strong>API key</strong>.</li>
    <li>Customer's application calls the merchant's API (or the generic usage endpoint) — each call is recorded as a <strong>usage event</strong>, rate-limited and idempotent.</li>
    <li>Hourly, <strong>AggregateUsageJob</strong> rolls raw events into daily totals.</li>
    <li>Customer can check their usage/allowance/overage at any time on their <strong>Dashboard</strong> or <strong>Usage Details</strong> page — with 90%/100% alerts.</li>
    <li>Mid-cycle, the customer (or admin) can <strong>change plan</strong> — recorded for later proration, restricted to the same billing interval.</li>
    <li>At cycle end (or on-demand), <strong>BillingService</strong> generates an <strong>invoice</strong> — base price + overage, correctly split across any mid-cycle plan-change segments.</li>
    <li>Customer views the invoice and pays it (<strong>Pay Now</strong>, simulated gateway) — status moves Pending &rarr; Paid.</li>
    <li>Subscription automatically rolls into its <strong>next period</strong>, and the cycle repeats.</li>
    <li>If the subscription is cancelled at any point, a final invoice bills only the days actually used, and status becomes <strong>Cancelled</strong>.</li>
    <li>Throughout, the merchant admin's <strong>Dashboard</strong> shows live usage, projected overage revenue, and churn-risk customers across their entire customer base.</li>
</ol>

<div class="footer-note">
    APIFlow &mdash; Technical Documentation &middot; Generated {{ now()->format('d M Y') }}
</div>

</body>
</html>
