<?php
/**
 * Add New Modules: Tasks, Transport, and Meals
 * With their respective submodules
 */

require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/database.php';

function addNewModules($subdomain = 'demo') {
    echo "Adding new modules for tenant: {$subdomain}\n";

    $tenant = Database::resolveTenant($subdomain);
    if (!$tenant) {
        die("Error: Tenant '{$subdomain}' not found!\n");
    }

    $pdo = Database::getTenantConnection();

    // First, update sort orders of existing modules to make room for Tasks
    echo "\nUpdating sort orders of existing modules...\n";
    $pdo->exec("UPDATE modules SET sort_order = sort_order + 3 WHERE sort_order >= 2");
    echo "  ✓ Sort orders updated\n";

    // Add new modules
    echo "\nAdding new modules...\n";

    $newModules = [
        [
            'name' => 'Tasks',
            'display_name' => 'Tasks',
            'icon' => 'ti ti-checkbox',
            'sort_order' => 2
        ],
        [
            'name' => 'Transport',
            'display_name' => 'Transport',
            'icon' => 'ti ti-bus',
            'sort_order' => 3
        ],
        [
            'name' => 'Meals',
            'display_name' => 'Meals',
            'icon' => 'ti ti-tools-kitchen-2',
            'sort_order' => 4
        ],
    ];

    $moduleStmt = $pdo->prepare("
        INSERT INTO modules (name, display_name, icon, sort_order, is_active, created_at)
        VALUES (:name, :display_name, :icon, :sort_order, 1, NOW())
    ");

    $moduleIds = [];
    foreach ($newModules as $module) {
        $moduleStmt->execute($module);
        $moduleIds[$module['name']] = $pdo->lastInsertId();
        echo "  + {$module['display_name']} (ID: {$moduleIds[$module['name']]})\n";
    }

    // Add submodules for each new module
    echo "\nAdding submodules...\n";

    $submodules = [
        // Tasks Module
        'Tasks' => [
            ['name' => 'Tasks.MyTasks', 'display_name' => 'My Tasks', 'route' => '/tasks/my-tasks', 'icon' => 'ti-list-check', 'sort_order' => 1],
            ['name' => 'Tasks.AllTasks', 'display_name' => 'All Tasks', 'route' => '/tasks', 'icon' => 'ti-list', 'sort_order' => 2],
            ['name' => 'Tasks.CreateTask', 'display_name' => 'Create Task', 'route' => '/tasks/create', 'icon' => 'ti-plus', 'sort_order' => 3],
            ['name' => 'Tasks.Categories', 'display_name' => 'Task Categories', 'route' => '/tasks/categories', 'icon' => 'ti-category', 'sort_order' => 4],
        ],

        // Transport Module
        'Transport' => [
            ['name' => 'Transport.Overview', 'display_name' => 'Transport Overview', 'route' => '/transport/dashboard', 'icon' => 'ti-dashboard', 'sort_order' => 1],
            ['name' => 'Transport.Routes', 'display_name' => 'Routes', 'route' => '/transport/routes', 'icon' => 'ti-route', 'sort_order' => 2],
            ['name' => 'Transport.RoutePlanning', 'display_name' => 'Route Planning', 'route' => '/transport/route-planning', 'icon' => 'ti-map-pin', 'sort_order' => 3],
            ['name' => 'Transport.Vehicles', 'display_name' => 'Vehicles', 'route' => '/transport/vehicles', 'icon' => 'ti-car', 'sort_order' => 4],
            ['name' => 'Transport.Drivers', 'display_name' => 'Drivers', 'route' => '/transport/drivers', 'icon' => 'ti-steering-wheel', 'sort_order' => 5],
            ['name' => 'Transport.Students', 'display_name' => 'Student Assignments', 'route' => '/transport/students', 'icon' => 'ti-users', 'sort_order' => 6],
            ['name' => 'Transport.Tracking', 'display_name' => 'Live Tracking', 'route' => '/transport/tracking', 'icon' => 'ti-map-2', 'sort_order' => 7],
            ['name' => 'Transport.Maintenance', 'display_name' => 'Vehicle Maintenance', 'route' => '/transport/maintenance', 'icon' => 'ti-tool', 'sort_order' => 8],
        ],

        // Meals Module
        'Meals' => [
            ['name' => 'Meals.Overview', 'display_name' => 'Meals Overview', 'route' => '/meals/dashboard', 'icon' => 'ti-dashboard', 'sort_order' => 1],
            ['name' => 'Meals.MenuPlanning', 'display_name' => 'Menu Planning', 'route' => '/meals/menu-planning', 'icon' => 'ti-calendar', 'sort_order' => 2],
            ['name' => 'Meals.Menus', 'display_name' => 'Menus', 'route' => '/meals/menus', 'icon' => 'ti-file-text', 'sort_order' => 3],
            ['name' => 'Meals.Recipes', 'display_name' => 'Recipes', 'route' => '/meals/recipes', 'icon' => 'ti-book', 'sort_order' => 4],
            ['name' => 'Meals.Ingredients', 'display_name' => 'Ingredients', 'route' => '/meals/ingredients', 'icon' => 'ti-carrot', 'sort_order' => 5],
            ['name' => 'Meals.Nutrition', 'display_name' => 'Nutrition Tracking', 'route' => '/meals/nutrition', 'icon' => 'ti-heart', 'sort_order' => 6],
            ['name' => 'Meals.StudentDiets', 'display_name' => 'Student Diets', 'route' => '/meals/student-diets', 'icon' => 'ti-users', 'sort_order' => 7],
            ['name' => 'Meals.Inventory', 'display_name' => 'Kitchen Inventory', 'route' => '/meals/inventory', 'icon' => 'ti-box', 'sort_order' => 8],
        ],
    ];

    $submoduleStmt = $pdo->prepare("
        INSERT INTO submodules (module_id, name, display_name, route, icon, sort_order, is_active)
        VALUES (:module_id, :name, :display_name, :route, :icon, :sort_order, 1)
    ");

    $totalSubmodules = 0;
    foreach ($submodules as $moduleName => $items) {
        if (!isset($moduleIds[$moduleName])) {
            echo "Warning: Module '{$moduleName}' not found, skipping...\n";
            continue;
        }

        $moduleId = $moduleIds[$moduleName];
        echo "\n  {$moduleName} Module:\n";

        foreach ($items as $item) {
            $submoduleStmt->execute([
                'module_id' => $moduleId,
                'name' => $item['name'],
                'display_name' => $item['display_name'],
                'route' => $item['route'],
                'icon' => $item['icon'],
                'sort_order' => $item['sort_order'],
            ]);
            $totalSubmodules++;
            echo "    + {$item['display_name']}\n";
        }
    }

    echo "\n✓ Successfully added 3 modules and {$totalSubmodules} submodules!\n";

    // Display final module order
    echo "\n=== Final Module Order ===\n";
    $stmt = $pdo->query("SELECT sort_order, name, display_name FROM modules WHERE is_active = 1 ORDER BY sort_order");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  {$row['sort_order']}. {$row['display_name']}\n";
    }
}

// Run if executed directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    $subdomain = $argv[1] ?? 'demo';
    addNewModules($subdomain);
}
?>
