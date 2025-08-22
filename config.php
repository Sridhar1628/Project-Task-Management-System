<?php
/**
 * Safe config loader using a simple .env file.
 * .env format:
 *   KEY=value
 *   (no quotes needed; no spaces around '=')
 */

function loadEnv($path)
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(ltrim($line), '#') === 0) continue;

        // Split KEY=VALUE
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) continue;

        $key = trim($parts[0]);
        $value = trim($parts[1]);

        // Strip surrounding quotes if present
        if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
            (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
            $value = substr($value, 1, -1);
        }

        // Set in environment for getenv()
        if (!getenv($key)) {
            putenv("$key=$value");
        }
        // Also define constants for legacy code if needed
        if (!defined($key)) {
            define($key, $value);
        }
    }
}

// Load .env from project root
loadEnv(__DIR__ . '/.env');

// Fallbacks (optional)
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'sridhar');
if (!defined('ADMIN_SECRET')) define('ADMIN_SECRET', 'changeme');

// Safer connection function (don’t echo raw errors in production)
function getMySQLConnection()
{
    $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        // Log internally if you have a logger; show generic message to users
        die("Database connection failed.");
    }
    // Optional: set charset
    $conn->set_charset('utf8mb4');
    return $conn;
}
$conn = getMySQLConnection(); // Initialize connection for use in other scripts