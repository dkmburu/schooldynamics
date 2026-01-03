<?php
/**
 * Add Screening Queue to Students Menu
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/database.php';

Env::load();

echo "========================================\n";
echo "Adding Screening Queue Menu Item\n";
echo "========================================\n\n";

try {
    Database::resolveTenant('demo');
    $pdo = Database::getTenantConnection();

    // Get Students module ID
    $stmt = $pdo->query("SELECT id FROM modules WHERE name = 'Students' LIMIT 1");
    $module = $stmt->fetch();

    if (!$module) {
        die("Error: Students module not found!\n");
    }

    $moduleId = $module['id'];

    // Check if Screening Queue already exists
    $stmt = $pdo->prepare("SELECT id FROM submodules WHERE name = 'Students.ScreeningQueue'");
    $stmt->execute();
    $existing = $stmt->fetch();

    if ($existing) {
        echo "ℹ Screening Queue menu item already exists (ID: {$existing['id']})\n";
    } else {
        // Update sort order for existing items
        echo "1. Updating sort order for existing menu items...\n";
        $pdo->exec("
            UPDATE submodules
            SET sort_order = sort_order + 1
            WHERE module_id = {$moduleId} AND sort_order >= 3
        ");
        echo "   ✓ Sort orders updated\n";

        // Insert Screening Queue
        echo "2. Inserting Screening Queue menu item...\n";
        $stmt = $pdo->prepare("
            INSERT INTO submodules (module_id, name, display_name, route, icon, sort_order, is_active)
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ");

        $stmt->execute([
            $moduleId,
            'Students.ScreeningQueue',
            'Screening Queue',
            '/applicants/screening',
            'fas fa-clipboard-list',
            3
        ]);

        echo "   ✓ Screening Queue added successfully!\n";
    }

    // Show current Students menu structure
    echo "\n3. Current Students menu:\n";
    $stmt = $pdo->query("
        SELECT id, display_name, route, icon, sort_order
        FROM submodules
        WHERE module_id = {$moduleId}
        ORDER BY sort_order
    ");

    while ($row = $stmt->fetch()) {
        echo "   [{$row['sort_order']}] {$row['display_name']} → {$row['route']}\n";
    }

    echo "\n========================================\n";
    echo "✓ Menu update completed!\n";
    echo "========================================\n\n";
    echo "Refresh your browser to see the new menu item.\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
