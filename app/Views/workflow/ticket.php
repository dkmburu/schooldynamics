<?php
$contentView = __DIR__ . '/_ticket_content.php';
$pageTitle = "Ticket: " . ($ticket->ticket_number ?? 'View');
require __DIR__ . '/../layouts/tenant.php';
?>
