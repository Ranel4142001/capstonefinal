    <?php
// Optional: You can put any PHP logic here that might be needed for the sidebar,
// e.g., fetching dynamic menu items or user-specific links.
// For now, it's just the static HTML.
?>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h3>POS System</h3>
        <button id="closeSidebar" class="close-sidebar-btn">&times;</button> </div>
    <ul class="sidebar-menu">
        <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard Home</a></li>
        <li><a href="pos_system.php"><i class="fas fa-cash-register"></i> POS System</a></li>
        <li><a href="inventory.php"><i class="fas fa-boxes"></i> Inventory</a></li>
        <li><a href="add_product.php"><i class="fas fa-plus-square"></i> Add Product</a></li>
        <li><a href="user_management.php"><i class="fas fa-users"></i> User Management</a></li>
        <li class="menu-heading">Reports</li>
        <li><a href="reports/sales_report.php"><i class="fas fa-chart-line"></i> Sales Report</a></li>
        <li><a href="reports/stock_report.php"><i class="fas fa-warehouse"></i> Stock Report</a></li>
        <li><a href="suppliers.php"><i class="fas fa-truck"></i> Suppliers</a></li>
        <li><a href="customers.php"><i class="fas fa-user-friends"></i> Customers</a></li>
        <li class="menu-item-bottom"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>