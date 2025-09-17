<?php
// views/reports/sales_analytics.php

require_once '../../includes/auth_check.php';
require_once '../../includes/functions.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$conn = get_db_connection();

// --- Date Filtering Logic (same as detailed report for consistency) ---
$start_date = isset($_GET['start_date']) ? sanitize_input($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? sanitize_input($_GET['end_date']) : date('Y-m-d');

$message = '';
$message_type = '';

// --- Fetch Sales Analytics Data ---
$top_selling_products = [];
$sales_by_cashier = [];
$daily_sales_trend = [];

try {
    // Top 10 Selling Products (by quantity)
    $sql_top_products = "
        SELECT
            p.name AS product_name,
            SUM(si.quantity) AS total_quantity_sold,
            SUM(si.quantity * si.price_at_sale) AS total_product_sales
        FROM
            sale_items si
        JOIN
            products p ON si.product_id = p.id
        JOIN
            sales s ON si.sale_id = s.id
        WHERE
            s.sale_date BETWEEN ? AND ? + INTERVAL 1 DAY
        GROUP BY
            p.name
        ORDER BY
            total_quantity_sold DESC
        LIMIT 10;
    ";
    $stmt_top_products = $conn->prepare($sql_top_products);
    if ($stmt_top_products) {
        $stmt_top_products->bind_param("ss", $start_date, $end_date);
        $stmt_top_products->execute();
        $top_selling_products = $stmt_top_products->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_top_products->close();
    } else {
        $message .= "Failed to prepare top products query: " . $conn->error . "<br>";
        $message_type = 'danger';
    }

    // Sales by Cashier
    $sql_cashier_sales = "
        SELECT
            u.username AS cashier_name,
            SUM(s.total_amount) AS total_sales_by_cashier,
            COUNT(s.id) AS total_transactions
        FROM
            sales s
        JOIN
            users u ON s.cashier_id = u.id
        WHERE
            s.sale_date BETWEEN ? AND ? + INTERVAL 1 DAY
        GROUP BY
            u.username
        ORDER BY
            total_sales_by_cashier DESC;
    ";
    $stmt_cashier_sales = $conn->prepare($sql_cashier_sales);
    if ($stmt_cashier_sales) {
        $stmt_cashier_sales->bind_param("ss", $start_date, $end_date);
        $stmt_cashier_sales->execute();
        $sales_by_cashier = $stmt_cashier_sales->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_cashier_sales->close();
    } else {
        $message .= "Failed to prepare cashier sales query: " . $conn->error . "<br>";
        $message_type = 'danger';
    }

    // Daily Sales Trend
    $sql_daily_sales = "
        SELECT
            DATE(sale_date) AS sale_day,
            SUM(total_amount) AS daily_total_sales
        FROM
            sales
        WHERE
            sale_date BETWEEN ? AND ? + INTERVAL 1 DAY
        GROUP BY
            DATE(sale_date)
        ORDER BY
            sale_day ASC;
    ";
    $stmt_daily_sales = $conn->prepare($sql_daily_sales);
    if ($stmt_daily_sales) {
        $stmt_daily_sales->bind_param("ss", $start_date, $end_date);
        $stmt_daily_sales->execute();
        $daily_sales_trend = $stmt_daily_sales->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_daily_sales->close();
    } else {
        $message .= "Failed to prepare daily sales query: " . $conn->error . "<br>";
        $message_type = 'danger';
    }

} catch (mysqli_sql_exception $e) {
    $message .= "Database error fetching analytics data: " . $e->getMessage();
    $message_type = 'danger';
}

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
                            <span class="nav-link text-white me-2">Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?> (<?php echo htmlspecialchars($_SESSION["role"]); ?>)</span>
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
                <h3>Sales Analytics Report</h3>
                <p id="reportDateRange">Date Range: <?php echo htmlspecialchars($start_date); ?> to <?php echo htmlspecialchars($end_date); ?></p>
                <p>Generated by: <?php echo htmlspecialchars($_SESSION["username"] ?? 'N/A'); ?> on <?php echo date('Y-m-d H:i:s'); ?></p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show no-print" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card mb-4 no-print">
                <div class="card-header">
                    Filter Sales Analytics
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

            <div class="mb-4 text-end no-print">
                <button class="btn btn-secondary" onclick="printReportInNewWindow()"><i class="fas fa-print"></i> Print Report</button>
            </div>

            <div class="card">
                <div class="card-header">
                    Sales Analytics Data
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th colspan="3" class="table-primary text-center">Top 10 Selling Products (by Quantity)</th>
                                        </tr>
                                        <tr>
                                            <th>Product</th>
                                            <th>Quantity Sold</th>
                                            <th>Total Sales (Product)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($top_selling_products)): ?>
                                            <?php foreach ($top_selling_products as $product): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                                    <td><?php echo number_format($product['total_quantity_sold']); ?></td>
                                                    <td>₱<?php echo number_format($product['total_product_sales'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="3">No top-selling products found for this period.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                    <thead>
                                        <tr>
                                            <th colspan="3" class="table-success text-center">Daily Sales Trend</th>
                                        </tr>
                                        <tr>
                                            <th>Date</th>
                                            <th colspan="2">Total Sales</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($daily_sales_trend)): ?>
                                            <?php foreach ($daily_sales_trend as $daily_sale): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($daily_sale['sale_day']); ?></td>
                                                    <td colspan="2">₱<?php echo number_format($daily_sale['daily_total_sales'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="3">No daily sales trend data found for this period.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                    <thead>
                                        <tr>
                                            <th colspan="3" class="table-info text-center">Sales by Cashier</th>
                                        </tr>
                                        <tr>
                                            <th>Cashier</th>
                                            <th>Total Sales</th>
                                            <th>Transactions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($sales_by_cashier)): ?>
                                            <?php foreach ($sales_by_cashier as $cashier): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($cashier['cashier_name']); ?></td>
                                                    <td>₱<?php echo number_format($cashier['total_sales_by_cashier'], 2); ?></td>
                                                    <td><?php echo number_format($cashier['total_transactions']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="3">No cashier sales data found for this period.</td>
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

    function printReportInNewWindow() {
        var content = document.getElementById('printableArea').innerHTML;
        var printWindow = window.open('', '_blank');
        printWindow.document.write('<html><head><title>Sales Analytics Report</title>');
        printWindow.document.write('<link rel="stylesheet" href="../../public/css/report.css" media="all">');
        printWindow.document.write('</head><body>');
        printWindow.document.write(content);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.focus();
        setTimeout(function() {
            printWindow.print();
            printWindow.close();
        }, 500); // Wait for styles to apply before printing
    }
</script>
</body>
</html>