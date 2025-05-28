<?php
session_start();

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Optional: Check role for inventory access if needed, e.g.:
// if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager') {
//     header("location: dashboard.php"); // Or unauthorized page
//     exit;
// }

// Include database connection (though most data will be fetched via JS/API)
require_once '../config/db.php';

// Fetch categories for the filter dropdown
$categories = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Log error, but don't stop page load, just show empty categories
    error_log("Error fetching categories: " . $e->getMessage());
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
                        <ul class="navbar-nav me-auto mb-2 mb-lg-0"></ul>
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
                <h2 class="mb-4">Inventory Management</h2>

                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Product List</h5>
                        <a href="add_product.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add New Product
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <input type="text" id="productSearch" class="form-control" placeholder="Search by name or barcode...">
                            </div>
                            <div class="col-md-4">
                                <select id="categoryFilter" class="form-select">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo htmlspecialchars($category['id']); ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-outline-secondary w-100" id="resetFiltersBtn">Reset</button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
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
                                <tbody id="productTableBody">
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">Loading products...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <nav aria-label="Product Pagination">
                            <ul class="pagination justify-content-center" id="productPagination">
                                </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="overlay" id="overlay"></div>

    <div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProductModalLabel">Edit Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editProductForm">
                        <input type="hidden" id="editProductId" name="id">
                        <div class="mb-3">
                            <label for="editProductName" class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="editProductName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="editProductCategory" class="form-label">Category</label>
                            <select class="form-select" id="editProductCategory" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category['id']); ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editProductPrice" class="form-label">Price</label>
                            <input type="number" step="0.01" class="form-control" id="editProductPrice" name="price" required>
                        </div>
                        <div class="mb-3">
                            <label for="editProductStock" class="form-label">Stock</label>
                            <input type="number" class="form-control" id="editProductStock" name="stock_quantity" required>
                        </div>
                        <div class="mb-3">
                            <label for="editProductBarcode" class="form-label">Barcode</label>
                            <input type="text" class="form-control" id="editProductBarcode" name="barcode">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../public/js/main.js"></script>
    <script src="../public/js/inventory_script.js"></script>
</body>
</html>
