<?php

/**
 * Room Status Handler
 * 
 * This file contains functions to automatically update room statuses based on
 * reservation times. It marks rooms as 'occupied' when approved reservations start
 * and back to 'available' when they end.
 */

// Connect to database
function connectToDatabase()
{
    try {
        // Initialize connection
        $conn = mysqli_init();
        
        // Disable SSL verification
        if (!$conn->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false)) {
            error_log("Failed to set MYSQLI_OPT_SSL_VERIFY_SERVER_CERT option");
        }

        // Try to connect with SSL first
        if (!$conn->real_connect(
            "mcismartspacedb.mysql.database.azure.com", 
            "mcismartspace", 
            "Mcismartspace42", 
            "smartspace",
            3306,
            NULL,
            MYSQLI_CLIENT_SSL
        )) {
            // If SSL fails, try without SSL as fallback
            $conn = mysqli_init();
            $conn->real_connect(
                "mcismartspacedb.mysql.database.azure.com", 
                "mcismartspace", 
                "Mcismartspace42", 
                "smartspace",
                3306
            );
        }

        // Check for connection errors
        if ($conn->connect_error) {
            error_log("Database connection failed: " . $conn->connect_error);
            return null;
        }

        // Set charset
        $conn->set_charset("utf8mb4");
        
        return $conn;
    } catch (Exception $e) {
        error_log("Database connection exception: " . $e->getMessage());
        return null;
    }
}

/**
 * Update room statuses based on current time
 * 
 * This function:
 * 1. Marks approved reservations as 'completed' when their end time has passed
 * 2. Sets rooms to 'occupied' if there's an active, approved reservation now
 * 3. Sets rooms back to 'available' when all approved reservations are finished
 * 4. Preserves rooms with 'maintenance' status
 * 
 * @return bool Success or failure
 */
function updateRoomStatuses()
{
    // Make sure we're using Philippines timezone
    date_default_timezone_set('Asia/Manila');

    // Get current date and time
    $currentDateTime = date('Y-m-d H:i:s');

    // Only output debug information when script is run directly
    $directRun = (realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME']));

    if ($directRun) echo "Current server time: $currentDateTime\n";

    // Connect to database
    $conn = connectToDatabase();
    if (!$conn) return false;

    // Always perform the update to ensure room statuses are current
    // Removing the 5-minute check to ensure immediate updates

    try {
        // Start transaction to ensure all updates are atomic
        $conn->begin_transaction();

        // Set timezone in MySQL to match PHP timezone
        $conn->query("SET time_zone = '+08:00'");

        // Debug logging
        error_log("Running room status update at: " . $currentDateTime);

        // Step 1: Mark approved reservations as completed if their end time has passed
        $completeReservationsSql = "
            UPDATE room_requests
            SET Status = 'completed'
            WHERE Status = 'approved'
            AND TIMESTAMP(ReservationDate, EndTime) <= NOW()";
        
        $conn->query($completeReservationsSql);
        $completedCount = $conn->affected_rows;
        if ($directRun) echo "Reservations marked as completed: " . $completedCount . "\n";

        // Check what bookings are active right now
        $activeBookingsQuery = "
            SELECT r.id, r.room_name, rr.ReservationDate, rr.StartTime, rr.EndTime
            FROM room_requests rr
            JOIN rooms r ON rr.RoomID = r.id
            WHERE rr.Status = 'approved'
            AND rr.ReservationDate = CURDATE()
            AND CURTIME() >= rr.StartTime 
            AND CURTIME() <= rr.EndTime";

        $bookingsResult = $conn->query($activeBookingsQuery);

        if ($directRun) {
            echo "Current active bookings:\n";
            if ($bookingsResult->num_rows > 0) {
                while ($row = $bookingsResult->fetch_assoc()) {
                    echo "Room {$row['room_name']} (ID: {$row['id']}) is booked on {$row['ReservationDate']} from {$row['StartTime']} to {$row['EndTime']}\n";
                }
                // Reset result pointer
                $bookingsResult->data_seek(0);
            } else {
                echo "No active bookings found.\n";
            }
        }

        // 2. Find rooms that should be marked as occupied
        // (approved reservations where current DATE matches ReservationDate AND current TIME is between start and end time)
        $occupiedRoomsSql = "
            UPDATE rooms r
            SET r.RoomStatus = 'occupied'
            WHERE r.id IN (
                SELECT DISTINCT rr.RoomID
                FROM room_requests rr
                WHERE rr.Status = 'approved'
                AND rr.ReservationDate = CURDATE()
                AND CURTIME() >= rr.StartTime 
                AND CURTIME() <= rr.EndTime
            )
            AND r.RoomStatus != 'maintenance'";

        $conn->query($occupiedRoomsSql);

        // Log how many rows were affected
        $occupiedCount = $conn->affected_rows;
        if ($directRun) echo "Rooms marked as occupied: " . $occupiedCount . "\n";

        // 3. Find rooms that should be marked as available again
        // (no current approved reservations for TODAY)
        $availableRoomsSql = "
            UPDATE rooms r
            SET r.RoomStatus = 'available'
            WHERE r.RoomStatus = 'occupied'
            AND r.id NOT IN (
                SELECT DISTINCT rr.RoomID
                FROM room_requests rr
                WHERE rr.Status = 'approved'
                AND rr.ReservationDate = CURDATE()
                AND CURTIME() >= rr.StartTime 
                AND CURTIME() <= rr.EndTime
            )";

        $conn->query($availableRoomsSql);

        // Log how many rows were affected
        $availableCount = $conn->affected_rows;
        if ($directRun) echo "Rooms marked as available: " . $availableCount . "\n";

        // Get all room statuses after update
        if ($directRun) {
            $roomStatusQuery = "SELECT id, room_name, RoomStatus FROM rooms";
            $roomStatusResult = $conn->query($roomStatusQuery);

            echo "Updated room statuses:\n";
            while ($row = $roomStatusResult->fetch_assoc()) {
                echo "Room {$row['room_name']} (ID: {$row['id']}) status: {$row['RoomStatus']}\n";
            }
        }

        // Update the last check time
        updateLastCheckTime($currentDateTime);

        // Commit the transaction
        $conn->commit();

        return true;
    } catch (Exception $e) {
        // If an error occurs, roll back the changes
        $conn->rollback();
        error_log("Error updating room statuses: " . $e->getMessage());
        if ($directRun) echo "Error updating room statuses: " . $e->getMessage() . "\n";
        return false;
    } finally {
        // Do not close the connection here, as it's managed globally
    }
}

/**
 * Get the last time the status update was run
 */
function getLastUpdateTime()
{
    $conn = connectToDatabase();
    if (!$conn) return null;

    // Check if the settings table exists and has our last_check value
    $checkTableSql = "SHOW TABLES LIKE 'system_settings'";
    $tableExists = $conn->query($checkTableSql)->num_rows > 0;

    if (!$tableExists) {
        // Create the settings table if it doesn't exist
        $createTableSql = "CREATE TABLE system_settings (
            setting_key VARCHAR(50) PRIMARY KEY,
            setting_value TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $conn->query($createTableSql);
        $conn->close();
        return null;
    }

    // Get the last check time
    $lastCheckSql = "SELECT setting_value FROM system_settings WHERE setting_key = 'room_status_last_check'";
    $result = $conn->query($lastCheckSql);

    if ($result && $result->num_rows > 0) {
        $value = $result->fetch_assoc()['setting_value'];
        $conn->close();
        return $value;
    }

    $conn->close();
    return null;
}

/**
 * Update the timestamp for the last status check
 */
function updateLastCheckTime($timestamp)
{
    $conn = connectToDatabase();
    if (!$conn) return false;

    $sql = "INSERT INTO system_settings (setting_key, setting_value) 
            VALUES ('room_status_last_check', ?) 
            ON DUPLICATE KEY UPDATE setting_value = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $timestamp, $timestamp);
    $result = $stmt->execute();

    $conn->close();
    return $result;
}

// Automatically run the update when this file is included
updateRoomStatuses();

// Main function that runs when the file is executed directly
if (realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'])) {
    // If the file is called directly (not included), run the update
    echo "Running room status update manually...\n";
    $result = updateRoomStatuses();
    echo "Update " . ($result ? "completed successfully" : "failed") . "\n";
}