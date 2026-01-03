<?php
/**
 * Reorder Modules
 * Move Transport and Meals after Finance & Fees
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/database.php';

function reorderModules($subdomain = 'demo') {
    echo "Reordering modules for tenant: {$subdomain}\n";

    $tenant = Database::resolveTenant($subdomain);
    if (!$tenant) {
        die("Error: Tenant '{$subdomain}' not found!\n");
    }

    $pdo = Database::getTenantConnection();

    // Define new correct order
    // Dashboard(1), Tasks(2), Students(3), Academics(4), Finance(5),
    // Transport(6), Meals(7), Communication(8), Reports(9), Settings(10)
    $correctOrder = [
        'Dashboard' => 1,
        'Tasks' => 2,
        'Students' => 3,
        'Academics' => 4,
        'Finance' => 5,
        'Transport' => 6,
        'Meals' => 7,
        'Communication' => 8,
        'Reports' => 9,
        'Settings' => 10,
    ];

    $stmt = $pdo->prepare("UPDATE modules SET sort_order = :sort_order WHERE name = :name");

    echo "\nUpdating sort orders...\n";
    foreach ($correctOrder as $name => $order) {
        $stmt->execute(['name' => $name, 'sort_order' => $order]);
        echo "  {$order}. {$name}\n";
    }

    echo "\n✓ Sort order updated!\n";

    // Verify
    echo "\n=== Current Module Order (Active Only) ===\n";
    $verify = $pdo->query("
        SELECT sort_order, name, display_name, is_active
        FROM modules
        WHERE is_active = 1
        ORDER BY sort_order
    ");
    while ($row = $verify->fetch(PDO::FETCH_ASSOC)) {
        echo "  {$row['sort_order']}. {$row['display_name']}\n";
    }
}

// Run if executed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    $subdomain = $argv[1] ?? 'demo';
    reorderModules($subdomain);
}
?>
