<?php
session_start();
define('DB_HOST', 'localhost');
define('DB_NAME', 'Thmako_System'); // Use the new database name
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Use native prepared statements
];
try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // For development, show error. For production, log error and show a generic message.
    error_log("Database Connection Failed: " . $e->getMessage());
    die("មានបញ្ហាក្នុងការភ្ជាប់ទៅកាន់មូលដ្ឋានទិន្នន័យ។ សូមព្យាយាមម្តងទៀតនៅពេលក្រោយ។");
}

// --- Helper function to check if user is logged in ---
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// --- Redirect user if not logged in ---
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}
?>
