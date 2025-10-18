<?php
error_reporting(E_ALL ^ E_NOTICE);
ini_set('session.use_only_cookies', '1');
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - Online Course Registration Portal</title>
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

        .forgot-password-form {
            max-width: 400px;
            margin: 40px auto;
            padding: 25px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
        }

        .forgot-password-form h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .required-asterisk {
            color: red;
        }

        .required-note {
            font-size: 0.9em;
            color: #555;
            text-align: center;
            margin-top: 15px;
        }

        .message {
            text-align: center;
            color: red;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
    <div class="jumbotron">
        <div class="container text-center">
            <h1>Online Course Registration Portal - Forgot Password</h1>
        </div>
    </div>

    <nav class="navbar navbar-inverse">
        <div class="container-fluid">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#myNavbar">
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>                        
                </button>
            </div>
            <div class="collapse navbar-collapse" id="myNavbar">
                <ul class="nav navbar-nav">
                    <li><a href="master.php"><span class="glyphicon glyphicon-home"></span> Home</a></li>
                    <li><a href="about.php"><span class="glyphicon glyphicon-earphone"></span> About</a></li>
                    <li><a href="contact.php"><span class="glyphicon glyphicon-earphone"></span> Contact</a></li>
                </ul>
                <ul class="nav navbar-nav navbar-right">
                    <?php
                    if (isset($_SESSION['USER_EMAIL'])) {
                        echo '<li><a href="profile.php"><span class="glyphicon glyphicon-briefcase"></span> Profile</a></li>';
                        echo '<li><a href="index.php?logout=1"><span class="glyphicon glyphicon-off"></span> Logout</a></li>';
                    } else {
                        echo '<li><a href="login.php"><span class="glyphicon glyphicon-user"></span> Login</a></li>';
                        echo '<li><a href="register.php"><span class="glyphicon glyphicon-pencil"></span> Register</a></li>';
                    }
                    ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="forgot-password-form">
            <h2>Forgot Password</h2>

            <?php
            if (isset($_SESSION['message'])) {
                echo '<p class="message">' . htmlspecialchars($_SESSION['message']) . '</p>';
                unset($_SESSION['message']);
            }
            ?>

            <form action="forgot_password_process.php" method="POST" novalidate>
                <input type="hidden" name="action" value="verify">

                <div class="form-group">
                    <label for="email">Email Address <span class="required-asterisk">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="ssn">Last 4 Digits of SSN <span class="required-asterisk">*</span></label>
                    <input type="text" class="form-control" id="ssn" name="ssn" maxlength="4" pattern="\d{4}" title="Please enter exactly 4 digits" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Verify</button>

                <div class="required-note">
                    <p><span class="required-asterisk">*</span> Indicates a required field.</p>
                </div>
            </form>
        </div>
    </div>

    <?php include('footer.php'); ?>
</body>
</html>
