<?php
$contentView = __DIR__ . '/_view_invoice_content.php';
$pageTitle = "Invoice " . ($invoice['invoice_number'] ?? '');
require __DIR__ . '/../layouts/tenant.php';
