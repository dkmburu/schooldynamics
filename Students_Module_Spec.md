# Students Module — End‑to‑End Lifecycle (Applicants → Admission → Enrolment)
**Version:** 1.0  
**Date:** 2025-11-03 11:26 (Africa/Nairobi)  
**System Context:** PHP (7.4+ compatible), MySQL 8+, Single codebase, Router DB → Tenant DBs (one DB per school)

---

## 1) Scope & Objectives
This document specifies the Student lifecycle from first touch (Applications/Expressions of Interest) through interviews, entrance exams, communications, admission, fee onboarding, transport opting, and final enrolment. It defines states, workflows, UI screens, data contracts, validations, RBAC, integrations (SMS/Email/Calendar), and audit trails for a multi‑tenant SIMS.

**Goals**
- Provide a consistent flow from "Applicant" to "Active Student" with full auditability.
- Support one‑to‑many guardians, transport selection, fee onboarding (entry/admission fees), medical and consent forms, documents.
- Automate reminders (SMS/Email) for interviews/exams/fees; integrate with calendaring for interview slots.
- Be performant for schools with thousands of students.

---

## 2) Lifecycle Overview (State Machine)
States (canonical):
1. **Applicant:Draft** – Prospect created (by school or self‑service form). Incomplete biodata.
2. **Applicant:Submitted** – Application submitted; awaiting screening.
3. **Applicant:Screening** – Eligibility check; may request documents.
4. **Applicant:Interview Scheduled** – Interview slot booked (date/time/venue or virtual link).
5. **Applicant:Interviewed** – Interview held; outcomes recorded.
6. **Applicant:Entrance Exam Scheduled** – Exam slot/date booked (optional—some schools skip).
7. **Applicant:Exam Taken** – Scores recorded.
8. **Applicant:Decision:Accepted** – Offer issued (conditional or unconditional).
9. **Applicant:Decision:Waitlisted** – Awaiting space/outcome.
10. **Applicant:Decision:Rejected** – Not advancing (store rationale).
11. **Pre‑Admission** – Accepted applicant in onboarding (document upload, guardian capture, fee deposit, transport opt‑in).
12. **Admitted (Student:Pending Enrolment)** – Student record created; awaiting class/stream allocation.
13. **Active Student (Enrolled)** – Fully enrolled, timetable/fees/portals enabled.
14. **Suspended** – Temporarily inactive; retain history.
15. **Transferred Out / Withdrawn** – Closed; archive retained.
16. **Alumni** – Graduated; limited access.

**State Transitions**
- Draft → Submitted (on application finalization)
- Submitted → Screening → (Interview Scheduled | Rejected)
- Screening → Interview Scheduled → Interviewed → Decision
- Decision:Accepted → Pre‑Admission → Admitted → Active
- Active → (Suspended | Transferred | Alumni)

---

## 3) Key Entities & Relationships (High‑Level)
- **Applicants** (prospects) ↔ **Applications** (one latest active application per intake/year).
- **Applicant ↔ Guardians (prospective)**: many‑to‑many (primary flag).
- **Applicant Interviews** (schedule, panel, outcome) and **Entrance Exams** (slot, score, result).
- **Communications**: SMS/Email queue with templates and delivery status.
- **Admissions**: offer metadata (conditional notes, expiry), checklist completion.
- **Students**: created from accepted Applicant (new ID/admission number), with carry‑forward biodata & docs.
- **Guardians (live)**: one‑to‑many per Student; roles (mother/father/guardian), contact priority; portal access flags.
- **Transport**: route/stop selection; effective dates; billing linkage.
- **Finance Onboarding**: admission fee invoice, optional entry/registration fee, payment plan (if supported), receipt & allocation.
- **Documents**: birth cert, transfer letter, medical form, consent forms, passport photos.
- **Calendar**: interview/exam slots stored as events; collisions prevented; reminders issued.

---

## 4) Data Model (Indicative — not final DDL)
**Applicants & Intake**
- applicants(id, intake_year_id, first_name, middle_name, last_name, dob, gender, nationality, prior_school, grade_applying_for_id, status, created_by, created_at)
- applicant_contacts(id, applicant_id, phone, email, address, city, country, is_primary)
- applicant_documents(id, applicant_id, doc_type, file_path, uploaded_by, uploaded_at)
- applicant_guardians(id, applicant_id, guardian_temp_id, relation, is_primary)
- applicant_interviews(id, applicant_id, scheduled_at, duration_min, location, panel_json, status, outcome, notes, created_at)
- applicant_exams(id, applicant_id, scheduled_at, exam_center, paper_code, status, score, grade, notes)
- applicant_decisions(id, applicant_id, decision, decision_at, decided_by, conditions, offer_expiry_at)
- applicant_audit(id, applicant_id, action, payload_json, user_id, ip, at)

**Guardians (Live)**
- guardians(id, first_name, last_name, phone, email, alt_phone, address, city, country, id_number, portal_enabled, is_active, created_at)
- student_guardians(id, student_id, guardian_id, relation, is_primary, receive_billing_sms, receive_academic_sms)

**Students**
- students(id, admission_no, upi_code, first_name, middle_name, last_name, dob, gender, nationality, status, admission_date, exit_date, exit_reason, created_at)
- student_profiles(id, student_id, medical_notes, allergies, blood_group, special_needs, photo_path)
- student_class_allocations(id, student_id, academic_year_id, term_id, class_id, stream_id, start_date, end_date, status)
- student_documents(id, student_id, doc_type, file_path, uploaded_at)
- student_flags(id, student_id, transport_opt_in, lunch_opt_in, boarding_flag, scholarship_flag)

**Transport**
- transport_routes(id, name, description, active)
- transport_stops(id, route_id, stop_name, sequence)
- transport_assignments(id, student_id, route_id, stop_id, effective_from, effective_to, status)

**Finance Onboarding**
- invoices(id, student_id, invoice_no, invoice_date, due_date, total, currency_id, status)
- invoice_lines(id, invoice_id, fee_component_id, description, qty, unit_price, amount)
- receipts(id, student_id, amount, method_id, reference, received_at, posted_by)
- allocations(id, receipt_id, invoice_id, amount)
- adjustments(id, student_id, type, amount, reason, approved_by, approved_at)

**Calendar / Scheduling**
- calendar_events(id, tenant_scope, module, entity_id, title, starts_at, ends_at, location, organizer_id, attendees_json, reminder_minutes, status)

**Communications**
- messages_sms(id, to_phone, template_code, body, context_json, scheduled_at, sent_at, status, cost)
- messages_email(id, to_email, subject, body_html, context_json, scheduled_at, sent_at, status)

**Indexes (non‑exhaustive)**
- applicants(intake_year_id, grade_applying_for_id, status), applicant_interviews(applicant_id, scheduled_at), applicant_exams(applicant_id, scheduled_at)
- students(admission_no UNIQUE), student_class_allocations(student_id, academic_year_id, term_id)
- transport_assignments(student_id, effective_from)
- invoices(student_id, invoice_date), receipts(student_id, received_at)

---

## 5) Workflows
### 5.1 Application Capture
**Entry points:**
- Public application form with unique secure link (per intake/campaign).
- Internal capture by admissions staff (phone, walk‑in, event).

**Steps:**
1. Create Applicant (Draft) with basic biodata and contact (phone/email).
2. Upload/collect preliminary documents (optional).
3. Applicant or staff completes required fields → Submit.
4. Auto‑acknowledgement via SMS/Email with application reference.

**Validations:** required biodata; grade applying for; contact phone/email format; document size/type.
**RBAC:** Admissions:write to create/submit; Public form bypass RBAC but throttled and CSRF‑protected.

### 5.2 Screening & Shortlisting
- Staff view queue (Submitted). Filters by grade/intake.
- Check prerequisites (age bands, prior grades, docs). Record screening notes.
- Decision: Invite to Interview or Reject or Waitlist.
- Auto‑SMS/Email with next steps.

### 5.3 Interview Scheduling (Calendar Integration)
- Calendar shows available slots (capacity per slot; avoid collisions).
- Book slot → create calendar_events row; add applicant as attendee.
- Send confirmation and reminders (e.g., T‑48h, T‑24h, T‑2h) via SMS.
- Reschedule allowed with audit log; previous slot freed.
- After interview: record outcome (score bands, rubric, comments) and panel.

**Reminder SMS templates (examples):**
- INT_CONFIRM: "Hello {guardian_name}, {student_name}'s interview is on {date} at {time} at {location}. Reply 1 to confirm, 2 to reschedule."
- INT_REM_24: "Reminder: Interview for {student_name} is tomorrow {time}. Bring {required_docs}."
- INT_REM_2H: "Final reminder: Interview at {time} today. See you."

### 5.4 Entrance Exam (Optional)
- Schedule exam slot similar to interview; enforce capacity.
- Print/assign candidate number.
- Record paper(s), raw scores, compute grade bound.
- Decision rules (configurable): e.g., Interview >= B and Exam >= 60% ⇒ Accept; else Waitlist/Reject.
- Notify outcome via template.

### 5.5 Offer & Pre‑Admission Checklist
- Generate Offer Letter with conditions and expiry date.
- Pre‑admission checklist items (configurable):
  - Guardian(s) capture (at least one primary with phone/email).
  - Student documents upload (birth cert, photo, medical form, transfer letter).
  - Medical & consent forms completion.
  - Transport opt‑in (select route/stop effective from date).
  - Entry/Admission fee invoice (auto‑generated).
  - Payment plan setup (if supported) or full payment before admission.
- Portal for guardians to complete checklist (secure link) + auto reminders.

**Payment Integration (Onboarding):**
- Create invoice: components e.g., Admission Fee, ID Card, Caution Fee.
- Payment posting (receipt) and allocation against the onboarding invoice.
- If balance outstanding past offer expiry → auto reminder/escalation → optional lapse/withdraw.

### 5.6 Admission & Student Record Creation
- When checklist satisfied (or override by Admin):
  1. Create Student record; assign Admission Number (unique).
  2. Migrate/Link Guardians (promote from prospective to live; de‑dup by phone/email/ID number).
  3. Copy documents to student_documents and bind to student id.
  4. Set initial class/stream for the academic year/term.
  5. Set student_flags (transport_opt_in, lunch, boarding, scholarship).
  6. Enable Guardian Portal (optional) and send welcome credentials/reset link.
- Audit log with mapping applicant_id → student_id.

### 5.7 Post‑Admission Enrolment
- Generate first term invoices (based on fee tariffs for Year/Term/Class + optional items).
- Allocate timetable; mark availability in attendance rosters.
- Add to communication cohorts (e.g., class broadcasts).
- If transport opted: create transport_assignments.

### 5.8 Lifecycle Changes (Active → Suspended/Transferred/Alumni)
- Suspend: set status, specify from–to dates and reason; disable portal temporarily; billing policy configurable.
- Transfer Out: generate clearance letter; settle balances; mark exit; archive documents.
- Alumni: graduation processing; final reports archived; portal downgraded.

---

## 6) UI/UX — Screens & Components
### 6.1 Applicants
- Applicants List: search/filter (grade, status); columns with tbl_sh header and tbl_data cells.
- Applicant Profile: tabs: Overview, Guardians (prospective), Documents, Screening, Interview, Exam, Decision, Activity Log.
- Interview Calendar: monthly/weekly/day view; slot capacity; drag‑drop reschedule; conflict warnings.
- Bulk Actions: invite to interview, schedule exams, send batch reminders.

### 6.2 Pre‑Admission Portal (Guardian)
- Secure link; checklist progress; upload docs (drag/drop), transport selection with route/stop maps; pay onboarding invoice; download offer letter.
- Responsive; SMS/email OTP for link re‑access.

### 6.3 Admission & Student Profile
- Admission Wizard: confirm biodata → guardians mapping/de‑dup → documents → class/stream → flags → finish.
- Student Profile: tabs: Overview, Guardians, Class/Stream, Medical, Transport, Documents, Fees, Communications, Audit.
- Transport Picker: route → stop with capacity info; effective from date; cost preview.

### 6.4 Communications
- Template library with placeholders; test preview; schedule send; delivery dashboard.
- Conversation log under Applicant/Student (threaded with replies/notes).

---

## 7) RBAC Matrix (Examples)
- Admissions Officer: Applicants(view, write), Interviews(view, write), Exams(view, write), Decisions(write), Students(view), Finance(view onboarding invoices)
- Bursar: Finance(view, write receipts), Onboarding invoice(view, write), Adjustments(approve), Students(view)
- Class Teacher: Students(view limited), Communications(view to class guardians)
- Transport Officer: Transport(view, write assignments), Students(view)
- Admin: All modules (view/write/approve)

Enforcement:
- Sidebar shows only viewable submodules; write actions (buttons) hidden if not granted.
- API endpoints verify tenant + role + permission.

---

## 8) Validations & Business Rules
- Age vs grade bands (configurable table).
- Unique constraints: admission_no, guardian phone+email combo (per tenant), one primary guardian per student.
- Offer expiry cannot be in the past; reminders scheduled before expiry.
- Transport route capacity not exceeded; effective dates non‑overlapping.
- Medical/doc files: size/type whitelist; virus scan hook (optional).
- Payment allocations must not exceed invoice balance.
- State transitions restricted (cannot admit unless checklist passed or override by Admin with reason).

---

## 9) Automation & Schedulers
- Interview reminders: T‑48h/T‑24h/T‑2h cron jobs querying calendar_events and messages_sms.
- Offer expiry reminders: T‑7/T‑3/T‑1 days; escalate to admissions mailbox.
- Onboarding invoice reminders: at issue + 7 days + 3 days before due; stop once paid.
- Data hygiene jobs: de‑dup guardians, missing documents report, orphaned uploads cleanup (temp area).

---

## 10) API/Endpoints (Internal JSON/AJAX contracts)
(Paths indicative; all subject to tenant auth + CSRF)

### Applications
- POST /api/applicants/create → { first_name, last_name, dob, grade_applied_for_id, contacts[], docs[] }
- POST /api/applicants/{id}/submit → transitions Draft→Submitted
- POST /api/applicants/{id}/decision → { decision: Accepted|Waitlisted|Rejected, conditions, expiry_at }

### Interviews & Exams
- POST /api/applicants/{id}/interview/schedule → { starts_at, duration_min, location, panel_ids[] }
- POST /api/applicants/{id}/interview/outcome → { outcome, notes, rubric_scores[] }
- POST /api/applicants/{id}/exam/schedule → { starts_at, paper_code, center }
- POST /api/applicants/{id}/exam/score → { score, grade, notes }

### Pre‑Admission & Admission
- POST /api/applicants/{id}/offer → generates offer + onboarding invoice
- POST /api/applicants/{id}/admit → creates student, guardians link, class/stream
- POST /api/students/{id}/class-assign → { academic_year_id, term_id, class_id, stream_id }
- POST /api/students/{id}/transport/assign → { route_id, stop_id, effective_from }

### Communications
- POST /api/messages/sms/send → { to, template_code, context_json, schedule_at }
- GET /api/messages/sms/status?id= → delivery report

Response patterns: success: true, data: {}, errors: []

---

## 11) Sample Templates
Offer Issued (Email)
Subject: Offer of Admission — {student_name}
Body: Dear {guardian_name}, we are pleased to offer {student_name} a place in Grade {grade}. Please complete the checklist by {offer_expiry}. Link: {portal_link}

Onboarding Invoice (SMS)
"{student_name}'s admission fee invoice of {amount} is due on {due_date}. Pay via {pay_channel}. Ref: {invoice_no}."

Exam Outcome (SMS)
"{student_name}'s entrance exam result: {grade}. Next steps: {link}."

---

## 12) Audit & Compliance
- audit_logs on all mutations with before/after snapshot hash; user id; IP; URL; result.
- Exportable applicant and student records with change history.
- Consent logging for portal access, communications preferences.
- PII handling: field‑level access (e.g., medical notes hidden from non‑authorized roles).

---

## 13) Error Handling & Edge Cases
- Double bookings on interview/exam slots → hard check on save; suggest nearest alternatives.
- Applicant already exists (duplicate phone/email) → merge helper.
- Payment received with no onboarding invoice → create receipt as unallocated; queue for reconciliation.
- Transport stop changes mid‑term → proration rules; notification to guardians.
- Admission reversal (rare) → rollback helper (soft‑delete student; reinstate applicant with note).

---

## 14) Performance Considerations
- Index lookups on applicant status, scheduled_at ranges, student_id foreign keys.
- Pagination + server‑side filters on big grids.
- Async jobs for bulk SMS/emails and large invoice generations.
- Caching of static lookups (grades, routes, fee components).

---

## 15) Acceptance Criteria (UAT)
- Create Applicant → schedule interview/exam → record outcomes → issue offer → complete checklist → admit → enrol → generate first term invoice.
- SMS reminders fire at expected times; statuses visible.
- Guardians linked (1..n), primary designated, portal enabled; communications respect preferences.
- Transport assignment reflects in Finance optional line items.
- Full audit trail present for each step.
- RBAC hides actions appropriately.

---

## 16) Implementation Notes (PHP/MySQL)
- PHP 7.4 OOP (no PHP 8‑only features); mysqli OO `$connection->` style; prepared statements.
- Tenant resolution via router DB then instantiate tenant DB connection per request.
- Controllers thin → Services (business rules) → Repositories (SQL) → Models (DTOs).
- Uploads outside public root; streamed downloads with RBAC check; log downloads.
- Use shared UI classes: tbl_sh for headers, tbl_data for cells; responsive, fluid pages.

---

## 17) Roadmap Add‑Ons
- Self‑service booking for interviews via portal (limited slots exposure).
- Video interview links (Meet/Zoom) auto‑creation.
- Payment plan engine with dunning calendar.
- Digital signature for offer acceptance and policy forms.
- MIS dashboards (conversion rates, time‑to‑admit, exam performance heatmaps).

---

End of Students Module Spec
