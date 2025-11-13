<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing Azure MySQL Connection</h2>";

// Check if certificate exists
$sslCert = __DIR__ . '/DigiCertGlobalRootCA.crt.pem';
echo "<p><strong>Certificate Path:</strong> " . $sslCert . "</p>";
echo "<p><strong>Certificate Exists:</strong> " . (file_exists($sslCert) ? 'YES ✓' : 'NO ✗') . "</p>";

if (file_exists($sslCert)) {
    echo "<p><strong>Certificate Readable:</strong> " . (is_readable($sslCert) ? 'YES ✓' : 'NO ✗') . "</p>";
}

// Connection settings
$host = 'mcismartspacedb.mysql.database.azure.com';
$user = 'mcismartspace';
$pass = 'Mcismartspace42';
$name = 'smartspace';
$port = 3306;

echo "<hr>";
echo "<h3>Connection Details:</h3>";
echo "<p><strong>Host:</strong> $host</p>";
echo "<p><strong>Port:</strong> $port</p>";
echo "<p><strong>User:</strong> $user</p>";
echo "<p><strong>Database:</strong> $name</p>";

echo "<hr>";
echo "<h3>Testing Connection...</h3>";

// Initialize connection
$conn = mysqli_init();
if (!$conn) {
    die('<p style="color:red;">❌ mysqli_init failed</p>');
}

$sslConnected = false;

// Try with SSL certificate first
if (file_exists($sslCert)) {
    echo "<p>🔒 Attempt 1: Trying SSL with certificate...</p>";
    mysqli_ssl_set($conn, NULL, NULL, $sslCert, NULL, NULL);
    mysqli_options($conn, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
    mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 10);
    
    try {
        mysqli_report(MYSQLI_REPORT_OFF); // Disable exception throwing
        if (mysqli_real_connect($conn, $host, $user, $pass, $name, $port, NULL, MYSQLI_CLIENT_SSL)) {
            $sslConnected = true;
            echo '<p style="color:green;">✓ Connected with SSL certificate!</p>';
        } else {
            echo '<p style="color:orange;">⚠️ SSL with certificate failed: ' . mysqli_connect_error() . '</p>';
        }
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // Re-enable
    } catch (Exception $e) {
        echo '<p style="color:orange;">⚠️ SSL with certificate exception: ' . $e->getMessage() . '</p>';
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    }
}

// Try SSL without certificate verification
if (!$sslConnected) {
    echo "<p>🔒 Attempt 2: Trying SSL without certificate verification...</p>";
    $conn = mysqli_init();
    mysqli_options($conn, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
    mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 10);
    
    try {
        mysqli_report(MYSQLI_REPORT_OFF);
        if (mysqli_real_connect($conn, $host, $user, $pass, $name, $port, NULL, MYSQLI_CLIENT_SSL)) {
            $sslConnected = true;
            echo '<p style="color:green;">✓ Connected with SSL (no certificate)!</p>';
        } else {
            echo '<p style="color:orange;">⚠️ SSL without certificate failed: ' . mysqli_connect_error() . '</p>';
        }
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    } catch (Exception $e) {
        echo '<p style="color:orange;">⚠️ SSL without certificate exception: ' . $e->getMessage() . '</p>';
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    }
}

// Try without SSL as last resort
if (!$sslConnected) {
    echo "<p>🔓 Attempt 3: Trying without SSL...</p>";
    $conn = mysqli_init();
    mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 10);
    
    try {
        mysqli_report(MYSQLI_REPORT_OFF);
        if (mysqli_real_connect($conn, $host, $user, $pass, $name, $port, NULL, 0)) {
            echo '<p style="color:green;">✓ Connected without SSL!</p>';
        } else {
            die('<p style="color:red;">❌ All connection attempts failed: ' . mysqli_connect_error() . '</p>');
        }
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    } catch (Exception $e) {
        die('<p style="color:red;">❌ All connection attempts failed: ' . $e->getMessage() . '</p>');
    }
}

// Connection successful - check SSL status
$result = $conn->query("SHOW STATUS LIKE 'Ssl_cipher'");
if ($result) {
    $row = $result->fetch_assoc();
    if (!empty($row['Value'])) {
        echo '<p><strong>SSL Cipher:</strong> ' . $row['Value'] . ' 🔒</p>';
        echo '<p style="color:green;"><strong>✓ Connection is encrypted!</strong></p>';
    } else {
        echo '<p style="color:orange;"><strong>⚠️ Connection is NOT encrypted (no SSL)</strong></p>';
    }
}

// Test query
$result = $conn->query("SELECT DATABASE() as db, VERSION() as version");
if ($result) {
    $row = $result->fetch_assoc();
    echo '<p><strong>Current Database:</strong> ' . $row['db'] . '</p>';
    echo '<p><strong>MySQL Version:</strong> ' . $row['version'] . '</p>';
}

if ($conn) {
    $conn->close();
}

echo "<hr>";
echo "<p><em>Test completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
