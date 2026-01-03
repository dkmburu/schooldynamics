# Students Module - Implementation Plan

## 📋 Overview
Based on the Students_Module_Spec.md, we'll implement the full lifecycle:
**Applicants → Interview/Exam → Admission → Students → Enrollment**

## 🎯 Implementation Phases

### **Phase 1: Applicants Management (Foundation)**
*Estimated: 2-3 days*

#### Phase 1A: Database Schema
- [ ] Create applicants table
- [ ] Create applicant_contacts table
- [ ] Create applicant_documents table
- [ ] Create applicant_guardians (prospective) table
- [ ] Create applicant_audit table
- [ ] Add sample data for testing

#### Phase 1B: Applicants List & Search
- [ ] Controller: `ApplicantsController.php`
- [ ] View: Applicants list with filters
- [ ] Features:
  - Pagination (50 per page)
  - Search by name, phone, email
  - Filter by status, grade, intake year
  - Bulk actions (export, send reminders)
  - Status badges (Draft, Submitted, Screening, etc.)

#### Phase 1C: Applicant Profile
- [ ] View: Applicant detail page with tabs:
  - Overview (biodata)
  - Guardians (prospective)
  - Documents
  - Activity Log
- [ ] Edit applicant details
- [ ] Upload documents
- [ ] Add/edit guardians

#### Phase 1D: Application Capture
- [ ] Create new applicant form
- [ ] Validations (age vs grade, required fields)
- [ ] Auto-generate application reference number
- [ ] Submit application (Draft → Submitted)
- [ ] Auto-acknowledgement (placeholder for SMS/Email)

---

### **Phase 2: Students Management (Core)**
*Estimated: 2-3 days*

#### Phase 2A: Students CRUD
- [ ] Expand students table (already exists)
- [ ] Create student_profiles table (medical, photo)
- [ ] Create student_flags table (transport, lunch, boarding)
- [ ] Controller: `StudentsController.php`
- [ ] Views:
  - Students list (with search & filters)
  - Add student (manual entry)
  - Edit student
  - Student profile overview

#### Phase 2B: Guardians Management
- [ ] Expand guardians table (already exists)
- [ ] Create student_guardians link (already exists)
- [ ] Guardian de-duplication logic (by phone/email)
- [ ] Add/edit guardians for student
- [ ] Primary guardian designation
- [ ] Contact preferences (billing, academic SMS)

#### Phase 2C: Student Profile Tabs
- [ ] Tab: Overview (biodata, photo, status)
- [ ] Tab: Guardians (list with primary flag)
- [ ] Tab: Class/Stream assignments
- [ ] Tab: Documents (upload/download)
- [ ] Tab: Medical info (allergies, blood group)
- [ ] Tab: Activity/Audit log

---

### **Phase 3: Application Processing**
*Estimated: 3-4 days*

#### Phase 3A: Screening & Interviews
- [ ] Create applicant_interviews table
- [ ] Screening workflow (notes, eligibility)
- [ ] Interview scheduling form
- [ ] Calendar integration (basic)
- [ ] Interview outcome capture
- [ ] Status transitions (Screening → Interview Scheduled → Interviewed)

#### Phase 3B: Entrance Exams
- [ ] Create applicant_exams table
- [ ] Exam scheduling
- [ ] Score entry
- [ ] Grade calculation
- [ ] Results recording

#### Phase 3C: Decision Making
- [ ] Create applicant_decisions table
- [ ] Decision form (Accept/Waitlist/Reject)
- [ ] Conditional acceptance (notes, expiry)
- [ ] Status transitions to Decision states

---

### **Phase 4: Admission Workflow**
*Estimated: 2-3 days*

#### Phase 4A: Offer & Pre-Admission
- [ ] Generate offer (placeholder for PDF)
- [ ] Pre-admission checklist:
  - Guardian capture ✓
  - Documents upload ✓
  - Medical form ✓
  - Transport selection (later phase)
  - Fee invoice (later phase)
- [ ] Checklist progress tracking

#### Phase 4B: Admission Conversion
- [ ] Admit button/workflow
- [ ] Create Student from Applicant
- [ ] Generate admission number (unique)
- [ ] Migrate guardians (prospective → live)
- [ ] Copy documents
- [ ] Set initial class/stream
- [ ] Audit trail (applicant_id → student_id)

---

### **Phase 5: Class & Stream Management**
*Estimated: 1-2 days*

- [ ] Create student_class_allocations table
- [ ] Assign student to class/stream
- [ ] Academic year/term context
- [ ] Class roster view
- [ ] Promote/transfer between classes

---

### **Phase 6: Transport Integration**
*Estimated: 1-2 days*

- [ ] Create transport_routes table
- [ ] Create transport_stops table
- [ ] Create transport_assignments table
- [ ] Route & stop management UI
- [ ] Assign student to route/stop
- [ ] Transport opt-in flag
- [ ] Effective dates

---

### **Phase 7: Communications (Basic)**
*Estimated: 2 days*

- [ ] Create messages_sms table
- [ ] Create messages_email table
- [ ] Template management
- [ ] Manual send SMS/Email
- [ ] Placeholder for SMS gateway integration
- [ ] Delivery status tracking

---

### **Phase 8: Documents & Uploads**
*Estimated: 1 day*

- [ ] Document upload handler
- [ ] File type/size validation
- [ ] Secure file storage (outside public)
- [ ] Download with RBAC check
- [ ] Document viewer

---

### **Phase 9: Advanced Features** *(Future)*

- [ ] Interview calendar (drag-drop, capacity)
- [ ] SMS reminders automation
- [ ] Payment integration
- [ ] Public application form (self-service)
- [ ] Guardian portal
- [ ] Digital offer letters (PDF)
- [ ] Bulk operations

---

## 🚀 Recommended Starting Point

### **Start with Phase 1: Applicants** ✅

**Why?**
1. Foundation for the entire lifecycle
2. Independent - doesn't require other modules
3. Can be tested immediately
4. Builds momentum

**What we'll build first:**

1. **Database tables** (30 mins)
   - applicants
   - applicant_contacts
   - applicant_documents
   - applicant_guardians
   - applicant_audit

2. **Applicants List Page** (2-3 hours)
   - Table with search & filters
   - Status badges
   - Pagination
   - "Add New" button

3. **Add Applicant Form** (2-3 hours)
   - Biodata form (name, DOB, gender, grade)
   - Contact info (phone, email)
   - Grade applying for dropdown
   - Save as Draft / Submit

4. **Applicant Profile Page** (3-4 hours)
   - View applicant details
   - Edit button
   - Tabs: Overview, Guardians, Documents, Log
   - Status display
   - Actions (Approve, Reject, Schedule Interview)

**Total for Phase 1A-1D: ~1-2 days**

---

## 📦 Deliverables Per Phase

### Phase 1 Deliverables:
- ✅ 5 database tables created
- ✅ Applicants list page (with filters)
- ✅ Add/Edit applicant form
- ✅ Applicant profile page (with tabs)
- ✅ Status workflow (Draft → Submitted → Screening)
- ✅ Sample data for testing

### Phase 2 Deliverables:
- ✅ Students CRUD (complete)
- ✅ Student profile with 6 tabs
- ✅ Guardians management
- ✅ Document upload/download
- ✅ Medical info management

---

## 🎨 UI Components Needed

### Reusable Components:
1. **Status Badge** - Color-coded status display
2. **Document Uploader** - Drag-drop file upload
3. **Guardian Picker** - Select/add guardians
4. **Class/Stream Picker** - Dropdown with search
5. **Date Picker** - Calendar widget
6. **Tab Component** - Consistent tab layout
7. **Action Buttons** - Approve, Reject, Edit, Delete
8. **Search Bar** - Global search with filters
9. **Pagination** - Consistent across tables

---

## 🔐 RBAC Mapping

| Role | Applicants | Students | Guardians | Documents | Admission |
|------|-----------|----------|-----------|-----------|-----------|
| ADMIN | Full | Full | Full | Full | Full |
| HEAD_TEACHER | View, Edit | Full | Full | View | Approve |
| ADMISSIONS_OFFICER | Full | View | Edit | Edit | Submit |
| BURSAR | View | View | View | View | - |
| TEACHER | - | View | View | - | - |
| CLERK | View, Add | Add, Edit | Add, Edit | Upload | - |

---

## 📊 Success Metrics

After Phase 1:
- ✅ Can create and manage applicants
- ✅ Can track application status
- ✅ Can view applicant details
- ✅ Can filter and search applicants

After Phase 2:
- ✅ Can manage active students
- ✅ Can link guardians to students
- ✅ Can upload/download documents
- ✅ Can view student profiles

After Phase 4:
- ✅ Complete lifecycle: Applicant → Student
- ✅ Admission workflow functional
- ✅ Data audit trail working

---

## 🎯 Next Steps

**Option A: Start with Phase 1A (Database)**
- Create migration script for applicants tables
- Add sample/seed data
- Test with some demo applicants

**Option B: Start with Phase 1B (UI First)**
- Build applicants list page (with dummy data)
- Get UI approved
- Then build backend

**Recommendation: Option A** - Build foundation first, then UI on top.

---

## 📝 Questions to Clarify

1. **Intake Years**: Create intake_years table or use academic_years?
2. **Grade Structure**: Need grades/levels table? (Grade 1-12, PP1, PP2, etc.)
3. **Application Fee**: Charge for application? Integration with Finance?
4. **SMS Gateway**: Which provider? (Africa's Talking, Twilio, etc.)
5. **Document Storage**: Max file size? Allowed types?
6. **Auto-numbering**: Format for admission numbers? (e.g., 2025/001)

---

**Ready to start? Let's begin with Phase 1A: Database tables!** 🚀
