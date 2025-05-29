<?php
// views/reports/add_stocks.php (now for adding stock to existing products)

require_once '../../config/db.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/functions.php'; // For get_db_connection() and sanitize_input()

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Role-based access control (adjust roles as necessary)
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff') {
    header("Location: ../dashboard.php");
    exit();
}

$conn = get_db_connection();

// Define a low stock threshold
define('LOW_STOCK_THRESHOLD', 10); // You can adjust this value as needed

// Initialize variables for form fields and messages
$product_id = '';
$quantity_to_add = '';

$message = '';
$message_type = '';

// Fetch products for the dropdown AND for the table display
$products = [];
if ($conn) {
    // Modified SQL query to fetch all necessary product details including category name
    $sql_products = "
        SELECT
            p.id,
            p.name,
            p.stock_quantity,
            p.price,
            p.barcode,
            c.name AS category_name
        FROM
            products p
        LEFT JOIN
            categories c ON p.category_id = c.id
        ORDER BY
            p.name ASC";
    $result_products = $conn->query($sql_products);
    if ($result_products && $result_products->num_rows > 0) {
        while ($row = $result_products->fetch_assoc()) {
            $products[] = $row;
        }
        $result_products->free();
    } else {
        $message .= "No products found in the database. Please add products first to manage stock.<br>";
        $message_type = 'warning';
    }
} else {
    $message .= "Database connection not established for fetching products.";
    $message_type = 'danger';
}

// --- Handle Form Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Retrieve and Sanitize Form Data
    $product_id = sanitize_input($_POST['product_id'] ?? '');
    $quantity_to_add = sanitize_input($_POST['quantity_to_add'] ?? '');
    $user_id = $_SESSION['user_id'] ?? null; // Get the ID of the logged-in user

    // 2. Basic Validation
    if (empty($product_id) || empty($quantity_to_add)) {
        $message = "Both Product and Quantity to Add are required.";
        $message_type = 'danger';
    } elseif (!is_numeric($product_id) || $product_id <= 0) {
        $message = "Invalid Product selected.";
        $message_type = 'danger';
    } elseif (!is_numeric($quantity_to_add) || $quantity_to_add <= 0) {
        $message = "Quantity to Add must be a positive number.";
        $message_type = 'danger';
    } elseif (!$user_id) {
        $message = "User not logged in. Cannot log stock change.";
        $message_type = 'danger';
    }
    else {
        // 3. Update Product Stock in Database and log history
        if ($conn) {
            try {
                $conn->begin_transaction();

                // Step A: Update products table
                $stmt_update_product = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity + ?, updated_at = NOW() WHERE id = ?");
                if ($stmt_update_product) {
                    $stmt_update_product->bind_param("ii", $quantity_to_add, $product_id);

                    if ($stmt_update_product->execute()) {
                        // Step B: Get the NEW current stock quantity AFTER the update
                        $stmt_get_new_stock = $conn->prepare("SELECT stock_quantity FROM products WHERE id = ?");
                        $stmt_get_new_stock->bind_param("i", $product_id);
                        $stmt_get_new_stock->execute();
                        $result_new_stock = $stmt_get_new_stock->get_result();
                        $new_stock_after_add = $result_new_stock->fetch_assoc()['stock_quantity'];
                        $stmt_get_new_stock->close();

                        // Step C: Log the stock change to stock_history
                        $stmt_log_stock_history = $conn->prepare(
                            "INSERT INTO stock_history (product_id, quantity_change, current_quantity_after_change, change_type, change_date, user_id, description)
                            VALUES (?, ?, ?, ?, NOW(), ?, ?)"
                        );

                        $change_type = 'purchase_in'; // Or 'adjustment_in'
                        $description = "Stock added manually (via Add Stock form) for product ID: $product_id";

                        $stmt_log_stock_history->bind_param(
                            "iiisss", // i for int, s for string (for description, user_id can be int)
                            $product_id,
                            $quantity_to_add, // Positive for addition
                            $new_stock_after_add,
                            $change_type,
                            $user_id,
                            $description
                        );
                        $stmt_log_stock_history->execute();
                        $stmt_log_stock_history->close();


                        $conn->commit(); // Commit the transaction
                        $message = "Stock successfully added to product!"; // Message for the redirect
                        $message_type = 'success'; // Type for the redirect

                        // RE-ENABLE THIS REDIRECT TO DISPLAY THE SUCCESS NOTIFICATION
                        header("Location: add_stocks.php?status=success&msg=" . urlencode($message));
                        exit(); // Crucial to stop further execution after redirect


                    } else {
                        $conn->rollback();
                        $message = "Error adding stock: " . $stmt_update_product->error;
                        $message_type = 'danger';
                    }
                    $stmt_update_product->close();
                } else {
                    $conn->rollback();
                    $message = "Failed to prepare stock update query: " . $conn->error;
                    $message_type = 'danger';
                }
            } catch (mysqli_sql_exception $e) {
                $conn->rollback();
                $message = "Database error: " . $e->getMessage();
                $message_type = 'danger';
            }
        } else {
            $message = "Database connection not established.";
            $message_type = 'danger';
        }
    }
}

// Retrieve messages from GET parameters if redirected
// RE-ENABLE THIS BLOCK TO PROCESS MESSAGES FROM THE URL
if (isset($_GET['status']) && isset($_GET['msg'])) {
    $message_type = $_GET['status'];
    $message = urldecode($_GET['msg']);
}


// Close the database connection (only if it's currently open)
if ($conn) {
    $conn->close();
}

include '../../includes/header.php'; // Includes your header with Bootstrap and custom CSS
?>

<div class="dashboard-wrapper">
    <?php include '../../includes/sidebar.php'; ?>
    <div class="main-content" id="main-content">
        <?php $base_url_path = '/capstonefinal'; ?>
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
                            <a class="nav-link btn btn-danger btn-sm text-white" href="<?php echo $base_url_path; ?>/index.php">Logout</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="container-fluid dashboard-page-content mt-5 pt-3">

            <h2 class="mb-4">Add Stock to Existing Product</h2>

            <?php
            // The PHP block for displaying the alert from $message and $message_type
            // is now commented out to prevent duplicate notifications.
            // The JavaScript below will handle displaying alerts from URL parameters.
            /*
            if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show no-print" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif;
            */
            ?>

            <div class="card mb-4">
                <div class="card-header">
                    Add Stock Form
                </div>
                <div class="card-body">
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="form-group mb-3">
                            <label for="product_id" class="form-label">Product:</label>
                            <select id="product_id" name="product_id" class="form-select" required>
                                <option value="">Select a Product</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?php echo htmlspecialchars($product['id']); ?>"
                                        <?php echo ($product['id'] == $product_id) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($product['name']); ?> (Current Stock: <?php echo number_format($product['stock_quantity']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label for="quantity_to_add" class="form-label">Quantity to Add:</label>
                            <input type="number" id="quantity_to_add" name="quantity_to_add" class="form-control" value="<?php echo htmlspecialchars($quantity_to_add); ?>" required min="1">
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
                    <?php if (!empty($products)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
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
                                    <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product['id']); ?></td>
                                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                                            <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                            <td>₱<?php echo number_format($product['price'], 2); ?></td>
                                            <td>
                                                <?php echo number_format($product['stock_quantity']); ?>
                                                <?php if ($product['stock_quantity'] <= LOW_STOCK_THRESHOLD): ?>
                                                    <span class="badge bg-warning text-dark ms-2">(Low Stock)</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($product['barcode']); ?></td>
                                            <td>
                                                <a href="#" class="btn btn-info btn-sm me-1" title="Edit"><i class="fas fa-edit"></i></a>
                                                <a href="#" class="btn btn-danger btn-sm" title="Delete"><i class="fas fa-trash-alt"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No products found to display inventory.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="overlay" id="overlay"></div>
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../public/js/main.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // This entire block handles alerts from GET parameters.
        // It's re-enabled to allow success messages to be displayed.
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');
        const msg = urlParams.get('msg');

        if (status && msg) {
            const alertPlaceholder = document.querySelector('.dashboard-page-content'); // Adjust selector if needed
            const alertHtml = `
                <div class="alert alert-${status} alert-dismissible fade show no-print" role="alert">
                    ${decodeURIComponent(msg)}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            if (alertPlaceholder) {
                // Prepend or append the alert
                alertPlaceholder.insertAdjacentHTML('afterbegin', alertHtml);
                const newAlert = alertPlaceholder.querySelector('.alert');
                setTimeout(() => {
                    const bootstrapAlert = new bootstrap.Alert(newAlert);
                    bootstrapAlert.close();
                }, 5000); // 5 seconds
                // Remove the URL parameters after displaying the alert to clean the URL
                history.replaceState({}, document.title, window.location.pathname);
            }
        }
        // The 'else' block for handling non-redirected messages is not needed here
        // as all messages (success/error) will now be handled via URL parameters and redirects.


        // Toggle sidebar on desktop
        const sidebarToggleDesktop = document.getElementById('sidebarToggleDesktop');
        if (sidebarToggleDesktop) {
            sidebarToggleDesktop.addEventListener('click', function() {
                document.body.classList.toggle('sidebar-toggled');
                document.querySelector('.sidebar').classList.toggle('toggled');
            });
        }
    });
</script>
</body>
</html>