<?php

$base_url_path = '/capstonefinal'; // Adjust this if 'capstonefinal' is not directly under your web server's document root

?>

<div class="sidebar" id="sidebar">
   
    <ul class="sidebar-menu">
        <li><a href="<?php echo $base_url_path; ?>/views/dashboard.php" class="sidebar-link"><i class="fas fa-home"></i> Dashboard Home</a></li>
        <li><a href="<?php echo $base_url_path; ?>/views/pos_system.php" class="sidebar-link"><i class="fas fa-cash-register"></i> POS System</a></li>

        
          <li class="sidebar-dropdown">
            <a href="#inventoryControlSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="sidebar-link dropdown-toggle">
                <i class="fas fa-warehouse"></i> Inventory Control
            </a>
            <ul class="collapse list-unstyled" id="inventoryControlSubmenu">
                <li>
                    <a href="<?php echo $base_url_path; ?>/views/reports/add_stocks.php" class="sidebar-link submenu-link">
<<<<<<< HEAD
                        <i class="fas fa-boxes"></i> Add Stocks
                    </a>
                </li>
                
=======
                        <i class="fas fa-boxes"></i> Add New Stocks
                    </a>
                </li>
>>>>>>> 5c836252c639a910d545c22323eb3ef1ad3b5bfc
                <li>
                    <a href="<?php echo $base_url_path; ?>/views/categories.php" class="sidebar-link submenu-link">
                        <i class="fas fa-tags"></i> Add Categories
                    </a>
                </li>
<<<<<<< HEAD
               
=======
                <li>
                    <a href="<?php echo $base_url_path; ?>/views/reports/stock_report.php" class="sidebar-link submenu-link">
                        <i class="fas fa-boxes"></i> Current Stock Levels
                    </a>
                </li>
>>>>>>> 5c836252c639a910d545c22323eb3ef1ad3b5bfc
            </ul>
        </li>

        <li class="sidebar-dropdown">
            <a href="#manageProductsSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="sidebar-link dropdown-toggle">
                <i class="fas fa-box-open"></i> Manage Products
            </a>
            <ul class="collapse list-unstyled" id="manageProductsSubmenu">
<<<<<<< HEAD
                
=======
>>>>>>> 5c836252c639a910d545c22323eb3ef1ad3b5bfc
                <li>
                    <a href="<?php echo $base_url_path; ?>/views/inventory.php" class="sidebar-link submenu-link">
                        <i class="fas fa-boxes"></i> Current Products
                    </a>
                </li>

                <li>
                    <a href="<?php echo $base_url_path; ?>/views/add_product.php" class="sidebar-link submenu-link">
                        <i class="fas fa-plus-square"></i> Add Product
                    </a>
                </li>
            </ul>
        </li>
<<<<<<< HEAD
            
         <li class="menu-heading">Reports</li>
        
=======


>>>>>>> 5c836252c639a910d545c22323eb3ef1ad3b5bfc
        <li class="sidebar-dropdown">
            <a href="#salesReportsSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="sidebar-link dropdown-toggle">
                <i class="fas fa-chart-line"></i> Sales Reports
            </a>
            <ul class="collapse list-unstyled" id="salesReportsSubmenu">
                <li>
                    <a href="<?php echo $base_url_path; ?>/views/reports/detailed_sales_report.php" class="sidebar-link submenu-link">
                        <i class="fas fa-file-invoice-dollar"></i> Detailed Sales
                    </a>
                </li>
                <li>
                    <a href="<?php echo $base_url_path; ?>/views/reports/sales_analytics.php" class="sidebar-link submenu-link">
                        <i class="fas fa-chart-pie"></i> Sales Analytics
                    </a>
                </li>
            </ul>
        </li>

<<<<<<< HEAD
         <li>
        <a href="<?php echo $base_url_path; ?>/views/reports/stock_report.php" class="sidebar-link submenu-link">
        <i class="fas fa-boxes"></i>Stock Reports
        </a>
         </li>

          <li class="menu-heading">Administration</li>


=======
      
>>>>>>> 5c836252c639a910d545c22323eb3ef1ad3b5bfc
        <li><a href="<?php echo $base_url_path; ?>/views/suppliers.php" class="sidebar-link"><i class="fas fa-truck"></i> Suppliers</a></li>
        <li><a href="<?php echo $base_url_path; ?>/views/user_management.php" class="sidebar-link"><i class="fas fa-users"></i> User Management</a></li>

        <li><a href="<?php echo $base_url_path; ?>/views/customers.php" class="sidebar-link"><i class="fas fa-user-friends"></i> Customers</a></li>
        <li class="menu-item-bottom"><a href="<?php echo $base_url_path; ?>/views/logout.php" class="sidebar-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>