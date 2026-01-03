<?php
/**
 * Provision Demo Tenant
 * Creates a demo school tenant with full database setup
 */

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/create_tenant_schema.php';

Env::load();

echo "========================================\n";
echo "Demo Tenant Provisioning\n";
echo "========================================\n\n";

// Demo tenant details
$subdomain = 'demo';
$schoolName = 'Demo High School';
$dbName = 'sims_demo';
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = 'wkid2019';

echo "Tenant Details:\n";
echo "  Subdomain: {$subdomain}\n";
echo "  School Name: {$schoolName}\n";
echo "  Database: {$dbName}\n\n";

try {
    // Step 1: Create tenant database
    echo "Step 1: Creating tenant database...\n";
    $masterPdo = new PDO("mysql:host={$dbHost}", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $charset = Env::get('DB_CHARSET', 'utf8mb4');
    $collation = Env::get('DB_COLLATION', 'utf8mb4_unicode_ci');

    $masterPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}`
                      CHARACTER SET {$charset}
                      COLLATE {$collation}");
    echo "✓ Database '{$dbName}' created\n\n";

    // Step 2: Create schema in tenant database
    echo "Step 2: Creating tenant database schema...\n";
    $tenantPdo = new PDO("mysql:host={$dbHost};dbname={$dbName}", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    createTenantSchema($tenantPdo, $schoolName);

    // Step 3: Seed initial data
    echo "Step 3: Seeding initial data...\n";

    // Insert school profile
    $tenantPdo->exec("
        INSERT INTO school_profile (school_name, phone, email, currency, timezone)
        VALUES ('{$schoolName}', '+254700000000', 'info@demo.school', 'KES', 'Africa/Nairobi')
    ");
    echo "  ✓ School profile\n";

    // Insert modules
    $modules = [
        ['Dashboard', 'Dashboard', 'home', 1],
        ['Students', 'Student Management', 'users', 2],
        ['Academics', 'Academics', 'book', 3],
        ['Finance', 'Finance & Fees', 'currency-dollar', 4],
        ['Communication', 'Communication', 'mail', 5],
        ['Reports', 'Reports', 'chart-bar', 6],
        ['Settings', 'Settings', 'settings', 7]
    ];

    foreach ($modules as $i => $module) {
        $tenantPdo->exec("
            INSERT INTO modules (name, display_name, icon, sort_order)
            VALUES ('{$module[0]}', '{$module[1]}', '{$module[2]}', {$module[3]})
        ");
    }
    echo "  ✓ Modules\n";

    // Insert roles
    $roles = [
        ['ADMIN', 'System Administrator', 'Full system access', 1],
        ['HEAD_TEACHER', 'Head Teacher', 'School management access', 1],
        ['TEACHER', 'Teacher', 'Teaching and assessment access', 1],
        ['BURSAR', 'Bursar/Accountant', 'Finance and fees access', 1],
        ['CLERK', 'Clerk', 'Data entry and basic operations', 1]
    ];

    foreach ($roles as $role) {
        $tenantPdo->exec("
            INSERT INTO roles (name, display_name, description, is_system)
            VALUES ('{$role[0]}', '{$role[1]}', '{$role[2]}', {$role[3]})
        ");
    }
    echo "  ✓ Roles\n";

    // Insert default admin user
    $username = 'admin';
    $password = 'admin123';
    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    $tenantPdo->exec("
        INSERT INTO users (username, email, password_hash, full_name, employee_number, status)
        VALUES ('{$username}', 'admin@demo.school', '{$passwordHash}', 'System Administrator', 'EMP001', 'active')
    ");

    $adminUserId = $tenantPdo->lastInsertId();

    // Assign ADMIN role to user
    $tenantPdo->exec("
        INSERT INTO user_roles (user_id, role_id)
        VALUES ({$adminUserId}, 1)
    ");
    echo "  ✓ Default admin user (admin / admin123)\n";

    // Insert academic year
    $currentYear = date('Y');
    $tenantPdo->exec("
        INSERT INTO academic_years (year_name, start_date, end_date, is_current)
        VALUES ('{$currentYear}', '{$currentYear}-01-01', '{$currentYear}-12-31', 1)
    ");
    echo "  ✓ Academic year\n";

    // Insert sample classes
    $classes = ['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6'];
    foreach ($classes as $i => $className) {
        $tenantPdo->exec("
            INSERT INTO classes (class_name, level, capacity)
            VALUES ('{$className}', " . ($i + 1) . ", 40)
        ");
    }
    echo "  ✓ Sample classes\n";

    echo "\n";

    // Step 4: Register tenant in router database
    echo "Step 4: Registering tenant in router database...\n";
    $routerPdo = Database::getRouterConnection();

    $encryptedPassword = Database::encryptPassword($dbPass);

    $stmt = $routerPdo->prepare("
        INSERT INTO tenants (subdomain, school_name, db_host, db_name, db_user, db_pass_enc, status, plan_tier, max_users, max_students)
        VALUES (:subdomain, :school_name, :db_host, :db_name, :db_user, :db_pass_enc, 'active', 'standard', 100, 1000)
        ON DUPLICATE KEY UPDATE
            school_name = VALUES(school_name),
            db_name = VALUES(db_name)
    ");

    $stmt->execute([
        'subdomain' => $subdomain,
        'school_name' => $schoolName,
        'db_host' => $dbHost,
        'db_name' => $dbName,
        'db_user' => $dbUser,
        'db_pass_enc' => $encryptedPassword
    ]);

    echo "✓ Tenant registered in router database\n\n";

    echo "========================================\n";
    echo "✓ Demo Tenant Provisioned Successfully!\n";
    echo "========================================\n\n";

    echo "Access Details:\n";
    echo "  URL: http://{$subdomain}.schooldynamics.local\n";
    echo "  Username: {$username}\n";
    echo "  Password: {$password}\n";
    echo "  Database: {$dbName}\n\n";

    echo "⚠️  Add to your hosts file:\n";
    echo "  127.0.0.1 {$subdomain}.schooldynamics.local\n\n";

} catch (PDOException $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
