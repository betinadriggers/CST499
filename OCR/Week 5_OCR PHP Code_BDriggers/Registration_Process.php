<?php
error_reporting(E_ALL ^ E_NOTICE);
ini_set('session.use_only_cookies','1');
session_start();
require_once 'CR_DB_Conn_Class.php';

function showModalAndRedirect($message, $redirect = null) {
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Message</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .modal-backdrop {
            background-color: rgba(0, 0, 0, 0.5);
        }
        .modal-content {
            background-color: #343a40;
            color: white;
            border-radius: 8px;
            margin-top: 150px;
        }
        .modal-header {
            background-color: #212529;
            border-bottom: 1px solid #495057;
        }
        .modal-title {
            color: white;
        }
        .modal-footer {
            border-top: 1px solid #495057;
        }
        .btn-primary {
            background-color: #007bff;
            border: none;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <!-- Modal -->
    <div class="modal fade" id="msgModal" tabindex="-1" role="dialog" aria-labelledby="msgModalLabel">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h4 class="modal-title" id="msgModalLabel">Notice</h4>
          </div>
          <div class="modal-body">
            {$message}
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-primary" id="okBtn">OK</button>
          </div>
        </div>
      </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#msgModal').modal({ backdrop: 'static', keyboard: false });
            $('#msgModal').modal('show');
            $('#okBtn').on('click', function() {
                $('#msgModal').modal('hide');
                window.location.href = '{$redirect}';
            });
        });
    </script>
</body>
</html>
HTML;
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    $ssn = trim($_POST['ssn']);

    $db = new Database();

    // Check if email already exists
    $check_sql = "SELECT id FROM tblUser WHERE email = ?";
    $stmt = $db->con->prepare($check_sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        $db->closeConnection();
        showModalAndRedirect("Email is already registered. Please log in.", "login.php");
    }

    $stmt->close();

    // Insert new user
    $insert_sql = "INSERT INTO tblUser (email, password, firstName, lastName, address, phone, ssn)
                   VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db->con->prepare($insert_sql);
    $stmt->bind_param("sssssss", $email, $password, $firstName, $lastName, $address, $phone, $ssn);

    if ($stmt->execute()) {
        $stmt->close();
        $db->closeConnection();
        showModalAndRedirect("Registration successful! Please log in.", "login.php");
    } else {
        $stmt->close();
        $db->closeConnection();
        showModalAndRedirect("Registration failed. Please complete all fields correctly.", "register.php");
    }
}
?>