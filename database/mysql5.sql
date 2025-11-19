DROP DATABASE IF EXISTS smartspace;
CREATE DATABASE smartspace;
USE smartspace;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- =========================
-- Roles
-- =========================
CREATE TABLE roles (
  RoleID INT NOT NULL AUTO_INCREMENT,
  RoleName VARCHAR(50) NOT NULL,
  PRIMARY KEY (RoleID)
);

INSERT INTO roles (RoleID, RoleName) VALUES
(1, 'Registrar'),
(2, 'Department Admin'),
(3, 'Teacher'),
(4, 'Student');

-- =========================
-- Registrar
-- =========================
CREATE TABLE registrar (
  regid INT NOT NULL AUTO_INCREMENT,
  Reg_Email VARCHAR(50) NOT NULL UNIQUE,
  Reg_Password VARCHAR(255) NOT NULL,
  RoleID INT NOT NULL DEFAULT 1,
  PRIMARY KEY (regid),
  FOREIGN KEY (RoleID) REFERENCES roles(RoleID)
);

-- =========================
-- Department Admin
-- =========================
CREATE TABLE dept_admin (
  AdminID INT NOT NULL AUTO_INCREMENT,
  FirstName VARCHAR(50) NOT NULL,
  LastName VARCHAR(50) NOT NULL,
  Department VARCHAR(50) NOT NULL,
  Email VARCHAR(50) NOT NULL UNIQUE,
  Password VARCHAR(255) NOT NULL,
  RoleID INT NOT NULL DEFAULT 2,
  PRIMARY KEY (AdminID),
  FOREIGN KEY (RoleID) REFERENCES roles(RoleID)
);

-- =========================
-- Student
-- =========================
CREATE TABLE student (
  StudentID INT NOT NULL AUTO_INCREMENT,
  FirstName VARCHAR(50) NOT NULL,
  LastName VARCHAR(50) NOT NULL,
  Department VARCHAR(50) NOT NULL,
  Program VARCHAR(50) NOT NULL,
  YearSection VARCHAR(50) NOT NULL,
  Email VARCHAR(50) NOT NULL UNIQUE,
  Password VARCHAR(255) NOT NULL,
  AdminID INT NOT NULL,
  RoleID INT NOT NULL DEFAULT 4,
  PenaltyStatus ENUM('active', 'banned') DEFAULT 'active',
  PenaltyExpiresAt TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (StudentID),
  FOREIGN KEY (AdminID) REFERENCES dept_admin(AdminID) ON DELETE CASCADE,
  FOREIGN KEY (RoleID) REFERENCES roles(RoleID)
);

-- =========================
-- Teachers
-- =========================
CREATE TABLE teacher (
  TeacherID INT NOT NULL AUTO_INCREMENT,
  FirstName VARCHAR(50) NOT NULL,
  LastName VARCHAR(50) NOT NULL,
  Department VARCHAR(50) NOT NULL,
  Email VARCHAR(50) NOT NULL UNIQUE,
  Password VARCHAR(255) NOT NULL,
  AdminID INT NOT NULL,
  RoleID INT NOT NULL DEFAULT 3,
  PRIMARY KEY (TeacherID),
  FOREIGN KEY (AdminID) REFERENCES dept_admin(AdminID) ON DELETE CASCADE,
  FOREIGN KEY (RoleID) REFERENCES roles(RoleID)
);

-- =========================
-- Buildings
-- =========================
CREATE TABLE buildings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    building_name VARCHAR(255) NOT NULL,
    department VARCHAR(255) NOT NULL,
    number_of_floors INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================
-- Rooms
-- =========================
CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_name VARCHAR(255) NOT NULL,
    room_type VARCHAR(255) NOT NULL,
    capacity INT NOT NULL,
    RoomStatus ENUM('available', 'occupied', 'maintenance') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    building_id INT,
    FOREIGN KEY (building_id) REFERENCES buildings(id)
);

CREATE TABLE room_maintenance (
  id INT AUTO_INCREMENT PRIMARY KEY,
  room_id INT NOT NULL,
  reason TEXT,
  start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  end_date TIMESTAMP NULL,
  admin_id INT,
  FOREIGN KEY (room_id) REFERENCES rooms(id),
  FOREIGN KEY (admin_id) REFERENCES dept_admin(AdminID) ON DELETE CASCADE
);

-- =========================
-- Room Requests
-- =========================
CREATE TABLE room_requests (
    RequestID INT NOT NULL AUTO_INCREMENT,
    StudentID INT,
    TeacherID INT,
    RoomID INT NOT NULL,
    ActivityName VARCHAR(255) NOT NULL,
    Purpose TEXT NOT NULL,
    RequestDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ReservationDate DATE NOT NULL,
    StartTime TIME NOT NULL,
    EndTime TIME NOT NULL,
    NumberOfParticipants INT NOT NULL,
    Status ENUM('pending', 'approved', 'rejected', 'cancelled', 'completed') DEFAULT 'pending',
    RejectionReason TEXT,
    ApprovedBy INT DEFAULT NULL,
    ApproverFirstName VARCHAR(128) DEFAULT NULL,
    ApproverLastName VARCHAR(128) DEFAULT NULL,
    RejectedBy INT DEFAULT NULL,
    RejecterFirstName VARCHAR(128) DEFAULT NULL,
    RejecterLastName VARCHAR(128) DEFAULT NULL,
    PRIMARY KEY (RequestID),
    FOREIGN KEY (StudentID) REFERENCES student(StudentID) ON DELETE CASCADE,
    FOREIGN KEY (TeacherID) REFERENCES teacher(TeacherID) ON DELETE CASCADE,
    FOREIGN KEY (RoomID) REFERENCES rooms(id)
);

ALTER TABLE room_requests 
MODIFY COLUMN Status ENUM('pending', 'approved', 'rejected', 'cancelled', 'completed') DEFAULT 'pending';

-- =========================
-- Equipment
-- =========================
CREATE TABLE equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE equipment_units (
    unit_id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT NOT NULL,
    room_id INT NOT NULL,
    serial_number VARCHAR(100) UNIQUE,
    status ENUM('working', 'needs_repair', 'maintenance', 'missing') DEFAULT 'working',
    purchased_at DATE DEFAULT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id),
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

-- Equipment Issues
CREATE TABLE equipment_issues (
  id INT AUTO_INCREMENT PRIMARY KEY,
  unit_id INT NOT NULL,
  student_id INT DEFAULT NULL,
  teacher_id INT DEFAULT NULL,
  issue_type VARCHAR(255) NOT NULL,
  description TEXT NOT NULL,
  status ENUM('pending','in_progress','resolved','rejected') DEFAULT 'pending',
  statusCondition ENUM('working', 'needs_repair', 'maintenance', 'missing') DEFAULT 'working',
  admin_response TEXT DEFAULT NULL,
  reported_at TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  resolved_at TIMESTAMP NULL DEFAULT NULL,
  image_path VARCHAR(255) DEFAULT NULL,
  -- reference_number will be set in a BEFORE INSERT trigger using the assigned AUTO_INCREMENT id
  reference_number VARCHAR(15) DEFAULT NULL,
  rejection_reason TEXT DEFAULT NULL,
  FOREIGN KEY (unit_id) REFERENCES equipment_units(unit_id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES student(StudentID) ON DELETE CASCADE,
  FOREIGN KEY (teacher_id) REFERENCES teacher(TeacherID) ON DELETE CASCADE,
  UNIQUE KEY (reference_number)
);

-- Equipment Audit
CREATE TABLE equipment_audit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    action_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) 
);

-- =========================
-- System Settings
-- =========================
CREATE TABLE system_settings (
  setting_key VARCHAR(50) NOT NULL,
  setting_value TEXT DEFAULT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (setting_key)
);

-- =========================
-- Penalty System (enhanced version)
-- =========================
CREATE TABLE penalty (
  id INT NOT NULL AUTO_INCREMENT,
  student_id INT NOT NULL,
  reason TEXT NOT NULL,
  descriptions TEXT NOT NULL,
  penalty_type ENUM('warning', 'ban') DEFAULT 'ban',
  status ENUM('active', 'expired', 'revoked') DEFAULT 'active',
  issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  expires_at TIMESTAMP NULL DEFAULT NULL,
  issued_by INT DEFAULT NULL,
  revoked_at TIMESTAMP NULL DEFAULT NULL,
  revoked_by INT DEFAULT NULL,
  revoke_reason TEXT DEFAULT NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (student_id) REFERENCES student(StudentID) ON DELETE CASCADE,
  FOREIGN KEY (issued_by) REFERENCES dept_admin(AdminID) ON DELETE SET NULL,
  FOREIGN KEY (revoked_by) REFERENCES dept_admin(AdminID) ON DELETE SET NULL
);

-- Penalty Audit Trail
CREATE TABLE penalty_audit (
  id INT NOT NULL AUTO_INCREMENT,
  penalty_id INT NOT NULL,
  action ENUM('issued', 'expired', 'revoked', 'updated') NOT NULL,
  action_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  performed_by INT DEFAULT NULL,
  details TEXT DEFAULT NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (penalty_id) REFERENCES penalty(id) ON DELETE CASCADE,
  FOREIGN KEY (performed_by) REFERENCES dept_admin(AdminID) ON DELETE SET NULL
);

-- =========================
-- Login Attempts
-- =========================
CREATE TABLE login_attempts (
    id INT NOT NULL AUTO_INCREMENT,
    ip_address VARCHAR(45) NOT NULL,
    email VARCHAR(100) NOT NULL,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    success TINYINT(1) DEFAULT 0,
    PRIMARY KEY (id),
    INDEX idx_ip_time (ip_address, attempt_time),
    INDEX idx_cleanup (attempt_time)
);

-- =========================
-- Trigger for Equipment Issues Reference Numbers
-- =========================
-- Note: reference_number is set by application code when inserting records.
-- Avoid triggers that update the same table (MySQL disallows updating the table
-- that fired the trigger). If you prefer a DB-side approach, consider a
-- generated column or setting the value in a BEFORE INSERT using a different
-- mechanism. For now, keep reference_number managed by application logic.

-- If the server doesn't support GENERATED column using AUTO_INCREMENT, use
-- a BEFORE INSERT trigger that sets NEW.reference_number using the assigned
-- AUTO_INCREMENT id. This modifies the row being inserted (allowed) and
-- does not perform an UPDATE on the same table.
-- Database trigger removed: reference_number will be generated by application code
-- to produce a short, random EQ###### value and to ensure consistent values
-- across the UI. If you prefer DB-side generation, reintroduce a trigger that
-- uses UUID_SHORT() or similar, but be aware of cross-environment differences.

COMMIT;
