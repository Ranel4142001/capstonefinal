<?php
// views/reports/sales_report.php

// Include authentication check to ensure the user is logged in and has appropriate permissions
require_once '../../includes/auth_check.php';

// Check if the user has permission to view reports (e.g., admin or staff)
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff') {
    header("Location: ../dashboard.php"); // Redirect non-authorized users
    exit();
}

// Include common utility functions
require_once '../../includes/functions.php';

// Start a session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Initialize database connection
$conn = get_db_connection();

// --- Date Filtering Logic ---
$start_date = isset($_GET['start_date']) ? sanitize_input($_GET['start_date']) : date('Y-m-01'); // Default to start of current month
$end_date = isset($_GET['end_date']) ? sanitize_input($_GET['end_date']) : date('Y-m-d'); // Default to current date

// Prepare message variables
$message = '';
$message_type = '';

// --- Fetch Sales Data ---
$sales_data = [];
$total_sales_amount = 0;
$total_items_sold = 0;

try {
    // SQL query to fetch sales, joining with sale_items, products, and users
    // This query aggregates sales by transaction and also gets individual item details for display
    $sql = "
        SELECT
            s.id AS sale_id,
            s.sale_date,
            s.total_amount,
            u.username AS cashier_name,
            GROUP_CONCAT(CONCAT(pi.name, ' (Qty: ', si.quantity, ' @ ', si.price_at_sale, ')') SEPARATOR '<br>') AS items_sold
        FROM
            sales s
        JOIN
            users u ON s.cashier_id = u.id
        LEFT JOIN
            sale_items si ON s.id = si.sale_id
        LEFT JOIN
            products pi ON si.product_id = pi.id
        WHERE
            s.sale_date BETWEEN ? AND ? + INTERVAL 1 DAY
        GROUP BY
            s.id, s.sale_date, s.total_amount, u.username
        ORDER BY
            s.sale_date DESC;
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        // Bind parameters for the date range
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sales_data[] = $row;
                $total_sales_amount += $row['total_amount'];
            }

            // Get total items sold for the summary
            $sql_total_items = "
                SELECT SUM(si.quantity) AS total_qty
                FROM sales s
                JOIN sale_items si ON s.id = si.sale_id
                WHERE s.sale_date BETWEEN ? AND ? + INTERVAL 1 DAY;
            ";
            $stmt_items = $conn->prepare($sql_total_items);
            if ($stmt_items) {
                $stmt_items->bind_param("ss", $start_date, $end_date);
                $stmt_items->execute();
                $result_items = $stmt_items->get_result();
                if ($result_items->num_rows > 0) {
                    $row_items = $result_items->fetch_assoc();
                    $total_items_sold = $row_items['total_qty'] ?? 0;
                }
                $stmt_items->close();
            } else {
                $message .= "Failed to prepare total items query: " . $conn->error . "<br>";
                $message_type = 'danger';
            }

        } else {
            // No sales found for the selected date range
            $message = "No sales found for the selected date range.";
            $message_type = 'info';
        }
        $stmt->close();
    } else {
        // Database query preparation failed
        $message = "Database query preparation failed: " . $conn->error;
        $message_type = 'danger';
    }
} catch (mysqli_sql_exception $e) {
    // Catch database connection or query execution errors
    $message = "Database error fetching sales data: " . $e->getMessage();
    $message_type = 'danger';
}

// Close the database connection
$conn->close();

// Include the common header file
// Assuming header.php contains the <html>, <head>, and opening <body> tags,
// along with Bootstrap CSS and your custom CSS.
include '../../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php
    // Include sidebar navigation
    // sidebar.php should contain the HTML for your sidebar.
    include '../../includes/sidebar.php';
    ?>
    <div class="main-content" id="main-content">
        <?php
        // This is your top navigation bar/header.
        // It's crucial for consistent UI across features.
        // I've included it directly here as it seems to be part of the main-content area.
        ?>
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
                            <span class="nav-link text-white me-2">Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?> (Role: <?php echo htmlspecialchars($_SESSION["role"]); ?>)</span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-danger btn-sm text-white" href="../logout.php">Logout</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="container-fluid dashboard-page-content mt-5 pt-3">
            <h2 class="mb-4">Sales Report</h2>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header">
                    Filter Sales Report
                </div>
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="start_date" class="form-label">Start Date:</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="end_date" class="form-label">End Date:</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary w-100">Apply Filter</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    Summary for <?php echo htmlspecialchars($start_date); ?> to <?php echo htmlspecialchars($end_date); ?>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="summary-box bg-success text-white text-center p-3 rounded shadow-sm">
                                <h4>Total Sales Amount:</h4>
                                <h3>₱<?php echo number_format($total_sales_amount, 2); ?></h3>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="summary-box bg-info text-white text-center p-3 rounded shadow-sm">
                                <h4>Total Items Sold:</h4>
                                <h3><?php echo number_format($total_items_sold); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Detailed Sales Transactions
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Sale ID</th>
                                    <th>Date</th>
                                    <th>Cashier</th>
                                    <th>Items Sold</th>
                                    <th>Total Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($sales_data)): ?>
                                    <?php foreach ($sales_data as $sale): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($sale['sale_id']); ?></td>
                                            <td><?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($sale['sale_date']))); ?></td>
                                            <td><?php echo htmlspecialchars($sale['cashier_name']); ?></td>
                                            <td><?php echo $sale['items_sold']; ?></td>
                                            <td>₱<?php echo number_format($sale['total_amount'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No sales transactions found for this period.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="overlay" id="overlay"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../public/js/main.js"></script>
<script>
    // This script only targets elements with the 'alert' class for auto-hiding.
    // Your summary boxes no longer have this class.
    document.addEventListener('DOMContentLoaded', function() {
        const alertElement = document.querySelector('.alert'); // Renamed variable to avoid conflict with Bootstrap's Alert constructor
        if (alertElement) {
            setTimeout(() => {
                const bootstrapAlert = new bootstrap.Alert(alertElement);
                bootstrapAlert.close();
            }, 5000); // 5 seconds
        }
    });
</script>
</body>
</html>