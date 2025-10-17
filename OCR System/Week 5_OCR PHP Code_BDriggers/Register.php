<?php
error_reporting(E_ALL ^ E_NOTICE);
ini_set('session.use_only_cookies','1');
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1"/>
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
    </style>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
/* ---------------- Format SSN provided by user. ---------------- */
            const ssnInput = document.getElementById("ssn");
            ssnInput.addEventListener("input", function (e) {
                let value = e.target.value.replace(/\D/g, "");
                if (value.length > 9) value = value.substring(0, 9);
                let formatted = value;
                if (value.length > 5) {
                    formatted = value.slice(0, 3) + "-" + value.slice(3, 5) + "-" + value.slice(5);
                } else if (value.length > 3) {
                    formatted = value.slice(0, 3) + "-" + value.slice(3);
                }
                e.target.value = formatted;
            });

/* ---------------- Format phone number provided by user. ---------------- */
            const phoneInput = document.getElementById("phone");
            phoneInput.addEventListener("input", function (e) {
                let value = e.target.value.replace(/\D/g, "");
                if (value.length > 10) value = value.substring(0, 10);
                let formatted = value;
                if (value.length > 6) {
                    formatted = `(${value.slice(0, 3)}) ${value.slice(3, 6)}-${value.slice(6)}`;
                } else if (value.length > 3) {
                    formatted = `(${value.slice(0, 3)}) ${value.slice(3)}`;
                }
                e.target.value = formatted;
            });

            const form = document.querySelector("form");
            form.addEventListener("submit", function () {
               	ssnInput.value = ssnInput.value.replace(/-/g, "");  // Remove dashes from SSN
                phoneInput.value = phoneInput.value.replace(/[^\d]/g, "");  // Remove non-digits from phone
            });
        });
    </script>
</head>
<body>
    <div class="jumbotron">
        <div class="container text-center">
            <h1>Online Course Registration Portal Profile Registration</h1>
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
                    <li><a href="about.php"><span class="glyphicon glyphicon-exclamation-sign"></span> About</a></li>
                    <li><a href="contact.php"><span class="glyphicon glyphicon-earphone"></span> Contact</a></li>
                </ul>
                <ul class="nav navbar-nav navbar-right">
                    <?php
                    if( isset($_SESSION['USERNAME'])) {
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

    <!-- Registration Form -->
    <div class="container">
        <div class="login-form">
            <h2>Register</h2>
            <form action="registration_process.php" method="POST">
                <div class="form-group">
                    <label for="email">Email <span class="required-asterisk">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="password">Password <span class="required-asterisk">*</span></label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="firstName">First Name <span class="required-asterisk">*</span></label>
                    <input type="text" class="form-control" id="firstName" name="firstName" required>
                </div>

                <div class="form-group">
                    <label for="lastName">Last Name <span class="required-asterisk">*</span></label>
                    <input type="text" class="form-control" id="lastName" name="lastName" required>
                </div>

                <div class="form-group">
                    <label for="address">Address <span class="required-asterisk">*</span></label>
                    <input type="text" class="form-control" id="address" name="address" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number <span class="required-asterisk">*</span></label>
                    <input type="text" class="form-control" id="phone" name="phone" required>
                </div>

                <div class="form-group">
                    <label for="ssn">Social Security Number <span class="required-asterisk">*</span></label>
                    <input type="text" class="form-control" id="ssn" name="ssn" maxlength="11" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Register</button>

                <div class="required-note">
                    <p><span class="required-asterisk">*</span> Indicates a required field.</p>
                </div>
            </form>
        </div>
    </div>

    <?php include('footer.php'); ?>
</body>
</html>