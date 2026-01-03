<?php
require_once __DIR__ . '/../config/env.php';
Env::load();

$pdo = new PDO("mysql:host=localhost;dbname=sims_router", "root", "wkid2019", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$result = $pdo->query("SHOW CREATE TABLE tenants")->fetch();
echo $result['Create Table'] . "\n";
