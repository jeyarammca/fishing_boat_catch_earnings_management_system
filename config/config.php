<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'fishing_management');

// Site Configuration
define('SITE_URL', 'http://localhost/fishing_boat_catch_earnings_management_system/');
define('SITE_NAME', 'Fishing Boat Catch & Earnings Management');
define('SITE_SHORT_NAME', 'Fishing Management');

// Session Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour
define('REMEMBER_ME_DURATION', 604800); // 7 days

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8");

// Function to escape strings for MySQL
function escape_string($string) {
    global $conn;
    return $conn->real_escape_string($string);
}

// Function to execute query
function execute_query($query) {
    global $conn;
    $result = $conn->query($query);
    if (!$result) {
        die("Query Error: " . $conn->error);
    }
    return $result;
}

// Function to get last insert ID
function get_last_id() {
    global $conn;
    return $conn->insert_id;
}

// Function to get affected rows
function get_affected_rows() {
    global $conn;
    return $conn->affected_rows;
}

// Close function
function close_connection() {
    global $conn;
    $conn->close();
}

// Automatic session management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
define('IS_LOGGED_IN', isset($_SESSION['user_id']));

// Multilingual support: load language from session or default to English
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

// Allow language switching via GET parameter (e.g. ?lang=ta)
$allowed_langs = ['en','ta','ml','kn','hi'];
if (isset($_GET['lang']) && in_array($_GET['lang'], $allowed_langs)) {
    $_SESSION['lang'] = $_GET['lang'];
}

function load_language($code = 'en') {
    $file = __DIR__ . '/../lang/' . $code . '.php';
    if (file_exists($file)) {
        return include $file;
    }
    // fallback to English
    $file = __DIR__ . '/../lang/en.php';
    return file_exists($file) ? include $file : [];
}

$LANG = load_language($_SESSION['lang']);

function __($key) {
    global $LANG;
    return isset($LANG[$key]) ? $LANG[$key] : $key;
}

// CSRF helpers
function generate_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token($token) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($token) || empty($_SESSION['csrf_token'])) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_input() {
    $t = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($t) . '">';
}

?>
