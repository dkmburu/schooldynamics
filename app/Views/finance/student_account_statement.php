<?php
/**
 * Student Account Statement - Wrapper
 */
$contentView = __DIR__ . '/_student_account_statement_content.php';
$name = ($account['student_first_name'] ?? $account['applicant_first_name']) . ' ' . ($account['student_last_name'] ?? $account['applicant_last_name']);
$pageTitle = 'Account Statement - ' . $name;
require __DIR__ . '/../layouts/tenant.php';
