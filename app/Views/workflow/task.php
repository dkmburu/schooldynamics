<?php
$contentView = __DIR__ . '/_task_content.php';
$pageTitle = "Task: " . ($task->task_number ?? 'View');
require __DIR__ . '/../layouts/tenant.php';
?>
