<?php
/**
 * SchoolDynamics SIMS - Front Controller
 * All requests are routed through this file
 */

// Bootstrap the application
require_once __DIR__ . '/../bootstrap/app.php';

// Load environment
Env::load();

// Resolve tenant from subdomain
$subdomain = getSubdomain();

if (!$subdomain) {
    // No subdomain - show landing page or redirect to main admin
    http_response_code(404);
    echo "<h1>SchoolDynamics SIMS</h1>";
    echo "<p>Please access your school using: <strong>yourschool.schooldynamics.local</strong></p>";
    echo "<p>Main Admin: <a href='/admin'>Admin Portal</a></p>";
    exit;
}

// Check if this is main admin subdomain
if ($subdomain === 'admin' || $subdomain === 'mainadmin') {
    // Route to main admin portal
    require_once __DIR__ . '/../bootstrap/routes_admin.php';
    Router::dispatch();
    exit;
}

// Resolve tenant database
$tenantResolved = Database::resolveTenant($subdomain);

if ($tenantResolved === false) {
    http_response_code(404);
    echo "<h1>School Not Found</h1>";
    echo "<p>The school <strong>{$subdomain}</strong> was not found in our system.</p>";
    echo "<p>Please check the URL and try again.</p>";
    exit;
}

if ($tenantResolved === 'maintenance') {
    http_response_code(503);
    $tenant = Database::getCurrentTenant();
    echo "<h1>Maintenance Mode</h1>";
    echo "<p><strong>{$tenant['school_name']}</strong> is currently undergoing maintenance.</p>";
    echo "<p>Please check back later.</p>";
    exit;
}

// Tenant resolved successfully - load tenant routes
require_once __DIR__ . '/../bootstrap/routes_tenant.php';

// Dispatch the request
Router::dispatch();
