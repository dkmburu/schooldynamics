<?php
/**
 * Global Helper Functions
 */

/**
 * Escape HTML output
 */
function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Get environment variable
 */
function env($key, $default = null) {
    return Env::get($key, $default);
}

/**
 * Redirect to a URL
 */
function redirect($url, $statusCode = 302) {
    header("Location: {$url}", true, $statusCode);
    exit;
}

/**
 * Get current URL
 */
function currentUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Get base URL
 */
function baseUrl($path = '') {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . '://' . $host . '/' . ltrim($path, '/');
}

/**
 * Get asset URL
 */
function asset($path) {
    return baseUrl('assets/' . ltrim($path, '/'));
}

/**
 * Generate CSRF token
 */
function csrfToken() {
    if (!isset($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

/**
 * Generate CSRF field for forms
 */
function csrfField() {
    $token = csrfToken();
    $tokenName = env('CSRF_TOKEN_NAME', '_csrf_token');
    return '<input type="hidden" name="' . $tokenName . '" value="' . $token . '">';
}

/**
 * Verify CSRF token
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['_csrf_token']) && hash_equals($_SESSION['_csrf_token'], $token);
}

/**
 * Set flash message
 */
function flash($key, $message = null) {
    if ($message === null) {
        // Get flash message
        $value = $_SESSION["_flash_{$key}"] ?? null;
        unset($_SESSION["_flash_{$key}"]);
        return $value;
    } else {
        // Set flash message
        $_SESSION["_flash_{$key}"] = $message;
    }
}

/**
 * Get old input value
 */
function old($key, $default = '') {
    return $_SESSION['_old_input'][$key] ?? $default;
}

/**
 * Store old input
 */
function storeOldInput($data) {
    $_SESSION['_old_input'] = $data;
}

/**
 * Clear old input
 */
function clearOldInput() {
    unset($_SESSION['_old_input']);
}

/**
 * Get validation error for field
 */
function error($key) {
    return $_SESSION['_errors'][$key] ?? null;
}

/**
 * Store validation errors
 */
function storeErrors($errors) {
    $_SESSION['_errors'] = $errors;
}

/**
 * Clear validation errors
 */
function clearErrors() {
    unset($_SESSION['_errors']);
}

/**
 * Check if request is AJAX
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' ||
           !empty($_SERVER['HTTP_X_AJAX_NAVIGATION']);
}

/**
 * Format date
 */
function formatDate($date, $format = null) {
    if ($format === null) {
        $format = env('DISPLAY_DATE_FORMAT', 'd M Y');
    }
    if (empty($date)) {
        return '';
    }
    return date($format, strtotime($date));
}

/**
 * Format datetime
 */
function formatDateTime($datetime, $format = null) {
    if ($format === null) {
        $format = env('DISPLAY_DATETIME_FORMAT', 'd M Y H:i');
    }
    if (empty($datetime)) {
        return '';
    }
    return date($format, strtotime($datetime));
}

/**
 * Format money
 */
function formatMoney($amount, $currency = 'KES') {
    return $currency . ' ' . number_format($amount, 2);
}

/**
 * Check if request is AJAX
 */
function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Get request method
 */
function requestMethod() {
    return $_SERVER['REQUEST_METHOD'];
}

/**
 * Check if POST request
 */
function isPost() {
    return requestMethod() === 'POST';
}

/**
 * Check if GET request
 */
function isGet() {
    return requestMethod() === 'GET';
}

/**
 * dd - Dump and die (for debugging)
 */
function dd(...$vars) {
    echo '<pre style="background: #1e1e1e; color: #dcdcdc; padding: 20px; border-radius: 5px; margin: 20px; font-family: monospace; font-size: 14px;">';
    foreach ($vars as $var) {
        var_dump($var);
    }
    echo '</pre>';
    exit;
}

/**
 * Log message to file
 */
function logMessage($message, $level = 'info') {
    $logPath = env('STORAGE_PATH') . '/logs';
    if (!is_dir($logPath)) {
        mkdir($logPath, 0755, true);
    }

    $logFile = $logPath . '/app_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;

    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

/**
 * Get subdomain from host
 */
function getSubdomain() {
    $host = $_SERVER['HTTP_HOST'] ?? '';

    // Extract subdomain (e.g., schoolname.schooldynamics.local -> schoolname)
    $parts = explode('.', $host);

    if (count($parts) >= 3) {
        return $parts[0];
    }

    return null;
}

/**
 * Check if user is authenticated
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get authenticated user ID
 */
function authUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get authenticated user data
 */
function authUser() {
    return $_SESSION['user_data'] ?? null;
}

/**
 * Check if user has permission
 */
function hasPermission($permission) {
    $permissions = $_SESSION['user_permissions'] ?? [];
    return in_array($permission, $permissions);
}

/**
 * Get navigation modules with submodules for sidebar menu
 */
function getNavigationModules() {
    static $navigationModules = null;

    if ($navigationModules !== null) {
        return $navigationModules;
    }

    try {
        $pdo = Database::getTenantConnection();

        // Get modules with their submodules
        $stmt = $pdo->query("
            SELECT
                m.id as module_id,
                m.name as module_name,
                m.display_name as module_display_name,
                m.icon as module_icon,
                m.sort_order as module_sort_order,
                sm.id as submodule_id,
                sm.name as submodule_name,
                sm.display_name as submodule_display_name,
                sm.route as submodule_route,
                sm.icon as submodule_icon,
                sm.sort_order as submodule_sort_order
            FROM modules m
            LEFT JOIN submodules sm ON sm.module_id = m.id AND sm.is_active = 1
            WHERE m.is_active = 1
            ORDER BY m.sort_order, sm.sort_order
        ");

        $modules = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $moduleId = $row['module_id'];

            if (!isset($modules[$moduleId])) {
                $modules[$moduleId] = [
                    'id' => $row['module_id'],
                    'name' => $row['module_name'],
                    'display_name' => $row['module_display_name'],
                    'icon' => $row['module_icon'],
                    'sort_order' => $row['module_sort_order'],
                    'submodules' => []
                ];
            }

            if ($row['submodule_id']) {
                $modules[$moduleId]['submodules'][] = [
                    'id' => $row['submodule_id'],
                    'name' => $row['submodule_name'],
                    'display_name' => $row['submodule_display_name'],
                    'route' => $row['submodule_route'],
                    'icon' => $row['submodule_icon'],
                    'sort_order' => $row['submodule_sort_order']
                ];
            }
        }

        $navigationModules = array_values($modules);
        return $navigationModules;

    } catch (Exception $e) {
        logMessage("Error loading navigation: " . $e->getMessage(), 'error');
        return [];
    }
}

/**
 * Convert submodule icon to FontAwesome class
 * Maps Tabler icons (ti-*) to FontAwesome equivalents
 */
function convertSubmoduleIcon($icon) {
    if (empty($icon)) {
        return 'far fa-circle';
    }

    // If already a FontAwesome icon, return as-is
    if (strpos($icon, 'fa') === 0 || strpos($icon, 'fas ') === 0 || strpos($icon, 'far ') === 0) {
        return $icon;
    }

    // Map Tabler icons to FontAwesome
    $iconMap = [
        'ti-user-check' => 'fas fa-user-check',
        'ti-user-plus' => 'fas fa-user-plus',
        'ti-school' => 'fas fa-graduation-cap',
        'ti-book-2' => 'fas fa-book',
        'ti-book' => 'fas fa-book-open',
        'ti-clipboard-check' => 'fas fa-clipboard-check',
        'ti-dashboard' => 'fas fa-chart-pie',
        'ti-file-invoice' => 'fas fa-file-invoice',
        'ti-receipt' => 'fas fa-receipt',
        'ti-coin' => 'fas fa-coins',
        'ti-list-check' => 'fas fa-list-check',
        'ti-list' => 'fas fa-list',
        'ti-plus' => 'fas fa-plus',
        'ti-category' => 'fas fa-tags',
        'ti-route' => 'fas fa-route',
        'ti-map-pin' => 'fas fa-map-marker-alt',
        'ti-car' => 'fas fa-car',
        'ti-steering-wheel' => 'fas fa-id-card',
        'ti-users' => 'fas fa-users',
        'ti-map-2' => 'fas fa-map-location-dot',
        'ti-tool' => 'fas fa-wrench',
        'ti-calendar' => 'fas fa-calendar',
        'ti-file-text' => 'fas fa-file-lines',
        'ti-carrot' => 'fas fa-carrot',
        'ti-heart' => 'fas fa-heart',
        'ti-box' => 'fas fa-box',
        'ti-message' => 'fas fa-envelope',
        'ti-template' => 'fas fa-file-code',
        'ti-report' => 'fas fa-chart-bar',
    ];

    // Remove 'ti ' prefix if present and look up
    $iconKey = str_replace('ti ', 'ti-', $icon);

    return $iconMap[$iconKey] ?? 'far fa-circle';
}

/**
 * Convert submodule icon to Tabler Icons format
 * Used for the new Tabler-based layout
 */
function convertSubmoduleIconToTabler($icon) {
    if (empty($icon)) {
        return 'ti ti-circle';
    }

    // If already a Tabler icon with 'ti ti-' prefix, return as-is
    if (strpos($icon, 'ti ti-') === 0) {
        return $icon;
    }

    // If it's a Tabler icon with just 'ti-' prefix, add 'ti '
    if (strpos($icon, 'ti-') === 0) {
        return 'ti ' . $icon;
    }

    // Map FontAwesome icons to Tabler icons
    $faToTabler = [
        'fas fa-user-check' => 'ti ti-user-check',
        'fas fa-user-plus' => 'ti ti-user-plus',
        'fas fa-graduation-cap' => 'ti ti-school',
        'fas fa-book' => 'ti ti-book',
        'fas fa-book-open' => 'ti ti-book-2',
        'fas fa-clipboard-check' => 'ti ti-clipboard-check',
        'fas fa-chart-pie' => 'ti ti-chart-pie',
        'fas fa-file-invoice' => 'ti ti-file-invoice',
        'fas fa-receipt' => 'ti ti-receipt',
        'fas fa-coins' => 'ti ti-coin',
        'fas fa-list-check' => 'ti ti-list-check',
        'fas fa-list' => 'ti ti-list',
        'fas fa-plus' => 'ti ti-plus',
        'fas fa-tags' => 'ti ti-category',
        'fas fa-route' => 'ti ti-route',
        'fas fa-map-marker-alt' => 'ti ti-map-pin',
        'fas fa-car' => 'ti ti-car',
        'fas fa-id-card' => 'ti ti-id',
        'fas fa-users' => 'ti ti-users',
        'fas fa-map-location-dot' => 'ti ti-map-2',
        'fas fa-wrench' => 'ti ti-tool',
        'fas fa-calendar' => 'ti ti-calendar',
        'fas fa-file-lines' => 'ti ti-file-text',
        'fas fa-carrot' => 'ti ti-carrot',
        'fas fa-heart' => 'ti ti-heart',
        'fas fa-box' => 'ti ti-box',
        'fas fa-envelope' => 'ti ti-mail',
        'fas fa-file-code' => 'ti ti-file-code',
        'fas fa-chart-bar' => 'ti ti-chart-bar',
        'fas fa-cog' => 'ti ti-settings',
        'fas fa-edit' => 'ti ti-edit',
        'fas fa-trash' => 'ti ti-trash',
        'fas fa-eye' => 'ti ti-eye',
        'fas fa-search' => 'ti ti-search',
        'fas fa-filter' => 'ti ti-filter',
        'fas fa-download' => 'ti ti-download',
        'fas fa-upload' => 'ti ti-upload',
        'fas fa-print' => 'ti ti-printer',
        'fas fa-check' => 'ti ti-check',
        'fas fa-times' => 'ti ti-x',
        'fas fa-info' => 'ti ti-info-circle',
        'fas fa-warning' => 'ti ti-alert-triangle',
        'far fa-circle' => 'ti ti-circle',
    ];

    return $faToTabler[$icon] ?? 'ti ti-circle';
}

/**
 * Format status text (remove underscores, capitalize words)
 */
function formatStatus($status) {
    return ucwords(str_replace('_', ' ', $status));
}
