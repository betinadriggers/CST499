<?php
session_start();

// Redirect if user is not verified
if (!isset($_SESSION['verified_email'])) {
    $_SESSION['message'] = "Please verify your identity first.";
    header("Location: forgot_password.php");
    exit();
}

$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password - Online Course Registration Portal</title>
    <link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding-top: 70px;
            background-color: #f8f8f8;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        }

        .form-container {
            max-width: 450px;
            margin: 0 auto;
            margin-top: 50px;
            padding: 30px;
            background-color: #fff;
            border: 1px solid #ccc;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
        }

        .form-group label {
            font-weight: 600;
        }

        .required-asterisk {
            color: red;
        }

        .message {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
        }

        .message.error {
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }

        .message.success {
            background-color: #dff0d8;
            color: #3c763d;
            border: 1px solid #d6e9c6;
        }

        .btn-block {
            margin-top: 15px;
        }

        footer {
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 10px;
            margin-top: 40px;
            position: relative;
            bottom: 0;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2>Reset Your Password</h2>

            <?php if (!empty($message)): ?>
                <div class="message error"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <form action="password_reset_process.php" method="POST">
                <div class="form-group">
                    <label for="new_password">New Password <span class="required-asterisk">*</span></label>
                    <input type="password" class="form-control" name="new_password" required minlength="6">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password <span class="required-asterisk">*</span></label>
                    <input type="password" class="form-control" name="confirm_password" required minlength="6">
                </div>

                <button type="submit" class="btn btn-primary btn-block">Update Password</button>
            </form>
        </div>
    </div>

    <?php include('footer.php'); ?>
</body>
</html>
