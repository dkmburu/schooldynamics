<?php
$contentView = __DIR__ . '/_view_payment_content.php';
$pageTitle = "Receipt " . ($payment['receipt_number'] ?? '');
require __DIR__ . '/../layouts/tenant.php';
