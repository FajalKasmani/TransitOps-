# Odoo Hackathon 2026 Core Context: TransitOps Platform

## 👥 Team & Environment
- **Team Composition:** 3 Student Developers executing a unified workflow via Git.
- **Development Environment:** 100% Local Execution (Local XAMPP stack).
- **Core Tech Stack:** Core Native PHP 8.x, MySQL 8+, Bootstrap 5 (loaded exclusively via CDN), jQuery (for seamless AJAX operations).
- **Execution Rule:** Zero external cloud configurations, live hosting, or microservice dependencies. The application must install, spin up, and run flawlessly offline.

---

## 🎯 Mandatory Judging Rubrics & Technical Constraints

### 1. Dynamic Data Engine (MySQL-First)
- **Constraint:** Zero hardcoded datasets, reference tables, or static JSON/CSV configurations for system operations.
- **Implementation:** All operational logic, vehicle pools, driver statuses, trip options, and analytical configurations must be fetched dynamically out of the active MySQL database instance.

### 2. Ironclad Server-Side Security
- **Input Sanitization:** Every user payload (`$_POST`, `$_GET`, `$_REQUEST`) must be passed through rigorous server-side scrubbing routines (`filter_var()`, prepared statements) before processing.
- **SQL Injection Prevention:** 100% of database calls interacting with variables must utilize PDO prepared statements with bounded parameters. No raw SQL string interpolation.
- **XSS Mitigation:** All database strings rendered into the DOM must be securely escaped using `htmlspecialchars()`.
- **RBAC Enforcement:** Session states must actively block unauthorized horizontal or vertical privilege escalation by terminating or redirecting unprivileged role actions.

### 3. Visual Excellence & Enterprise UX
- **Design Paradigm:** Odoo-grade clean, highly scannable, component-driven dashboard layout built on top of a semantic Bootstrap 5 engine.
- **Visual Polish:** Intentional layout spacing, explicit grid setups, clean responsive breakdowns for multi-device viewports, native dark-mode capabilities via `data-bs-theme="dark"`, and unified UI components (cards, tables, buttons, modally triggered interactions).

---

## 🏗️ Architectural Directives for AI Agents

When generating code, refactoring modules, or building out UI components for this workspace, you must adhere to the following conventions:

1. **Coding Style:** Pure, clean PHP using an Object-Oriented approach for backend service components (`Database`, `Auth`, `Vehicle`, `Driver`, `Trip`, `Maintenance`, `Report`).
2. **Error Management:** Use robust, user-facing alert components instead of unhandled raw exceptions. Log exceptions secretly into the system's `action_logs` table.
3. **State Integrity:** All operations changing multiple entities simultaneously (e.g., dispatching a vehicle changes both vehicle and driver state tables) must be protected inside explicit PDO ACID transactions (`beginTransaction`, `commit`, `rollBack`).
4. **Scannability:** Tables must feature logical filtering, pagination hooks, and clear contrast. Dashboard spaces must instantly communicate system health via structural KPI widgets.