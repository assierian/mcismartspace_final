<?php
require '../../auth/middleware.php';
checkAccess(['Student', 'Teacher']);

header('Content-Type: application/json');

// Get the user's department from session data
$user_department = '';
$user_type = $_SESSION['user_type'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;

try {
    $conn = db();
    
    if ($user_type == 'student' && $user_id > 0) {
        $sql = "SELECT Department FROM student WHERE StudentID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $user_department = $row['Department'];
        }
    } elseif ($user_type == 'teacher' && $user_id > 0) {
        $sql = "SELECT Department FROM teacher WHERE TeacherID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $user_department = $row['Department'];
        }
    }

    echo json_encode([
        'success' => true, 
        'department' => $user_department,
        'user_type' => $user_type,
        'user_id' => $user_id
    ]);
    
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error retrieving user department: ' . $e->getMessage()
    ]);
}
?>
