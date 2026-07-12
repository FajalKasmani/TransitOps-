---
name: TransitOps
description: Specialized rules for writing secure local PHP/MySQL business applications with Bootstrap 5 templates.
---

# Development Ruleset

## 1. Data Security & Validation (High Priority)
- **Database Interactivity:** You must use PHP Data Objects (PDO) for all database transactions. 
- **SQL Injection Prevention:** Never use variable concatenation inside SQL strings. Use Prepared Statements and bound parameters exclusively:
  `$stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");`
  `$stmt->execute(['email' => $userInput]);`
- **Cross-Site Scripting (XSS) Mitigation:** Every single dynamic variable echoing into the HTML DOM must be safely escaped:
  `<?php echo htmlspecialchars($data, ENT_QUOTES, 'UTF-8'); ?>`
- **Input Sanitization:** Validate types, formats, lengths, and values server-side using `filter_var()` or custom regex bounds before running actions.

## 2. Business UI Layouts (ERP / CRM Aesthetic)
- **Framework:** Bootstrap 5.
- **Components:** Design clean metrics cards, dynamic tabular lists with hover states (`.table-striped`, `.table-hover`), clear sidebars, and accessible form modules.
- **Spacing Utilities:** Avoid cluttered grids. Use standard spacing classes (`mb-4`, `p-3`, `gap-3`) to maintain visual breathing room.
- **Responsiveness:** Always design components inside responsive layout vectors (`.col-12`, `.col-md-6`, `.col-lg-4`) to support all screens.