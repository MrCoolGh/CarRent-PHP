<?php
// --- ENHANCED DEBUGGING & ERROR HANDLING ---
// Display all PHP errors (for development purposes)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log errors to a file (adjust the file path as needed)
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');

// Enable MySQLi to throw exceptions for errors instead of warnings
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// --- DATABASE CONNECTION ---
// Replace with your actual database credentials
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'rentcar');

$conn = null;
try {
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    // Set the character set to avoid encoding issues
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Connection failed: " . $e->getMessage() . " (Error Code: " . $e->getCode() . ")");
}

// --- GET CONNECTION HELPER ---
function getConnection() {
    global $conn;
    return $conn;
}

/**
 * Fetches user data by ID.
 */
function getUserById($userId) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    } catch (mysqli_sql_exception $e) {
        error_log("Error in getUserById: " . $e->getMessage());
        die("Database query failed in getUserById. File: " . $e->getFile() .
            " Line: " . $e->getLine() . " Error: " . $e->getMessage());
        return null;
    }
}

/**
 * Updates a user's profile.
 */
function updateUserProfile($userId, $firstName, $lastName, $email, $phone, $dob, $profileImagePath) {
    global $conn;
    try {
        $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, date_of_birth = ?, profile_image = ? WHERE id = ?");
        $stmt->bind_param("ssssssi", $firstName, $lastName, $email, $phone, $dob, $profileImagePath, $userId);
        return $stmt->execute();
    } catch (mysqli_sql_exception $e) {
        error_log("Error in updateUserProfile: " . $e->getMessage());
        die("Database query failed in updateUserProfile. File: " . $e->getFile() .
            " Line: " . $e->getLine() . " Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Updates a user's password.
 */
function updateUserPassword($userId, $newPassword) {
    global $conn;
    try {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $userId);
        return $stmt->execute();
    } catch (mysqli_sql_exception $e) {
        error_log("Error in updateUserPassword: " . $e->getMessage());
        die("Database query failed in updateUserPassword. File: " . $e->getFile() .
            " Line: " . $e->getLine() . " Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Deletes a user account from the database.
 */
function deleteUser($userId) {
    global $conn;
    try {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        if ($stmt->affected_rows === 0) {
            error_log("No user deleted with id: " . $userId);
            return false;
        }
        return true;
    } catch (mysqli_sql_exception $e) {
        error_log("Error in deleteUser: " . $e->getMessage());
        return false;
    }
}
?>