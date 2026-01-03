# Academics Module – Navigation Hierarchy (CBC Aligned)

This document describes the **navigation hierarchy** for the **Academics** main menu, aligned to **KICD CBC** requirements.
The ### represents the submenu under Academics while the - represents the Tab Navigation

---

## Academics

### 1. Curriculum
- Curriculum Frameworks
- Learning Areas
- Strands & Sub-Strands
- Competencies & Values
- Learning Outcomes

---

### 2. Classes & Timetables
- Grades & Classes
- Subject Mapping
- Timetable
- Teacher Allocation
- Rooms & Resources

---

### 3. Lesson Planning
- Schemes of Work
- Lesson Plans
- Learning Activities
- Teaching Resources
- Review & Approval

---

### 4. Assessments
- Assessment Types
- Assessment Setup
- Rubrics
- Assessment Entry
- Moderation

---

### 5. Learner Portfolios
- Portfolio Overview
- Artifacts
- Teacher Feedback
- Learner Reflections
- Parent Access

---

### 6. Results & Reports
- Competency Reports
- Term Reports
- Progress Reports
- Promotion & Transition
- Exports

---

### 7. Academic Records
- Learner History
- Competency Growth
- Transitions
- Archived Reports

---

### 8. Homework & Assignments
- Assignments
- Submissions
- Feedback
- Parent View

---

### 9. Attendance & Engagement
- Class Attendance
- Lesson Attendance
- Engagement Indicators
- Absence Reasons

---

### 10. Learning Resources
- Digital Resources
- Teacher Uploads
- CBC Exemplars
- Version History

---

### 11. Academic Analytics
- Learner Profiles
- Competency Gaps
- Class Comparisons
- Teacher Insights
- Early Alerts

---

### 12. Academic Settings
- Policies
- Assessment Rules
- Rubric Thresholds
- Promotion Rules
- Result Locking
- Roles & Permissions

---


# Academics Module – Navigation & UI Behavior (CBC Aligned)

This document describes the **Academics navigation hierarchy**, and explains **how each tab should work**, **what the UI must contain**, and **who the primary users are**.

---

## Academics

---

## 1. Curriculum

### Curriculum Frameworks
The UI should present curriculum frameworks (e.g. CBC Lower Primary, Upper Primary, JSS) as selectable cards or rows, showing status (Active, Draft, Archived). Users should be able to view details but edits should be tightly controlled. Version history should be visible.
**Key UI Elements:** framework list, status badge, version info, view-only toggle, archive action.  
**Ideal Users:** System Admin, Academic Director.

---

### Learning Areas
This screen should display learning areas in a structured list, filtered by curriculum and grade band. Selecting a learning area opens its mapped grades and competencies.
**Key UI Elements:** searchable list, grade filters, mapping panel, edit/add controls.  
**Ideal Users:** Curriculum Admin, HOD.

---

### Strands & Sub-Strands
The UI should show a hierarchical tree view (Learning Area → Strand → Sub-Strand). Once assessments exist, items become locked and visually marked.
**Key UI Elements:** expandable tree, lock indicators, add/edit buttons (pre-lock), audit info.  
**Ideal Users:** Curriculum Admin, HOD.

---

### Competencies & Values
This view should present competencies and values as tag-like entities that can be mapped to learning areas and outcomes.
**Key UI Elements:** competency list, tagging UI, mapping drawer, descriptions.  
**Ideal Users:** Curriculum Admin, Senior Teachers.

---

### Learning Outcomes
Learning outcomes should be listed per sub-strand, with references to competencies. Editing should be restricted after instructional use.
**Key UI Elements:** outcome table, competency mapping, usage indicators.  
**Ideal Users:** Curriculum Admin, HOD.

---

## 2. Classes & Timetables

### Grades & Classes
This screen should manage grade levels and streams, showing enrollment counts and assigned teachers.
**Key UI Elements:** class list, student counts, assign teacher action, filters.  
**Ideal Users:** Academic Admin, Registrar.

---

### Subject Mapping
The UI should allow mapping learning areas to classes with multi-teacher support.
**Key UI Elements:** mapping grid, teacher selector, save validation.  
**Ideal Users:** Academic Admin, HOD.

---

### Timetable
A visual timetable builder with drag-and-drop periods, showing conflicts in real time.
**Key UI Elements:** timetable grid, drag blocks, conflict warnings, save/publish.  
**Ideal Users:** Timetable Officer, Academic Admin.

---

### Teacher Allocation
This view focuses on workload, showing subjects, classes, and periods per teacher.
**Key UI Elements:** teacher list, workload summary, assignment panel.  
**Ideal Users:** Academic Admin, HOD.

---

### Rooms & Resources
Rooms and special learning spaces are managed here, linked to timetables.
**Key UI Elements:** room list, capacity info, availability indicator.  
**Ideal Users:** Academic Admin.

---

## 3. Lesson Planning

### Schemes of Work
Schemes should be listed per term and class, with progress indicators.
**Key UI Elements:** scheme list, curriculum references, progress bar.  
**Ideal Users:** Teachers, HOD.

---

### Lesson Plans
Lesson plans should open in a structured form tied to CBC requirements.
**Key UI Elements:** lesson editor, outcome references, save drafts.  
**Ideal Users:** Teachers.

---

### Learning Activities
This tab focuses on learner activities linked to outcomes.
**Key UI Elements:** activity list, outcome tags, experiential markers.  
**Ideal Users:** Teachers.

---

### Teaching Resources
Resources should be attachable to lessons and searchable.
**Key UI Elements:** resource picker, preview, attach/detach controls.  
**Ideal Users:** Teachers.

---

### Review & Approval
The UI should allow HODs to review, comment, approve or request revisions.
**Key UI Elements:** approval status, comments panel, approve/reject actions.  
**Ideal Users:** HOD, Senior Teachers.

---

## 4. Assessments

### Assessment Types
A configuration-style screen listing CBC assessment methods.
**Key UI Elements:** type list, enable/disable toggles.  
**Ideal Users:** Academic Admin.

---

### Assessment Setup
Teachers select class, learners, outcomes and competencies here.
**Key UI Elements:** class selector, learner list, mapping summary.  
**Ideal Users:** Teachers.

---

### Rubrics
Rubrics should be created visually with criteria and performance levels.
**Key UI Elements:** rubric builder, level descriptors, preview.  
**Ideal Users:** Teachers, HOD.

---

### Assessment Entry
The UI should allow rubric-based scoring with narrative feedback.
**Key UI Elements:** learner list, rubric scoring panel, comment box.  
**Ideal Users:** Teachers.

---

### Moderation
This screen supports review of assessments across classes.
**Key UI Elements:** comparison views, moderation notes, lock action.  
**Ideal Users:** HOD, Academic Director.

---

## 5. Learner Portfolios

### Portfolio Overview
A dashboard-like summary of a learner’s evidence and competencies.
**Key UI Elements:** competency summary, artifact counts, timeline.  
**Ideal Users:** Teachers, School Leadership.

---

### Artifacts
Artifacts should be uploaded, previewed and tagged.
**Key UI Elements:** upload control, preview panel, tagging UI.  
**Ideal Users:** Teachers, Learners.

---

### Teacher Feedback
Narrative feedback linked to artifacts and outcomes.
**Key UI Elements:** feedback editor, timestamp, author info.  
**Ideal Users:** Teachers.

---

### Learner Reflections
A simplified input interface for learner self-reflection.
**Key UI Elements:** reflection text area, save history.  
**Ideal Users:** Learners.

---

### Parent Access
Read-only portfolio view with visibility controls.
**Key UI Elements:** access toggles, preview mode.  
**Ideal Users:** Parents, Guardians.

---

## 6. Results & Reports

### Competency Reports
Visual summaries of competency mastery.
**Key UI Elements:** charts, level indicators, narrative blocks.  
**Ideal Users:** Teachers, Parents, School Leadership.

---

### Term Reports
Formal CBC report format with narratives.
**Key UI Elements:** report preview, download/print.  
**Ideal Users:** Teachers, Parents.

---

### Progress Reports
Mid-term snapshots for intervention.
**Key UI Elements:** progress indicators, alerts.  
**Ideal Users:** Teachers, HOD.

---

### Promotion & Transition
Decision-support view based on competency evidence.
**Key UI Elements:** recommendation flags, decision log.  
**Ideal Users:** Academic Committee.

---

### Exports
Standardized exports for regulators.
**Key UI Elements:** export buttons, format selector.  
**Ideal Users:** Academic Admin.

---

## 7. Academic Records

### Learner History
Chronological academic record view.
**Key UI Elements:** timeline, year filters.  
**Ideal Users:** Academic Admin.

---

### Competency Growth
Graphical growth tracking.
**Key UI Elements:** line charts, comparison toggles.  
**Ideal Users:** Teachers, School Leadership.

---

### Transitions
Transfer and promotion records.
**Key UI Elements:** transition logs, approvals.  
**Ideal Users:** Academic Admin.

---

### Archived Reports
Read-only report archive.
**Key UI Elements:** archive list, view/download.  
**Ideal Users:** Academic Admin.

---

## 8. Homework & Assignments

### Assignments
Assignment creation interface.
**Key UI Elements:** assignment form, due dates.  
**Ideal Users:** Teachers.

---

### Submissions
Submission tracking per learner.
**Key UI Elements:** submission list, timestamps.  
**Ideal Users:** Teachers.

---

### Feedback
Feedback linked to submissions.
**Key UI Elements:** comment box, attachments.  
**Ideal Users:** Teachers.

---

### Parent View
Read-only assignment progress view.
**Key UI Elements:** status indicators.  
**Ideal Users:** Parents.

---

## 9. Attendance & Engagement

### Class Attendance
Daily attendance capture and should be mobile friendly
**Key UI Elements:** class list, present/absent/ absent with permission toggles.  
Should allow techer to also using emojis and notes take note of any unusual behaviour of a child eg: sleepy, or bruised, or unruly etc.
**Ideal Users:** Teachers.

---

### Lesson Attendance
Attendance per lesson.
**Key UI Elements:** lesson selector, learner list.  
**Ideal Users:** Teachers.

---

### Engagement Indicators
Participation-focused indicators.
**Key UI Elements:** engagement flags, notes.  
**Ideal Users:** Teachers, HOD.

---

### Absence Reasons
Reason capture and review.
**Key UI Elements:** reason dropdowns, notes.  
**Ideal Users:** Teachers, Admin.

---

## 10. Learning Resources

### Digital Resources
Central resource library.
**Key UI Elements:** search, preview, tags.  
**Ideal Users:** Teachers, Learners.

---

### Teacher Uploads
Teacher-contributed materials.
**Key UI Elements:** upload, ownership tags.  
**Ideal Users:** Teachers.

---

### CBC Exemplars
Approved examples aligned to CBC.
**Key UI Elements:** exemplar list, view-only.  
**Ideal Users:** Teachers.

---

### Version History
Resource version tracking.
**Key UI Elements:** version list, restore option.  
**Ideal Users:** Academic Admin.

---

## 11. Academic Analytics

### Learner Profiles
Holistic learner performance view.
**Key UI Elements:** charts, summaries.  
**Ideal Users:** Teachers, School Leadership.

---

### Competency Gaps
Gap identification dashboard.
**Key UI Elements:** alerts, filters.  
**Ideal Users:** Teachers, HOD.

---

### Class Comparisons
Comparative analytics.
**Key UI Elements:** comparison charts.  
**Ideal Users:** School Leadership.

---

### Teacher Insights
Instructional effectiveness indicators.
**Key UI Elements:** metrics, trends.  
**Ideal Users:** Academic Director.

---

### Early Alerts
Early warning signals.
**Key UI Elements:** alert list, severity tags.  
**Ideal Users:** Teachers, HOD.

---

## 12. Academic Settings

### Policies
Academic policy configuration.
**Key UI Elements:** policy editor, versioning.  
**Ideal Users:** System Admin.

---

### Assessment Rules
Rules governing assessments.
**Key UI Elements:** rule toggles.  
**Ideal Users:** Academic Admin.

---

### Rubric Thresholds
Performance level definitions.
**Key UI Elements:** threshold editor.  
**Ideal Users:** Academic Admin.

---

### Promotion Rules
CBC-aligned promotion criteria.
**Key UI Elements:** rule builder.  
**Ideal Users:** Academic Committee.

---

### Result Locking
Result finalization controls.
**Key UI Elements:** lock/unlock actions.  
**Ideal Users:** Academic Admin.

--