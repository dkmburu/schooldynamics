# Multi-Campus Architecture Design

## Overview
Support schools with multiple campuses (e.g., Demo School Nairobi - High School, Demo School Nakuru - ECD)

## Database Schema

### Campuses Table
```sql
CREATE TABLE `campuses` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `campus_code` VARCHAR(20) NOT NULL UNIQUE COMMENT 'Short code: NBO-HS, NKR-ECD',
    `campus_name` VARCHAR(255) NOT NULL COMMENT 'Nairobi Campus - High School',
    `location` VARCHAR(255) COMMENT 'City/Area',
    `address` TEXT,
    `phone` VARCHAR(20),
    `email` VARCHAR(255),
    `is_main` TINYINT(1) DEFAULT 0 COMMENT 'Main/Head Office campus',
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_active` (`is_active`),
    INDEX `idx_code` (`campus_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Campus Resources (What can be campus-specific or shared)

#### Campus-Specific Resources
- **Students** (always belongs to one campus)
- **Applicants** (apply to specific campus)
- **Classes/Streams** (campus-specific)
- **Staff Assignments** (staff can work across campuses but assignments are campus-specific)
- **Transport Routes** (campus-specific)
- **Timetables** (campus-specific)

#### Shared Resources
- **Staff** (can work across multiple campuses)
- **Subjects** (curriculum shared)
- **Fee Tariffs** (can be shared or campus-specific)
- **Academic Years & Terms** (shared across all campuses)
- **Grades/Levels** (shared)
- **Modules & Permissions** (shared)

### Modified Tables

#### Students Table
```sql
ALTER TABLE `students`
ADD COLUMN `campus_id` INT UNSIGNED NOT NULL AFTER `id`,
ADD FOREIGN KEY (`campus_id`) REFERENCES `campuses`(`id`);
```

#### Applicants Table
```sql
ALTER TABLE `applicants`
ADD COLUMN `campus_id` INT UNSIGNED NOT NULL AFTER `id`,
ADD FOREIGN KEY (`campus_id`) REFERENCES `campuses`(`id`);
```

#### Classes Table
```sql
ALTER TABLE `classes`
ADD COLUMN `campus_id` INT UNSIGNED NOT NULL AFTER `id`,
ADD FOREIGN KEY (`campus_id`) REFERENCES `campuses`(`id`);
```

#### Staff Table (New - to be designed)
```sql
CREATE TABLE `staff` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `staff_number` VARCHAR(50) NOT NULL UNIQUE,
    `user_id` BIGINT UNSIGNED COMMENT 'Link to users table for login',
    `first_name` VARCHAR(100) NOT NULL,
    `middle_name` VARCHAR(100),
    `last_name` VARCHAR(100) NOT NULL,
    `date_of_birth` DATE,
    `gender` ENUM('male', 'female', 'other'),
    `nationality` VARCHAR(100) DEFAULT 'Kenya',
    `id_number` VARCHAR(50),
    `staff_type` ENUM('teaching', 'non-teaching', 'admin', 'support') NOT NULL,
    `employment_type` ENUM('permanent', 'contract', 'part-time', 'casual') NOT NULL,
    `date_joined` DATE,
    `date_left` DATE,
    `status` ENUM('active', 'on_leave', 'suspended', 'terminated', 'retired') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Staff Campus Assignments
```sql
CREATE TABLE `staff_campus_assignments` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `staff_id` BIGINT UNSIGNED NOT NULL,
    `campus_id` INT UNSIGNED NOT NULL,
    `is_primary` TINYINT(1) DEFAULT 0 COMMENT 'Primary campus assignment',
    `position` VARCHAR(255) COMMENT 'Role at this campus',
    `department` VARCHAR(100),
    `start_date` DATE NOT NULL,
    `end_date` DATE,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`staff_id`) REFERENCES `staff`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`campus_id`) REFERENCES `campuses`(`id`) ON DELETE CASCADE,
    INDEX `idx_staff_campus` (`staff_id`, `campus_id`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Campus Selector UI

### Location
Top navigation bar, next to school logo/name

### Design
```html
<div class="navbar-item">
    <div class="dropdown">
        <button class="btn btn-outline-primary dropdown-toggle" data-toggle="dropdown">
            <i class="fas fa-building mr-2"></i>
            <span id="current-campus-name">Nairobi Campus - HS</span>
        </button>
        <div class="dropdown-menu">
            <div class="dropdown-header">Select Campus</div>
            <a class="dropdown-item active" href="#" data-campus-id="1">
                <i class="fas fa-check mr-2"></i> Nairobi Campus - High School
            </a>
            <a class="dropdown-item" href="#" data-campus-id="2">
                Nakuru Campus - ECD
            </a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item text-muted" href="#">
                <i class="fas fa-globe mr-2"></i> All Campuses (Admin)
            </a>
        </div>
    </div>
</div>
```

### Session Management
- Store selected campus in `$_SESSION['current_campus_id']`
- Default to user's primary campus assignment
- Admins can switch to "All Campuses" view

### Data Filtering
All queries automatically filter by campus:
```php
// Middleware adds campus filter
if (!Gate::hasRole('SUPER_ADMIN')) {
    $campusId = $_SESSION['current_campus_id'];
    // Add to all queries
}
```

## Implementation Steps

1. **Phase 1: Database**
   - Create campuses table
   - Seed initial campuses
   - Add campus_id to applicants, students, classes

2. **Phase 2: UI**
   - Add campus selector to navbar
   - Add campus selection during applicant creation
   - Add campus filter to all lists

3. **Phase 3: Reports**
   - Campus-specific reports
   - Cross-campus comparison reports (admin only)

## Migration Strategy

### For Existing Data
```sql
-- Add default campus
INSERT INTO campuses (campus_code, campus_name, is_main, is_active)
VALUES ('MAIN', 'Main Campus', 1, 1);

-- Update existing records
UPDATE students SET campus_id = 1;
UPDATE applicants SET campus_id = 1;
UPDATE classes SET campus_id = 1;
```

## Business Rules

1. **Students**: Can only belong to ONE campus at a time
2. **Staff**: Can work across MULTIPLE campuses
3. **Transfers**: Moving student between campuses = Transfer record
4. **Reports**:
   - Campus Heads: See only their campus
   - Admin: See all campuses + aggregated views
5. **Permissions**: Campus-scoped (can view/edit only assigned campus data)

## Example Use Cases

### Use Case 1: Multi-Campus School
**Demo School** has:
- Nairobi Campus (High School) - Grades 9-12
- Nakuru Campus (ECD & Primary) - PP1-Grade 8

Staff member teaching at both:
- Primary assignment: Nairobi Campus
- Secondary assignment: Nakuru Campus (Fridays only)

### Use Case 2: Campus Transfer
Student transfers from Nakuru to Nairobi:
1. Create transfer record
2. Update student.campus_id
3. Transfer class allocation
4. Notify guardians via SMS/Email
5. Update fee structure (if different)

## Future Enhancements
- Campus-specific fee structures
- Inter-campus transport routes
- Consolidated financial reports across campuses
- Campus performance comparison dashboards
