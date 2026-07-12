Product Requirements Document (PRD) – TransitOps Smart Transport Operations Platform
Goal: Build an end‑to‑end transport‑operations platform in 8 hours using only Core PHP, MySQL, and Bootstrap. The system must be data‑driven (no hard‑coded look‑ups) and enforce all business rules via application logic and DB constraints where possible. Minimal third‑party libraries (e.g., PHPMailer for email reminders) are allowed.
ItemDescriptionProductTransitOps – Smart Transport Operations PlatformUse‑CaseLogistics companies replace spreadsheets & manual logbooks with a centralized, role‑based system to manage vehicles, drivers, trips, maintenance, fuel, and expenses.Primary Benefits• Real‑time operational visibility
• Automated compliance checks (license expiry, vehicle capacity)
• Reduced downtime & cost
• Data‑driven insights (fuel efficiency, ROI, utilization)Target UsersFleet Manager, Driver, Safety Officer, Financial Analyst, AdministratorTech StackPHP 8.x ( Procedural / OOP), MySQL 8+, Bootstrap 5, jQuery (for AJAX), PHPMailer (email), optional GD for simple chartsDeliveryFully functional MVP with responsive UI, RBAC, CRUD for core entities, automated status transitions, KPI dashboard, CSV export, email alerts.
Business Rules (must be enforced):
Vehicle registration number unique.
Retired / In‑Shop vehicles never appear in dispatch pool.
Drivers with expired licences or Suspended status cannot be assigned.
A driver or vehicle already On Trip cannot be assigned to another trip.
Cargo weight ≤ Vehicle max load capacity.
Dispatch → Vehicle & Driver status = On Trip.
Complete trip → Vehicle & Driver status = Available.
Cancel dispatched trip → Vehicle & Driver status = Available.
Create maintenance record → Vehicle status = In Shop.
Close maintenance → Vehicle status = Available (unless retired).
KPIs displayed on Dashboard (must be calculated in real‑time):
Active Vehicles
Available Vehicles
Vehicles In Maintenance
Active Trips
Pending Trips
Drivers On Duty
Fleet Utilization %
Compliance – All data stored in MySQL; no static arrays or CSV files used for reference data.
Security – Password hashing (bcrypt), session‑based auth, role‑based access control (RBAC).
Usability – Responsive UI, dark‑mode toggle, search/filter/sort, CSV export, email reminders for expiring licences.
Performance – All calculations performed on the server side; DB indexes on foreign keys and status fields.
Testing – Manual functional verification; basic unit tests for core CRUD and validation logic.
3. Functional Requirements Breakdown
3.1 Authentication & Authorization
Login Page – Email / Password.
Password Storage – password_hash() (bcrypt).
Roles – admin, fleet_manager, driver, safety_officer, financial_analyst.
RBAC – Role‑based menu visibility & action permissions (view, add, edit, delete).
Session – PHP sessions; session timeout 30 min.
3.2 Dashboard (Role‑Specific)
Fleet Manager – Full KPI set + filters (vehicle type, status, region).
Safety Officer – KPI focused on driver compliance & safety scores.
Financial Analyst – KPI on operational cost, fuel efficiency, ROI.
Driver – Minimal view (own schedule, status).
3.3 Vehicle Registry
FieldTypeConstraintsregistration_numberVARCHAR(20)UNIQUE, NOT NULLvehicle_nameVARCHAR(100)NOT NULLtypeENUM('car','van','truck','motorcycle')NOT NULLmax_load_capacityDECIMAL(10,2)> 0odometerDECIMAL(12,2)≥ 0acquisition_costDECIMAL(12,2)≥ 0statusENUM('available','on_trip','in_shop','retired')NOT NULLregionVARCHAR(50)optional
CRUD – Add, Edit, Delete (soft‑delete flag is_deleted).
Business Rule – Retired / In‑Shop vehicles hidden from dispatch via status filter.
3.4 Driver Management
FieldTypeConstraintslicense_numberVARCHAR(30)UNIQUE, NOT NULLnameVARCHAR(150)NOT NULLlicense_categoryVARCHAR(10)NOT NULLlicense_expiry_dateDATENOT NULLcontact_numberVARCHAR(20)optionalsafety_scoreDECIMAL(3,2)0‑5 scalestatusENUM('available','on_trip','off_duty','suspended')NOT NULLemailVARCHAR(255)optional (for notifications)
CRUD – Same as vehicles.
Email Alert – 7 days before expiry (PHPMailer).
3.5 Trip Management
FieldTypeConstraintsvehicle_idINTFK → vehicles.iddriver_idINTFK → drivers.idsourceVARCHAR(200)NOT NULLdestinationVARCHAR(200)NOT NULLcargo_weightDECIMAL(10,2)> 0planned_distanceDECIMAL(12,2)> 0statusENUM('draft','dispatched','completed','cancelled')NOT NULLactual_distanceDECIMAL(12,2)nullablestart_timeDATETIMEnullableend_timeDATETIMEnullable
Validation (application layer):
cargo_weight ≤ vehicle.max_load_capacity
status transitions only allowed as per workflow.
Auto‑Updates:
dispatched → set vehicles.status='on_trip', drivers.status='on_trip'
completed → set both back to 'available'
cancelled → set both back to 'available'
3.6 Maintenance Workflow
FieldTypeConstraintsvehicle_idINTFK → vehicles.iddescriptionVARCHAR(255)NOT NULLcostDECIMAL(12,2)≥ 0dateDATENOT NULLstatusENUM('open','closed')NOT NULLnotesTEXToptional
Business Rule – Adding a open maintenance record automatically sets vehicles.status='in_shop'.
Closing a record (status='closed') resets vehicle status to 'available' unless vehicle is retired.
3.7 Fuel & Expense Management
Two separate tables keep data normalized.
Fuel Logs
FieldTypeConstraintsvehicle_idINTFK → vehicles.idlitersDECIMAL(12,2)> 0costDECIMAL(12,2)≥ 0dateDATENOT NULL
Other Expenses (tolls, repairs, etc.)
FieldTypeConstraintsvehicle_idINTFK → vehicles.idtypeENUM('toll','maintenance','other')NOT NULLdescriptionVARCHAR(255)NOT NULLcostDECIMAL(12,2)≥ 0dateDATENOT NULL
Operational Cost Calculation (per vehicle, per period) – computed in reports:

TotalCost = Σ(fuel_logs.cost) + Σ(expenses.cost) + Σ(maintenance_logs.cost)
3.8 Reports & Analytics
Fuel Efficiency – $ \frac{actual\_distance}{liters} $ (km/l or miles/gal).
Fleet Utilization – $ \frac{active\_trips}{total\_vehicles} \times 100 $ %.
Operational Cost – as above.
Vehicle ROI – $ \frac{revenue - (maintenance + fuel)}{acquisition\_cost} $ (revenue can be entered manually per trip or left as placeholder).
Export – CSV generation via PHP fputcsv(). PDF export optional (use dompdf if needed, but not required).
3.9 Additional Features
FeatureDescriptionVehicle DocumentsStore scanned licence, insurance, etc. (vehicle_documents table).Email Reminders7‑day warning for license_expiry_date.Search / Filters / SortingGlobal search across entities; filters by status, region, type; sortable tables.Dark ModeBootstrap theme toggle (data-bs-theme="dark").Responsive DesignMobile‑first layout; all pages work on tablets & phones.LoggingSimple audit trail (action_logs table) – user, timestamp, entity, action.BackupMySQL dump script (can be run manually or via cron).
4. Non‑Functional Requirements (NFR)
CategoryRequirementSecurityHTTPS (future), password complexity, session timeout, input validation & SQL injection prevention (prepared statements).PerformanceAll SELECTs indexed; dashboard KPIs cached in a dashboard_cache table (refreshed every 5 min).UsabilityIntuitive UI, breadcrumb navigation, inline validation messages, accessible (ARIA).MaintainabilityPHP OOP with namespaced classes; PHP‑Documentor comments; separate config.php, db.php.LocalizationEnglish only (simple lang.php array for future expansion).ComplianceData retained per retention policy (GDPR‑style); soft‑delete flags.TestingManual verification of each workflow; basic PHPUnit tests for DB functions.DocumentationREADME with setup steps, DB import script, API usage (if any).
5. User Roles & Permissions Matrix
RoleCan ViewCan AddCan EditCan DeleteNotesAdminAllAllAllAllFull control; can manage users & roles.Fleet ManagerVehicles, Drivers, Trips, Maintenance, Fuel/ExpensesVehicles, Drivers, TripsEdit own entriesDelete (soft)Cannot manage users.Safety OfficerDrivers (licence expiry), MaintenanceNoneEdit driver safety scoreNoneEmail alerts only.Financial AnalystReports, Expenses, FuelNoneEdit expense entriesNoneView all vehicle costs.DriverOwn profile, assigned tripsNone (trip request via form)Update status (e.g., start/end trip)NoneLimited UI.
6. Database Design (3NF)
Below is a complete, normalized schema. All tables use InnoDB, utf8mb4_unicode_ci collation, and appropriate indexes.
6.1 Core Tables (SQL)

-- -----------------------------------------------------
-- 1. roles (lookup)
-- -----------------------------------------------------
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(30) NOT NULL UNIQUE,
    description TEXT
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 2. users (authentication & RBAC)
-- -----------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    full_name VARCHAR(150),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role (role_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON UPDATE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 3. vehicles
-- -----------------------------------------------------
CREATE TABLE vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration_number VARCHAR(20) NOT NULL UNIQUE,
    vehicle_name VARCHAR(100) NOT NULL,
    type ENUM('car','van','truck','motorcycle') NOT NULL,
    max_load_capacity DECIMAL(10,2) NOT NULL CHECK (max_load_capacity > 0),
    odometer DECIMAL(12,2) NOT NULL DEFAULT 0,
    acquisition_cost DECIMAL(12,2) NOT NULL DEFAULT 0,
    status ENUM('available','on_trip','in_shop','retired') NOT NULL DEFAULT 'available',
    region VARCHAR(50),
    is_deleted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_region (region)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 4. drivers
-- -----------------------------------------------------
CREATE TABLE drivers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    license_number VARCHAR(30) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    license_category VARCHAR(10) NOT NULL,
    license_expiry_date DATE NOT NULL,
    contact_number VARCHAR(20),
    safety_score DECIMAL(3,2) DEFAULT 0 CHECK (safety_score >= 0 AND safety_score <= 5),
    status ENUM('available','on_trip','off_duty','suspended') NOT NULL DEFAULT 'available',
    email VARCHAR(255),
    is_deleted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_license_expiry (license_expiry_date),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 5. trips
-- -----------------------------------------------------
CREATE TABLE trips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    driver_id INT NOT NULL,
    source VARCHAR(200) NOT NULL,
    destination VARCHAR(200) NOT NULL,
    cargo_weight DECIMAL(10,2) NOT NULL CHECK (cargo_weight > 0),
    planned_distance DECIMAL(12,2) NOT NULL CHECK (planned_distance > 0),
    status ENUM('draft','dispatched','completed','cancelled') NOT NULL DEFAULT 'draft',
    actual_distance DECIMAL(12,2),
    start_time DATETIME,
    end_time DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_vehicle (vehicle_id),
    INDEX idx_driver (driver_id),
    INDEX idx_status (status),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON UPDATE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES drivers(id) ON UPDATE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 6. maintenance_logs
-- -----------------------------------------------------
CREATE TABLE maintenance_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    cost DECIMAL(12,2) NOT NULL DEFAULT 0,
    date DATE NOT NULL,
    status ENUM('open','closed') NOT NULL DEFAULT 'open',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_vehicle (vehicle_id),
    INDEX idx_status (status),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON UPDATE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 7. fuel_logs
-- -----------------------------------------------------
CREATE TABLE fuel_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    liters DECIMAL(12,2) NOT NULL CHECK (liters > 0),
    cost DECIMAL(12,2) NOT NULL DEFAULT 0,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_vehicle (vehicle_id),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON UPDATE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 8. expenses
-- -----------------------------------------------------
CREATE TABLE expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    type ENUM('toll','maintenance','other') NOT NULL,
    description VARCHAR(255) NOT NULL,
    cost DECIMAL(12,2) NOT NULL DEFAULT 0,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_vehicle (vehicle_id),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON UPDATE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 9. vehicle_documents
-- -----------------------------------------------------
CREATE TABLE vehicle_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT NOT NULL,
    document_type VARCHAR(50) NOT NULL,               -- e.g., 'license', 'insurance'
    file_path VARCHAR(255) NOT NULL,
    expiry_date DATE,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_vehicle (vehicle_id),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON UPDATE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 10. action_logs (audit)
-- -----------------------------------------------------
CREATE TABLE action_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    entity VARCHAR(30) NOT NULL,      -- vehicles, drivers, trips, etc.
    entity_id INT NOT NULL,
    action VARCHAR(20) NOT NULL,      -- CREATE, UPDATE, DELETE
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    details TEXT,
    INDEX idx_user (user_id),
    INDEX idx_entity (entity, entity_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE SET NULL
) ENGINE=InnoDB;
6.2 Normalisation Notes
First Normal Form (1NF) – All values are atomic; no repeating groups.
Second Normal Form (2NF) – No partial dependencies; every non‑key attribute depends on the whole primary key (no composite keys).
Third Normal Form (3NF) – No transitive dependencies; e.g., vehicles.region is stored directly (no dependent lookup table because region is simple reference). If needed, a regions lookup can be added later.
6.3 Constraints & Triggers (where DB can enforce)
Check Constraints – Already added for numeric ranges.
Unique Constraints – Registration numbers, licence numbers.
Foreign Keys – Ensure referential integrity.
Trigger Example (pseudo‑SQL – can be implemented in PHP if MySQL triggers cause performance issues):

DELIMITER //
CREATE TRIGGER trg_maintenance_open
BEFORE INSERT ON maintenance_logs
FOR EACH ROW
BEGIN
    IF NEW.status = 'open' THEN
        UPDATE vehicles SET status = 'in_shop' WHERE id = NEW.vehicle_id;
    END IF;
END //
DELIMITER ;
Trigger for maintenance close – Reset vehicle status to available (unless retired).
Note: In a high‑traffic scenario, these status changes could be handled entirely in PHP to avoid trigger overhead. The above is optional.
7. Technical Architecture (Core PHP + Bootstrap)
7.1 Directory Structure

TransitOps/
├─ api/               -- PHP classes (Auth, DB, CRUD, Reports)
│   ├─ classes/
│   │   ├─ Database.php
│   │   ├─ Auth.php
│   │   ├─ Vehicle.php
│   │   ├─ Driver.php
│   │   ├─ Trip.php
│   │   ├─ Maintenance.php
│   │   ├─ Fuel.php
│   │   └─ Expense.php
│   ├─ routes/
│   │   ├─ auth.php
│   │   ├─ vehicles.php
│   │   ├─ drivers.php
│   │   ├─ trips.php
│   │   └─ reports.php
│   └─ config.php
├─ public/
│   ├─ login.php
│   ├─ logout.php
│   ├─ index.php          -- dashboard
│   ├─ vehicles/
│   │   ├─ list.php
│   │   ├─ add.php
│   │   └─ edit.php
│   ├─ drivers/
│   │   ├─ list.php
│   │   ├─ add.php
│   │   └─ edit.php
│   ├─ trips/
│   │   ├─ list.php
│   │   ├─ add.php
│   │   └─ edit.php
│   ├─ maintenance/
│   │   ├─ list.php
│   │   └─ add.php
│   ├─ reports/
│   │   ├─ fuel_efficiency.php
│   │   ├─ operational_cost.php
│   │   └─ roi.php
│   └─ assets/
│       ├─ css/
│       ├─ js/
│       └─ img/
└─ assets/
    ├─ bootstrap/5.3.3
    └─ PHPMailer/
7.2 Core PHP Patterns
OOPHP – All DB interactions via a singleton Database class using prepared statements.
Error Handling – Custom AppException with JSON error responses.
Session – session_start(); regenerate ID on login.
Input Sanitisation – Use filter_var() and htmlspecialchars() for output.
Pagination – Simple offset‑limit for lists (can be extended later).
7.3 Bootstrap UI
Navbar – Role‑specific menu items (via session).
Cards – KPI widgets on dashboard.
Modals – For quick add/edit (or inline forms).
DataTables – jQuery DataTables plugin (optional; pure Bootstrap tables if JS limited).
Dark Mode – document.body.setAttribute('data-bs-theme', theme) via a toggle button.
7.4 Minimal Third‑Party Libraries
LibraryPurposeReasonPHPMailerSend license‑expiry reminder emailsWell‑maintained, no external APIs needed.Chart.js (optional)Visual charts in reportsLightweight; can be swapped for PHP‑generated images.dompdf (optional)PDF exportNot required per spec; easy to add later.
All other UI/UX is built with Bootstrap components + custom PHP logic.
8. Workflow Examples (Illustrated)
8.1 Vehicle Registration (Step 1 from PDF)
Fleet Manager → Vehicles → Add.
Form validates uniqueness of registration_number.
Insert into vehicles (status='available').
Success → redirect to vehicle list.
8.2 Driver Registration (Step 2)
Same as above but insert into drivers.
Set license_expiry_date; trigger email scheduler (cron) to send reminder 7 days before.
8.3 Trip Creation & Dispatch (Steps 3‑5)
Trips → Add → select vehicle & driver (filtered by status='available').
Validate cargo_weight ≤ vehicle.max_load_capacity.
Insert row with status='dispatched'.
PHP updates vehicles.status='on_trip' and drivers.status='on_trip'.
8.4 Trip Completion (Step 6)
Edit trip → set actual_distance, fuel_consumed.
Insert fuel log (via fuel_logs table).
Update trip status='completed', set end_time.
PHP resets vehicle & driver status to 'available'.
8.5 Maintenance Record (Step 8)
Maintenance → Add → select vehicle, fill description & cost.
Insert into maintenance_logs (status='open').
Trigger sets vehicles.status='in_shop'.
8.6 Reports (Step 9)
Reports → Fuel Efficiency → query fuel_logs & trips for the selected period.
Compute $efficiency = $actual_distance / $liters.
Render table + CSV export button.
9. Deployment & Local Setup
9.1 Prerequisites
PHP 8.2+ with ext-pdo, ext-mbstring, ext-json.
MySQL 8+ (or MariaDB).
Web server (Apache/Nginx).
9.2 Install Script (install.php)

<?php
// 1. Create DB & run schema.sql
// 2. Insert default roles (admin, fleet_manager, driver, safety_officer, financial_analyst)
// 3. Create admin user (email: admin@transitops.com, password: ChangeMe123!)
?>
All scripts are pure PHP – no external APIs.
9.3 Environment Variables (config.php)

<?php
return [
    'db_host'     => '127.0.0.1',
    'db_name'     => 'transitops',
    'db_user'     => 'root',
    'db_pass'     => '',
    'base_url'    => 'http://localhost/TransitOps/public',
    'smtp_host'   => 'smtp.gmail.com',
    'smtp_user'   => 'your@email.com',
    'smtp_pass'   => 'app_password',
    'site_name'   => 'TransitOps',
];
?>
9.4 Cron Job (optional)

# Daily reminder for expiring licences
0 8 * * * php /path/to/TransitOps/cron/reminders.php
10. Testing & Validation
Test CaseDescriptionExpected ResultTC‑AUTH‑01Login with valid credentialsSession started, redirect to dashboardTC‑AUTH‑02Login with invalid credentialsError message, no sessionTC‑VEH‑01Add duplicate registration numberValidation error, no duplicate rowTC‑TRIP‑01Create trip with overweight cargoValidation error, trip not createdTC‑TRIP‑02Dispatch trip → status changesVehicle & Driver status = on_tripTC‑MAINT‑01Add open maintenanceVehicle status = in_shopTC‑REPORT‑01Export CSV for fuel efficiencyFile download with correct columnsTC‑EMAIL‑01Licence expires in 7 daysReminder email sent
Manual testing will be performed using a local XAMPP / Docker stack. Automated unit tests will cover core DB functions and validation logic (PHPUnit).
11. Timeline & Milestones (8‑Hour Hackathon)
HourMilestone0‑1Setup environment, import DB schema, seed roles & admin user.1‑2Build Authentication & RBAC (login, session, role checks).2‑4Implement Vehicle CRUD + validation, list with filters.4‑5Implement Driver CRUD + expiry reminders (PHPMailer).5‑6Build Trip Management with lifecycle & auto‑status transitions.6‑7Implement Maintenance, Fuel & Expense logging + status sync.7‑8Build Dashboard KPIs, reports, CSV export, UI polish (Bootstrap, dark mode).
12. Risks & Assumptions
RiskMitigationComplexity of business rulesImplement validation in PHP first; later move to DB triggers if needed.Time constraintsPrioritize core CRUD + dashboard; optional features (PDF export, charts) can be added as “nice‑to‑have”.Email sending failuresUse PHPMailer exceptions; log errors in action_logs.Data integrityAll foreign keys enforced; use transactions for multi‑step actions (e.g., dispatch).Browser compatibilityUse Bootstrap native components; avoid custom JS libraries.
Assumptions
Users have standard email & password; no OAuth2 integration required.
All monetary values stored as DECIMAL.
No multi‑language requirement at MVP.
13. Glossary
TermDefinitionRBACRole‑Based Access Control – permissions tied to user roles.KPIKey Performance Indicator – e.g., Active Vehicles, Fleet Utilization.ROIReturn on Investment – $ \frac{Revenue - (Maintenance + Fuel)}{Acquisition Cost} $.CRUDCreate, Read, Update, Delete – basic data operations.PHPMailerPHP library for sending emails (used for reminders).DataTablesjQuery plugin (optional) for searchable, paginated tables.Soft‑DeleteFlag is_deleted instead of physically removing rows (preserves audit).
14. Deliverables (What you’ll receive)
Complete PHP project (structured as per Section 7).
MySQL schema (schema.sql) – ready to import.
Installation script (install.php) – one‑click setup.
README.md – environment setup, how to run, testing steps.
Live demo (if hosted) – login with admin@transitops.com / ChangeMe123! to explore all modules.
All code follows PSR‑12 coding standards, includes PHPDocumentor comments, and is ready for further extension.

i need to make this and i am using antgravity , uses the skill from vercel give me full step by step guide to achav proper phase vise all phase with detailed prompte and yes make a file in startimg that can track what work are donmr every time ththis was apends what done fetechers and ui thigs i