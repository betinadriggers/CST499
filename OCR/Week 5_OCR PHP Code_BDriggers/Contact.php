<?php
error_reporting(E_ALL ^ E_NOTICE);
ini_set('session.use_only_cookies','1');
session_start();
if (isset($_SESSION['USERNAME'])) 
    echo "Welcome : " . $_SESSION['USERNAME'];
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
        /* Ensure the body takes up the full height of the screen */
        html, body {
            height: 100%;
            margin: 0;
            display: flex;
            flex-direction: column;
        }

        body {
            padding-top: 70px; /* Adjusting for navbar */
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

        /* Footer styling */
        footer {
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 10px;
            margin-top: auto; /* This will push the footer to the bottom */
        }

        .contact-form {
            margin-top: 40px;
        }

        .contact-form h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .contact-form .form-group {
            margin-bottom: 15px;
        }

        .contact-form button {
            width: 100%;
        }

        .required {
            color: red;
        }

        .note {
            font-size: 14px;
            color: #555;
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>

<body>
    <div class="jumbotron">
        <div class="container text-center">
            <h1>Online Course Registration Portal</h1>
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
                    <!-- Updated Home link to point to master.php -->
                    <li><a href="master.php"><span class="glyphicon glyphicon-home"></span> Home</a></li>
                    <li><a href="about.php"><span class="glyphicon glyphicon-exclamation-sign"></span> About</a></li>
                    <li><a href="contact.php"><span class="glyphicon glyphicon-earphone"></span> Contact</a></li>
                </ul>
                <ul class="nav navbar-nav navbar-right">
                    <?php
                    ini_set('session.use_only_cookies','1');
                    session_start();

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

<!-- Contact Us Form -->
<div class="container contact-form">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <h2>Contact Us</h2>
            <p class="text-center">We would love to hear from you! Please fill out the form below to get in touch with us.</p>

            <form action="submit_contact.php" method="POST">
                <div class="form-group">
                    <label for="name">Your Name <span class="required">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="email">Your Email <span class="required">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="message">Your Message <span class="required">*</span></label>
                    <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Send Message</button>
            </form>

            <div class="note">
                <p><span class="required">*</span> Indicates a required field.</p>
            </div>
        </div>
    </div>
</div>

    <!-- Footer -->
    <?php include('footer.php'); ?>

</body>
</html>
