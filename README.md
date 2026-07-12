# TransitOps - Smart Transport Operations Platform

TransitOps is a smart logistics MVP platform designed to manage and optimize vehicle fleets, driver rosters, trip dispatches, maintenance operations, and operating expenses.

---

## Technical Stack
- **Backend:** Native PHP 8.2+ (OOP patterns, PDO prep statements, transaction isolation)
- **Database:** MySQL 8.0+ (InnoDB engine, utf8mb4 collation, constraint checks, cascading foreign keys)
- **Frontend:** Bootstrap 5.3, Bootstrap Icons, responsive layout with built-in dark/light mode toggle
- **Reminders/Alerts:** Cron task scheduler + PHPMailer integration

---

## Folder Structure Overview

```text
TransitOps/
├── api/
│   ├── classes/
│   │   ├── Auth.php          # Session management & RBAC validations
│   │   ├── Database.php      # PDO MySQL Connection Singleton
│   │   ├── Driver.php        # Personnel CRUD & expiry checks
│   │   ├── Expense.php       # Operational expense CRUD
│   │   ├── Fuel.php          # Refueling log CRUD
│   │   ├── Maintenance.php   # Repairs CRUD & shop status automation
│   │   ├── Reports.php       # Dashboard KPIs, ROI & fuel efficiency
│   │   ├── Trip.php          # Route dispatch CRUD & asset status automation
│   │   └── Vehicle.php       # Fleet CRUD & capacity validations
│   └── cron/
│       └── reminders.php      # Driver license 7-day expiry warning alerts
├── config.php                 # Root application configurations
├── docs/
│   └── PRD.md                 # Product Requirement Document (Blueprints)
├── install.php                # Database installer & seeder script
├── public/
│   ├── index.php              # Role-tailored operational dashboard
│   ├── login.php              # Secure login screen with theme switcher
│   ├── logout.php             # Logout handler
│   ├── drivers/               # Roster management (add, edit, list)
│   ├── expenses/              # Fuel and expense loggers (add, list)
│   ├── includes/              # Layout partials (header.php, footer.php)
│   ├── maintenance/           # Vehicle downtime registry (add, edit, list)
│   ├── reports/               # ROI charts, fuel efficiency, CSV exporter
│   ├── trips/                 # Dispatched route scheduler (add, edit, list)
│   └── vehicles/              # Fleet asset registry (add, edit, list)
├── schema.sql                 # Complete InnoDB DDL definitions
└── TRACKER.md                 # MVP milestones and development logs
```

---

## Local Setup & Installation

### 1. Prerequisites
- **Web Server:** Apache or Nginx (e.g. local XAMPP environment)
- **PHP Version:** PHP 8.2+ with `ext-pdo` enabled
- **Database Server:** MySQL 8.0+ / MariaDB

### 2. Deployment Instructions
1. Place the project directory under the server's root folder (e.g., `C:/xampp/htdocs/TransitOps/`).
2. Verify or update MySQL credentials in `config.php` (set database host, username, and password).
3. Execute the automated installer. You can do this in two ways:
   - **Via Web Browser:** Navigate to `http://localhost/TransitOps/install.php` and click "Proceed to Login" once successful.
   - **Via CLI / Terminal:** Run the following command in the root folder:
     ```bash
     php install.php
     ```
4. Access the platform login screen at `http://localhost/TransitOps/public/login.php`.

---

## Default Login Credentials
- **Role:** Administrator (full permissions)
- **Username / Email:** `admin@transitops.com`
- **Password:** `ChangeMe123!`

---

## Compliance Alert Scheduler (Cron Job)
To automate driver license warning emails 7 days prior to expiry, register the following command inside your server's cron tab scheduler:

```bash
# Triggers warning notifications daily at 08:00 AM
0 8 * * * php /path/to/TransitOps/api/cron/reminders.php
```
*Note: In local environments, you can manually test this task by invoking `http://localhost/TransitOps/api/cron/reminders.php?run=1`.*
