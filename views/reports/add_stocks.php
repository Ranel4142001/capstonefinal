<?php
// views/reports/add_stocks.php (now for adding stock to existing products)

// Include authentication check to ensure the user is logged in.
require_once '../../includes/auth_check.php';

// Set a flag to check if the user is an admin
$is_admin = ($_SESSION['role'] === 'admin');

// Include common utility functions.
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
                <button id="sidebarToggle" class="btn btn-outline-light d-lg-none me-3" type="button">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <button id="sidebarToggleDesktop" class="btn btn-outline-light d-none d-lg-block me-3" type="button">
                    <i class="fas fa-bars"></i> </button>
                <a class="navbar-brand" href="#">POS System</a>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0"></ul>
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item d-flex align-items-center">
                            <span class="nav-link text-white me-2">Welcome, <?php echo htmlspecialchars($_SESSION["username"] ?? 'Guest'); ?> (<?php echo htmlspecialchars($_SESSION["role"] ?? 'N/A'); ?>)</span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-danger btn-sm text-white" href="<?php echo $base_url_path; ?>/logout.php">Logout</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="container-fluid dashboard-page-content mt-5 pt-3">
            <h2 class="mb-4">Add Stock to Existing Product</h2>

            <?php if (!$is_admin): ?>
                <div class="alert alert-warning" role="alert">
                    You do not have permission to add stock. Only administrators can perform this action.
                </div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header">
                    Add Stock Form
                </div>
                <div class="card-body">
                    <form id="addStockForm">
                        <fieldset <?php echo !$is_admin ? 'disabled' : ''; ?>>
                            <div class="form-group mb-3">
                                <label for="product_id" class="form-label">Product:</label>
                                <select id="product_id" name="product_id" class="form-select" required>
                                    <option value="">Loading Products...</option>
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label for="quantity_to_add" class="form-label">Quantity to Add:</label>
                                <input type="number" id="quantity_to_add" name="quantity_to_add" class="form-control" value="" required min="1">
                            </div>

                            <button type="submit" class="btn btn-primary mt-3">Add Stock</button>
                        </fieldset>
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
<script src="//kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../public/js/main.js"></script>
<script src="../../public/js/add_stocks_script.js"></script>
</body>
</html>