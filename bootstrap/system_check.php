<?php
/**
 * System Health Check
 * Verifies all components are properly set up
 */

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

Env::load();

echo "========================================\n";
echo "SchoolDynamics System Health Check\n";
echo "========================================\n\n";

$checks = [];
$errors = [];

// 1. Check PHP version
echo "1. Checking PHP version...\n";
$phpVersion = PHP_VERSION;
if (version_compare($phpVersion, '7.4.0') >= 0) {
    echo "   ✓ PHP {$phpVersion}\n";
    $checks[] = 'php';
} else {
    echo "   ✗ PHP {$phpVersion} (Requires 7.4+)\n";
    $errors[] = 'PHP version too old';
}

// 2. Check .env file
echo "2. Checking .env file...\n";
if (file_exists(__DIR__ . '/../.env')) {
    echo "   ✓ .env file exists\n";
    $checks[] = 'env';
} else {
    echo "   ✗ .env file missing\n";
    $errors[] = '.env file not found';
}

// 3. Check Router DB connection
echo "3. Checking Router database...\n";
try {
    $pdo = Database::getRouterConnection();
    echo "   ✓ Connected to Router DB\n";

    // Check tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $requiredTables = ['tenants', 'main_admin_users', 'tenant_metrics', 'router_audit_logs'];
    $missingTables = array_diff($requiredTables, $tables);

    if (empty($missingTables)) {
        echo "   ✓ All Router tables exist\n";
        $checks[] = 'router_db';
    } else {
        echo "   ✗ Missing tables: " . implode(', ', $missingTables) . "\n";
        $errors[] = 'Router DB tables missing';
    }

    // Check admin user
    $stmt = $pdo->query("SELECT COUNT(*) FROM main_admin_users WHERE status = 'active'");
    $adminCount = $stmt->fetchColumn();
    if ($adminCount > 0) {
        echo "   ✓ Admin user exists ({$adminCount} users)\n";
        $checks[] = 'admin_user';
    } else {
        echo "   ✗ No admin users found\n";
        $errors[] = 'No admin users';
    }

    // Check tenants
    $stmt = $pdo->query("SELECT COUNT(*) FROM tenants");
    $tenantCount = $stmt->fetchColumn();
    echo "   ✓ Tenants registered: {$tenantCount}\n";
    $checks[] = 'tenants';

} catch (Exception $e) {
    echo "   ✗ Router DB error: " . $e->getMessage() . "\n";
    $errors[] = 'Router DB connection failed';
}

// 4. Check demo tenant
echo "4. Checking demo tenant...\n";
try {
    $resolved = Database::resolveTenant('demo');
    if ($resolved === true) {
        echo "   ✓ Demo tenant resolved\n";

        $pdo = Database::getTenantConnection();
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        echo "   ✓ Demo tenant DB has " . count($tables) . " tables\n";

        // Check key tables
        $keyTables = ['users', 'students', 'roles', 'modules'];
        $missingTables = array_diff($keyTables, $tables);

        if (empty($missingTables)) {
            echo "   ✓ All key tables exist\n";
            $checks[] = 'tenant_db';
        } else {
            echo "   ✗ Missing tables: " . implode(', ', $missingTables) . "\n";
            $errors[] = 'Tenant DB tables missing';
        }

        // Check tenant user
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'");
        $userCount = $stmt->fetchColumn();
        if ($userCount > 0) {
            echo "   ✓ Tenant users exist ({$userCount} users)\n";
            $checks[] = 'tenant_user';
        }

    } else {
        echo "   ✗ Demo tenant not found or in maintenance\n";
        $errors[] = 'Demo tenant not available';
    }
} catch (Exception $e) {
    echo "   ✗ Demo tenant error: " . $e->getMessage() . "\n";
    $errors[] = 'Demo tenant check failed';
}

// 5. Check file permissions
echo "5. Checking file permissions...\n";
$storageDir = __DIR__ . '/../storage';
if (is_writable($storageDir)) {
    echo "   ✓ Storage directory writable\n";
    $checks[] = 'storage';
} else {
    echo "   ✗ Storage directory not writable\n";
    $errors[] = 'Storage directory permissions';
}

// 6. Check required directories
echo "6. Checking required directories...\n";
$requiredDirs = [
    'storage/logs',
    'storage/uploads',
    'storage/cache',
    'storage/sessions'
];

$allDirsExist = true;
foreach ($requiredDirs as $dir) {
    $fullPath = __DIR__ . '/../' . $dir;
    if (!is_dir($fullPath)) {
        echo "   ✗ Missing: {$dir}\n";
        $allDirsExist = false;
        mkdir($fullPath, 0755, true);
        echo "      Created: {$dir}\n";
    }
}

if ($allDirsExist) {
    echo "   ✓ All required directories exist\n";
}
$checks[] = 'directories';

// 7. Check configuration
echo "7. Checking configuration...\n";
$secureKey = Env::get('SECURE_KEY');
if (!empty($secureKey) && $secureKey !== 'your-secure-key-here') {
    echo "   ✓ SECURE_KEY is set\n";
    $checks[] = 'secure_key';
} else {
    echo "   ⚠ SECURE_KEY needs to be changed for production\n";
}

echo "\n========================================\n";
echo "Summary\n";
echo "========================================\n";
echo "Checks passed: " . count($checks) . "\n";
echo "Errors found: " . count($errors) . "\n";

if (empty($errors)) {
    echo "\n✓ System is ready!\n\n";
    echo "Access URLs:\n";
    echo "  Main Admin: http://admin.schooldynamics.local\n";
    echo "  Demo School: http://demo.schooldynamics.local\n\n";
    echo "Default Login:\n";
    echo "  Username: admin\n";
    echo "  Password: admin123\n\n";
} else {
    echo "\n✗ Issues found:\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
    echo "\nRun installation scripts:\n";
    echo "  php bootstrap/install_router_db.php\n";
    echo "  php bootstrap/provision_demo_tenant.php\n";
}

echo "========================================\n";
