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

        .about-content {
            max-width: 800px;
            margin: 40px auto;
            padding: 25px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
        }

        .about-content h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .about-content p {
            font-size: 1.2em;
            text-align: justify;
            line-height: 1.6;
        }

        .about-content ul {
            list-style-type: none;
            padding-left: 0;
        }

        .about-content ul li {
            font-size: 1.2em;
            margin: 10px 0;
        }

        .about-content ul li::before {
            content: "✔";
            margin-right: 10px;
            color: green;
        }

        .login-btn-container {
            text-align: center;
            margin-top: 30px;
        }

        .login-btn-container .btn {
            font-size: 18px;
            padding: 10px 20px;
        }
    </style>

    <script>
        $(document).ready(function(){
            // Refresh the page when the About button is clicked
            $("a[href='about.php']").click(function(event){
                event.preventDefault(); // Prevent default behavior
                location.reload(); // Refresh the page
            });
        });
    </script>
</head>

<body>
    <div class="jumbotron">
        <div class="container text-center">
            <h1>About the Online Course Registration Portal</h1>
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

    <div class="container">
        <div class="about-content">
            <h2>Welcome to the Online Course Registration Portal</h2>
            
            <h3>Key Features:</h3>
            <ul>
                <li>Manage your profile information.</li>
                <li>Access important course information.</li>
                <li>View and cancel scheduled courses.</li>
                <li>View and submit course waitlist request.</li>
            </ul>
            
            <!-- Login Button -->
            <div class="login-btn-container">
                <a href="login.php" class="btn btn-primary">Login</a>
            </div>
        </div>
    </div>

    <?php include('footer.php'); ?>
</body>
</html>
