<?php
/**
 * db.php
 * Database Connection & Global Session Setup
 */

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
    die("Database connection failed. Please check your configuration.");
}

// Stateless Session Management
require_once __DIR__ . '/../utils/session_handler.php';
$handler = new PdoSessionHandler($conn);
session_set_save_handler($handler, true);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
