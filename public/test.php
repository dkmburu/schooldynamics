<?php
// Simple test file to verify PHP is working
echo "PHP is working!<br>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "<br>";
echo "HTTP Host: " . $_SERVER['HTTP_HOST'] . "<br>";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "<br>";
echo "<br>";
echo "If you see this, PHP is working correctly!<br>";
echo "<br>";

// Test if files exist
$indexFile = __DIR__ . '/index.php';
$htaccessFile = __DIR__ . '/.htaccess';

echo "index.php exists: " . (file_exists($indexFile) ? 'YES' : 'NO') . "<br>";
echo "index.php path: " . $indexFile . "<br>";
echo ".htaccess exists: " . (file_exists($htaccessFile) ? 'YES' : 'NO') . "<br>";
echo ".htaccess path: " . $htaccessFile . "<br>";

if (file_exists($htaccessFile)) {
    echo "<br>.htaccess contents:<br><pre>";
    echo htmlspecialchars(file_get_contents($htaccessFile));
    echo "</pre>";
}

// Test mod_rewrite
echo "<br>Testing mod_rewrite...<br>";
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    echo "mod_rewrite loaded: " . (in_array('mod_rewrite', $modules) ? 'YES' : 'NO') . "<br>";
} else {
    echo "Cannot check mod_rewrite (apache_get_modules not available)<br>";
}

phpinfo();
