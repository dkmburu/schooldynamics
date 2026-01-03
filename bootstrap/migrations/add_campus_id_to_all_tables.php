<?php
/**
 * Add campus_id to all campus-related tables
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/database.php';

Env::load();

echo "========================================\n";
echo "Adding campus_id to Campus-Related Tables\n";
echo "========================================\n\n";

try {
    Database::resolveTenant('demo');
    $pdo = Database::getTenantConnection();

    // Tables that should have campus_id
    $tablesToUpdate = [
        'applicants' => 'already has campus_id',
        'students' => 'already has campus_id',
        'classes' => [
            'column' => 'campus_id',
            'position' => 'AFTER id',
            'default' => 1
        ],
        'staff_attendance' => 'already has campus_id',
        'applicant_interviews' => [
            'column' => 'campus_id',
            'position' => 'AFTER applicant_id',
            'default' => 1
        ],
        'applicant_exams' => [
            'column' => 'campus_id',
            'position' => 'AFTER applicant_id',
            'default' => 1
        ],
        'intake_campaigns' => [
            'column' => 'campus_id',
            'position' => 'AFTER id',
            'default' => 1,
            'comment' => 'Campaign can be campus-specific or shared (NULL = all campuses)'
        ],
    ];

    foreach ($tablesToUpdate as $table => $config) {
        echo "Checking table: {$table}...\n";

        // Check if table exists
        $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() == 0) {
            echo "  ⚠ Table '{$table}' doesn't exist yet, skipping\n\n";
            continue;
        }

        if (is_string($config)) {
            echo "  ℹ {$config}\n\n";
            continue;
        }

        // Check if column already exists
        $stmt = $pdo->query("SHOW COLUMNS FROM {$table} LIKE '{$config['column']}'");
        if ($stmt->rowCount() > 0) {
            echo "  ℹ campus_id already exists\n\n";
            continue;
        }

        // Add campus_id column
        $sql = "ALTER TABLE {$table} ADD COLUMN `{$config['column']}` INT UNSIGNED";

        // Add NULL or DEFAULT based on config
        if (isset($config['default'])) {
            $sql .= " DEFAULT {$config['default']}";
        } else {
            $sql .= " NULL";
        }

        // Add position
        if (isset($config['position'])) {
            $sql .= " {$config['position']}";
        }

        $pdo->exec($sql);

        // Add comment separately if needed
        if (isset($config['comment'])) {
            try {
                $pdo->exec("ALTER TABLE {$table} MODIFY COLUMN `{$config['column']}` INT UNSIGNED " .
                          (isset($config['default']) ? "DEFAULT {$config['default']}" : "NULL") .
                          " COMMENT '{$config['comment']}'");
            } catch (Exception $e) {
                // Comment failed, but column is added
            }
        }
        echo "  ✓ Added campus_id column\n";

        // Add foreign key if not NULL
        if (isset($config['default']) || !isset($config['nullable'])) {
            try {
                $pdo->exec("ALTER TABLE {$table} ADD FOREIGN KEY (`{$config['column']}`) REFERENCES `campuses`(`id`)");
                echo "  ✓ Added foreign key constraint\n";
            } catch (Exception $e) {
                echo "  ⚠ Could not add foreign key (may already exist or data issues)\n";
            }
        }

        // Add index
        try {
            $pdo->exec("ALTER TABLE {$table} ADD INDEX `idx_campus` (`{$config['column']}`)");
            echo "  ✓ Added index\n";
        } catch (Exception $e) {
            echo "  ℹ Index may already exist\n";
        }

        echo "\n";
    }

    echo "========================================\n";
    echo "✓ Campus ID updates completed!\n";
    echo "========================================\n\n";

    echo "Summary of campus-linked tables:\n";
    echo "  1. applicants (campus_id) ✓\n";
    echo "  2. students (campus_id) ✓\n";
    echo "  3. classes (campus_id) - Classes are campus-specific\n";
    echo "  4. applicant_interviews (campus_id) - Interview location\n";
    echo "  5. applicant_exams (campus_id) - Exam location\n";
    echo "  6. intake_campaigns (campus_id) - Campaign per campus\n";
    echo "  7. staff_attendance (campus_id) - Attendance at specific campus\n";
    echo "  8. staff_campus_assignments (campus_id) - Staff work locations\n\n";

    echo "Note: staff table does NOT have campus_id because staff can work\n";
    echo "across multiple campuses. Use staff_campus_assignments for that.\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
