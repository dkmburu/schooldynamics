<?php
/**
 * Environment Configuration Loader
 * Loads and parses .env file
 */

class Env
{
    private static $config = [];
    private static $loaded = false;

    /**
     * Load environment variables from .env file
     */
    public static function load($envPath = null)
    {
        if (self::$loaded) {
            return;
        }

        if ($envPath === null) {
            $envPath = dirname(__DIR__) . '/.env';
        }

        if (!file_exists($envPath)) {
            throw new Exception(".env file not found at: {$envPath}");
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        // First pass: load all values
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parse KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remove quotes if present
                if (preg_match('/^"(.*)"$/', $value, $matches)) {
                    $value = $matches[1];
                } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
                    $value = $matches[1];
                }

                self::$config[$key] = $value;
            }
        }

        // Mark as loaded before doing substitutions to prevent infinite loop
        self::$loaded = true;

        // Second pass: handle variable substitutions
        foreach (self::$config as $key => $value) {
            if (strpos($value, '${') !== false) {
                $value = preg_replace_callback('/\$\{([A-Z_]+)\}/', function($matches) {
                    return isset(self::$config[$matches[1]]) ? self::$config[$matches[1]] : '';
                }, $value);
                self::$config[$key] = $value;
            }

            // Also set as system environment variable
            if (!getenv($key)) {
                putenv("{$key}={$value}");
            }
        }
    }

    /**
     * Get environment variable
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        if (!self::$loaded) {
            self::load();
        }

        if (isset(self::$config[$key])) {
            return self::$config[$key];
        }

        // Fallback to system environment
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }

        return $default;
    }

    /**
     * Get environment variable as boolean
     */
    public static function getBool($key, $default = false)
    {
        $value = self::get($key, $default);

        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower($value), ['true', '1', 'yes', 'on'], true);
    }

    /**
     * Get environment variable as integer
     */
    public static function getInt($key, $default = 0)
    {
        return (int) self::get($key, $default);
    }

    /**
     * Check if environment is production
     */
    public static function isProduction()
    {
        return self::get('APP_ENV') === 'production';
    }

    /**
     * Check if debug mode is enabled
     */
    public static function isDebug()
    {
        return self::getBool('APP_DEBUG', false);
    }

    /**
     * Get all configuration
     */
    public static function all()
    {
        if (!self::$loaded) {
            self::load();
        }

        return self::$config;
    }
}
