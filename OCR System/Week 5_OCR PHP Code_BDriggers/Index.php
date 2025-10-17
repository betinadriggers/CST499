<?php
error_reporting(E_ALL ^ E_NOTICE);
ini_set('session.use_only_cookies', '1');
session_start();

// Check if logout is requested
if (isset($_GET['logout'])) {
    // Destroy session variables and the session itself
    session_unset(); // Remove all session variables
    session_destroy(); // Destroy the session
    header('Location: login.php'); // Redirect to login page
    exit(); // Make sure the script stops executing
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Course Registration Portal</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

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
    </style>
</head>
<body>

<!-- Centered Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container">
        <div class="collapse navbar-collapse justify-content-center" id="navbarNav">
            <ul class="navbar-nav text-center">
                <li class="nav-item">
                    <a class="nav-link active" href="master.php"><i class="bi bi-house-fill"></i> Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="about.php"><i class="bi bi-info-circle-fill"></i> About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#"><i class="bi bi-telephone-fill"></i> Contact</a>
                </li>
                <?php if (isset($_SESSION['USERNAME'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php"><i class="bi bi-person-badge-fill"></i> Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?logout=1"><i class="bi bi-box-arrow-right"></i> Logout</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php"><i class="bi bi-box-arrow-in-right"></i> Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php"><i class="bi bi-pencil-square"></i> Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
    </div>
</nav>

<!-- Main content of the page -->

<!-- Include footer from 'footer.php' -->
<?php include('footer.php'); ?>

<!-- Bootstrap JS and dependencies -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"></script>

</body>
</html>
