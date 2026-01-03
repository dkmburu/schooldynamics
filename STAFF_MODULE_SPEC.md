# Staff Module Specification

## Overview
Comprehensive staff management for teaching and non-teaching staff, including leave management, attendance, payroll integration, and performance tracking.

## Database Schema

### 1. Staff (Core)
```sql
CREATE TABLE `staff` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `staff_number` VARCHAR(50) NOT NULL UNIQUE COMMENT 'e.g., STF-2025-001',
    `user_id` BIGINT UNSIGNED COMMENT 'Link to users table for portal login',

    -- Personal Information
    `first_name` VARCHAR(100) NOT NULL,
    `middle_name` VARCHAR(100),
    `last_name` VARCHAR(100) NOT NULL,
    `date_of_birth` DATE,
    `gender` ENUM('male', 'female', 'other'),
    `nationality` VARCHAR(100) DEFAULT 'Kenya',
    `id_number` VARCHAR(50) UNIQUE COMMENT 'National ID',
    `passport_number` VARCHAR(50),

    -- Employment Information
    `staff_type` ENUM('teaching', 'non-teaching', 'admin', 'support') NOT NULL,
    `employment_type` ENUM('permanent', 'contract', 'part-time', 'casual', 'intern') NOT NULL,
    `job_title` VARCHAR(255) COMMENT 'Teacher, Principal, Accountant, etc.',
    `department` VARCHAR(100) COMMENT 'Mathematics, Admin, Sports, etc.',
    `tsc_number` VARCHAR(50) COMMENT 'TSC Registration Number (Kenya)',

    -- Dates
    `date_joined` DATE,
    `contract_start_date` DATE,
    `contract_end_date` DATE,
    `probation_end_date` DATE,
    `date_left` DATE,
    `termination_reason` TEXT,

    -- Status
    `status` ENUM('active', 'on_leave', 'suspended', 'terminated', 'retired', 'resigned') DEFAULT 'active',

    -- Salary & Banking
    `basic_salary` DECIMAL(12,2),
    `bank_name` VARCHAR(100),
    `bank_account_number` VARCHAR(50),
    `bank_branch` VARCHAR(100),
    `nssf_number` VARCHAR(50),
    `nhif_number` VARCHAR(50),
    `kra_pin` VARCHAR(50) COMMENT 'Tax PIN',

    -- Emergency Contact
    `emergency_contact_name` VARCHAR(255),
    `emergency_contact_phone` VARCHAR(20),
    `emergency_contact_relationship` VARCHAR(50),

    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_staff_number` (`staff_number`),
    INDEX `idx_status` (`status`),
    INDEX `idx_type` (`staff_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2. Staff Contacts
```sql
CREATE TABLE `staff_contacts` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `staff_id` BIGINT UNSIGNED NOT NULL,
    `phone` VARCHAR(20),
    `alt_phone` VARCHAR(20),
    `email` VARCHAR(255),
    `personal_email` VARCHAR(255),
    `address` TEXT,
    `city` VARCHAR(100),
    `county` VARCHAR(100),
    `country` VARCHAR(100) DEFAULT 'Kenya',
    `postal_code` VARCHAR(20),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`staff_id`) REFERENCES `staff`(`id`) ON DELETE CASCADE,
    INDEX `idx_staff` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3. Staff Campus Assignments
```sql
CREATE TABLE `staff_campus_assignments` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `staff_id` BIGINT UNSIGNED NOT NULL,
    `campus_id` INT UNSIGNED NOT NULL,
    `is_primary` TINYINT(1) DEFAULT 0 COMMENT 'Primary campus',
    `position` VARCHAR(255) COMMENT 'Role at this campus',
    `start_date` DATE NOT NULL,
    `end_date` DATE,
    `workload_percentage` INT DEFAULT 100 COMMENT 'Percentage of time at this campus',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`staff_id`) REFERENCES `staff`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`campus_id`) REFERENCES `campuses`(`id`) ON DELETE CASCADE,
    INDEX `idx_staff_campus` (`staff_id`, `campus_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4. Staff Subject Assignments (Teaching Staff)
```sql
CREATE TABLE `staff_subject_assignments` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `staff_id` BIGINT UNSIGNED NOT NULL,
    `subject_id` INT UNSIGNED NOT NULL,
    `class_id` INT UNSIGNED NOT NULL,
    `academic_year_id` INT UNSIGNED NOT NULL,
    `term_id` INT UNSIGNED,
    `is_class_teacher` TINYINT(1) DEFAULT 0 COMMENT 'Is this staff the class teacher',
    `start_date` DATE,
    `end_date` DATE,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`staff_id`) REFERENCES `staff`(`id`) ON DELETE CASCADE,
    INDEX `idx_staff_subject` (`staff_id`, `subject_id`),
    INDEX `idx_class` (`class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 5. Staff Qualifications
```sql
CREATE TABLE `staff_qualifications` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `staff_id` BIGINT UNSIGNED NOT NULL,
    `qualification_type` ENUM('certificate', 'diploma', 'degree', 'masters', 'phd', 'professional') NOT NULL,
    `qualification_name` VARCHAR(255) NOT NULL COMMENT 'B.Ed Mathematics',
    `institution` VARCHAR(255) NOT NULL,
    `year_obtained` YEAR,
    `certificate_number` VARCHAR(100),
    `verified` TINYINT(1) DEFAULT 0,
    `document_path` VARCHAR(500),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`staff_id`) REFERENCES `staff`(`id`) ON DELETE CASCADE,
    INDEX `idx_staff` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 6. Staff Documents
```sql
CREATE TABLE `staff_documents` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `staff_id` BIGINT UNSIGNED NOT NULL,
    `document_type` VARCHAR(100) NOT NULL COMMENT 'ID Copy, Certificate, Contract, etc.',
    `document_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `file_size` INT UNSIGNED,
    `mime_type` VARCHAR(100),
    `uploaded_by` BIGINT UNSIGNED,
    `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`staff_id`) REFERENCES `staff`(`id`) ON DELETE CASCADE,
    INDEX `idx_staff` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 7. Leave Types
```sql
CREATE TABLE `leave_types` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `leave_name` VARCHAR(100) NOT NULL COMMENT 'Annual Leave, Sick Leave, Maternity',
    `leave_code` VARCHAR(20) NOT NULL UNIQUE,
    `max_days_per_year` INT COMMENT 'Maximum days allowed per year',
    `is_paid` TINYINT(1) DEFAULT 1,
    `requires_approval` TINYINT(1) DEFAULT 1,
    `approval_chain` TEXT COMMENT 'JSON: [{role: HoD}, {role: Principal}]',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_code` (`leave_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 8. Leave Applications
```sql
CREATE TABLE `leave_applications` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `leave_ref` VARCHAR(50) NOT NULL UNIQUE COMMENT 'LV-2025-001',
    `staff_id` BIGINT UNSIGNED NOT NULL,
    `leave_type_id` INT UNSIGNED NOT NULL,

    -- Leave Details
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `days_requested` INT NOT NULL,
    `reason` TEXT NOT NULL,
    `contact_during_leave` VARCHAR(20),

    -- Approval Workflow
    `status` ENUM('draft', 'submitted', 'pending_hod', 'pending_principal', 'approved', 'rejected', 'cancelled') DEFAULT 'draft',
    `submitted_at` TIMESTAMP NULL,
    `hod_approved_by` BIGINT UNSIGNED,
    `hod_approved_at` TIMESTAMP NULL,
    `hod_comments` TEXT,
    `principal_approved_by` BIGINT UNSIGNED,
    `principal_approved_at` TIMESTAMP NULL,
    `principal_comments` TEXT,
    `rejection_reason` TEXT,

    -- Relief Teacher
    `relief_teacher_id` BIGINT UNSIGNED COMMENT 'Who will cover',
    `relief_notes` TEXT,

    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (`staff_id`) REFERENCES `staff`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types`(`id`),
    FOREIGN KEY (`relief_teacher_id`) REFERENCES `staff`(`id`) ON DELETE SET NULL,
    INDEX `idx_staff` (`staff_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_dates` (`start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 9. Leave Balances
```sql
CREATE TABLE `leave_balances` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `staff_id` BIGINT UNSIGNED NOT NULL,
    `leave_type_id` INT UNSIGNED NOT NULL,
    `academic_year_id` INT UNSIGNED NOT NULL,
    `days_allocated` INT NOT NULL,
    `days_taken` INT DEFAULT 0,
    `days_pending` INT DEFAULT 0,
    `days_remaining` INT GENERATED ALWAYS AS (`days_allocated` - `days_taken` - `days_pending`) STORED,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`staff_id`) REFERENCES `staff`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types`(`id`),
    UNIQUE KEY `unique_staff_leave_year` (`staff_id`, `leave_type_id`, `academic_year_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 10. Staff Attendance
```sql
CREATE TABLE `staff_attendance` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `staff_id` BIGINT UNSIGNED NOT NULL,
    `campus_id` INT UNSIGNED NOT NULL,
    `attendance_date` DATE NOT NULL,
    `clock_in` TIME,
    `clock_out` TIME,
    `status` ENUM('present', 'absent', 'late', 'half_day', 'on_leave') NOT NULL,
    `marked_by` BIGINT UNSIGNED,
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`staff_id`) REFERENCES `staff`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`campus_id`) REFERENCES `campuses`(`id`),
    UNIQUE KEY `unique_staff_date` (`staff_id`, `attendance_date`),
    INDEX `idx_date` (`attendance_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 11. Staff Performance Reviews
```sql
CREATE TABLE `staff_performance_reviews` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `staff_id` BIGINT UNSIGNED NOT NULL,
    `review_period` VARCHAR(100) COMMENT 'Term 1 2025, Annual 2024',
    `reviewer_id` BIGINT UNSIGNED NOT NULL,
    `review_date` DATE NOT NULL,

    -- Ratings (1-5 scale)
    `punctuality_rating` INT,
    `lesson_preparation_rating` INT,
    `student_engagement_rating` INT,
    `professionalism_rating` INT,
    `teamwork_rating` INT,
    `overall_rating` DECIMAL(3,2) COMMENT 'Average of all ratings',

    -- Comments
    `strengths` TEXT,
    `areas_for_improvement` TEXT,
    `action_plan` TEXT,
    `reviewer_comments` TEXT,
    `staff_comments` TEXT COMMENT 'Staff response to review',

    `signed_by_staff` TINYINT(1) DEFAULT 0,
    `signed_at` TIMESTAMP NULL,

    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`staff_id`) REFERENCES `staff`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`reviewer_id`) REFERENCES `users`(`id`),
    INDEX `idx_staff` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Module Features

### 1. Staff Management
- Add new staff (teaching/non-teaching)
- Staff profile with tabs: Overview, Campus Assignments, Qualifications, Documents, Leave, Attendance, Performance
- Staff directory with filters
- Staff transfers between campuses

### 2. Leave Management
#### For Staff:
- Apply for leave
- View leave balance
- View leave history
- Cancel pending leave

#### For Approvers:
- Approve/reject leave requests
- View pending approvals
- View team leave calendar
- Assign relief teachers

#### Leave Types:
- Annual Leave (21 days)
- Sick Leave (14 days)
- Maternity Leave (90 days)
- Paternity Leave (14 days)
- Compassionate Leave (variable)
- Study Leave (variable)
- Unpaid Leave

### 3. Attendance Tracking
- Daily staff attendance marking
- Clock in/out system
- Attendance reports
- Late arrival tracking
- Absence notifications

### 4. Performance Management
- Periodic performance reviews
- Goal setting and tracking
- 360-degree feedback
- Professional development planning

### 5. Payroll Integration (Future)
- Salary structure management
- Allowances and deductions
- Payslip generation
- Statutory deductions (PAYE, NSSF, NHIF)

## UI Screens

### Staff Module Navigation
```
Staff
├── All Staff
├── Add Staff
├── Teaching Staff
├── Non-Teaching Staff
├── Staff on Leave
├── Leave Management
│   ├── Apply for Leave
│   ├── My Leave Applications
│   ├── Pending Approvals (HoD/Principal)
│   ├── Leave Calendar
│   └── Leave Reports
├── Attendance
│   ├── Mark Attendance
│   ├── Attendance Register
│   └── Attendance Reports
└── Performance Reviews
```

## RBAC Permissions
- `Staff.view` - View staff list
- `Staff.write` - Add/edit staff
- `Staff.manage_leave` - Approve leave requests
- `Staff.view_salary` - View salary information
- `Staff.mark_attendance` - Mark staff attendance
- `Staff.manage_reviews` - Create performance reviews

## Implementation Priority

### Phase 1: Core Staff Management
1. Staff CRUD operations
2. Staff profiles
3. Campus assignments
4. Qualifications tracking

### Phase 2: Leave Management
1. Leave types setup
2. Leave application workflow
3. Approval chain
4. Leave balance tracking
5. Relief teacher assignment

### Phase 3: Attendance
1. Daily attendance marking
2. Attendance reports
3. Late/absence tracking

### Phase 4: Performance
1. Performance review forms
2. Rating system
3. Action plans

### Phase 5: Advanced Features
1. Payroll integration
2. Contract renewal reminders
3. Professional development tracking
4. Staff portal (self-service)
