<?php
session_start();

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Include the common header file
// This will bring in the <head> section and the opening <body> tag
include '../includes/header.php';
?>

    <div class="dashboard-wrapper">

        <?php include '../includes/sidebar.php'; ?>

        <div class="main-content" id="main-content">
            <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top px-3 custom-navbar-top">
                <div class="container-fluid">
                    <button id="sidebarToggle" class="btn btn-outline-light d-lg-none me-3" type="button">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <button id="sidebarToggleDesktop" class="btn btn-outline-light d-none d-lg-block me-3" type="button">
                        <i class="fas fa-bars"></i> </button>

                    <a class="navbar-brand" href="#">POS System</a>

                    <div class="collapse navbar-collapse" id="navbarNav">
                        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                            </ul>
                        <ul class="navbar-nav ms-auto">
                            <li class="nav-item d-flex align-items-center">
                                <span class="nav-link text-white me-2">Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?> (Role: <?php echo htmlspecialchars($_SESSION["role"]); ?>)</span>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link btn btn-danger btn-sm text-white" href="../index.php">Logout</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <div class="container-fluid dashboard-page-content mt-5 pt-3">
                <h1 class="mb-4"> Mitzikikay General Merchandise Point Of Sale Dashboard!</h1>
                <p>This dashboard provides an overview of today's sales, low stock items, and your total products.</p>

                <div class="row">
                    <div class="col-md-4">
                        <div class="card text-center mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Today's Sales</h5>
                                <p class="card-text fs-2">₱ <span id="todaySalesAmount">0.00</span></p>
                                <a href="pos_system.php" class="btn btn-primary">Go to Sales</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Low Stock Items</h5>
                                <p class="card-text fs-2"><span id="lowStockCount">0</span></p>
                                <a href="inventory.php" class="btn btn-warning">View Inventory</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Total Products</h5>
                                <p class="card-text fs-2"><span id="totalProductsCount">0</span></p>
                                <a href="inventory.php" class="btn btn-info">Manage Products</a>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
        </div>
    </div>

    <div class="overlay" id="overlay"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../public/js/main.js"></script>
    <script src="../public/js/dashboard_script.js"></script> </body>
</html>
