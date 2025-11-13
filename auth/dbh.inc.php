<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Manila');

function db(): mysqli
{
    static $conn = null;

    if ($conn instanceof mysqli && @$conn->ping()) {
        return $conn;
    }

    // Azure MySQL connection settings
    $host = getenv('DB_HOST') ?: 'mcismartspacedb.mysql.database.azure.com';
    $user = getenv('DB_USER') ?: 'mcismartspace'; 
    $pass = getenv('DB_PASS') ?: 'Mcismartspace42';
    $name = getenv('DB_NAME') ?: 'smartspace';
    $port = 3306;

    // No SSL certificate for now - try a direct connection
    
    $conn = mysqli_init();
    if (!$conn) {
        die('mysqli_init failed');
    }

    // Disable SSL verification - crucial for Azure MySQL connections
    if (!mysqli_options($conn, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false)) {
        error_log("Failed to set MYSQLI_OPT_SSL_VERIFY_SERVER_CERT option");
    }

    // ✅ Use basic connection mode with proper error handling
    try {
        // Try to connect using SSL but with verification disabled
        if (!mysqli_real_connect($conn, $host, $user, $pass, $name, $port, NULL, MYSQLI_CLIENT_SSL)) {
            error_log("MySQL SSL Connection Error: " . mysqli_connect_error());
            
            // If SSL connection fails, try without SSL as fallback
            $conn = mysqli_init();
            if (!mysqli_real_connect($conn, $host, $user, $pass, $name, $port, NULL)) {
                error_log("MySQL Non-SSL Connection Error: " . mysqli_connect_error());
                die('Database connection failed: ' . mysqli_connect_error());
            } else {
                error_log("Connected without SSL");
            }
        } else {
            error_log("Connected with SSL");
        }
    } catch (Exception $e) {
        error_log("MySQL Exception: " . $e->getMessage());
        die('Database connection failed: ' . $e->getMessage());
    }

    if (mysqli_connect_errno()) {
        error_log("MySQL Connect Error: " . mysqli_connect_error());
        die('Database connection failed: ' . mysqli_connect_error());
    }

    $conn->set_charset('utf8mb4');
    $GLOBALS['conn'] = $conn;
    return $conn;
}

db();