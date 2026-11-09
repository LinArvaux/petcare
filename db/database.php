<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'petcare_ai');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PORT', 3306);

function get_db_connection(): mysqli {
    static $conn = null;
    if ($conn !== null) return $conn;

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        $conn->set_charset('utf8mb4');
    } catch (mysqli_sql_exception $e) {
        error_log('DB connection failed: ' . $e->getMessage());
        http_response_code(500);
        die('Database connection failed. Please try again later.');
    }
    return $conn;
}