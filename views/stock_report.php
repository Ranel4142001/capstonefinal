<?php
        
        include '../includes/auth_check.php';
        include '../includes/layout_start.php';
        include '../includes/functions.php';
        include '../config/db.php';



// --- Date Filtering Logic ---
$start_date = isset($_GET['start_date']) ? sanitize_input($_GET['start_date']) : date('Y-m-01'); // Default to start of current month
$end_date = isset($_GET['end_date']) ? sanitize_input($_GET['end_date']) : date('Y-m-d');     // Default to current date

// --- Pagination Logic ---
$allowed_records_per_page = [10, 20, 30, 40, 50, 60, 70, 80, 90, 100];
$default_records_per_page = 10; // Default if not specified or invalid

$records_per_page = isset($_GET['per_page']) && in_array((int)$_GET['per_page'], $allowed_records_per_page) ? (int)$_GET['per_page'] : $default_records_per_page;

$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) {
    $current_page = 1;
}
$offset = ($current_page - 1) * $records_per_page;

// --- Sorting Logic ---
$allowed_sort_orders = ['quantity_asc', 'quantity_desc', 'name_asc'];
$default_sort_order = 'quantity_asc'; // Default sort by lowest quantity

$sort_order = isset($_GET['sort_order']) && in_array($_GET['sort_order'], $allowed_sort_orders) ? $_GET['sort_order'] : $default_sort_order;

$message = '';
$message_type = '';

/**
 * Function to get total number of products (for pagination).
 * We still paginate based on the total number of products, as the core "stock report" is about products.
 */
function getTotalProductCount($conn) {
    $sql = "SELECT COUNT(id) AS total_count FROM products";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        return $row['total_count'];
    }
    return 0;
}

/**
 * Function to get stock data from the database.
 * The quantity displayed will be AS OF the $asOfDateTime.
 */
function getStockDataAsOfDate($conn, $limit, $offset, $sort_order, $asOfDateTime) {
    $orderByClause = "";
    switch ($sort_order) {
        case 'quantity_asc':
            $orderByClause = "calculated_quantity ASC, p.name ASC";
            break;
        case 'quantity_desc':
            $orderByClause = "calculated_quantity DESC, p.name ASC";
            break;
        case 'name_asc':
        default:
            $orderByClause = "p.name ASC";
            break;
    }

    // SQL query to calculate stock quantity up to the given asOfDateTime
    // It gets the `current_quantity_after_change` from the LATEST entry for each product
    // ON or BEFORE the `asOfDateTime`. If no entry, it assumes 0 or initial quantity.
    $sql = "
        SELECT
            p.id AS product_id,
            p.name AS product_name,
            c.name AS category_name,
            s.name AS supplier_name,
            COALESCE(
                (
                    SELECT sh.current_quantity_after_change
                    FROM stock_history sh
                    WHERE sh.product_id = p.id
                      AND sh.change_date <= ?
                    ORDER BY sh.change_date DESC, sh.id DESC -- Get the very last change
                    LIMIT 1
                ),
                0 -- Default to 0 if no history exists for this product before the date
            ) AS calculated_quantity,
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
    $stmt->bind_param("sii", $asOfDateTime, $limit, $offset);
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

// Fetch total product count
$total_products = getTotalProductCount($conn);

// Calculate total pages
$total_pages = ceil($total_products / $records_per_page);

// Ensure current page is not greater than total pages (if any products exist)
if ($total_products > 0 && $current_page > $total_pages) {
    $current_page = $total_pages;
    $offset = ($current_page - 1) * $records_per_page; // Recalculate offset for the adjusted page
} elseif ($total_products == 0 && $current_page > 1) {
    $current_page = 1; // If no products, go to page 1
    $offset = 0;
}


// Fetch stock data with pagination and sorting, as of the end date
// We add ' + INTERVAL 1 DAY' to $end_date in the query to include the whole day.
$stockItems = [];
try {
    $stockItems = getStockDataAsOfDate($conn, $records_per_page, $offset, $sort_order, $end_date . ' 23:59:59');
} catch (Exception $e) {
    $message = "Error fetching stock data: " . $e->getMessage();
    $message_type = 'danger';
}


// Close database connection
$conn->close();


?>

        <div id="printableArea" class="container-fluid dashboard-page-content mt-5 pt-3">
            <div class="print-header">
                <h3>Stock Report (From <?php echo htmlspecialchars($start_date); ?> to <?php echo htmlspecialchars($end_date); ?>)</h3>
                <p>Generated by: <?php echo htmlspecialchars($_SESSION["username"]); ?> on <?php echo date('Y-m-d H:i:s'); ?></p>
            </div>
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show no-print" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card mb-4 no-print">
                <div class="card-header">
                    Filter Stock Report
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
                            <label for="per_page" class="form-label">Records per page:</label>
                            <select class="form-select" id="per_page" name="per_page">
                                <?php foreach ($allowed_records_per_page as $num): ?>
                                    <option value="<?php echo $num; ?>" <?php echo ($records_per_page == $num) ? 'selected' : ''; ?>>
                                        <?php echo $num; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                         <div class="col-md-3">
                            <label for="sort_order" class="form-label">Sort by Quantity:</label>
                            <select class="form-select" id="sort_order" name="sort_order">
                                <option value="quantity_asc" <?php echo ($sort_order == 'quantity_asc') ? 'selected' : ''; ?>>Lowest First</option>
                                <option value="quantity_desc" <?php echo ($sort_order == 'quantity_desc') ? 'selected' : ''; ?>>Highest First</option>
                                <option value="name_asc" <?php echo ($sort_order == 'name_asc') ? 'selected' : ''; ?>>Product Name</option>
                            </select>
                        </div>
                        <div class="col-12 mt-3">
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
                    Stock Levels
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Product ID</th>
                                    <th>Product Name</th>
                                    <th>Category</th>
                                    <th>Supplier</th>
                                    <th>Stocks</th>
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
                                            <td><?php echo htmlspecialchars($item['calculated_quantity']); ?></td>
                                            <td>₱<?php echo number_format($item['cost_price'], 2); ?></td>
                                            <td>₱<?php echo number_format($item['selling_price'], 2); ?></td>
                                            <td>
                                                <?php
                                                    $statusClass = '';
                                                    $displayStatus = '';
                                                    $lowStockThreshold = 10; // Define a default low stock threshold

                                                    // Use calculated_quantity for stock status
                                                    if (isset($item['status']) && $item['status'] == 0) { // Assuming 'is_active' 0 means inactive/discontinued
                                                        $statusClass = 'badge bg-danger';
                                                        $displayStatus = 'Inactive';
                                                    } elseif ($item['calculated_quantity'] <= $lowStockThreshold) {
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
                                        <td colspan="8" class="text-center">No product or stock data available for the selected period.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <nav aria-label="Page navigation" class="no-print">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $current_page - 1; ?>&per_page=<?php echo $records_per_page; ?>&sort_order=<?php echo htmlspecialchars($sort_order); ?>&start_date=<?php echo htmlspecialchars($start_date); ?>&end_date=<?php echo htmlspecialchars($end_date); ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo ($current_page == $i) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&per_page=<?php echo $records_per_page; ?>&sort_order=<?php echo htmlspecialchars($sort_order); ?>&start_date=<?php echo htmlspecialchars($start_date); ?>&end_date=<?php echo htmlspecialchars($end_date); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $current_page + 1; ?>&per_page=<?php echo $records_per_page; ?>&sort_order=<?php echo htmlspecialchars($sort_order); ?>&start_date=<?php echo htmlspecialchars($start_date); ?>&end_date=<?php echo htmlspecialchars($end_date); ?>" aria-label="Next">
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

<?php
        // Close layout (footer, scripts, closing tags)
        include '../includes/layout_end.php'; ?>
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
        printWindow.document.write('<title>Stock Report Print</title>');

        // Link your CSS files from the main page
        // IMPORTANT: Adjust the paths if your CSS files are not relative to the root like this
        printWindow.document.write('<link rel="stylesheet" href="<?php echo $base_url_path; ?>/public/css/style.css">');
        printWindow.document.write('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">');
        printWindow.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">');

        // Add print-specific styles to hide elements not meant for print
        printWindow.document.write('<style>');
        printWindow.document.write('@media print { .no-print { display: none !important; } }');
        printWindow.document.write('body { font-size: 10pt; } table { width: 100%; border-collapse: collapse; } table, th, td { border: 1px solid black; padding: 8px; }');
        printWindow.document.write('.print-header { text-align: center; margin-bottom: 20px; }');
        printWindow.document.write('</style>');

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