<?php
require_once __DIR__ . '/../middleware/rate_limiter.php';
require_once __DIR__ . '/../middleware/session_manager.php';
require_once __DIR__ . '/dbh.inc.php';

// Debug logging
function debug_log($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 3, __DIR__ . '/../debug.log');
}

$conn = db();
$rateLimiter = new RateLimiter($conn);
$sessionManager = new SessionManager();

debug_log('Login attempt started');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    debug_log('Processing login request for email: ' . ($_POST['email'] ?? 'unknown'));
    $check = $rateLimiter->isAllowed();
    if (!$check['allowed']) {
        debug_log('Rate limit exceeded for IP');
        header("Location: ../index.php?error=locked");
        exit();
    }

    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Check for duplicate email addresses across tables (security measure)
    $tables = ['student', 'teacher', 'dept_admin', 'registrar'];
    $duplicateCount = 0;
    foreach ($tables as $table) {
        $emailField = ($table === 'registrar') ? 'Reg_Email' : 'Email';
        $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM $table WHERE $emailField = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'];
        $duplicateCount += $count;
    }
    
    if ($duplicateCount > 1) {
        debug_log('SECURITY WARNING: Email ' . $email . ' exists in multiple user tables (' . $duplicateCount . ' occurrences)');
    }

    if (empty($email) || empty($password)) {
        debug_log('Empty email or password provided');
        die("Email and password are required.");
    }

    $loginSuccess = false;
    $userData = [];

    // Check registrar
    $stmt = $conn->prepare("SELECT regid, Reg_Password, 'Registrar' as Role, 'Registrar' as FirstName FROM registrar WHERE Reg_Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['Reg_Password'])) {
            $loginSuccess = true;
            $userData = [
                'user_id' => $row['regid'],
                'role' => 'Registrar',
                'email' => $email,
                'name' => 'Registrar'
            ];
            $redirectUrl = "../registrar/registrar.php";
            debug_log('Registrar login successful for: ' . $email);
        }
    }

    // Check dept_admin
    if (!$loginSuccess) {
        $stmt = $conn->prepare("SELECT AdminID, FirstName, LastName, Password, Department, 'Department Admin' as Role FROM dept_admin WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['Password'])) {
                $loginSuccess = true;
                $userData = [
                    'user_id' => $row['AdminID'],
                    'role' => 'Department Admin',
                    'email' => $email,
                    'name' => $row['FirstName'],
                    'firstname' => $row['FirstName'],
                    'lastname' => $row['LastName'],
                    'department' => $row['Department']
                ];
                $redirectUrl = "../department-admin/dept-admin.php";
                debug_log('Department Admin login successful for: ' . $email . ', Name: ' . $row['FirstName']);
            }
        }
    }

    // Check teacher
    if (!$loginSuccess) {
        $stmt = $conn->prepare("SELECT TeacherID, FirstName, LastName, Password, Department, 'Teacher' as Role FROM teacher WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['Password'])) {
                $loginSuccess = true;
                $userData = [
                    'user_id' => $row['TeacherID'],
                    'role' => 'Teacher',
                    'email' => $email,
                    'name' => $row['FirstName'],
                    'firstname' => $row['FirstName'],
                    'lastname' => $row['LastName'] ?? '',
                    'department' => $row['Department'] ?? ''
                ];
                $redirectUrl = "../users/users_browse_room.php";
                debug_log('Teacher login successful for: ' . $email . ', Name: ' . $row['FirstName']);
            }
        }
    }

    // Check student
    if (!$loginSuccess) {
        $stmt = $conn->prepare("SELECT StudentID, FirstName, LastName, Password, Department, 'Student' as Role FROM student WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['Password'])) {
                $loginSuccess = true;
                $userData = [
                    'user_id' => $row['StudentID'],
                    'role' => 'Student',
                    'email' => $email,
                    'name' => $row['FirstName'],
                    'firstname' => $row['FirstName'],
                    'lastname' => $row['LastName'] ?? '',
                    'department' => $row['Department'] ?? ''
                ];
                $redirectUrl = "../users/users_browse_room.php";
                debug_log('Student login successful for: ' . $email . ', Name: ' . $row['FirstName']);
            }
        }
    }

    // Process successful login
    if ($loginSuccess) {
        // Clear any existing session data to prevent session mixups
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_regenerate_id(true);
        }
        
        // Add user type to session data for extra security validation
        $userData['user_type'] = strtolower($userData['role']);
        
        $rateLimiter->recordSuccessfulAttempt($email);
        debug_log('About to create session with data: ' . json_encode($userData));
        $sessionManager->createSession($userData);
        debug_log('Session created successfully. Session name: ' . ($_SESSION['name'] ?? 'NOT SET') . ', email: ' . ($_SESSION['email'] ?? 'NOT SET'));
        
        // Set a cache-busting cookie for user-specific content
        setcookie('cache_invalidate', uniqid(), time() + 3600, '/', '', true, true);
        
        // Set a session flag to indicate we need to clear service worker cache
        $_SESSION['clear_sw_cache'] = true;
        
        header("Location: " . $redirectUrl);
        exit();
    } else {
        $rateLimiter->recordFailedAttempt($email);
        $remaining = $rateLimiter->getRemainingAttempts();
        debug_log('Login failed for: ' . $email . ', Attempts remaining: ' . $remaining);
        header("Location: ../index.php?error=invalid&attempts_left=" . $remaining);
        exit();
    }
}
?>