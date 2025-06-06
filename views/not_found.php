<?php
session_start(); // Start session to potentially use $_SESSION data (e.g., username for welcome)

// Define the base URL path for consistency in includes
// This should match your project's root path relative to the web server's document root
$base_url_path = '/capstonefinal';

// Include the common header file
// This will bring in the <head> section and the opening <body> tag
include '../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include '../includes/sidebar.php'; // Include sidebar for consistent navigation ?>

    <div class="main-content" id="main-content">
        <!-- Navigation bar (if you want it on your 404 page, otherwise you can omit it) -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top px-3 custom-navbar-top">
            <div class="container-fluid">
                <button id="sidebarToggle" class="btn btn-outline-light d-lg-none me-3" type="button">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <button id="sidebarToggleDesktop" class="btn btn-outline-light d-none d-lg-block me-3" type="button">
                    <i class="fas fa-bars"></i>
                </button>
                <a class="navbar-brand" href="#">POS System</a>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0"></ul>
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item d-flex align-items-center">
                            <span class="nav-link text-white me-2">Welcome, <?php echo htmlspecialchars($_SESSION["username"] ?? 'Guest'); ?> (Role: <?php echo htmlspecialchars($_SESSION["role"] ?? 'N/A'); ?>)</span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-danger btn-sm text-white" href="<?php echo $base_url_path; ?>/logout.php">Logout</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="container-fluid dashboard-page-content mt-5 pt-3 text-center">
            <div class="py-5">
                <h1 class="display-1 fw-bold text-dark">404</h1>
                <h2 class="display-4 mb-4">Page Not Found</h2>
                <p class="lead mb-4">The page you're looking for doesn't exist or has been moved.</p>
                <a href="<?php echo $base_url_path; ?>/views/dashboard.php" class="btn btn-primary btn-lg mt-3">Go to Dashboard</a>
                <a href="<?php echo $base_url_path; ?>/index.php" class="btn btn-secondary btn-lg mt-3 ms-2">Go to Home</a>
            </div>
        </div>
    </div>

    <div class="overlay" id="overlay"></div>

    
