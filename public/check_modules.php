<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

$tenant = Database::resolveTenant('demo');
$pdo = Database::getTenantConnection();

echo "<h2>Current Modules</h2>";
$stmt = $pdo->query("SELECT id, name, display_name, icon, sort_order, is_active FROM modules ORDER BY sort_order");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Name</th><th>Display Name</th><th>Icon</th><th>Sort</th><th>Active</th></tr>";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['name']}</td>";
    echo "<td>{$row['display_name']}</td>";
    echo "<td>{$row['icon']}</td>";
    echo "<td>{$row['sort_order']}</td>";
    echo "<td>{$row['is_active']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr><h2>Submodules by Module</h2>";
$stmt = $pdo->query("
    SELECT m.display_name as module_name, sm.name, sm.display_name, sm.route, sm.icon
    FROM submodules sm
    JOIN modules m ON m.id = sm.module_id
    WHERE sm.is_active = 1
    ORDER BY m.sort_order, sm.sort_order
");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Module</th><th>Name</th><th>Display Name</th><th>Route</th><th>Icon</th></tr>";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>{$row['module_name']}</td>";
    echo "<td>{$row['name']}</td>";
    echo "<td>{$row['display_name']}</td>";
    echo "<td>{$row['route']}</td>";
    echo "<td>{$row['icon']}</td>";
    echo "</tr>";
}
echo "</table>";
?>
