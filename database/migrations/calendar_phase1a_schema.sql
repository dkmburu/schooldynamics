-- Calendar Module Phase 1A: Academic Calendar Foundation
-- Created: 2025-01-07
-- Description: Core tables for academic terms, important dates, and national holidays

-- =============================================================================
-- LOOKUP TABLES
-- =============================================================================

-- Term Date Types (Academic dates, Exams, Holidays, Breaks)
CREATE TABLE IF NOT EXISTS term_date_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL COMMENT 'academic, exam, holiday, break',
    color VARCHAR(20) NULL COMMENT 'Hex color for calendar display',
    icon VARCHAR(50) NULL COMMENT 'Icon class for UI',
    description TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- National Holiday Types
CREATE TABLE IF NOT EXISTS national_holiday_types (
    id TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- ACADEMIC TERMS
-- =============================================================================

-- Academic Terms (Main structure for school year)
CREATE TABLE IF NOT EXISTS academic_terms (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id INT UNSIGNED NOT NULL,
    campus_id INT UNSIGNED NULL COMMENT 'NULL means all campuses',
    academic_year VARCHAR(20) NOT NULL COMMENT 'e.g., 2024/2025',
    term_number TINYINT NOT NULL COMMENT '1, 2, 3',
    term_name VARCHAR(50) NOT NULL COMMENT 'Term 1, First Term, etc.',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status VARCHAR(20) DEFAULT 'draft' COMMENT 'draft, published, current, completed',
    is_current BOOLEAN DEFAULT FALSE COMMENT 'Currently active term',
    notes TEXT NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_school (school_id),
    INDEX idx_campus (campus_id),
    INDEX idx_academic_year (academic_year),
    INDEX idx_status (status),
    INDEX idx_current (is_current),
    INDEX idx_dates (start_date, end_date),
    UNIQUE KEY unique_term (school_id, campus_id, academic_year, term_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Important Dates Within Terms (Mid-term, Exams, Visiting Days, etc.)
CREATE TABLE IF NOT EXISTS term_important_dates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    term_id INT UNSIGNED NOT NULL,
    date_type_id INT UNSIGNED NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL COMMENT 'NULL for single-day events, set for multi-day periods',
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    affects_timetable BOOLEAN DEFAULT FALSE COMMENT 'Does this date affect normal timetable?',
    is_school_open BOOLEAN DEFAULT TRUE COMMENT 'Is school open on these dates?',
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (term_id) REFERENCES academic_terms(id) ON DELETE CASCADE,
    FOREIGN KEY (date_type_id) REFERENCES term_date_types(id),
    INDEX idx_term (term_id),
    INDEX idx_date_type (date_type_id),
    INDEX idx_dates (start_date, end_date),
    INDEX idx_timetable (affects_timetable)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- NATIONAL HOLIDAYS
-- =============================================================================

-- National Holidays (Public holidays that affect school calendar)
CREATE TABLE IF NOT EXISTS national_holidays (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    holiday_type_id TINYINT UNSIGNED NOT NULL,
    country_code VARCHAR(2) DEFAULT 'KE' COMMENT 'ISO country code',
    holiday_name VARCHAR(100) NOT NULL,
    holiday_date DATE NOT NULL,
    year YEAR NOT NULL,
    is_recurring BOOLEAN DEFAULT TRUE COMMENT 'Does this holiday recur annually?',
    recurrence_rule VARCHAR(255) NULL COMMENT 'e.g., "2nd Monday of October" for flexible dates',
    is_school_holiday BOOLEAN DEFAULT TRUE COMMENT 'Is school closed?',
    description TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (holiday_type_id) REFERENCES national_holiday_types(id),
    INDEX idx_holiday_type (holiday_type_id),
    INDEX idx_country (country_code),
    INDEX idx_date (holiday_date),
    INDEX idx_year (year),
    INDEX idx_school_holiday (is_school_holiday),
    UNIQUE KEY unique_holiday_date (holiday_name, holiday_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- National Holiday Observances (Auto-generated for recurring holidays)
-- This table stores actual observance dates for each year
CREATE TABLE IF NOT EXISTS holiday_observances (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    national_holiday_id INT UNSIGNED NOT NULL,
    observance_date DATE NOT NULL,
    year YEAR NOT NULL,
    is_observed BOOLEAN DEFAULT TRUE COMMENT 'Was it actually observed that year?',
    observance_notes TEXT NULL COMMENT 'e.g., Moved due to weekend',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (national_holiday_id) REFERENCES national_holidays(id) ON DELETE CASCADE,
    INDEX idx_holiday (national_holiday_id),
    INDEX idx_date (observance_date),
    INDEX idx_year (year),
    UNIQUE KEY unique_observance (national_holiday_id, year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- SCHOOL-SPECIFIC HOLIDAYS
-- =============================================================================

-- School Holidays (School-specific non-working days)
CREATE TABLE IF NOT EXISTS school_holidays (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id INT UNSIGNED NOT NULL,
    campus_id INT UNSIGNED NULL,
    term_id INT UNSIGNED NULL COMMENT 'Link to specific term if applicable',
    holiday_name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL COMMENT 'NULL for single day',
    holiday_type VARCHAR(50) NULL COMMENT 'staff_only, students_only, all',
    affects_timetable BOOLEAN DEFAULT TRUE,
    description TEXT NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (term_id) REFERENCES academic_terms(id) ON DELETE SET NULL,
    INDEX idx_school (school_id),
    INDEX idx_campus (campus_id),
    INDEX idx_term (term_id),
    INDEX idx_dates (start_date, end_date),
    INDEX idx_type (holiday_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- CALENDAR SETTINGS
-- =============================================================================

-- Academic Calendar Settings (Per school configuration)
CREATE TABLE IF NOT EXISTS academic_calendar_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id INT UNSIGNED NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    terms_per_year TINYINT DEFAULT 3,
    default_term_length_weeks TINYINT DEFAULT 13,
    week_starts_on VARCHAR(10) DEFAULT 'monday' COMMENT 'monday or sunday',
    working_days JSON NULL COMMENT 'Array of working days e.g., ["mon","tue","wed","thu","fri"]',
    default_school_start_time TIME DEFAULT '08:00:00',
    default_school_end_time TIME DEFAULT '15:30:00',
    settings JSON NULL COMMENT 'Additional flexible settings',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_school_year (school_id, academic_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
