<?php
/**
 * Fix Module Sort Order
 * Ensure proper ordering: Dashboard(1), Tasks(2), Transport(3), Meals(4), Students(5), etc.
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/database.php';

function fixSortOrder($subdomain = 'demo') {
    echo "Fixing module sort order for tenant: {$subdomain}\n";

    $tenant = Database::resolveTenant($subdomain);
    if (!$tenant) {
        die("Error: Tenant '{$subdomain}' not found!\n");
    }

    $pdo = Database::getTenantConnection();

    // Define correct order
    $correctOrder = [
        'Dashboard' => 1,
        'Tasks' => 2,
        'Transport' => 3,
        'Meals' => 4,
        'Students' => 5,
        'Academics' => 6,
        'Finance' => 7,
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

    echo "\n✓ Sort order fixed!\n";

    // Verify
    echo "\n=== Current Module Order (Active Only) ===\n";
    $verify = $pdo->query("SELECT sort_order, name, display_name, is_active FROM modules ORDER BY sort_order");
    while ($row = $verify->fetch(PDO::FETCH_ASSOC)) {
        $status = $row['is_active'] ? '✓' : '✗';
        echo "  {$status} {$row['sort_order']}. {$row['display_name']}\n";
    }
}

// Run if executed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    $subdomain = $argv[1] ?? 'demo';
    fixSortOrder($subdomain);
}
?>
