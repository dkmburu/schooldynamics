<?php
/**
 * Budgets View Wrapper
 * Sets content view and requires tenant layout
 */

$contentView = __DIR__ . '/_budgets_content.php';
$pageTitle = $pageTitle ?? "Budget Management";

require __DIR__ . '/../layouts/tenant.php';
