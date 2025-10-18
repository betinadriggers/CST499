<?php
error_reporting(E_ALL ^ E_NOTICE);
ini_set('session.use_only_cookies', '1');
session_start();

// Check if user is logging out
if (isset($_GET['logout'])) {
    // Destroy the session and logout the user
    session_unset(); // Remove all session variables
    session_destroy(); // Destroy the session
    header('Location: login.php'); // Redirect to login page
    exit();
}

if (isset($_SESSION['USERNAME'])) {
    echo "Welcome : " . $_SESSION['USERNAME'];
}
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
                    <li><a href="master.php"><span class="glyphicon glyphicon-home"></span> Home</a></li>
                    <li><a href="about.php"><span class="glyphicon glyphicon-exclamation-sign"></span> About</a></li>
                    <li><a href="contact.php"><span class="glyphicon glyphicon-earphone"></span> Contact</a></li>
                </ul>
                <ul class="nav navbar-nav navbar-right">
                    <?php
                    if (isset($_SESSION['USERNAME'])) {
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

    <!-- Main content of the page -->

    <!-- Include footer from 'footer.php' -->
    <?php include('footer.php'); ?>

</body>
</html>
