<?php
/**
 * Seed Test Campuses
 * Add sample campuses for demo
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/database.php';

Env::load();

echo "========================================\n";
echo "Seeding Test Campuses\n";
echo "========================================\n\n";

try {
    Database::resolveTenant('demo');
    $pdo = Database::getTenantConnection();

    // Check if we already have multiple campuses
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM campuses");
    $count = $stmt->fetch()['count'];

    if ($count > 1) {
        echo "ℹ  Multiple campuses already exist. Skipping...\n";
        exit(0);
    }

    echo "Adding sample campuses...\n\n";

    // Add Nairobi Campus - High School
    $pdo->exec("
        INSERT INTO campuses (campus_code, campus_name, location, address, phone, email, is_main, is_active, sort_order)
        VALUES (
            'NBO-HS',
            'Nairobi Campus - High School',
            'Nairobi',
            'Karen Road, Nairobi',
            '+254712345678',
            'nairobi@schooldynamics.local',
            0,
            1,
            2
        )
    ");
    echo "✓ Added: Nairobi Campus - High School\n";

    // Add Nakuru Campus - ECD & Primary
    $pdo->exec("
        INSERT INTO campuses (campus_code, campus_name, location, address, phone, email, is_main, is_active, sort_order)
        VALUES (
            'NKR-ECD',
            'Nakuru Campus - ECD & Primary',
            'Nakuru',
            'Milimani Estate, Nakuru',
            '+254723456789',
            'nakuru@schooldynamics.local',
            0,
            1,
            3
        )
    ");
    echo "✓ Added: Nakuru Campus - ECD & Primary\n";

    echo "\n========================================\n";
    echo "✓ Test campuses seeded successfully!\n";
    echo "========================================\n\n";

    // Show all campuses
    $stmt = $pdo->query("SELECT * FROM campuses ORDER BY sort_order");
    echo "Current Campuses:\n";
    while ($campus = $stmt->fetch()) {
        $main = $campus['is_main'] ? ' [MAIN]' : '';
        echo "  - {$campus['campus_name']} ({$campus['campus_code']}){$main}\n";
    }

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
