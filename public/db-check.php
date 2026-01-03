<?php
/**
 * Quick Database Checker
 * Access via: http://localhost/schooldynamics/public/db-check.php
 */

$host = 'localhost';
$user = 'root';
$pass = 'wkid2019';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Checker</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #007bff; color: white; }
        tr:hover { background: #f8f9fa; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; }
        .badge-success { background: #28a745; color: white; }
        .badge-warning { background: #ffc107; color: #000; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗄️ SchoolDynamics Database Checker</h1>

        <?php
        try {
            $pdo = new PDO("mysql:host={$host}", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);

            echo '<p class="success">✓ Connected to MySQL Server</p>';

            // List all databases
            echo '<h2>All Databases</h2>';
            echo '<table>';
            echo '<tr><th>Database Name</th><th>Status</th></tr>';

            $databases = $pdo->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($databases as $db) {
                $highlight = ($db === 'sims_router') ? ' style="background: #d4edda;"' : '';
                $badge = ($db === 'sims_router') ? '<span class="badge badge-success">Router DB</span>' : '';
                echo "<tr{$highlight}><td><strong>{$db}</strong></td><td>{$badge}</td></tr>";
            }
            echo '</table>';

            // Check sims_router specifically
            if (in_array('sims_router', $databases)) {
                echo '<h2 class="success">✓ sims_router Database Found!</h2>';

                $pdo->exec("USE sims_router");

                echo '<h3>Tables in sims_router</h3>';
                echo '<table>';
                echo '<tr><th>Table Name</th><th>Rows</th><th>Created</th></tr>';

                $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($tables as $table) {
                    $count = $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
                    $status = $pdo->query("SHOW TABLE STATUS LIKE '{$table}'")->fetch();
                    echo "<tr>";
                    echo "<td><strong>{$table}</strong></td>";
                    echo "<td><span class='badge badge-warning'>{$count}</span></td>";
                    echo "<td>" . ($status['Create_time'] ?? 'N/A') . "</td>";
                    echo "</tr>";
                }
                echo '</table>';

                // Show main_admin_users
                echo '<h3>Main Admin Users</h3>';
                $admins = $pdo->query("SELECT * FROM main_admin_users")->fetchAll();
                if ($admins) {
                    echo '<table>';
                    echo '<tr><th>ID</th><th>Username</th><th>Email</th><th>Full Name</th><th>Status</th></tr>';
                    foreach ($admins as $admin) {
                        echo "<tr>";
                        echo "<td>{$admin['id']}</td>";
                        echo "<td><strong>{$admin['username']}</strong></td>";
                        echo "<td>{$admin['email']}</td>";
                        echo "<td>{$admin['full_name']}</td>";
                        echo "<td><span class='badge badge-success'>{$admin['status']}</span></td>";
                        echo "</tr>";
                    }
                    echo '</table>';
                    echo '<p><strong>Default Login:</strong> admin / admin123</p>';
                }

            } else {
                echo '<h2 class="error">❌ sims_router Database NOT Found!</h2>';
                echo '<p>Run the installation script:</p>';
                echo '<pre>php bootstrap/install_router_db.php</pre>';
            }

        } catch (PDOException $e) {
            echo '<p class="error">❌ Database Connection Error:</p>';
            echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        }
        ?>

        <hr style="margin: 30px 0;">
        <p><small>MySQL Connection: <?= $host ?> | User: <?= $user ?></small></p>
        <p><a href="<?= $_SERVER['PHP_SELF'] ?>">Refresh</a></p>
    </div>
</body>
</html>
