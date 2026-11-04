# Maziwa 2

Maziwa 2 is a Laravel + Filament dairy collection and payments management system built for managing farmers, collection routes, milk deliveries, pricing, debt tracking, and sales. It is designed for a dairy or milk collection business that needs operational visibility and structured financial records.

## Project purpose

The system allows staff to:

- Register and manage farmers
- Assign farmers to collection routes
- Record milk collection entries by date and collector
- Apply route-based or effective-date milk pricing
- Track farmer debt balances and debt transactions
- Record milk sales and sale pricing
- Manage user access for admin and collector roles through Filament admin panels

## Core business workflow

1. A farmer is registered and assigned to a route.
2. A collector records milk quantity collected for the farmer.
3. The system resolves the correct rate based on route and effective date.
4. Farmer debt and monthly payable figures are tracked automatically.
5. Admins can manage users, pricing, and operational records from the admin panel.

## Key features

- Admin authentication and role-based access
- Collector user role for operational staff
- Farmer record management with unique farmer IDs
- Route-based organization of farmers
- Milk collection logging with correction support
- Dynamic pricing by effective date
- Debt transaction tracking
- Filament dashboard and admin administration
- Laravel migrations and seeders for easy setup

## Tech stack

- Laravel 12
- PHP 8.2+
- MySQL database
- Filament admin panel
- Livewire
- Eloquent ORM
- Vite for frontend assets

## Project structure

```text
app/
  Filament/
  Models/
  Providers/
config/
database/
  migrations/
  seeders/
public/
resources/
routes/
```

### Main model entities

- User: system users, admin/collector roles, authentication
- Route: collection routes for farmers
- Farmer: farmer profile and route assignment
- MilkCollection: daily milk delivery records
- MilkPrice: price record by route and effective date
- Sale: sales records
- SalePrice: sale price records
- Farmer_debt_transaction: debt transactions for farmers

## Roles and access

The application is meant to support the following roles:

- Admin
  - Full management access
  - Can manage users and operational data
  - Handles system setup and oversight

- Collector
  - Can operate collection workflow and related records
  - Restricted to collection activities and route-level task access

## Default seeded users

The database seeders create the following users:

- Admin
  - Email: admin@maziwa.com
  - Username: admin
  - Password: admin123

- Collector
  - Email: collector@maziwa.com
  - Username: collector
  - Password: collector123

## Installation

1. Clone the repository:

```bash
git clone <repo-url>
cd maziwa2
```

2. Install PHP dependencies:

```bash
composer install
```

3. Install frontend dependencies:

```bash
npm install
```

4. Create your environment file:

```bash
cp .env.example .env
```

5. Update database settings in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=milk_system
DB_USERNAME=root
DB_PASSWORD=
```

6. Generate an app key:

```bash
php artisan key:generate
```

7. Run migrations and seed the database:

```bash
php artisan migrate --seed
```

8. Start the application:

```bash
php artisan serve
```

9. Access the admin panel:

```text
http://127.0.0.1:8000/admin
```

## Database seeding

The project includes seeders for the following:

- Routes
- Admin user
- Collector user
- 100 farmers
- Milk price records for each route
- Sale price records for the current pricing cycle
- Current-month milk collections
- Current-month sales
- Current-month farmer debt entries

Run:

```bash
php artisan db:seed
```

or:

```bash
php artisan migrate:fresh --seed
```

This is useful for resetting the database during development or demos.

## Migrations

The project uses Laravel migrations to build the schema. Fresh migrations are created per model to keep the database structure clear and maintainable.

Typical migration order includes:

- users
- routes
- farmers
- milk_collections
- milk_prices
- sales
- sale_prices
- farmer_debt_transactions

## Business logic notes

The current model layer includes features such as:

- Auto-generation of farmer IDs like `F0001`
- Debt balance tracking from farmer debt transactions
- Route-based price retrieval for milk collections
- Payment/collections calculations by period and effective date

## Dashboard calculations

The dashboard uses live business formulas to calculate the main operational totals:

- Farmer Earnings = Σ (Milk Collection Qty × Effective Route Price for that collection date)
- Total Revenue = Σ (Sale Qty × Sale Price Per Liter)
- Net Farmer Earnings = Farmer Earnings − Current Month Farmer Debt
- Current Month Debt = Σ (Debt Transactions of type `debt` for the current month)

Example formulas:

```text
Farmer Earnings = (qty1 × route_price1) + (qty2 × route_price2) + ...
Total Revenue = (sale_qty1 × sale_price1) + (sale_qty2 × sale_price2) + ...
Net Farmer Earnings = Farmer Earnings - Current Month Debt
```

These calculations are reflected in the dashboard summary cards and the farmer profile account balance logic.

## Sale price management

Sale pricing follows an effective-date model similar to milk pricing, but it is global and not tied to routes:

- Admin creates a sale price record with an effective date
- The effective date may be in the past, present, or future, so pricing changes can be recorded late or ahead of time
- The sale form loads the correct factory sale price for the selected sale date when saving
- Historical sales keep the exact price used at the time of the sale
- If the admin changes the sale rate later, it is applied only from the new effective date onward
- Sale price is used for total milk sold to the factory, not for route-based farmer milk pricing

Duplicate-date handling:

- The admin may still save a sale price for an effective date that already exists, but the form warns about it first
- This is intentional so the admin can continue when a factory change is entered late or after a communication delay
- The warning acts as a confirmation step instead of a hard block, allowing the business to override a previous date if needed
- The system still relies on the selected effective date to determine which factory rate applies to each sale

This keeps revenue reporting accurate while also allowing late or planned factory-rate changes without blocking legitimate corrections.

## Working with the app

### Run the full test suite

```bash
php artisan test
```

## Performance and data integrity improvements

The application was optimized for higher-write operational workloads and cleaner reporting while keeping the dairy workflow logic intact.

### Indexes added for write-heavy tables

The following index set was added to the main operational tables so the app remains efficient under heavier traffic:

- `farmers`
  - index on `route_id` for route-based farmer queries and filtering
- `milk_collections`
  - unique on `(farmer_id, collection_date)` to prevent duplicate daily entries per farmer
  - index on `(collector_id, collection_date)` for collector reporting
  - index on `(route_id, collection_date)` for route summaries
  - index on `collection_date` for date-based filtering
- `milk_prices`
  - index on `(route_id, effective_date, is_active)` for fast route price lookup
  - index on `effective_date` for effective-date filtering
- `sales`
  - index on `sale_date` for monthly and daily sales reporting
- `sale_prices`
  - index on `(effective_date, is_active)` for sale-price selection and sorting
- `farmer_debt_transactions`
  - index on `(farmer_id, transaction_date)` for farmer balances and history
  - index on `(transaction_type, transaction_date)` for debt summaries

These indexes are critical when the system scales to more data and more frequent collection entries.

### Query optimization changes

Several calculation-heavy paths were optimized to avoid repeated database lookups while processing the same route price data:

- `MilkPrice::getPriceMapForRoute()` caches effective route prices by route and date before calculating totals.
- Farmer account balance logic now resolves route prices once for the month instead of repeatedly querying the same milk price record per collection.
- Dashboard earnings are now computed from a single collected month dataset with a cached route price map.

This keeps the app much more efficient when totals are recalculated during view rendering or reporting.

### Concurrency-safe daily collection writes

The dairy workflow keeps a daily milk record per farmer. The new unique index on `milk_collections (farmer_id, collection_date)` prevents duplicate same-day entries for the same farmer, which is important when multiple collection events or retries happen under load.

## Recent project updates and fixes

The application was refactored and corrected to align the business logic with the dairy workflow and Filament admin requirements.

### 1. Fresh migrations and cleaned schema

- Rebuilt the database schema using one migration per model rather than mixing related tables into a single migration.
- Added clean migrations for:
  - users
  - routes
  - farmers
  - milk_collections
  - milk_prices
  - sales
  - sale_prices
  - farmer_debt_transactions
- Removed the old mixed legacy migration approach to keep the project easier to maintain and reset.

### 2. Standard Laravel password handling

- The user model was aligned with Laravel conventions by using the standard `password` column instead of `password_hash`.
- Authentication and user creation flows now follow the usual Laravel pattern for password hashing and validation.

### 3. Farmer identity and data consistency

- Farmer records use a custom alphanumeric `farmer_id` such as `F0001`, `F0002`, etc.
- The app was corrected to consistently use `farmer_id` instead of numeric `id` in collection, debt, and lookup logic.
- This prevents mismatches between the business identity and the database primary key.

### 4. Debt logic and account balance

- Farmer debt is calculated from current-month debt transactions.
- Account balance is treated as the net payable:

```text
Net Farmer Earnings = Month Milk Earnings - Current Month Debt
```

- The farmer profile now exposes a true account balance value based on operational earnings and liabilities.

### 5. Last-30-days farmer detail views

The farmer record view now includes neat data tables for recent activity:

- Debt history from the last 30 days
- Milk collection history from the last 30 days

The information is displayed in compact tables to keep the view readable and business-friendly.

### 6. Sale price model and effective-date support

- Sale pricing is no longer treated as route-specific.
- The model now represents an effective sale rate for the entire factory, with a distinct `effective_date`.
- This supports the real-world scenario where the factory changes rates and the admin records the update after the fact or in advance.
- Past, current, and future effective dates are all supported.

### 7. Duplicate-date warning flow

When a sale price is entered with a date that already exists, the system warns the admin instead of immediately rejecting the action.

This lets the user:

- review the duplicate entry
- confirm that they intentionally want to apply the change
- continue without being blocked by a business process that may need to handle late updates

### 8. Route and milk price display improvements

- Route detail views show the current active milk price.
- Price tables are filtered to show only the latest relevant entries to keep the admin view clean and business focused.
- This is especially useful where effective pricing changes happen regularly.

### 9. Filament global search fixes

- The debt summary resource was disabled from global search because it is a grouped aggregate and does not expose a valid searchable field.
- Farmer resources remain globally searchable by name so they are easy to find from the top search bar.
- This avoids broken queries while still supporting useful panel search.

### 10. Dashboard and operational metrics

The dashboard cards are backed by real calculation logic instead of placeholders:

- Farmer Earnings = total milk quantity × effective route price
- Total Revenue = total sold milk × sale price
- Net Farmer Earnings = farmer earnings − current month debt

These values reflect the actual operational flow of the dairy business.

### 11. Seeder coverage

The seeded data now reflects a realistic operational month with:

- 100 farmers
- admin and collector accounts
- route assignments
- milk prices
- sale prices
- milk collections
- sales
- debt transactions

This makes the demo and testing environment behave like a live working system.

## Implementation notes

### Farmer `farmer_id` generation

Farmer IDs are generated automatically during record creation using the pattern:

```text
F0001
F0002
F0003
```

This helps preserve a stable business identifier across the app even when the underlying database `id` changes or is not used as the operational key.

### Sale pricing formula

The sale price is used for milk sold to the factory and not for per-route farmer payments.

```text
Total Sales Revenue = Σ (Sale Qty × Sale Price for the effective date)
```

### Debt formula

A farmer's current debt and account position are computed using the monthly transaction totals.

```text
Current Month Debt = Σ debt transactions in current month
Account Balance = Milk Earnings this month - Current Month Debt
```

## Recommended workflow

1. Run migrations and seeders.
2. Log in as admin.
3. Configure routes, milk prices, and sale prices.
4. Create or manage farmers.
5. Record milk collections.
6. Review farmer debt and account balance.
7. Record sales using the current sale price.
8. Monitor dashboard card values and farmer records.

## Useful commands

```bash
php artisan migrate:fresh --seed
php artisan serve
php artisan test
php artisan tinker
```

## Summary

Maziwa 2 now reflects a realistic dairy business workflow: farmer records, route assignments, milk collection tracking, pricing by effective date, debt tracking, and sales reporting. The project has been cleaned up, corrected for business key consistency, and documented with the logic needed to understand how operational and financial calculations are derived.


### Refresh database with seed data

```bash
php artisan migrate:fresh --seed
```

### Clear cache

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

## Development notes

This project is suitable for:

- Dairy cooperative management
- Milk collection business operations
- Farmer account tracking and payment reporting
- Route and price management across regions or collection zones

It is structured in a Laravel-first way and uses Filament to provide an admin interface for operational users without needing a separate custom admin frontend.

## License

This project is configured as a Laravel application and follows the standard package licensing conventions used by Laravel projects.
