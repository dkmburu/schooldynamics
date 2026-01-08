-- ============================================================
-- Guardian Table Update
-- Make id_number required and unique for proper guardian management
-- Run Date: 2026-01-06
-- ============================================================

-- Step 1: First, update any NULL id_numbers with temporary values based on phone
-- (This should be done manually before running this migration)
-- UPDATE guardians SET id_number = CONCAT('TEMP_', phone) WHERE id_number IS NULL;

-- Step 2: Make id_number NOT NULL and add unique constraint
ALTER TABLE guardians
    MODIFY COLUMN id_number VARCHAR(50) NOT NULL,
    ADD UNIQUE INDEX idx_guardians_id_number (id_number);

-- Step 3: Verify the change
-- DESCRIBE guardians;
-- SHOW INDEX FROM guardians WHERE Key_name = 'idx_guardians_id_number';
