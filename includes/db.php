<?php
/**
 * db.php
 * Database Connection & Global Session Setup
 */

// --- LOCAL DEVELOPMENT HUB (.env loader) ---
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || !strpos($line, '=')) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value, " \t\n\r\0\x0B\"");
        putenv(sprintf('%s=%s', $name, $value));
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}
// --------------------------------------------

$db_url = getenv('DATABASE_URL');

if (!$db_url) {
    // Standard Vercel Environment Variable check
    die("DATABASE_URL environment variable is not set.");
}

// Parse DATABASE_URL
$parts = parse_url($db_url);
if (!$parts) {
    die("Invalid DATABASE_URL format.");
}

$host = $parts['host'];
$port = 6543; // Using Supabase Transaction Pooler port for serverless
$dbname = ltrim($parts['path'], '/');
$user = $parts['user'];
$pass = $parts['pass'];

// DSN with sslmode=require as requested
$dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";

try {
    $conn = new PDO($dsn, $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Disable emulated prepares for security and performance
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Stateless Session Management
if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../utils/session_handler.php';
    $handler = new PdoSessionHandler($conn);
    session_set_save_handler($handler, true);
    session_start();
}
