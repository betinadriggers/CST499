<?php
session_start();
require_once 'CR_DB_Conn_Class.php'; 

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Helper to sanitize and clean input
    function clean_input($data) {
        return htmlspecialchars(stripslashes(trim($data)));
    }

    // Sanitize and clean inputs
    $email    = clean_input($_POST['email']);
    $password = clean_input($_POST['password']);

    // Validate fields
    if (empty($email) || empty($password)) {
        echo "Please fill in all required fields.";
        exit();
    }

    // Database connection
    $db = new Database();
    $sql = "SELECT * FROM tblUser WHERE email = ?";
    $stmt = $db->con->prepare($sql);
    $stmt->bind_param("s", $email);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Check if password matches
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['USER_EMAIL'] = $user['email'];
                $_SESSION['USERNAME'] = $user['firstName']; // Or any other user data

                echo 'success'; // Indicate successful login
                exit();
            } else {
                echo 'Invalid password.';
                exit();
            }
        } else {
            echo 'No user found with that email.';
            exit();
        }
    } else {
        echo 'Database error: ' . $stmt->error;
        exit();
    }

    $db->closeConnection();
} else {
    echo 'Invalid request method.';
    exit();
}
?>
