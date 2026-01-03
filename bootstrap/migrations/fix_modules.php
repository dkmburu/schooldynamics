<?php
/**
 * Fix Modules Data
 * - Fix icon class names (add ti ti- prefix)
 * - Fix display names
 * - Deactivate Dashboard and Settings modules (they're hardcoded)
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/database.php';

function fixModules($subdomain = 'demo') {
    echo "Fixing modules for tenant: {$subdomain}\n";

    $tenant = Database::resolveTenant($subdomain);
    if (!$tenant) {
        die("Error: Tenant '{$subdomain}' not found!\n");
    }

    $pdo = Database::getTenantConnection();

    // Fix module icons and display names
    $updates = [
        ['name' => 'Dashboard', 'icon' => 'ti ti-home', 'display_name' => 'Dashboard', 'is_active' => 0], // Deactivate - hardcoded
        ['name' => 'Students', 'icon' => 'ti ti-users', 'display_name' => 'Students', 'is_active' => 1],
        ['name' => 'Academics', 'icon' => 'ti ti-book', 'display_name' => 'Academics', 'is_active' => 1],
        ['name' => 'Finance', 'icon' => 'ti ti-currency-dollar', 'display_name' => 'Finance & Fees', 'is_active' => 1],
        ['name' => 'Communication', 'icon' => 'ti ti-mail', 'display_name' => 'Communication', 'is_active' => 1],
        ['name' => 'Reports', 'icon' => 'ti ti-chart-bar', 'display_name' => 'Reports', 'is_active' => 1],
        ['name' => 'Settings', 'icon' => 'ti ti-settings', 'display_name' => 'Settings', 'is_active' => 0], // Deactivate - hardcoded
    ];

    $stmt = $pdo->prepare("
        UPDATE modules
        SET icon = :icon, display_name = :display_name, is_active = :is_active
        WHERE name = :name
    ");

    foreach ($updates as $update) {
        $stmt->execute($update);
        $status = $update['is_active'] ? 'ACTIVE' : 'DEACTIVATED';
        echo "  ✓ {$update['name']} → {$update['display_name']} ({$update['icon']}) [{$status}]\n";
    }

    echo "\n✓ Modules fixed successfully!\n";
}

// Run if executed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    $subdomain = $argv[1] ?? 'demo';
    fixModules($subdomain);
}
?>
