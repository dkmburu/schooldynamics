-- Upgrade terms table for Calendar Module
-- Created: 2026-01-07
-- Description: Add new columns to terms table and migrate data from academic_year_id to academic_year string

-- =============================================================================
-- STEP 1: ADD NEW COLUMNS TO TERMS TABLE
-- =============================================================================

ALTER TABLE terms
ADD COLUMN school_id INT UNSIGNED NULL AFTER id,
ADD COLUMN campus_id INT UNSIGNED NULL AFTER school_id,
ADD COLUMN academic_year VARCHAR(20) NULL AFTER campus_id,
ADD COLUMN status VARCHAR(20) DEFAULT 'published' AFTER end_date,
ADD COLUMN notes TEXT NULL AFTER status,
ADD COLUMN created_by INT UNSIGNED NULL AFTER notes;

-- =============================================================================
-- STEP 2: MIGRATE EXISTING DATA
-- =============================================================================

-- Set school_id to 1 (tenant_id) for all existing terms
-- Note: In multi-tenant setup, this should be set appropriately
UPDATE terms SET school_id = 1 WHERE school_id IS NULL;

-- Convert academic_year_id to academic_year string format
-- academic_year_id = 1 means year 2025, so academic_year should be "2025/2026"
UPDATE terms t
JOIN academic_years ay ON t.academic_year_id = ay.id
SET t.academic_year = CONCAT(ay.year_name, '/', (ay.year_name + 1))
WHERE t.academic_year IS NULL;

-- Set status based on is_current flag
UPDATE terms SET status = 'current' WHERE is_current = 1;
UPDATE terms SET status = 'published' WHERE is_current = 0 AND status != 'current';

-- =============================================================================
-- STEP 3: ADD INDEXES FOR PERFORMANCE
-- =============================================================================

ALTER TABLE terms
ADD INDEX idx_school (school_id),
ADD INDEX idx_campus (campus_id),
ADD INDEX idx_academic_year (academic_year),
ADD INDEX idx_status (status),
ADD INDEX idx_dates (start_date, end_date);

-- =============================================================================
-- STEP 4: MAKE school_id NOT NULL (after data migration)
-- =============================================================================

ALTER TABLE terms
MODIFY COLUMN school_id INT UNSIGNED NOT NULL,
MODIFY COLUMN academic_year VARCHAR(20) NOT NULL;

-- =============================================================================
-- STEP 5: UPDATE FOREIGN KEY CONSTRAINTS
-- =============================================================================

-- Drop foreign key from term_important_dates if it exists
-- (pointing to academic_terms)
SET @fk_name = (
    SELECT CONSTRAINT_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'term_important_dates'
    AND COLUMN_NAME = 'term_id'
    AND REFERENCED_TABLE_NAME = 'academic_terms'
    LIMIT 1
);

SET @sql = IF(@fk_name IS NOT NULL,
    CONCAT('ALTER TABLE term_important_dates DROP FOREIGN KEY ', @fk_name),
    'SELECT "No FK to drop"'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add new foreign key pointing to terms table
ALTER TABLE term_important_dates
ADD CONSTRAINT fk_term_important_dates_term_id
FOREIGN KEY (term_id) REFERENCES terms(id) ON DELETE CASCADE;

-- =============================================================================
-- VERIFICATION QUERIES (Run these to verify the migration)
-- =============================================================================

-- Verify all terms have required fields
-- SELECT * FROM terms WHERE school_id IS NULL OR academic_year IS NULL;

-- Count terms by status
-- SELECT status, COUNT(*) FROM terms GROUP BY status;

-- Show migrated data
-- SELECT id, school_id, academic_year, term_name, term_number, start_date, end_date, status, is_current FROM terms;
