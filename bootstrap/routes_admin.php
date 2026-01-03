<?php
/**
 * Main Admin Routes
 * Routes for the central administration portal
 */

// Main Admin Login
Router::get('/', function() {
    Response::view('admin.login');
});

Router::get('/login', function() {
    Response::view('admin.login');
});

Router::post('/login', 'AdminAuthController@login');

Router::get('/logout', 'AdminAuthController@logout');

// Main Admin Dashboard (requires authentication)
Router::get('/dashboard', 'AdminDashboardController@index');

// Tenant Management
Router::get('/tenants', 'AdminTenantsController@index');
Router::get('/tenants/create', 'AdminTenantsController@create');
Router::post('/tenants/create', 'AdminTenantsController@store');
Router::get('/tenants/:id/edit', 'AdminTenantsController@edit');
Router::post('/tenants/:id/edit', 'AdminTenantsController@update');
Router::post('/tenants/:id/toggle-status', 'AdminTenantsController@toggleStatus');
Router::post('/tenants/:id/toggle-maintenance', 'AdminTenantsController@toggleMaintenance');

// Tenant Metrics
Router::get('/metrics', 'AdminMetricsController@index');
Router::get('/metrics/:id', 'AdminMetricsController@show');

// Admin Users
Router::get('/users', 'AdminUsersController@index');
Router::get('/users/create', 'AdminUsersController@create');
Router::post('/users/create', 'AdminUsersController@store');

// Audit Logs
Router::get('/audit-logs', 'AdminAuditController@index');
