<?php
// Initialize session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set current page for navigation highlighting
$current_page = 'dashboard';

$conn = db();

// Get admin ID and department
$admin_id = $_SESSION['user_id'];
$department = $_SESSION['department'] ?? '';

// If department is not set in session, try to get it from database for backward compatibility
if (empty($department)) {
    $stmt = $conn->prepare("SELECT Department FROM dept_admin WHERE AdminID = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $department = $row['Department'];
        // Store in session for future use
        $_SESSION['department'] = $department;
    }
}

// For testing/debugging: Use a default department if none is found
if (empty($department)) {
    $department = 'Business Administration'; // Replace with one from your database
    // Uncomment this line to see the user_id issue
    // echo "Warning: No department found for admin ID: " . $admin_id;
}

// Get teacher count - all teachers in the current department
$teacher_count = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM teacher WHERE Department = ?");
$stmt->bind_param("s", $department);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $teacher_count = $row['count'];
}

// Get student count - all students in the current department
$student_count = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM student WHERE Department = ?");
$stmt->bind_param("s", $department);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $student_count = $row['count'];
}

// Get room count - direct count from buildings and rooms (including Common department)
$room_count = 0;
$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM rooms r
    JOIN buildings b ON r.building_id = b.id
    WHERE b.department = ? OR b.department = 'Common'
");
$stmt->bind_param("s", $department);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $room_count = $row['count'];
}

// Get equipment count - direct count from equipments in rooms of the department (including Common department)
$equipment_count = 0;
$stmt = $conn->prepare("
    SELECT COUNT(eu.unit_id) as count 
    FROM equipment_units eu
    JOIN rooms r ON eu.room_id = r.id
    JOIN buildings b ON r.building_id = b.id
    WHERE b.department = ? OR b.department = 'Common'
");
$stmt->bind_param("s", $department);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $equipment_count = $row['count'];
}

// Calculate pending room requests count for the current department
$pending_requests = 0;
$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM room_requests rr
    LEFT JOIN student s ON rr.StudentID = s.StudentID
    LEFT JOIN teacher t ON rr.TeacherID = t.TeacherID
    WHERE rr.Status = 'pending' AND (s.Department = ? OR t.Department = ?)
");
$stmt->bind_param("ss", $department, $department);
$stmt->execute();
$result = $stmt->get_result();
$pending_requests = 0;
while ($row = $result->fetch_assoc()) {
    $pending_requests += $row['count'];
}

// Calculate unresolved equipment issues for the current department
$unresolved_issues = 0;
$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM equipment_issues ei
    JOIN student s ON ei.student_id = s.StudentID AND s.Department = ?
    WHERE (ei.status = 'pending' OR ei.status = 'in_progress')
    UNION
    SELECT COUNT(*) as count 
    FROM equipment_issues ei
    JOIN teacher t ON ei.teacher_id = t.TeacherID AND t.Department = ?
    WHERE (ei.status = 'pending' OR ei.status = 'in_progress')
");
$stmt->bind_param("ss", $department, $department);
$stmt->execute();
$result = $stmt->get_result();
$unresolved_issues = 0;
while ($row = $result->fetch_assoc()) {
    $unresolved_issues += $row['count'];
}

// Get equipment status statistics (including Common department)
$equipment_stats = [];
$stmt = $conn->prepare("
    SELECT eu.status, COUNT(*) as count 
    FROM equipment_units eu
    JOIN rooms r ON eu.room_id = r.id
    JOIN buildings b ON r.building_id = b.id
    WHERE b.department = ? OR b.department = 'Common'
    GROUP BY eu.status
");
$stmt->bind_param("s", $department);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $equipment_stats[$row['status']] = $row['count'];
}

// Get equipment issues statistics (including Common department)
$issue_stats = [];
$stmt = $conn->prepare("
    SELECT ei.status, COUNT(*) as count 
    FROM equipment_issues ei
    JOIN equipment_units eu ON ei.unit_id = eu.unit_id
    JOIN rooms r ON eu.room_id = r.id
    JOIN buildings b ON r.building_id = b.id
    WHERE b.department = ? OR b.department = 'Common'
    GROUP BY ei.status
");
$stmt->bind_param("s", $department);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $issue_stats[$row['status']] = $row['count'];
}

// Get monthly room request trends (last 6 months) (including Common department)
$monthly_stats = [];
$stmt = $conn->prepare("
    SELECT 
        DATE_FORMAT(RequestDate, '%Y-%m') as month,
        COUNT(*) as count 
    FROM room_requests r
    JOIN rooms rm ON r.RoomID = rm.id
    JOIN buildings b ON rm.building_id = b.id
    WHERE (b.department = ? OR b.department = 'Common')
    AND RequestDate >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(RequestDate, '%Y-%m')
    ORDER BY month ASC
");
$stmt->bind_param("s", $department);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $monthly_stats[$row['month']] = $row['count'];
}

// Get recent equipment issues (including Common department)
$recent_issues = [];
$stmt = $conn->prepare("
    SELECT ei.id, e.name as equipment_name, ei.issue_type, ei.status, ei.reported_at
    FROM equipment_issues ei
    JOIN equipment_units eu ON ei.unit_id = eu.unit_id
    JOIN equipment e ON eu.equipment_id = e.id
    JOIN rooms r ON eu.room_id = r.id
    JOIN buildings b ON r.building_id = b.id
    WHERE b.department = ? OR b.department = 'Common'
    ORDER BY ei.reported_at DESC
    LIMIT 5
");
$stmt->bind_param("s", $department);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_issues[] = $row;
}

// Get room request statistics for current department
$room_stats = [];
$stmt = $conn->prepare("
    SELECT rr.Status, COUNT(*) as count 
    FROM room_requests rr
    LEFT JOIN student s ON rr.StudentID = s.StudentID
    LEFT JOIN teacher t ON rr.TeacherID = t.TeacherID
    WHERE (s.Department = ? OR t.Department = ?)
    GROUP BY rr.Status
");
$stmt->bind_param("ss", $department, $department);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    if (isset($room_stats[$row['Status']])) {
        $room_stats[$row['Status']] += $row['count'];
    } else {
        $room_stats[$row['Status']] = $row['count'];
    }
}

// Get equipment issues statistics for current department
$issue_stats = [];
$stmt = $conn->prepare("
    SELECT ei.status, COUNT(*) as count 
    FROM equipment_issues ei
    JOIN student s ON ei.student_id = s.StudentID
    WHERE s.Department = ?
    GROUP BY ei.status
    UNION
    SELECT ei.status, COUNT(*) as count 
    FROM equipment_issues ei
    JOIN teacher t ON ei.teacher_id = t.TeacherID
    WHERE t.Department = ?
    GROUP BY ei.status
");
$stmt->bind_param("ss", $department, $department);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    if (isset($issue_stats[$row['status']])) {
        $issue_stats[$row['status']] += $row['count'];
    } else {
        $issue_stats[$row['status']] = $row['count'];
    }
}

// Get monthly room request trends (last 6 months) for current department
$monthly_stats = [];
$stmt = $conn->prepare("
    SELECT 
        DATE_FORMAT(rr.RequestDate, '%Y-%m') as month,
        COUNT(*) as count 
    FROM room_requests rr
    JOIN student s ON rr.StudentID = s.StudentID
    WHERE s.Department = ?
    AND rr.RequestDate >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(rr.RequestDate, '%Y-%m')
    UNION
    SELECT 
        DATE_FORMAT(rr.RequestDate, '%Y-%m') as month,
        COUNT(*) as count 
    FROM room_requests rr
    JOIN teacher t ON rr.TeacherID = t.TeacherID
    WHERE t.Department = ?
    AND rr.RequestDate >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(rr.RequestDate, '%Y-%m')
    ORDER BY month ASC
");
$stmt->bind_param("ss", $department, $department);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    if (isset($monthly_stats[$row['month']])) {
        $monthly_stats[$row['month']] += $row['count'];
    } else {
        $monthly_stats[$row['month']] = $row['count'];
    }
}

// Get recent equipment issues for current department
$recent_issues = [];
$stmt = $conn->prepare("
    SELECT 
        ei.id, 
        e.name as equipment_name, 
        ei.issue_type, 
        ei.status, 
        ei.reported_at,
        COALESCE(s.FirstName, t.FirstName) as first_name,
        COALESCE(s.LastName, t.LastName) as last_name,
        CASE 
            WHEN s.StudentID IS NOT NULL THEN 'Student' 
            WHEN t.TeacherID IS NOT NULL THEN 'Teacher' 
            ELSE 'Unknown' 
        END as user_type
    FROM equipment_issues ei
    JOIN equipment_units eu ON ei.unit_id = eu.unit_id
    JOIN equipment e ON eu.equipment_id = e.id
    LEFT JOIN student s ON ei.student_id = s.StudentID AND s.Department = ?
    LEFT JOIN teacher t ON ei.teacher_id = t.TeacherID AND t.Department = ?
    WHERE (s.StudentID IS NOT NULL OR t.TeacherID IS NOT NULL)
    ORDER BY ei.reported_at DESC
    LIMIT 5
");
$stmt->bind_param("ss", $department, $department);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_issues[] = $row;
}

// Get recent room usage data - using activity logs structure
$recent_room_usage = [];
$stmt = $conn->prepare("
    SELECT rr.RequestID, rr.StartTime, rr.EndTime, rr.ActivityName, rr.RequestDate, rr.ReservationDate,
        r.room_name, r.room_type, b.building_name, 
        CASE 
            WHEN rr.StudentID IS NOT NULL THEN CONCAT(s.FirstName, ' ', s.LastName)
            WHEN rr.TeacherID IS NOT NULL THEN CONCAT(t.FirstName, ' ', t.LastName)
        END as user_name,
        CASE 
            WHEN rr.StudentID IS NOT NULL THEN s.Department
            WHEN rr.TeacherID IS NOT NULL THEN t.Department
        END as user_department,
        CASE 
            WHEN rr.StudentID IS NOT NULL THEN 'Student'
            WHEN rr.TeacherID IS NOT NULL THEN 'Teacher'
        END as user_role,
        CASE 
            WHEN rr.StudentID IS NOT NULL THEN s.StudentID
            WHEN rr.TeacherID IS NOT NULL THEN t.TeacherID
        END as user_id,
        CASE 
            WHEN rr.StudentID IS NOT NULL THEN s.Email
            WHEN rr.TeacherID IS NOT NULL THEN t.Email
        END as user_email,
        da.FirstName as admin_first_name, 
        da.LastName as admin_last_name,
        CASE 
            WHEN rr.ReservationDate > CURDATE() THEN 'Upcoming'
            WHEN rr.ReservationDate = CURDATE() THEN 
                CASE 
                    WHEN CONVERT_TZ(NOW(), '+00:00', '+08:00') BETWEEN rr.StartTime AND rr.EndTime THEN 'Active Now'
                    WHEN CONVERT_TZ(NOW(), '+00:00', '+08:00') > rr.EndTime THEN 'Completed'
                    ELSE 'Upcoming'
                END
            ELSE 'Completed'
        END as usage_status
    FROM room_requests rr
    JOIN rooms r ON rr.RoomID = r.id
    JOIN buildings b ON r.building_id = b.id
    LEFT JOIN student s ON rr.StudentID = s.StudentID
    LEFT JOIN teacher t ON rr.TeacherID = t.TeacherID
    LEFT JOIN dept_admin da ON rr.approvedBy = da.AdminID
    WHERE rr.Status IN ('approved', 'completed')
    AND (s.Department = ? OR t.Department = ?)
    AND rr.RequestDate >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ORDER BY 
        CASE 
            WHEN rr.ReservationDate = CURDATE() AND CONVERT_TZ(NOW(), '+00:00', '+08:00') BETWEEN rr.StartTime AND rr.EndTime THEN 0
            WHEN rr.ReservationDate >= CURDATE() THEN 1
            ELSE 2
        END, 
        rr.ReservationDate DESC, rr.StartTime DESC
    LIMIT 5
");
$stmt->bind_param("ss", $department, $department);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recent_room_usage[] = $row;
}

// Get rooms with most equipment issues (only unresolved issues) (including Common department)
$rooms_with_most_issues = [];
$stmt = $conn->prepare("
    SELECT 
        r.id as room_id,
        r.room_name,
        b.building_name,
        COUNT(ei.id) as issue_count
    FROM equipment_issues ei
    JOIN equipment_units eu ON ei.unit_id = eu.unit_id
    JOIN rooms r ON eu.room_id = r.id
    JOIN buildings b ON r.building_id = b.id
    WHERE (b.department = ? OR b.department = 'Common')
    AND ei.status IN ('pending', 'in_progress')
    GROUP BY r.id, r.room_name, b.building_name
    HAVING issue_count > 0
    ORDER BY issue_count DESC
    LIMIT 5
");
$stmt->bind_param("s", $department);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $rooms_with_most_issues[] = $row;
}

// Get most requested rooms (including Common department)
$most_requested_rooms = [];
$stmt = $conn->prepare("
    SELECT 
        r.id as room_id,
        r.room_name,
        b.building_name,
        COUNT(rr.RequestID) as request_count,
        ROUND((COUNT(CASE WHEN rr.Status = 'approved' THEN 1 ELSE NULL END) / COUNT(rr.RequestID)) * 100, 1) as approval_rate
    FROM room_requests rr
    JOIN rooms r ON rr.RoomID = r.id
    JOIN buildings b ON r.building_id = b.id
    WHERE b.department = ? OR b.department = 'Common'
    GROUP BY r.id, r.room_name, b.building_name
    ORDER BY request_count DESC
    LIMIT 5
");
$stmt->bind_param("s", $department);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $most_requested_rooms[] = $row;
}
