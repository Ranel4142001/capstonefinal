<?php
// views/reports/stock_report.php

// 1. Authenticate and start session FIRST
require_once '../../includes/auth_check.php';

// Ensure only 'admin' or 'staff' can access this page
if (empty($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff')) {
    header("Location: ../dashboard.php"); // Redirect to dashboard or login page if not authorized
    exit();
}

// 2. Include other necessary PHP files for database connection and functions
require_once '../../config/db.php';
require_once '../../includes/functions.php';

// --- Pagination Logic ---
$allowed_records_per_page = [10, 20, 30, 40, 50, 60, 70, 80, 90, 100];
$default_records_per_page = 10; // Default if not specified or invalid

// Get current page from URL, default to 1
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) {
    $current_page = 1;
}

// Get records per page from URL, validate against allowed values
$records_per_page = isset($_GET['per_page']) && in_array((int)$_GET['per_page'], $allowed_records_per_page) ? (int)$_GET['per_page'] : $default_records_per_page;

// Calculate offset for the SQL query
$offset = ($current_page - 1) * $records_per_page;

// --- Sorting Logic ---
$allowed_sort_orders = ['quantity_asc', 'quantity_desc', 'name_asc'];
$default_sort_order = 'quantity_asc'; // Default sort by lowest quantity

$sort_order = isset($_GET['sort_order']) && in_array($_GET['sort_order'], $allowed_sort_orders) ? $_GET['sort_order'] : $default_sort_order;

// Function to get total number of stock items (for pagination)
function getTotalStockCount($conn) {
    $sql = "SELECT COUNT(id) AS total_count FROM products";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        return $row['total_count'];
    }
    return 0;
}

// Function to get stock data from the database with LIMIT, OFFSET, and dynamic ORDER BY
function getStockData($conn, $limit, $offset, $sort_order) {
    $orderByClause = "";
    switch ($sort_order) {
        case 'quantity_asc':
            $orderByClause = "p.stock_quantity ASC, p.name ASC";
            break;
        case 'quantity_desc':
            $orderByClause = "p.stock_quantity DESC, p.name ASC";
            break;
        case 'name_asc': // Added a default sort by name if needed, though quantity is primary focus
        default:
            $orderByClause = "p.name ASC";
            break;
    }

    $sql = "SELECT
                p.id AS product_id,
                p.name AS product_name,
                c.name AS category_name,
                s.name AS supplier_name,
                p.stock_quantity,
                p.cost_price,
                p.price AS selling_price,
                p.is_active AS status
            FROM
                products p
            LEFT JOIN
                categories c ON p.category_id = c.id
            LEFT JOIN
                suppliers s ON p.supplier_id = s.id
            ORDER BY
                " . $orderByClause . "
            LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $stockData = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $stockData[] = $row;
        }
    }
    $stmt->close();
    return $stockData;
}

// Get database connection
$conn = get_db_connection();

// Fetch total stock count
$total_stock_items = getTotalStockCount($conn);

// Calculate total pages
$total_pages = ceil($total_stock_items / $records_per_page);

// Ensure current page is not greater than total pages (if any records exist)
if ($total_pages > 0 && $current_page > $total_pages) {
    $current_page = $total_pages;
    $offset = ($current_page - 1) * $records_per_page; // Recalculate offset for the adjusted page
} elseif ($total_pages == 0 && $current_page > 1) {
    $current_page = 1; // If no records, go to page 1
    $offset = 0;
}


// Fetch stock data with pagination and sorting
$stockItems = getStockData($conn, $records_per_page, $offset, $sort_order);

// Close database connection
$conn->close();

// 3. Include the header AFTER all PHP logic that might use sessions or send headers
require_once '../../includes/header.php'; // Adjust path
?>

<div class="dashboard-wrapper">
    <?php include '../../includes/sidebar.php'; // Adjust path ?>
    <div class="main-content" id="main-content">
        <?php $base_url_path = '/capstonefinal'; // Adjust if your base URL path is different ?>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top px-3 custom-navbar-top no-print">
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
                            <span class="nav-link text-white me-2">Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?> (Role: <?php echo htmlspecialchars($_SESSION["role"]); ?>)</span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-danger btn-sm text-white" href="<?php echo $base_url_path; ?>/index.php">Logout</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <div id="printableArea" class="container-fluid dashboard-page-content mt-5 pt-3">
            <div class="print-header">
                <h3>Stock Report</h3>
                <p>Generated by: <?php echo htmlspecialchars($_SESSION["username"]); ?> on <?php echo date('Y-m-d H:i:s'); ?></p>
            </div>
            <h2 class="mb-4 no-print">Stock Report</h2>

            <div class="card mb-4">
                <div class="card-header">
                    Current Stock Levels
                </div>
                <div class="card-body">
                    <div class="row align-items-center mb-3 no-print">
                        <div class="col-md-6">
                            <form action="" method="GET" class="d-flex align-items-center">
                                <label for="per_page" class="form-label me-2 mb-0">Records per page:</label>
                                <select class="form-select form-select-sm w-auto" id="per_page" name="per_page" onchange="this.form.submit()">
                                    <?php foreach ($allowed_records_per_page as $num): ?>
                                        <option value="<?php echo $num; ?>" <?php echo ($records_per_page == $num) ? 'selected' : ''; ?>>
                                            <?php echo $num; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="page" value="<?php echo $current_page; ?>">
                                <input type="hidden" name="sort_order" value="<?php echo htmlspecialchars($sort_order); ?>">
                            </form>
                        </div>
                        <div class="col-md-6 text-end">
                            <form action="" method="GET" class="d-flex align-items-center justify-content-end">
                                <label for="sort_order" class="form-label me-2 mb-0">Sort by Quantity:</label>
                                <select class="form-select form-select-sm w-auto" id="sort_order" name="sort_order" onchange="this.form.submit()">
                                    <option value="quantity_asc" <?php echo ($sort_order == 'quantity_asc') ? 'selected' : ''; ?>>Lowest First</option>
                                    <option value="quantity_desc" <?php echo ($sort_order == 'quantity_desc') ? 'selected' : ''; ?>>Highest First</option>
                                </select>
                                <input type="hidden" name="page" value="<?php echo $current_page; ?>">
                                <input type="hidden" name="per_page" value="<?php echo $records_per_page; ?>">
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Product ID</th>
                                    <th>Product Name</th>
                                    <th>Category</th>
                                    <th>Supplier</th>
                                    <th>Quantity in Stock</th>
                                    <th>Cost Price</th>
                                    <th>Selling Price</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($stockItems)): ?>
                                    <?php foreach ($stockItems as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['product_id']); ?></td>
                                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                            <td><?php echo htmlspecialchars($item['category_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($item['supplier_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($item['stock_quantity']); ?></td>
                                            <td>₱<?php echo number_format($item['cost_price'], 2); ?></td>
                                            <td>₱<?php echo number_format($item['selling_price'], 2); ?></td>
                                            <td>
                                                <?php
                                                    $statusClass = '';
                                                    $displayStatus = '';
                                                    $lowStockThreshold = 10; // Define a default low stock threshold

                                                    if (isset($item['status']) && $item['status'] == 0) { // Assuming 'is_active' 0 means inactive/discontinued
                                                        $statusClass = 'badge bg-danger';
                                                        $displayStatus = 'Inactive';
                                                    } elseif ($item['stock_quantity'] <= $lowStockThreshold) {
                                                        $statusClass = 'badge bg-warning text-dark';
                                                        $displayStatus = 'Low Stock';
                                                    } else {
                                                        $statusClass = 'badge bg-success';
                                                        $displayStatus = 'In Stock';
                                                    }
                                                ?>
                                                <span class="<?php echo $statusClass; ?>">
                                                    <?php echo $displayStatus; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No stock data available.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <nav aria-label="Page navigation" class="no-print">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $current_page - 1; ?>&per_page=<?php echo $records_per_page; ?>&sort_order=<?php echo htmlspecialchars($sort_order); ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo ($current_page == $i) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&per_page=<?php echo $records_per_page; ?>&sort_order=<?php echo htmlspecialchars($sort_order); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $current_page + 1; ?>&per_page=<?php echo $records_per_page; ?>&sort_order=<?php echo htmlspecialchars($sort_order); ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>

                </div>
            </div>
        </div>
    </div>
</div>

<div class="overlay" id="overlay"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../public/js/main.js"></script>
<script>
    // The printReportInNewWindow function has been removed as requested.
</script>
</body>
</html>
