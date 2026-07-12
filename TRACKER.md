# TransitOps – Smart Transport Operations Platform MVP Tracker

This file tracks our progress through the core modules of the application.

## Core Modules Checklist

- [x] **Auth** (Authentication & Session Management)
- [x] **Dashboards** (KPI Widgets, Visual Layouts, Role-based Dashboards)
- [x] **Vehicles** (Management, Capacity/Odometer checks)
- [x] **Drivers** (Management, Expiry dates, Safety scores)
- [x] **Trips** (Dispatch, Cargo tracking, Transaction protection)
- [ ] **Maintenance** (Logs, Statuses)
- [ ] **Expenses** (Logging, Categorization)
- [ ] **Reports** (Analytics, Financials, CSV export)

---

## Log of Generated Files & UI Components

### Phase 1: Foundation & Architecture
- [x] `schema.sql` - Database schema (10 tables, explicit foreign keys, indexes, check constraints, seeded roles/admin user)
- [x] `TRACKER.md` - Core project tracker
- [x] Boilerplate directories - Created `api/classes`, `api/routes`, `public/assets/css`, `public/assets/js`
- [x] `config.php` - Root environment variables configuration file
- [x] `api/classes/Database.php` - PDO singleton class referencing root configuration
- [x] `config/config.php` - Sub-folder configuration file (for backward compatibility)
- [x] `api/Database.php` - Sub-folder PDO singleton class (for backward compatibility)

### Phase 2: Authentication & Master Layout
- [x] `api/classes/Auth.php` - Session-based user authentication, logout, session timeout, and RBAC access checks
- [x] `public/login.php` - Responsive login interface with persistent dark-mode toggle switch
- [x] `public/logout.php` - Logout execution handler
- [x] `public/includes/header.php` - Header layout containing sidebar navigation and profile details
- [x] `public/includes/footer.php` - Footer layout with theme-toggle listeners and scripts

### Phase 3: Dashboards & Real-Time KPIs
- [x] `api/classes/Reports.php` - Server-side real-time query processor for KPIs, cost metrics, and safety score aggregates
- [x] `public/index.php` - Role-customized dashboard (customized for Admin, Fleet Manager, Safety Officer, Financial Analyst, and Driver)

### Phase 4: Vehicle & Driver Management
- [x] `api/classes/Vehicle.php` - Backend class with CRUD, capacity/odometer limits, and uniqueness validation
- [x] `api/classes/Driver.php` - Backend class with CRUD, license expiry checks, and safety score validation
- [x] `public/vehicles/list.php`, `add.php`, `edit.php` - Fleet management responsive interfaces
- [x] `public/drivers/list.php`, `add.php`, `edit.php` - Personnel registry with dynamic Safety Officer read-only locking
- [x] Redirections - Created legacy root routes `public/vehicles.php` and `public/drivers.php`

### Phase 5: Trip Dispatch & Automations
- [x] `api/classes/Trip.php` - Dispatch processor with capacity checks and transaction-isolated asset status sync
- [x] `public/trips/list.php`, `add.php`, `edit.php` - Trip schedule log, routing forms, and driver status controllers
- [x] Redirection - Created legacy root route `public/trips.php`
