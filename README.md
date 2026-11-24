# Maziwa 2

Maziwa 2 is a Laravel + Filament dairy collection, pricing, debt tracking, reporting, and M-Pesa B2C payout management system built for dairy cooperatives and milk collection businesses that require operational visibility and structured financial records.

---

## Highlights & Core Features

- **Role-Based Access & Security:** Shared `/admin/login` with automatic panel routing (`LoginResponse`)—Admins land on `/admin` and Collectors are isolated to `/collector` via `KeepCollectorsOutOfAdmin` middleware. Includes `User::canAccessPanel()` active-status validation, standard Laravel `Hash::make()` password hashing, password reset token support (`password_reset_tokens`), and automatic session-expiry login redirects.
- **Alphanumeric Farmer Identity:** Farmers are assigned auto-generated, stable IDs (`F0001`, `F0002`, etc.) used consistently across collections, debt transactions, and payouts instead of raw auto-increment database primary keys.
- **Route & Collection Management:** Organizes farmers into collection routes, logs daily milk deliveries with correction support, and uses lightweight Alpine.js modals on the collector interface for smooth, responsive entry without unnecessary Livewire state bloat.
- **Effective-Date Pricing (Route & Factory):** Route-specific farmer buying rates (`milk_prices`) and global factory selling rates (`sale_prices`) resolve dynamically by `effective_date`. Supports past, present, and future effective dates for retroactive or scheduled price changes, with a non-blocking duplicate-date warning confirmation. Historical sales and collections preserve the exact rate active on their transaction date.
- **Farmer Debt & Account Ledger:** Tracks debt transactions per farmer, calculates current-month liabilities, and exposes a live net payable account balance alongside 30-day milk collection and debt history tables on each farmer's profile.
- **Dashboard Analytics & Printable Reports:** Uses database-level aggregation for live KPI summary cards (earnings, debt, net revenue, route summaries) and exports printable Blade-rendered PDF/admin reports for farmer milk collections, factory sales, and M-Pesa payouts.
- **Concurrency & Performance:** Enforces a unique `(farmer_id, collection_date)` index to block duplicate daily entries under concurrent writes, applies composite operational indexes across high-write tables, and uses in-memory route price caching (`MilkPrice::getPriceMapForRoute()`) to eliminate N+1 queries.
- **Automated SMS Receipts:** `MilkCollectionObserver` dispatches queued `SendCollectionSms` jobs via HostPinnacle (normalizing Kenyan phone numbers to `254...`), sending collection quantity, collection date, route rate, daily litres/earnings, month-to-date earnings, and collector name.
- **M-Pesa B2C Payouts & Audit PDFs:** End-of-month bulk disbursements via `iankumu/mpesa` with duplicate-payout prevention, Safaricom result/timeout callback tracking, and downloadable PDF payout audit reports (up to 62-day range, filterable by `pending`, `success`, `failed`, and unresolved failures).
- **Linux-Safe Architecture & Seeders:** Normalized PSR-4/Livewire directory and view casing for Linux production hosting, clean one-migration-per-model schema design, and full demo seeders (routes, users, 100 farmers, prices, collections, sales, and debts).

---

## Tech Stack

| Layer | Technology |
| :--- | :--- |
| **Backend** | PHP 8.3+, Laravel 13, Eloquent ORM |
| **Admin & UI** | Filament 5.x, Livewire 4.x, Alpine.js, Vite |
| **Database** | MySQL (with composite & unique operational indexes) |
| **Payments** | `iankumu/mpesa` (Safaricom M-Pesa B2C API) |
| **Messaging** | HostPinnacle SMS API via Laravel HTTP Client & Database Queues |

---

## End-to-End Business Workflow

1. **Farmer Onboarding & Routing:** Admin creates collection routes, configures effective-date buying rates (`milk_prices`) per route, and registers farmers with auto-generated `F0001`-style IDs.
2. **Daily Milk Collection:** Collector logs into `/admin/login`, is routed to `/collector`, and records daily farmer milk quantities via Alpine.js modals (protected against duplicate same-day entries and unauthorized admin panel access).
3. **Instant SMS Receipt:** `MilkCollectionObserver` queues `SendCollectionSms`, which normalizes the farmer's phone number to `254...` and dispatches a delivery receipt with daily and month-to-date earnings via HostPinnacle.
4. **Debt & Factory Sales Tracking:** Admins record farmer debt advances (`farmer_debt_transactions`) and factory milk deliveries (`sales`) priced against the active global factory rate (`sale_prices`).
5. **Live Financial Reconciliation:** Dashboard widgets aggregate monthly earnings, active debts, and net business margin in real time using cached route price maps.
6. **End-of-Month B2C Disbursement & Audit:** Admin triggers **Run B2C Payouts** (`/payouts/run-end-month`), disbursing floored whole-shilling net payables via Safaricom M-Pesa B2C, reconciling async callbacks, and exporting PDF audit reports.

---

## Roles & Access Control

| Role | Panel Route | Permissions & Guardrails |
| :--- | :--- | :--- |
| **Admin** | `/admin` | Full operational management: users, routes, farmers, milk/sale pricing, debt ledger, B2C payouts, and downloadable PDF/Blade reports. |
| **Collector** | `/collector` | Restricted to daily milk collection workflows and route-level tasks. Blocked from `/admin` by `KeepCollectorsOutOfAdmin` middleware. |

---

## Domain Models & Project Structure

```text
app/
  Filament/       # Admin resources, dashboard widgets, report actions
  Http/           # Controllers (FarmerPayoutController, MpesaPayoutReportController)
  Jobs/           # Queued jobs (SendCollectionSms)
  Livewire/       # Collector interface & interactive components (Linux-safe casing)
  Models/         # Core Eloquent entities
  Observers/      # Model event hooks (MilkCollectionObserver)
  Providers/      # Panel & app service providers
  Services/       # External integrations (HostPinnacleSms)
config/           # App, services, and mpesa configuration
database/
  migrations/     # Clean one-migration-per-model schema
  seeders/        # Full operational demo seeders
public/           # Public assets and entry point
resources/
  views/          # Blade templates for printable collection, sales, and payout PDF reports
routes/           # Web, collector, payout, and public M-Pesa API callbacks
---
##Formulars
Farmer Earnings = Σ (Collection Qty × Effective Route Price on Collection Date)

Current Month Debt = Σ (Debt Transactions where type = "debt" in Current Month)

Net Farmer Earnings = Farmer Earnings − Current Month Debt

Total Sales Revenue = Σ (Sale Qty × Effective Factory Sale Price on Sale Date)
Total Net Revenue   = Total Sales Revenue − Σ (Collected Qty × Effective Route Price)
Payout Amount = floor(Net Farmer Earnings)   [Eligible only if Payout Amount ≥ KES 1]