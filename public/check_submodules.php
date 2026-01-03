<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

$tenant = Database::resolveTenant('demo');
$pdo = Database::getTenantConnection();

echo "<h2>Submodules Table Structure</h2>";
$stmt = $pdo->query("DESCRIBE submodules");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>{$row['Field']}</td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>{$row['Default']}</td>";
    echo "<td>{$row['Extra']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr><h2>Current Submodules</h2>";
$stmt = $pdo->query("
    SELECT sm.id, sm.module_id, m.name as module_name, sm.name, sm.display_name, sm.route, sm.icon, sm.sort_order
    FROM submodules sm
    LEFT JOIN modules m ON m.id = sm.module_id
    WHERE sm.is_active = 1
    ORDER BY sm.module_id, sm.sort_order
");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Module</th><th>Name</th><th>Display Name</th><th>Route</th><th>Icon</th><th>Sort</th></tr>";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['module_name']}</td>";
    echo "<td>{$row['name']}</td>";
    echo "<td>{$row['display_name']}</td>";
    echo "<td>{$row['route']}</td>";
    echo "<td>{$row['icon']}</td>";
    echo "<td>{$row['sort_order']}</td>";
    echo "</tr>";
}
echo "</table>";
?>
