<?php
// views/reports/add_stocks.php (now for adding stock to existing products)

// Include authentication check to ensure the user is logged in.
require_once '../../includes/auth_check.php';

// Ensure only authorized roles (admin or staff) can access this page.
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff') {
    header("Location: ../dashboard.php"); // Redirect non-authorized users.
    exit();
}

// Include common utility functions.
// This is essential for 'sanitize_input()' and 'get_db_connection()' (though get_db_connection
// is now called by the API, functions.php itself is still needed for sanitize_input and other common utilities).
require_once '../../includes/functions.php';

// Start a session if not already started.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Define a low stock threshold (used for display purposes in the table).
define('LOW_STOCK_THRESHOLD', 10);

// Include the common header file (contains HTML <head> and opening <body> tags, CSS links, etc.).
include '../../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php
    // Include the sidebar navigation.
    include '../../includes/sidebar.php';
    ?>
    <div class="main-content" id="main-content">
        <?php $base_url_path = '/capstonefinal'; // Define base URL path for consistency. ?>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top px-3 custom-navbar-top">
            <div class="container-fluid">
                <!-- Toggle button for mobile sidebar. -->
                <button id="sidebarToggle" class="btn btn-outline-light d-lg-none me-3" type="button">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <!-- Toggle button for desktop sidebar. -->
                <button id="sidebarToggleDesktop" class="btn btn-outline-light d-none d-lg-block me-3" type="button">
                    <i class="fas fa-bars"></i> <!-- Font Awesome icon for a menu bar. -->
                </button>
                <a class="navbar-brand" href="#">POS System</a>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0"></ul>
                    <ul class="navbar-nav ms-auto">
                        <!-- Display welcome message with username and role from session. -->
                        <li class="nav-item d-flex align-items-center">
                            <span class="nav-link text-white me-2">Welcome, <?php echo htmlspecialchars($_SESSION["username"] ?? 'Guest'); ?> (Role: <?php echo htmlspecialchars($_SESSION["role"] ?? 'N/A'); ?>)</span>
                        </li>
                        <!-- Logout button, using base URL path for consistency. -->
                        <li class="nav-item">
                            <a class="nav-link btn btn-danger btn-sm text-white" href="<?php echo $base_url_path; ?>/logout.php">Logout</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="container-fluid dashboard-page-content mt-5 pt-3">
            <h2 class="mb-4">Add Stock to Existing Product</h2>

            <div class="card mb-4">
                <div class="card-header">
                    Add Stock Form
                </div>
                <div class="card-body">
                    <!-- The form action is now handled by JavaScript via AJAX. -->
                    <form id="addStockForm">
                        <div class="form-group mb-3">
                            <label for="product_id" class="form-label">Product:</label>
                            <!-- The dropdown options will be populated by JavaScript. -->
                            <select id="product_id" name="product_id" class="form-select" required>
                                <option value="">Loading Products...</option>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label for="quantity_to_add" class="form-label">Quantity to Add:</label>
                            <input type="number" id="quantity_to_add" name="quantity_to_add" class="form-control" value="" required min="1">
                        </div>

                        <button type="submit" class="btn btn-primary mt-3">Add Stock</button>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    Current Product Inventory
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover" id="productInventoryTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Barcode</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Product inventory data will be loaded dynamically by public/js/add_stocks_script.js -->
                                <tr>
                                    <td colspan="7">Loading product inventory...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="overlay" id="overlay"></div>
<!-- Font Awesome for icons. Using protocol-relative URL to avoid CORS issues if page is HTTP. -->
<script src="//kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<!-- Bootstrap 5 JavaScript bundle (includes Popper.js) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Your main.js for global JavaScript functionalities (e.g., sidebar toggling) -->
<script src="../../public/js/main.js"></script>
<!-- NEW: Include the specific JavaScript for add_stocks page to handle AJAX operations -->
<script src="../../public/js/add_stocks_script.js"></script>
</body>
</html>
