<?php
/**
 * Verify Database Installation
 * Check if router DB and tables exist
 */

require_once __DIR__ . '/../config/env.php';
Env::load();

echo "========================================\n";
echo "Database Verification\n";
echo "========================================\n\n";

$host = Env::get('ROUTER_DB_HOST', 'localhost');
$port = Env::get('ROUTER_DB_PORT', '3306');
$user = Env::get('ROUTER_DB_USER', 'root');
$pass = Env::get('ROUTER_DB_PASS', '');

try {
    // Connect to MySQL server without database
    $dsn = "mysql:host={$host};port={$port}";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "✓ Connected to MySQL Server\n";
    echo "  Host: {$host}:{$port}\n";
    echo "  User: {$user}\n\n";

    // Check if sims_router database exists
    $result = $pdo->query("SHOW DATABASES LIKE 'sims_router'")->fetchAll();

    if (count($result) > 0) {
        echo "✓ Database 'sims_router' EXISTS\n\n";

        // Connect to the database
        $pdo->exec("USE sims_router");

        // List all tables
        echo "Tables in sims_router:\n";
        echo "------------------------\n";
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

        if (count($tables) > 0) {
            foreach ($tables as $table) {
                // Get row count
                $count = $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
                echo "  - {$table} ({$count} rows)\n";
            }
        } else {
            echo "  No tables found!\n";
        }

        echo "\n";

        // Check main_admin_users
        if (in_array('main_admin_users', $tables)) {
            echo "Main Admin Users:\n";
            echo "------------------------\n";
            $admins = $pdo->query("SELECT id, username, email, full_name, status FROM main_admin_users")->fetchAll();
            foreach ($admins as $admin) {
                echo "  ID: {$admin['id']}\n";
                echo "  Username: {$admin['username']}\n";
                echo "  Email: {$admin['email']}\n";
                echo "  Name: {$admin['full_name']}\n";
                echo "  Status: {$admin['status']}\n";
                echo "\n";
            }
        }

        // Check tenants
        if (in_array('tenants', $tables)) {
            echo "Tenants:\n";
            echo "------------------------\n";
            $tenants = $pdo->query("SELECT id, subdomain, school_name, status FROM tenants")->fetchAll();
            if (count($tenants) > 0) {
                foreach ($tenants as $tenant) {
                    echo "  - {$tenant['subdomain']} ({$tenant['school_name']}) - {$tenant['status']}\n";
                }
            } else {
                echo "  No tenants configured yet.\n";
            }
            echo "\n";
        }

    } else {
        echo "❌ Database 'sims_router' DOES NOT EXIST\n\n";
        echo "All databases on this server:\n";
        echo "------------------------\n";
        $databases = $pdo->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($databases as $db) {
            echo "  - {$db}\n";
        }
    }

    echo "\n========================================\n";
    echo "Verification Complete\n";
    echo "========================================\n";

} catch (PDOException $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "\nConnection Details:\n";
    echo "  Host: {$host}:{$port}\n";
    echo "  User: {$user}\n";
    echo "  Password: " . (empty($pass) ? '(empty)' : '(set)') . "\n";
    exit(1);
}
