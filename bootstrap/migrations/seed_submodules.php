<?php
/**
 * Seed Submodules Data
 * Populates submodules table with navigation items
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/database.php';

function seedSubmodules($subdomain = 'demo') {
    echo "Starting submodules seeding for tenant: {$subdomain}\n";

    $tenant = Database::resolveTenant($subdomain);
    if (!$tenant) {
        die("Error: Tenant '{$subdomain}' not found!\n");
    }

    $pdo = Database::getTenantConnection();

    // Get module IDs
    $modules = [];
    $stmt = $pdo->query("SELECT id, name FROM modules");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $modules[$row['name']] = $row['id'];
    }

    // Define submodules structure
    $submodules = [
        // Students Module
        'Students' => [
            ['name' => 'Students.Applicants', 'display_name' => 'All Applicants', 'route' => '/applicants', 'icon' => 'ti-user-check', 'sort_order' => 1],
            ['name' => 'Students.NewApplication', 'display_name' => 'New Application', 'route' => '/applicants/create', 'icon' => 'ti-user-plus', 'sort_order' => 2],
            ['name' => 'Students.ScreeningQueue', 'display_name' => 'Screening Queue', 'route' => '/applicants/screening', 'icon' => 'ti-clipboard-list', 'sort_order' => 3],
            ['name' => 'Students.AllStudents', 'display_name' => 'All Students', 'route' => '/students', 'icon' => 'ti-school', 'sort_order' => 4],
            ['name' => 'Students.AddStudent', 'display_name' => 'Add Student', 'route' => '/students/create', 'icon' => 'ti-user-plus', 'sort_order' => 5],
        ],

        // Academics Module
        'Academics' => [
            ['name' => 'Academics.Classes', 'display_name' => 'Classes', 'route' => '/academics/classes', 'icon' => 'ti-school', 'sort_order' => 1],
            ['name' => 'Academics.Subjects', 'display_name' => 'Subjects', 'route' => '/academics/subjects', 'icon' => 'ti-book-2', 'sort_order' => 2],
            ['name' => 'Academics.Attendance', 'display_name' => 'Attendance', 'route' => '/academics/attendance', 'icon' => 'ti-clipboard-check', 'sort_order' => 3],
        ],

        // Finance Module
        'Finance' => [
            ['name' => 'Finance.Dashboard', 'display_name' => 'Finance Dashboard', 'route' => '/finance/dashboard', 'icon' => 'ti-dashboard', 'sort_order' => 1],
            ['name' => 'Finance.Invoices', 'display_name' => 'Invoices', 'route' => '/finance/invoices', 'icon' => 'ti-file-invoice', 'sort_order' => 2],
            ['name' => 'Finance.Receipts', 'display_name' => 'Receipts', 'route' => '/finance/receipts', 'icon' => 'ti-receipt', 'sort_order' => 3],
            ['name' => 'Finance.FeeTariffs', 'display_name' => 'Fee Tariffs', 'route' => '/finance/fee-tariffs', 'icon' => 'ti-coin', 'sort_order' => 4],
        ],

        // Assessment Module
        'Assessment' => [
            ['name' => 'Assessment.Exams', 'display_name' => 'Exams', 'route' => '/assessment/exams', 'icon' => 'ti-file-text', 'sort_order' => 1],
            ['name' => 'Assessment.Grades', 'display_name' => 'Grades', 'route' => '/assessment/grades', 'icon' => 'ti-certificate', 'sort_order' => 2],
        ],

        // Communication Module
        'Communication' => [
            ['name' => 'Communication.Messages', 'display_name' => 'Messages', 'route' => '/communication/messages', 'icon' => 'ti-message', 'sort_order' => 1],
            ['name' => 'Communication.Templates', 'display_name' => 'Templates', 'route' => '/communication/templates', 'icon' => 'ti-template', 'sort_order' => 2],
        ],

        // Reports Module
        'Reports' => [
            ['name' => 'Reports.All', 'display_name' => 'All Reports', 'route' => '/reports', 'icon' => 'ti-report', 'sort_order' => 1],
        ],
    ];

    $insertStmt = $pdo->prepare("
        INSERT INTO submodules (module_id, name, display_name, route, icon, sort_order, is_active)
        VALUES (:module_id, :name, :display_name, :route, :icon, :sort_order, 1)
    ");

    $count = 0;
    foreach ($submodules as $moduleName => $items) {
        if (!isset($modules[$moduleName])) {
            echo "Warning: Module '{$moduleName}' not found, skipping...\n";
            continue;
        }

        $moduleId = $modules[$moduleName];

        foreach ($items as $item) {
            $insertStmt->execute([
                'module_id' => $moduleId,
                'name' => $item['name'],
                'display_name' => $item['display_name'],
                'route' => $item['route'],
                'icon' => $item['icon'],
                'sort_order' => $item['sort_order'],
            ]);
            $count++;
            echo "  + {$item['display_name']} ({$moduleName})\n";
        }
    }

    echo "\n✓ Successfully seeded {$count} submodules!\n";
}

// Run if executed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    $subdomain = $argv[1] ?? 'demo';
    seedSubmodules($subdomain);
}
?>
