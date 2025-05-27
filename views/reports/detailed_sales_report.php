<?php
// views/reports/detailed_sales_report.php

require_once '../../includes/auth_check.php';

// Ensure only 'admin' or 'staff' can access this page
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff') {
    header("Location: ../dashboard.php");
    exit();
}

require_once '../../includes/functions.php';

// Start session if it hasn't been started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$conn = get_db_connection();

// --- Date Filtering Logic ---
$start_date = isset($_GET['start_date']) ? sanitize_input($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? sanitize_input($_GET['end_date']) : date('Y-m-d');

// --- Pagination Logic ---
$allowed_records_per_page = [10, 20, 30, 40, 50, 60, 70, 80, 90, 100,];
$default_records_per_page = 10; // Default if not specified or invalid

$records_per_page = $default_records_per_page;
if (isset($_GET['records_per_page']) && in_array((int)$_GET['records_per_page'], $allowed_records_per_page)) {
    $records_per_page = (int)$_GET['records_per_page'];
}

$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;

$message = '';
$message_type = '';

// --- Fetch Sales Data for Table ---
$sales_data = [];
$total_sales_count = 0;
$total_pages = 1;

try {
    // First, get the total count of sales for the date range for pagination
    $count_sql = "
        SELECT COUNT(s.id) AS total_sales_count
        FROM sales s
        WHERE s.sale_date BETWEEN ? AND ? + INTERVAL 1 DAY;
    ";
    $count_stmt = $conn->prepare($count_sql);
    if ($count_stmt) {
        $count_stmt->bind_param("ss", $start_date, $end_date);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $count_row = $count_result->fetch_assoc();
        $total_sales_count = $count_row['total_sales_count'];
        $total_pages = ceil($total_sales_count / $records_per_page);
        $count_stmt->close();
    } else {
        $message .= "Failed to prepare sales count query: " . $conn->error . "<br>";
        $message_type = 'danger';
    }

    // SQL query to fetch sales with LIMIT and OFFSET for pagination
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
            s.sale_date DESC
        LIMIT ? OFFSET ?;
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ssii", $start_date, $end_date, $records_per_page, $offset);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $sales_data[] = $row;
            }
        } else {
            $message = "No sales found for the selected date range.";
            $message_type = 'info';
        }
        $stmt->close();
    } else {
        $message = "Database query preparation failed: " . $conn->error;
        $message_type = 'danger';
    }
} catch (mysqli_sql_exception $e) {
    $message = "Database error fetching sales data: " . $e->getMessage();
    $message_type = 'danger';
}

// --- Fetch Overall Sales Summary ---
$total_sales_amount = 0;
$total_items_sold = 0;
try {
    $sql_overall_summary = "
        SELECT
            SUM(s.total_amount) AS overall_total_amount,
            SUM(si.quantity) AS overall_total_qty
        FROM
            sales s
        LEFT JOIN
            sale_items si ON s.id = si.sale_id
        WHERE
            s.sale_date BETWEEN ? AND ? + INTERVAL 1 DAY;
    ";
    $stmt_overall_summary = $conn->prepare($sql_overall_summary);
    if ($stmt_overall_summary) {
        $stmt_overall_summary->bind_param("ss", $start_date, $end_date);
        $stmt_overall_summary->execute();
        $result_overall_summary = $stmt_overall_summary->get_result();
        if ($result_overall_summary->num_rows > 0) {
            $summary_row = $result_overall_summary->fetch_assoc();
            $total_sales_amount = $summary_row['overall_total_amount'] ?? 0;
            $total_items_sold = $summary_row['overall_total_qty'] ?? 0;
        }
        $stmt_overall_summary->close();
    } else {
        $message .= "Failed to prepare overall summary query: " . $conn->error . "<br>";
        $message_type = 'danger';
    }
} catch (mysqli_sql_exception $e) {
    $message .= "Database error fetching overall summary: " . $e->getMessage();
    $message_type = 'danger';
}

// Close the database connection
$conn->close();

include '../../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include '../../includes/sidebar.php'; ?>
    <div class="main-content" id="main-content">
        <?php $base_url_path = '/capstonefinal'; ?>
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
                <h3>Sales Report</h3>
                <p>Date Range: <?php echo htmlspecialchars($start_date); ?> to <?php echo htmlspecialchars($end_date); ?></p>
                <p>Generated by: <?php echo htmlspecialchars($_SESSION["username"]); ?> on <?php echo date('Y-m-d H:i:s'); ?></p>
            </div>
            <h2 class="mb-4 no-print">Detailed Sales Report</h2>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show no-print" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card mb-4 no-print">
                <div class="card-header">
                    Filter Sales Report
                </div>
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">Start Date:</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">End Date:</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="records_per_page" class="form-label">Records per page:</label>
                            <select class="form-select" id="records_per_page" name="records_per_page">
                                <?php foreach ($allowed_records_per_page as $value): ?>
                                    <option value="<?php echo $value; ?>" <?php echo ($value == $records_per_page) ? 'selected' : ''; ?>>
                                        <?php echo $value; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">Apply Filter</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="mb-4 text-end no-print">
                <button class="btn btn-secondary" onclick="printReportInNewWindow()"><i class="fas fa-print"></i> Print Report</button>
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

                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Sales Pagination" class="mt-4 no-print">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?start_date=<?php echo htmlspecialchars($start_date); ?>&end_date=<?php echo htmlspecialchars($end_date); ?>&records_per_page=<?php echo htmlspecialchars($records_per_page); ?>&page=<?php echo $current_page - 1; ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span> Previous
                                    </a>
                                </li>
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?start_date=<?php echo htmlspecialchars($start_date); ?>&end_date=<?php echo htmlspecialchars($end_date); ?>&records_per_page=<?php echo htmlspecialchars($records_per_page); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?start_date=<?php echo htmlspecialchars($start_date); ?>&end_date=<?php echo htmlspecialchars($end_date); ?>&records_per_page=<?php echo htmlspecialchars($records_per_page); ?>&page=<?php echo $current_page + 1; ?>" aria-label="Next">
                                        Next <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<div class="overlay" id="overlay"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../public/js/main.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const alertElement = document.querySelector('.alert');
        if (alertElement) {
            setTimeout(() => {
                const bootstrapAlert = new bootstrap.Alert(alertElement);
                bootstrapAlert.close();
            }, 5000); // 5 seconds
        }
    });

    // Custom print function: Opens content in a new window for printing
    function printReportInNewWindow() {
        var printContents = document.getElementById('printableArea').innerHTML;
        var originalTitle = document.title; // Store original title

        // Create a new window
        var printWindow = window.open('', '_blank', 'height=800,width=800');

        printWindow.document.write('<!DOCTYPE html>');
        printWindow.document.write('<html lang="en">');
        printWindow.document.write('<head>');
        printWindow.document.write('<meta charset="UTF-8">');
        printWindow.document.write('<meta name="viewport" content="width=device-width, initial-scale=1.0">');
        printWindow.document.write('<title>Sales Report Print</title>');

        // Link your CSS files from the main page
        // IMPORTANT: Adjust the paths if your CSS files are not relative to the root like this
        printWindow.document.write('<link rel="stylesheet" href="<?php echo $base_url_path; ?>/public/css/style.css">');
        printWindow.document.write('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">');
        printWindow.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">');

        printWindow.document.write('</head>');
        printWindow.document.write('<body>');
        printWindow.document.write(printContents); // Insert the content to print
        printWindow.document.write('</body>');
        printWindow.document.write('</html>');

        printWindow.document.close(); // Close the document to ensure content is parsed
        printWindow.focus(); // Focus the new window

        // Give the browser a moment to render the new window's content and load CSS
        // Then trigger the print.
        printWindow.onload = function() {
            // Check if styles are loaded, if necessary, you can use a timeout as a fallback
            setTimeout(function() {
                printWindow.print();
                printWindow.close(); // Close the print window after printing
            }, 500); // Small delay to ensure CSS loads
        };
    }
</script>
</body>
</html>