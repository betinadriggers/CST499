<?php
session_start();
require_once 'CR_DB_Conn_Class.php';

// Redirect if not logged in
if (!isset($_SESSION['USER_EMAIL'])) {
    header("Location: login.php");
    exit();
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    function clean_input($data) {
        return htmlspecialchars(stripslashes(trim($data)));
    }

    $currentPassword = clean_input($_POST['current_password']);
    $newPassword = clean_input($_POST['new_password']);
    $confirmPassword = clean_input($_POST['confirm_password']);
    $email = $_SESSION['USER_EMAIL'];

    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = "Please fill in all required fields.";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "New password and confirmation do not match.";
    } else {
        $db = new Database();
        $stmt = $db->con->prepare("SELECT password FROM tblUser WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($hashedPassword);
        $stmt->fetch();
        $stmt->close();

        if (password_verify($currentPassword, $hashedPassword)) {
            $newHashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $db->con->prepare("UPDATE tblUser SET password = ? WHERE email = ?");
            $updateStmt->bind_param("ss", $newHashedPassword, $email);
            if ($updateStmt->execute()) {
                $updateStmt->close();
                $db->closeConnection();
                header("Location: profile.php?password_changed=1");
                exit();
            } else {
                $error = "Error updating password.";
            }
            $updateStmt->close();
        } else {
            $error = "Current password is incorrect.";
        }

        $db->closeConnection();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password - Online Course Registration Portal</title>
    <link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
    <script src="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
    <style>
        html, body {
            height: 100%;
            margin: 0;
            display: flex;
            flex-direction: column;
        }

        body {
            padding-top: 70px;
            flex: 1;
        }

        .jumbotron {
            background-color: #343a40;
            color: white;
            padding: 80px 20px;
        }

        .navbar-nav {
            margin: 0 auto;
        }

        footer {
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 10px;
            margin-top: auto;
        }

        .form-container {
            max-width: 500px;
            margin: 40px auto;
            padding: 25px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
        }

        .form-container h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .message {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
        }

        .message.error {
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }

        .required-asterisk {
            color: red;
        }
    </style>
</head>
<body>

<div class="jumbotron">
    <div class="container text-center">
        <h1>Change Password</h1>
    </div>
</div>

<nav class="navbar navbar-inverse">
    <div class="container-fluid">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navBar">
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>                        
            </button>
        </div>
        <div class="collapse navbar-collapse" id="navBar">
            <ul class="nav navbar-nav">
                <li><a href="master.php"><span class="glyphicon glyphicon-home"></span> Home</a></li>
                <li><a href="about.php"><span class="glyphicon glyphicon-info-sign"></span> About</a></li>
                <li><a href="contact.php"><span class="glyphicon glyphicon-envelope"></span> Contact</a></li>
            </ul>
            <ul class="nav navbar-nav navbar-right">
                <li><a href="profile.php"><span class="glyphicon glyphicon-user"></span> Profile</a></li>
                <li><a href="index.php?logout=1"><span class="glyphicon glyphicon-log-out"></span> Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container form-container">
    <h2>Update Your Password</h2>

    <?php if (!empty($error)): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="form-group">
            <label for="current_password">Current Password <span class="required-asterisk">*</span></label>
            <input type="password" name="current_password" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="new_password">New Password <span class="required-asterisk">*</span></label>
            <input type="password" name="new_password" class="form-control" required minlength="6">
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm New Password <span class="required-asterisk">*</span></label>
            <input type="password" name="confirm_password" class="form-control" required minlength="6">
        </div>

        <button type="submit" class="btn btn-primary btn-block">Change Password</button>
    </form>
</div>

<?php include('footer.php'); ?>

</body>
</html>
