<?php
session_start();
require_once 'CR_DB_Conn_Class.php';

if (!isset($_SESSION['verified_email'])) {
    $_SESSION['message'] = "Unauthorized request.";
    header("Location: forgot_password.php");
    exit();
}

// Get passwords from form
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (empty($new_password) || empty($confirm_password)) {
    $_SESSION['message'] = "Both password fields are required.";
    header("Location: password_reset.php");
    exit();
}

if ($new_password !== $confirm_password) {
    $_SESSION['message'] = "Passwords do not match.";
    header("Location: password_reset.php");
    exit();
}

if (strlen($new_password) < 6) {
    $_SESSION['message'] = "Password must be at least 6 characters.";
    header("Location: password_reset.php");
    exit();
}

// Update password in the database
$db = new Database();

if ($db->con->connect_error) {
    $_SESSION['message'] = "Database connection failed.";
    header("Location: password_reset.php");
    exit();
}

$email = $_SESSION['verified_email'];
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

$stmt = $db->con->prepare("UPDATE tblUser SET password = ? WHERE email = ?");
if (!$stmt) {
    $_SESSION['message'] = "Failed to prepare statement.";
    header("Location: password_reset.php");
    exit();
}

$stmt->bind_param("ss", $hashed_password, $email);
if ($stmt->execute()) {
    unset($_SESSION['verified_email']);
    $_SESSION['message'] = "Password successfully updated. You may now login.";
    header("Location: login.php");
    exit();
} else {
    $_SESSION['message'] = "Password update failed.";
    header("Location: password_reset.php");
    exit();
}

$stmt->close();
$db->closeConnection();
