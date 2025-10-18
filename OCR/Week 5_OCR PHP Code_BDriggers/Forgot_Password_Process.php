<?php
ob_start(); // Start output buffering to prevent header issues
session_start();
require_once 'CR_DB_Conn_Class.php'; // Your database connection class

// Helper to sanitize input
function clean_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? '';

    // Create database connection
    $db = new Database();

    if ($action === "verify") {
        // Sanitize inputs
        $email = clean_input($_POST['email'] ?? '');
        $ssn = clean_input($_POST['ssn'] ?? '');

        // Validate inputs (fix regex and variable typo)
        if (empty($email) || empty($ssn) || !preg_match('/^\d{4}$/', $ssn)) {
            $_SESSION['message'] = "Please enter a valid email and last 4 digits of SSN.";
            header("Location: forgot_password.php");
            exit();
        }

        // Query the user by email
        $sql = "SELECT ssn FROM tblUser WHERE email = ?";
        $stmt = $db->con->prepare($sql);

        if (!$stmt) {
            $_SESSION['message'] = "Database error (prepare failed).";
            error_log("Prepare failed: " . $db->con->error);
            header("Location: forgot_password.php");
            exit();
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $stored_ssn = $user['ssn'];

            // Get last 4 digits from stored ssn
            $stored_last4 = substr($stored_ssn, -4);

            if ($stored_last4 === $ssn) {
                $_SESSION['verified_email'] = $email;
                $_SESSION['message'] = "Verification successful. Please reset your password.";
                header("Location: password_reset.php");
                exit();
            } else {
                $_SESSION['message'] = "SSN verification failed.";
                header("Location: forgot_password.php");
                exit();
            }
        } else {
            $_SESSION['message'] = "Email address not found.";
            header("Location: forgot_password.php");
            exit();
        }

    } elseif ($action === "reset") {
        // ... (your reset password code unchanged)
    } else {
        $_SESSION['message'] = "Invalid request.";
        header("Location: forgot_password.php");
        exit();
    }

    $db->closeConnection();
} else {
    $_SESSION['message'] = "Invalid request method.";
    header("Location: forgot_password.php");
    exit();
}

ob_end_flush(); // End output buffering
?>
