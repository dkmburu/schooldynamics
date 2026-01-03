# SIMS (School Information Management System) — Multi‑Tenant Architecture & Module Interaction Spec
**Version:** 1.0  
**Date:** 2025-11-03 06:30 (Africa/Nairobi)  
**Stack:** PHP 7.4+/8.x, MySQL 8+, Apache/Nginx (WAMP/Ubuntu), Single codebase, Router DB → Tenant DBs

---

## 1) Executive Summary
This document defines a production‑ready blueprint for a multi‑tenant School Information Management System (SIMS) built with PHP and MySQL. It uses a single codebase, a router database for tenant resolution by subdomain, and per‑school tenant databases. It covers module list, ownership boundaries, key entities, inter‑module interactions, RBAC, audit logging, data flows, and non‑functional requirements (security, performance, backups).

The system targets K‑12 contexts with Fees & Finance, Admissions, Academics, Assessment, Communication, Transport, Library, Inventory, eLearning, Guardian/Student Portals, Reports, and System Admin modules. It is optimized for East African school operations but remains globally adaptable.

---

## 2) Multi‑Tenant Architecture

### 2.1 Tenancy Model
- Single codebase deployed once. All schools (tenants) use the same PHP app.
- Router DB (`sims_router`) maps {subdomain}.sims.local (or prod domain) → tenant DB credentials.
- Tenant DB per school with identical schema; data isolation enforced at DB level.
- Strict audit logging and RBAC per tenant. No data leakage between tenants.

### 2.2 Router DB (Source of Truth)
- Table (minimal core):
  - tenants(id, subdomain, db_host, db_name, db_user, db_pass_enc, status, created_at, updated_at)
  - Optional: branding_json, plan_tier, features_flags_json, support_contact, maintenance_mode
- Resolution flow:
  1. Extract subdomain from incoming host header.
  2. Query sims_router.tenants by subdomain (status = active).
  3. Decrypt password with SECURE_KEY (do not store the key in repo).
  4. Create tenant connection; fail closed if error.
- .env loading:
  - Global .env path (Windows WAMP example): C:/wamp64_3.3.7/env/sim/.env
  - Holds SECURE_KEY, default mail/SMS, cache settings, paths, etc.

### 2.3 Tenant DB
- Schema versioning: migrations table in each tenant DB; app runs migrations on deploy.
- Naming: sims_{slug} or provided by router row.
- Core infra tables (per tenant):
  - audit_logs
  - modules, submodules
  - roles, permissions, role_permissions, user_roles
  - users (staff), guardians, students, documents
  - settings (key/value), school_profile, academic_calendar
  - Finance: chart_of_accounts, fee_components, fee_tariffs, invoices, invoice_lines, receipts, allocations, adjustments, payment_methods, reconciliations
  - Communication: messages_sms, messages_email, message_templates
  - Attendance & Assessment: classes, streams, subjects, timetables, attendance, assessments, grades
  - Operational: transport_routes, buses, library_books, inventory_items, stores_txns, etc.
- File storage:
  - Store uploads outside public web root (Windows/Linux compatible pathing).
  - Public controller streams files after RBAC + audit log + anti‑hotlink checks.

### 2.4 Request Lifecycle (High‑level)
1. HTTP request → Router resolves tenant → Tenant DB connection established.
2. Middleware stack: session, auth, CSRF, RBAC gate, tenant context.
3. Controller → Service → Repository → DB.
4. Responses are HTML (Bootstrap 4.6/5), JSON (AJAX), or file streams.
5. Every write → audit_logs entry (who, when, IP, URL, payload hash, result).

---

## 3) RBAC (Roles‑Based Access Control)

### 3.1 Concepts
- Modules (top‑level) and Submodules (screens/features).
- Permissions: per submodule, at least two actions: view, write. (Extendable: approve, export, delete).
- Roles: named sets of permissions (e.g., ADMIN, BURSAR, TEACHER, HEAD_TEACHER, CLERK, LIBRARIAN, TRANSPORT_OFFICER, COUNSELOR).
- User ↔ Role: many‑to‑many.
- Sidebar: shows only submodules with view permission; a “write” badge appears if granted.

### 3.2 Enforcement
- Gateway checks: deny if not view.
- Button/controls hidden if not write.
- API endpoints validate both auth and RBAC; fail closed (HTTP 403).

### 3.3 Audit & Approvals
- audit_logs for all mutations (before/after snapshot or diff).
- Optional maker‑checker patterns (e.g., tariffs submit → approve; receipts reversal → approve).

---

## 4) Global UX Conventions
- Layout: Collapsible sidebar; top bar actions; fluid pages; mobile responsive.
- Tables: Use class="tbl_sh" for header cells and class="tbl_data" for data cells.
- Date ranges: Default last 30 days where relevant; display visible human‑readable range.
- Search: Global search with autosuggest (graphs/reports, students, invoices, etc.) respecting RBAC (clickable vs greyed out).
- Notifications: Bell icon with badge; dropdown preview; modal for full message.
- Comments: Threaded replies where appropriate (e.g., student notes, tasks).

---

## 5) Core Modules & Interactions

### 5.1 Dashboard
Purpose: Snapshot KPIs + quick links.
Key Widgets (per role):
- Finance: fees billed vs received, outstanding AR by class/term, top debtors, daily collections.
- Academics: attendance rate today, assessment submissions due, average grade by class.
- Operations: transport status, library overdues, inventory low stock.
- Tasks: approvals pending (tariffs, reversals), notifications.
Interactions:
- Global date filter; drill‑downs to respective modules.
- Role‑aware widgets.

---

### 5.2 Tasks (Approvals & Workflows)
Purpose: Single inbox/outbox for approvals and to‑dos.
Examples:
- Fee tariff submit → Finance approval.
- Invoice batch generation → Admin approval (optional).
- Receipt reversal → Bursar approval.
- Procurement requests → Store manager approval.
Data:
- tasks(id, module, entity_id, action, status, assigned_to, due_at, payload_json, created_by, created_at)
Interactions:
- Links back to the originating record with approval log and comments.
- Notifications on assignment, nearing due dates.

---

### 5.3 Applicants Tracker → Admissions
Purpose: Manage prospects to enrolled students.
Flow:
1. Create campaign or open intake window.
2. Receive applications (web forms with secure unique URLs).
3. Screen → shortlist → interview → decision.
4. Convert accepted applicant → Student (carry forward biodata, docs, guardians).
Data highlights:
- Applicant profile, stage/status, scores/evaluations, documents, communication log.
Interactions:
- Post‑decision: triggers finance onboarding (initial fees, admission fee invoice, etc.).

---

### 5.4 Students (Core Student Records)
Entities:
- students (bio, birthdate, gender, admission no.), student_status (active, suspended, alumni).
- guardians (contacts, relationship), student_guardians (link + primary flag).
- Profiles: medical info (non‑diagnostic), transport opt‑in, dietary notes.
Processes:
- Admission (from Applicants), class/stream assignment, year promotions, transfers, exit/clearance.
Interactions:
- Finance: billing by class/term, optional items (transport, lunch).
- Communication: SMS/email to guardians per events (absences, invoices, results).
- Documents: birth cert, report cards, transfers.

---

### 5.5 Academics (Timetables, Subjects, Attendance)
Entities:
- subjects, classes, streams, teachers, timetables.
- attendance (daily or period‑based), reasons (optional), remarks.
Flows:
- Homeroom or per‑lesson attendance via teacher UI or mobile.
- Automated absent alerts to guardians (configurable time windows and templates).
Interactions:
- Assessment module consumes subject/class roster.
- Communication module sends alerts.
- Reports: daily/weekly attendance sheets, chronic absentee report.

---

### 5.6 Assessment (Exams, Continuous Assessment, Grades)
Entities:
- assessments (exam, CAT), assessment_components, marks, grade_scales, weightings.
Flows:
1. Create assessment → attach classes/subjects and marking windows.
2. Teachers enter marks → validations (min/max, late penalty optional).
3. Calculation: aggregate by weights; generate grades and positions per class/subject.
4. Publish results to Guardian/Student Portals (with release controls).
Interactions:
- Communication: result release notifications.
- Reports: class averages, subject performance, term/year trends.

---

### 5.7 Finance (Fees & Accounting Light)
Scope: Fee definition, invoicing, receipts, allocations, adjustments. (Optionally integrate with full GL.)
Core Entities:
- fee_components (tuition, lunch, transport, labs), fee_tariffs (by year/term/class).
- invoices, invoice_lines, receipts, allocations, adjustments (discounts, waivers, penalties).
- payment_methods (cash, cheque, mobile, EFT, RTGS).
Key Processes:
1. Tariff Setup: Define by year/term/class (+ optional by program/stream). Maker‑checker approval optional.
2. Invoice Generation: Batch by class/term; supports optional items (transport/lunch) based on student flags.
3. Collections: Post receipts; allocate across invoices (oldest first or manual split). Print/email receipt.
4. Adjustments: Discounts/waivers; penalties for late payment; reversals with approval.
5. Aging & Statements: Student account statement; aging buckets; reminders.
Interactions:
- Applicants → Admission fee invoice on acceptance (optional).
- Transport/Library penalties auto‑post to Finance (configurable).
- Communication sends reminders, statements (PDF link).

---

### 5.8 Reconciliations (Bank/Mobile Money)
Entities:
- bank_statements_imports, mpesa_imports, rules (matchers), reconciliations.
Flows:
1. Import CSV (bank) / export MPESA statements.
2. Auto‑match rules (amount + reference + payer name heuristics).
3. Create receipt drafts → review → post.
Interactions:
- Finance receipts pipeline.
- Reports: unmatched entries, reconciliation rate, exceptions.

---

### 5.9 School Management (Setup & Master Data)
Scope: School profile, academic calendar, years/terms, classes, streams, rooms, houses, co‑curricular.
Entities:
- school_profile, academic_calendar, academic_years, terms, classes, streams, rooms, houses.
Interactions:
- Drives billing (tariffs keyed to year/term/class).
- Drives timetable generation and attendance scope.

---

### 5.10 Staff Management (HR Lite)
Entities:
- staff (bio, contacts), roles, employment details, subjects handled, timetable allocations.
- Optional: leave tracking, timesheets, payroll export (integration ready).
Interactions:
- RBAC: users ↔ staff.
- Academics: teacher allocations; attendance entry rights.
- Communication: staff broadcasts.

---

### 5.11 Communication (SMS/Email/WhatsApp ready)
Entities:
- messages_sms, messages_email, message_templates (placeholders for {student_name}, {balance}, etc.).
Flows:
1. Compose or trigger from event (absent, invoice, due reminder, results release).
2. Queue → throttle → send via providers (e.g., HTTP API for SMS, SMTP for email).
3. Delivery status logging, cost accounting fields optional.
Interactions:
- Hooks across modules (attendance, finance, assessment, tasks).
- Templates with merge fields; role‑aware audience selection (guardians vs staff).

---

### 5.12 Transport
Entities:
- transport_routes, stops, buses, assignments, driver_contacts.
Flows:
- Assign students to route/stop; generate transport fee optional line in Finance.
- Attendance variant: on‑bus check‑in/out (optional future).
Interactions:
- Finance for billing; Communication for route changes, delays.

---

### 5.13 Library
Entities:
- library_books (ISBN, title, author, copies), loans, returns, fines.
Flows:
- Issue/return; overdue calculations; fines auto‑post to Finance (configurable).
Interactions:
- Communication for reminders; Finance for fines; Reports for overdues and circulation.

---

### 5.14 Stores & Inventory
Entities:
- inventory_items, categories, suppliers, grns, issues, adjustments, stock_levels.
Flows:
- Receive goods (GRN), issue to departments, stock take, reorder alerts.
Interactions:
- Finance (optional costing export), Procurement (future), Science labs/IT rooms tracking.

---

### 5.15 eLearning (Lite)
Scope (MVP):
- Upload/share class materials, assignment posting, submission tracking.
- Link to assessments or gradebook (optional).
Interactions:
- Student/Guardian portal visibility; notifications on new materials.

---

### 5.16 Guardian Portal
Features:
- View student profile, attendance, results, invoices, statements, payment options (link out), transport info, library loans.
- Download documents (report cards). Create support tickets/messages.
Security: Per‑guardian access only to linked students (primary + secondary flags).

---

### 5.17 Student Portal
Features:
- View timetable, attendance, results, assignments, announcements.
- Download materials; submit assignments (if enabled).
- View fee statement (read‑only).

---

### 5.18 Reports Center
Types:
- Finance (aging, collections, debtors, tariff vs actuals).
- Academics (attendance summaries, subject performance).
- Operations (transport load, library overdues, inventory valuation).
Features:
- Filters (date range, year/term/class), export (CSV/PDF), role‑scoped visibility.
- Drill‑downs and saved report configurations per user.

---

### 5.19 System Admin
Areas:
- Users & Roles (RBAC matrix with view/write selectors per submodule).
- Tenant settings (branding, academic settings, finance defaults, SMS/SMTP).
- Data tools: importers (students, guardians, fees), backups.
- Logs: audit logs, error logs viewer (read‑only), job queue monitor.
- Feature flags per tenant (modules on/off).

---

## 6) Cross‑Module Interaction Map (Selected Flows)

### A) Applicant → Student → Finance
1. Applicant accepted → Create Student, Guardians, carry docs.
2. If “admission fee on accept” enabled → Create Invoice(+line).
3. Notify guardians (welcome + invoice link).

### B) Attendance → Communication
1. Teacher marks absent by 9:30 AM.
2. System composes SMS to primary guardian using template with date/class.
3. Log message + delivery status; appear in student communication log.

### C) Assessment → Portals & Communication
1. Exam posted and closed; results aggregated.
2. Release → Push portal visibility + notify guardians/students.
3. Store PDF report cards to Documents (download via portals).

### D) Finance → Reconciliations → Receipts
1. Import bank/MPESA statements.
2. Auto‑match rules; create receipt drafts.
3. Approver posts receipts → allocations to outstanding invoices.
4. Send receipt email/SMS (optional).

### E) Transport/Library → Finance
- Transport opt‑in → Auto add optional fee lines in invoice batch.
- Library overdue → Fine computed → Auto add to next invoice or immediate invoice.

---

## 7) Non‑Functional Requirements

### 7.1 Security
- Per‑tenant DB credentials resolved at runtime.
- TLS (Let’s Encrypt/WAMP SSL); secure cookies; CSRF tokens; password hashing (bcrypt/argon2).
- Input validation + output escaping; prepared statements; content‑disposition headers for downloads.
- Least‑privilege DB users (router DB read‑only except admin ops).

### 7.2 Performance & Scale
- MySQL indexes on foreign keys and hot filters (student_id, class_id, term_id, invoice_id, message status).
- Pagination for large tables; background jobs for heavy tasks (invoice batches, comms).
- Caching of read‑mostly lookups (subjects, classes, fee components).
- Asynchronous queues (DB‑backed table jobs or system scheduler via cron).

### 7.3 Observability
- audit_logs for every mutation; user + IP + URL + payload hash + result.
- Error logging with request id; slow query log.
- Admin UI for viewing audit entries (filters, export).

### 7.4 Backups & DR
- Nightly tenant DB dumps; weekly full + daily incrementals.
- Router DB backed up before tenant backups.
- Document storage snapshot policy.
- Restore runbook; test restores quarterly.

### 7.5 Deployments
- Blue/Green or rolling; maintenance mode flag per tenant.
- Migrations are idempotent; versioned; auto‑apply with lock to avoid race conditions.

---

## 8) Directory Layout (Reference)
```
/public            # web root (index.php, assets)
/app
  /Controllers
  /Services
  /Repositories
  /Models
  /Views
  /Middlewares
  /Jobs
  /Helpers
/config            # env loader, router/tenant configs
/storage
  /logs
  /uploads         # outside public root in production
/bootstrap         # app init, route definitions
/vendor            # composer
```

---

## 9) Environment & Secrets
- .env stored outside repo (Windows example): C:/wamp64_3.3.7/env/sim/.env
- Keys: SECURE_KEY, SMTP creds, SMS API creds, default time zone (Africa/Nairobi), cache, session.
- Never commit secrets. Provide an .env.example template only.

---

## 10) Integration Points

### 10.1 SMS Gateway (HTTP API)
- Config per tenant in System Admin → Settings → Communication.
- Fields: apiClientID, key, secret, serviceID, url, sender_name, throttle_ms, dry_run.
- Queue table with retry & dead‑letter; log cost per message (optional accounting).

### 10.2 Email (SMTP)
- SMTP per tenant; templates with merge fields; DKIM/SPF guidance (docs link).

### 10.3 Payments
- MPESA/Banks: statement import (CSV, API later).
- Web payments integration (future): redirect/pay callback → auto‑receipt → allocation.

### 10.4 Document Rendering
- PDF generation for invoices, statements, report cards.
- Windows & Linux path normalization, UTF‑8 fonts embedded.

---

## 11) Reporting Framework
- Uniform filters (year/term/class/date range).
- Save report presets per user; export CSV/PDF.
- Respect RBAC; include audit trail of exports (who/when/filters).

---

## 12) Data Models (High‑Level Entity Map)
(Indicative — not full DDL; normalized keys and indexes implied)

- People: students, guardians, staff, link tables.
- Academics: classes, streams, subjects, timetables, attendance, assessments, marks, grade_scales.
- Finance: fee_components, fee_tariffs, invoices, invoice_lines, receipts, allocations, adjustments, payment_methods, reconciliations.
- Ops: transport_routes, stops, buses, library_books, loans, fines, inventory_items, stores_txns.
- Comms: messages_sms, messages_email, message_templates.
- System: users, roles, permissions, role_permissions, user_roles, modules, submodules, audit_logs, settings, documents.

---

## 13) UX Blueprints (Key Screens)

### Finance → Fee Tariffs
- Grid by Year/Term/Class; add/edit tariff; submit/approve workflow.
- Actions: clone last term/year; bulk update; export.

### Finance → Invoice Batch
- Wizard: pick Year/Term/Class → include optional items → preview → generate.
- Post‑gen actions: print/email, export aging, set reminders schedule.

### Finance → Receipts
- Form: payer (search guardian/student), amount, method, reference, allocations table (preview before save).
- Reversal: request → approval task → audit trail.

### Academics → Attendance
- Class list by period/day; quick mark; reason; bulk present/absent; save.
- Auto‑trigger communication based on policy.

### Assessment → Mark Entry
- Grid per class/subject; validations; import CSV; publish control; analytics.

### Applicants → Pipeline
- Kanban by stage; assign reviewers; evaluation rubric; decision → convert to student.

### Communication → Campaigns
- Audience builder (filters: class, status, balance>0, absent today).
- Template select; schedule send; track delivery metrics.

---

## 14) Background Jobs & Schedulers
- Jobs: invoice batch, reminder sends, statement emails, reconciliation auto‑match, nightly backups.
- Schedule (cron): define per server; logs to /storage/logs and DB jobs_log.
- Retry policy: exponential backoff; dead‑letter queue for manual intervention.

---

## 15) Migration Strategy
- Initial migration creates all core tables + seed roles/permissions.
- Version tagging in migrations with applied_at and checksum.
- Safe re‑runs; no destructive changes without explicit ALTER steps and backups.

---

## 16) Testing & UAT
- Seed demo tenant with synthetic data for flows (admissions → invoices → receipts → assessment → portals).
- Role‑based test accounts.
- UAT scripts for core scenarios and acceptance criteria.

---

## 17) Roadmap (Phase 2)
- GL integration (double‑entry), payroll, procurement, asset register.
- Mobile teacher app (attendance/marks).
- Parent mobile app (fees, results, notices).
- Real‑time transport tracking.
- Online payments integration (checkout + callbacks).
- Advanced analytics dashboards with drill‑downs.

---

## 18) Glossary
- Tenant: A school with its own DB instance.
- Router DB: Central DB that maps subdomains to tenant DB credentials.
- RBAC: Role‑based access control.
- Maker‑Checker: Submit/approve workflow for sensitive changes.

---

## 19) Acceptance Checklist
- [ ] Router DB resolves tenants reliably; maintenance mode tested.
- [ ] RBAC matrix enforces view/write; sidebar reflects permissions.
- [ ] Audit logs for all mutations with user/IP/URL/result.
- [ ] Document storage outside web root with secure streaming.
- [ ] Finance flows (tariffs → invoices → receipts → statements → aging) complete.
- [ ] Attendance alerts and result releases work with templates.
- [ ] Reconciliation import and auto‑match rules produce draft receipts.
- [ ] Portals scoped correctly to guardians/students.
- [ ] Backups + restore procedure validated.
- [ ] Reports export with audit trail of exports.

---

End of Document
