<?php
/**
 * Quick test to verify applicants functionality
 */

// Load environment and database
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

echo "<h2>Testing Applicants Functionality</h2>";
echo "<hr>";

// Test 1: Connect to demo tenant
echo "<h3>Test 1: Connecting to Demo Tenant</h3>";
$tenant = Database::resolveTenant('demo');
if ($tenant) {
    echo "✓ Successfully connected to demo tenant<br>";
    echo "Database: {$tenant['db_name']}<br>";
} else {
    echo "✗ Failed to connect to demo tenant<br>";
    exit;
}

// Test 2: Check if tables exist
echo "<hr><h3>Test 2: Checking Tables</h3>";
$pdo = Database::getTenantConnection();
$tables = ['applicants', 'grades', 'intake_campaigns', 'applicant_contacts', 'applicant_guardians'];
foreach ($tables as $table) {
    $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Table '{$table}' exists<br>";
    } else {
        echo "✗ Table '{$table}' NOT FOUND<br>";
    }
}

// Test 3: Count applicants
echo "<hr><h3>Test 3: Counting Applicants</h3>";
$stmt = $pdo->query("SELECT COUNT(*) as total FROM applicants");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Total applicants in database: <strong>{$result['total']}</strong><br>";

// Test 4: Get sample applicants
echo "<hr><h3>Test 4: Sample Applicants</h3>";
$stmt = $pdo->query("
    SELECT a.application_ref, a.first_name, a.last_name, a.status, g.grade_name
    FROM applicants a
    LEFT JOIN grades g ON g.id = a.grade_applying_for_id
    LIMIT 5
");
$applicants = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($applicants) > 0) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Ref</th><th>Name</th><th>Grade</th><th>Status</th></tr>";
    foreach ($applicants as $app) {
        echo "<tr>";
        echo "<td>{$app['application_ref']}</td>";
        echo "<td>{$app['first_name']} {$app['last_name']}</td>";
        echo "<td>{$app['grade_name']}</td>";
        echo "<td>{$app['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No applicants found<br>";
}

// Test 5: Check status counts
echo "<hr><h3>Test 5: Status Counts</h3>";
$stmt = $pdo->query("
    SELECT status, COUNT(*) as count
    FROM applicants
    GROUP BY status
");
$statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($statuses as $status) {
    echo "{$status['status']}: <strong>{$status['count']}</strong><br>";
}

echo "<hr>";
echo "<h3>✓ All Tests Complete!</h3>";
echo "<p><a href='http://demo.schooldynamics.local/applicants' target='_blank'>→ Open Applicants Page</a></p>";
?>
