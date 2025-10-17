<?php
session_start();
require_once 'CR_DB_Conn_Class.php';

function clean_input($data) {
    return htmlspecialchars(trim($data));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $current_password = clean_input($_POST['current_password']);
    $new_password     = clean_input($_POST['new_password']);
    $confirm_password = clean_input($_POST['confirm_password']);

    // Basic validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $_SESSION['password_message'] = "All fields are required.";
        header("Location: profile.php");
        exit();
    }

    if ($new_password !== $confirm_password) {
        $_SESSION['password_message'] = "New passwords do not match.";
        header("Location: profile.php");
        exit();
    }

    $email = $_SESSION['USER_EMAIL'] ?? '';

    if (empty($email)) {
        $_SESSION['password_message'] = "Unauthorized request.";
        header("Location: login.php");
        exit();
    }

    // Fetch user from DB
    $db = new Database();
    $stmt = $db->con->prepare("SELECT password FROM tblUser WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($current_password, $user['password'])) {
            // Hash new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Update password in DB
            $update_stmt = $db->con->prepare("UPDATE tblUser SET password = ? WHERE email = ?");
            $update_stmt->bind_param("ss", $hashed_password, $email);
            if ($update_stmt->execute()) {
                $_SESSION['password_message'] = "Password updated successfully.";
            } else {
                $_SESSION['password_message'] = "Failed to update password.";
            }
        } else {
            $_SESSION['password_message'] = "Current password is incorrect.";
        }
    } else {
        $_SESSION['password_message'] = "User not found.";
    }

    $stmt->close();
    $db->closeConnection();
    header("Location: profile.php");
    exit();
} else {
    $_SESSION['password_message'] = "Invalid request method.";
    header("Location: profile.php");
    exit();
}
