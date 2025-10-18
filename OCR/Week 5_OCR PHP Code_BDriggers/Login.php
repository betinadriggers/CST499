<?php
error_reporting(E_ALL ^ E_NOTICE);
ini_set('session.use_only_cookies', '1');
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Online Course Registration Portal</title>
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

        .login-form {
            max-width: 400px;
            margin: 40px auto;
            padding: 25px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
        }

        .login-form h2 {
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

        .error {
            color: red;
            text-align: center;
        }

        .forgot-password {
            text-align: right;
            margin-top: -10px;
            margin-bottom: 10px;
        }

        .forgot-password a {
            font-size: 0.9em;
        }
    </style>

    <script>
        $(document).ready(function() {
            $('#loginForm').submit(function(event) {
                event.preventDefault(); // Prevent default form submission
                var formData = $(this).serialize();

                $.ajax({
                    type: 'POST',
                    url: 'login_process.php',
                    data: formData,
                    success: function(response) {
                        if (response === 'success') {
                            window.location.href = 'profile.php';
                        } else {
                            $('#error-message').html(response);
                        }
                    },
                    error: function() {
                        $('#error-message').html('There was an error processing your request.');
                    }
                });
            });
        });
    </script>
</head>

<body>
    <div class="jumbotron">
        <div class="container text-center">
            <h1>Online Course Registration Portal Login</h1>
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

    <!-- Login Form -->
    <div class="container">
        <div class="login-form">
            <h2>Login</h2>

            <!-- Error message container -->
            <div id="error-message" class="error">
                <?php if (!empty($error)) echo $error; ?>
            </div>

            <form id="loginForm" method="POST" class="form-horizontal">
                <div class="form-group">
                    <label for="email">Email Address <span class="required-asterisk">*</span></label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="password">Password <span class="required-asterisk">*</span></label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <!-- Forgot Password Link -->
                <div class="form-group forgot-password">
                    <a href="forgot_password.php">Forgot Password?</a>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Login</button>

                <div class="required-note">
                    <p><span class="required-asterisk">*</span> Indicates a required field.</p>
                </div>
            </form>
        </div>
    </div>

    <?php include('footer.php'); ?>
</body>
</html>
