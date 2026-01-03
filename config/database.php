<?php
/**
 * Database Configuration
 * Handles Router DB and Tenant DB connections
 */

require_once __DIR__ . '/env.php';

class Database
{
    private static $routerConnection = null;
    private static $tenantConnection = null;
    private static $currentTenant = null;

    /**
     * Get Router Database connection
     * This is the central database that maps subdomains to tenant databases
     */
    public static function getRouterConnection()
    {
        if (self::$routerConnection === null) {
            $host = Env::get('ROUTER_DB_HOST', 'localhost');
            $port = Env::get('ROUTER_DB_PORT', '3306');
            $dbname = Env::get('ROUTER_DB_NAME', 'sims_router');
            $user = Env::get('ROUTER_DB_USER', 'root');
            $pass = Env::get('ROUTER_DB_PASS', '');
            $charset = Env::get('DB_CHARSET', 'utf8mb4');

            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

            try {
                self::$routerConnection = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE " . Env::get('DB_COLLATION', 'utf8mb4_unicode_ci')
                ]);
            } catch (PDOException $e) {
                self::logError("Router DB connection failed: " . $e->getMessage());
                throw new Exception("Unable to connect to router database. Please contact support.");
            }
        }

        return self::$routerConnection;
    }

    /**
     * Resolve tenant from subdomain and establish connection
     *
     * @param string $subdomain
     * @return bool
     */
    public static function resolveTenant($subdomain)
    {
        $routerDb = self::getRouterConnection();

        // Query router DB for tenant credentials
        $stmt = $routerDb->prepare("
            SELECT id, subdomain, db_host, db_name, db_user, db_pass_enc, status,
                   branding_json, maintenance_mode
            FROM tenants
            WHERE subdomain = :subdomain AND status = 'active'
            LIMIT 1
        ");

        $stmt->execute(['subdomain' => $subdomain]);
        $tenant = $stmt->fetch();

        if (!$tenant) {
            self::logError("Tenant not found for subdomain: {$subdomain}");
            return false;
        }

        // Check maintenance mode
        if ($tenant['maintenance_mode'] == 1) {
            self::$currentTenant = $tenant;
            return 'maintenance';
        }

        // Decrypt password
        $dbPass = self::decryptPassword($tenant['db_pass_enc']);

        // Establish tenant DB connection
        try {
            $charset = Env::get('DB_CHARSET', 'utf8mb4');
            $dsn = "mysql:host={$tenant['db_host']};dbname={$tenant['db_name']};charset={$charset}";

            self::$tenantConnection = new PDO($dsn, $tenant['db_user'], $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE " . Env::get('DB_COLLATION', 'utf8mb4_unicode_ci')
            ]);

            self::$currentTenant = $tenant;
            return true;

        } catch (PDOException $e) {
            self::logError("Tenant DB connection failed for {$subdomain}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get Tenant Database connection
     */
    public static function getTenantConnection()
    {
        if (self::$tenantConnection === null) {
            throw new Exception("No tenant connection established. Call resolveTenant() first.");
        }

        return self::$tenantConnection;
    }

    /**
     * Get current tenant information
     */
    public static function getCurrentTenant()
    {
        return self::$currentTenant;
    }

    /**
     * Encrypt password for storage in router DB
     */
    public static function encryptPassword($password)
    {
        $key = Env::get('SECURE_KEY');
        if (empty($key)) {
            throw new Exception("SECURE_KEY not configured in .env");
        }

        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($password, 'aes-256-cbc', $key, 0, $iv);

        return base64_encode($iv . '::' . $encrypted);
    }

    /**
     * Decrypt password from router DB
     */
    private static function decryptPassword($encryptedPassword)
    {
        $key = Env::get('SECURE_KEY');
        if (empty($key)) {
            throw new Exception("SECURE_KEY not configured in .env");
        }

        $decoded = base64_decode($encryptedPassword);
        list($iv, $encrypted) = explode('::', $decoded, 2);

        return openssl_decrypt($encrypted, 'aes-256-cbc', $key, 0, $iv);
    }

    /**
     * Log database errors
     */
    private static function logError($message)
    {
        $logPath = Env::get('STORAGE_PATH') . '/logs';
        if (!is_dir($logPath)) {
            mkdir($logPath, 0755, true);
        }

        $logFile = $logPath . '/db_errors_' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;

        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }

    /**
     * Begin transaction on tenant DB
     */
    public static function beginTransaction()
    {
        return self::getTenantConnection()->beginTransaction();
    }

    /**
     * Commit transaction on tenant DB
     */
    public static function commit()
    {
        return self::getTenantConnection()->commit();
    }

    /**
     * Rollback transaction on tenant DB
     */
    public static function rollback()
    {
        return self::getTenantConnection()->rollBack();
    }

    /**
     * Close all connections
     */
    public static function closeConnections()
    {
        self::$routerConnection = null;
        self::$tenantConnection = null;
        self::$currentTenant = null;
    }
}
