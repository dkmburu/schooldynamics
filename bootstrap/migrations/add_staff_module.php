<?php
/**
 * Add Staff Module to Navigation
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/database.php';

Env::load();

echo "========================================\n";
echo "Adding Staff Module\n";
echo "========================================\n\n";

try {
    Database::resolveTenant('demo');
    $pdo = Database::getTenantConnection();

    // Check if Staff module already exists
    $stmt = $pdo->query("SELECT id FROM modules WHERE name = 'Staff'");
    $existingModule = $stmt->fetch();

    if ($existingModule) {
        echo "ℹ  Staff module already exists\n";
        $moduleId = $existingModule['id'];
    } else {
        // Insert Staff module
        echo "1. Creating Staff module...\n";
        $stmt = $pdo->prepare("
            INSERT INTO modules (name, display_name, icon, sort_order, is_active)
            VALUES (?, ?, ?, ?, 1)
        ");
        $stmt->execute(['Staff', 'Staff', 'fas fa-users', 3]);
        $moduleId = $pdo->lastInsertId();
        echo "   ✓ Staff module created (ID: {$moduleId})\n";
    }

    // Add submodules
    echo "2. Adding Staff submodules...\n";

    $submodules = [
        ['name' => 'Staff.AllStaff', 'display_name' => 'All Staff', 'route' => '/staff', 'icon' => 'fas fa-users', 'sort_order' => 1],
        ['name' => 'Staff.AddStaff', 'display_name' => 'Add Staff', 'route' => '/staff/create', 'icon' => 'fas fa-user-plus', 'sort_order' => 2],
        ['name' => 'Staff.TeachingStaff', 'display_name' => 'Teaching Staff', 'route' => '/staff?type=teaching', 'icon' => 'fas fa-chalkboard-teacher', 'sort_order' => 3],
        ['name' => 'Staff.NonTeachingStaff', 'display_name' => 'Non-Teaching Staff', 'route' => '/staff?type=non-teaching', 'icon' => 'fas fa-user-tie', 'sort_order' => 4],
        ['name' => 'Staff.LeaveManagement', 'display_name' => 'Leave Management', 'route' => '/staff/leave', 'icon' => 'fas fa-calendar-alt', 'sort_order' => 5],
        ['name' => 'Staff.Attendance', 'display_name' => 'Attendance', 'route' => '/staff/attendance', 'icon' => 'fas fa-clipboard-check', 'sort_order' => 6],
    ];

    $insertStmt = $pdo->prepare("
        INSERT INTO submodules (module_id, name, display_name, route, icon, sort_order, is_active)
        VALUES (?, ?, ?, ?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE
            display_name = VALUES(display_name),
            route = VALUES(route),
            icon = VALUES(icon),
            sort_order = VALUES(sort_order)
    ");

    foreach ($submodules as $sub) {
        $insertStmt->execute([
            $moduleId,
            $sub['name'],
            $sub['display_name'],
            $sub['route'],
            $sub['icon'],
            $sub['sort_order']
        ]);
        echo "   ✓ {$sub['display_name']}\n";
    }

    echo "\n========================================\n";
    echo "✓ Staff module added successfully!\n";
    echo "========================================\n\n";

    echo "Staff module menu items:\n";
    $stmt = $pdo->query("
        SELECT display_name, route
        FROM submodules
        WHERE module_id = {$moduleId}
        ORDER BY sort_order
    ");
    while ($row = $stmt->fetch()) {
        echo "  - {$row['display_name']} → {$row['route']}\n";
    }

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
