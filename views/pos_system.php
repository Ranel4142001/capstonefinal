<?php
session_start();

// Check if the user is logged in, if not then redirect to login page
// Assuming index.php is your login page located in the root (capstonefinal/)
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../index.php"); // Corrected path based on your file structure
    exit;
}

// Optional: Include database connection if directly needed on this page
// require_once '../config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="../public/css/style.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <link rel="stylesheet" href="../public/css/print.css" media="print">
    
    </head>
<body>

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
                                <a class="nav-link btn btn-danger btn-sm text-white" href="../index.php">Logout</a> </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <div class="container-fluid dashboard-page-content mt-5 pt-3">

                <div class="row">
                    <div class="col-md-6"> <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="mb-0">Product Scan / Find</h5>
                            </div>
                            <div class="card-body">
                                <div class="input-group mb-3">
                                    <input type="text" id="barcodeInput" class="form-control form-control-lg"
                                        placeholder="Scan or type barcode" aria-label="Barcode Input" autofocus>
                                    <button class="btn btn-primary" type="button" id="lookupProductBtn">Find</button>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Current Sale (Cart)</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Price</th>
                                                <th>Qty</th>
                                                <th>Subtotal</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="cartItems">
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-4">No items in cart yet. Scan a product!</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <h4 class="mb-2">Total: <span id="cartTotal">₱ 0.00</span></h4>
                                <button class="btn btn-success me-2" id="completeSaleBtn">Complete Sale</button>
                                <button class="btn btn-danger" id="clearCartBtn">Clear Cart</button>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6"> <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="mb-0">Sale Details / Payment</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="discountInput" class="form-label">Discount (%)</label>
                                    <input type="number" class="form-control" id="discountInput" value="0" min="0" max="100">
                                </div>
                                <div class="mb-3">
                                    <label for="taxRateInput" class="form-label">Tax Rate (%)</label>
                                    <input type="number" class="form-control" id="taxRateInput" value="12" min="0" step="0.1">
                                </div>
                                <div class="mb-3">
                                    <label for="paymentMethod" class="form-label">Payment Method</label>
                                    <select class="form-select" id="paymentMethod">
                                        <option value="Cash">Cash</option>
                                        <option value="Credit Card">Credit Card</option>
                                        <option value="GCash">GCash</option>
                                    </select>
                                </div>
                                <div id="cashPaymentSection" class="mb-3">
                                    <label for="cashReceived" class="form-label">Cash Received</label>
                                    <input type="number" class="form-control" id="cashReceived" placeholder="Enter cash received">
                                    <div class="mt-2">Change Due: <span id="changeDue">₱ 0.00</span></div>
                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Customer Details (Optional)</h5>
                            </div>
                            <div class="card-body">
                                <input type="text" class="form-control mb-2" placeholder="Search customer by name/phone">
                                <button class="btn btn-outline-secondary btn-sm">Select Customer</button>
                                <div class="mt-2" id="selectedCustomer">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="overlay" id="overlay"></div>

    <div class="modal fade" id="heldSalesModal" tabindex="-1" aria-labelledby="heldSalesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="heldSalesModalLabel">Held Sales</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Timestamp</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="heldSalesList">
                                <tr><td colspan="5" class="text-center text-muted">No sales on hold.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div id="receipt-template" style="display: none;">
    <div>
        <h3>YOUR STORE NAME HERE</h3>
        <p>Your Address, Danao City, Philippines</p>
        <p>Contact: (012) 345-6789</p>
        <p>VAT Reg. TIN: 123-456-789-00000</p>
    </div>
    <hr>
    <p>Date: <span id="receipt-date"></span></p>
    <p>Sale ID: <span id="receipt-sale-id"></span></p>
    <p>Cashier: <span id="receipt-cashier"><?php echo htmlspecialchars($_SESSION["username"]); ?></span></p>
    <hr>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody id="receipt-items">
        </tbody>
    </table>
    <hr>

    <div>
        <p>Subtotal: <span id="receipt-subtotal"></span></p>
        <p>Discount (<span id="receipt-discount-percent"></span>%): <span id="receipt-discount-amount"></span></p>
        <p>Tax (<span id="receipt-tax-percent"></span>%): <span id="receipt-tax-amount"></span></p>
        <h4>Grand Total: <span id="receipt-grand-total"></span></h4>
    </div>
    <div>
        <p>Payment Method: <span id="receipt-payment-method"></span></p>
        <p id="receipt-cash-received-row">Cash Received: <span id="receipt-cash-received"></span></p>
        <p id="receipt-change-due-row">Change: <span id="receipt-change-due"></span></p>
    </div>
    <hr>
    <div>
        <p>Thank you for your purchase!</p>
        <p>Please come again.</p>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="../public/js/main.js"></script>
    <script src="../public/js/pos_script.js"></script>
</body>
</html>