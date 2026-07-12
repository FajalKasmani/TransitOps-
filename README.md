# 🚚 TransitOps – Smart Transport Operations Platform

TransitOps is a modern fleet and logistics management platform built to streamline transportation operations. It provides centralized management of vehicles, drivers, trips, maintenance, fuel consumption, and operational expenses through a responsive web interface with role-based access control.

---

# ✨ Features

* 🚛 Fleet & Vehicle Management
* 👨‍✈️ Driver Registry & License Tracking
* 🗺️ Trip Dispatch & Route Scheduling
* 🔧 Preventive Maintenance Management
* ⛽ Fuel Log Management
* 💰 Expense Tracking & Operational Cost Analysis
* 📊 Interactive Dashboard with KPIs & Charts
* 📈 Vehicle Analytics & ROI Reports
* 🔐 Role-Based Access Control (RBAC)
* 🌗 Built-in Light & Dark Theme
* 📧 Automated Driver License Expiry Reminders

---

# 🛠️ Technology Stack

| Layer                   | Technology                               |
| ----------------------- | ---------------------------------------- |
| **Backend**             | PHP 8.2+ (OOP, PDO, Transactions)        |
| **Database**            | MySQL 8.0+ (InnoDB, UTF-8, Foreign Keys) |
| **Frontend**            | Bootstrap 5.3, Bootstrap Icons           |
| **Charts**              | Chart.js                                 |
| **Authentication**      | PHP Sessions & RBAC                      |
| **Email Notifications** | PHPMailer                                |
| **Scheduler**           | Cron Jobs                                |

---

# 📂 Project Structure

```text
TransitOps/
│
├── api/
│   ├── classes/
│   │   ├── Auth.php
│   │   ├── Database.php
│   │   ├── Driver.php
│   │   ├── Expense.php
│   │   ├── Fuel.php
│   │   ├── Maintenance.php
│   │   ├── Reports.php
│   │   ├── Trip.php
│   │   └── Vehicle.php
│   │
│   └── cron/
│       └── reminders.php
│
├── docs/
│   └── PRD.md
│
├── public/
│   ├── index.php
│   ├── login.php
│   ├── logout.php
│   ├── drivers/
│   ├── vehicles/
│   ├── trips/
│   ├── maintenance/
│   ├── expenses/
│   ├── reports/
│   └── includes/
│
├── config.php
├── install.php
├── schema.sql
└── TRACKER.md
```

---

# 👥 User Roles

| Role                  | Responsibilities                                      |
| --------------------- | ----------------------------------------------------- |
| **Administrator**     | Full access to all modules and system settings        |
| **Fleet Manager**     | Manage vehicles, drivers, trips, and maintenance      |
| **Safety Officer**    | Monitor driver safety, license expiry, and compliance |
| **Financial Analyst** | Review operational costs, fuel usage, and ROI reports |
| **Driver**            | View assigned trips and schedules                     |

---

# 📊 Dashboard Overview

The dashboard provides real-time operational insights, including:

* Active & Available Vehicles
* Fleet Utilization
* Active & Pending Trips
* Operational Cost Summary
* Maintenance Alerts
* Fleet Status Chart
* Trip Activity Chart
* Driver Safety Statistics
* Vehicle ROI Analytics

---

# 🚀 Installation

## Prerequisites

* PHP 8.2 or later
* MySQL 8.0+ / MariaDB
* Apache or Nginx
* XAMPP, WAMP, or Laragon (recommended for local development)

---

## Setup

### 1. Clone the Repository

```bash
git clone https://github.com/your-username/TransitOps.git
```

or download the ZIP file and extract it into your web server directory.

---

### 2. Move the Project

Example (XAMPP):

```text
C:\xampp\htdocs\TransitOps
```

---

### 3. Configure the Database

Update your database credentials inside:

```text
config.php
```

Example:

```php
DB_HOST=localhost
DB_NAME=transitops
DB_USER=root
DB_PASS=
```

---

### 4. Install the Database

Open your browser:

```text
http://localhost/TransitOps/install.php
```

or via CLI:

```bash
php install.php
```

The installer automatically:

* Creates the database
* Creates all required tables
* Seeds sample data
* Creates the administrator account

---

### 5. Login

Navigate to:

```text
http://localhost/TransitOps/public/login.php
```

---

# 🔑 Default Administrator Account

| Email                                               | Password     |
| --------------------------------------------------- | ------------ |
| [admin@transitops.com](mailto:admin@transitops.com) | ChangeMe123! |

> Change the default password after the first login for security.

---

# ⏰ License Reminder Scheduler

Register the following cron job to send automated license expiry reminders every day at **08:00 AM**.

```bash
0 8 * * * php /path/to/TransitOps/api/cron/reminders.php
```

For local testing:

```text
http://localhost/TransitOps/api/cron/reminders.php?run=1
```

---

# 📱 Responsive Design

TransitOps is fully responsive and optimized for:

* 💻 Desktop
* 💼 Laptop
* 📱 Mobile
* 📟 Tablet

It also includes built-in **Light** and **Dark** themes for improved accessibility and user experience.

---

# 🔒 Security Features

* PDO Prepared Statements
* Password Hashing
* Session-Based Authentication
* Role-Based Authorization (RBAC)
* SQL Injection Protection
* XSS Protection using `htmlspecialchars()`
* Soft Delete Support
* Database Transactions
* Foreign Key Constraints

---

# 📄 License

This project was developed as an academic logistics management system for educational and demonstration purposes.
