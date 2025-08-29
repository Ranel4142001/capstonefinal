<?php
// views/categories.php

// Include authentication check to ensure the user is logged in.
// This prevents unauthorized access to the categories management page.
require_once '../includes/auth_check.php';

// Include common utility functions.
// This file is expected to contain 'get_db_connection()' and 'sanitize_input()'.
require_once '../includes/functions.php';

// Start a session if one has not already been started.
// Sessions are used here to store and display success/error messages after redirects.
// (Although AJAX will handle messages for CRUD, session might be used for other purposes, e.g., login status)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// -----------------------------------------------------------------------------
// PHP logic removed:
// - Database connection ($conn = get_db_connection();) is now done in api/categories.php
// - Handling Category Operations (Add, Edit, Delete) via POST requests is now handled by api/categories.php
// - Session message handling ($_SESSION['message'], unset($_SESSION['message'])) is now handled by JavaScript alerts from API responses.
// - Fetching Categories for Display is now handled by JavaScript calling api/categories.php
// - Database connection closure ($conn->close();) is now done in api/categories.ยม
// -----------------------------------------------------------------------------

// Include the common header file. This file usually contains the <head> section and the opening <body> tag.
include '../includes/header.php';
?>
    <div class="dashboard-wrapper">
        <?php
        // Include the sidebar navigation.
        include '../includes/sidebar.php';
        ?>
        <div class="main-content" id="main-content">
            <!-- Custom Navbar/Top Bar. This structure is consistent across your dashboard views. -->
            <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top px-3 custom-navbar-top">
                <div class="container-fluid">
                    <!-- Button to toggle sidebar on smaller screens -->
                    <button id="sidebarToggle" class="btn btn-outline-light d-lg-none me-3" type="button">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <!-- Button to toggle sidebar on larger screens (desktop) -->
                    <button id="sidebarToggleDesktop" class="btn btn-outline-light d-none d-lg-block me-3" type="button">
                        <i class="fas fa-bars"></i> <!-- Font Awesome icon for a menu bar -->
                    </button>
                    <a class="navbar-brand" href="#">POS System</a>
                    <div class="collapse navbar-collapse" id="navbarNav">
                        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                            <!-- Placeholder for potentially adding more navigation links on the left -->
                        </ul>
                        <ul class="navbar-nav ms-auto">
                            <!-- Display welcome message with username and role from session -->
                            <li class="nav-item d-flex align-items-center">
                                <span class="nav-link text-white me-2">Welcome, <?php echo htmlspecialchars($_SESSION["username"] ?? 'Guest'); ?> (<?php echo htmlspecialchars($_SESSION["role"] ?? 'N/A'); ?>)</span>
                            </li>
                            <!-- Logout button -->
                            <li class="nav-item">
                                <a class="nav-link btn btn-danger btn-sm text-white" href="../logout.php">Logout</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <div class="container-fluid dashboard-page-content mt-5 pt-3">
                <h2 class="mb-4">Category Management</h2>
                <?php
                // Display feedback message (success or danger) if set in session.
                // This is still useful for any non-AJAX PHP messages or general page alerts.
                if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Card for adding new categories -->
                <div class="card mb-4">
                    <div class="card-header">
                        Add New Category
                    </div>
                    <div class="card-body">
                        <!-- Form for adding a new category. Action is handled by JavaScript via AJAX. -->
                        <form id="addCategoryForm">
                            <input type="hidden" name="action" value="add_category"> <!-- Action for API -->
                            <div class="mb-3">
                                <label for="category_name" class="form-label">Category Name <span class="text-danger">*</span></label>
                                <input type="text" name="category_name" id="category_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="category_description" class="form-label">Description (Optional)</label>
                                <textarea name="category_description" id="category_description" class="form-control" rows="2"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Category</button>
                        </form>
                    </div>
                </div>

                <!-- Card for displaying existing categories -->
                <div class="card">
                    <div class="card-header">
                        Existing Categories
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="categoryTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Category data will be loaded dynamically by public/js/categories_script.js -->
                                    <tr>
                                        <td colspan="4">Loading categories...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ----------------------------------------------------------------------- -->
    <!-- Modals for Edit and Delete Operations (placed outside main content for structure) -->
    <!-- ----------------------------------------------------------------------- -->

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCategoryModalLabel">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- Form for editing a category. Action is handled by JavaScript via AJAX. -->
                <form id="editCategoryForm">
                    <input type="hidden" name="action" value="edit_category"> <!-- Action for API -->
                    <input type="hidden" name="category_id" id="edit_category_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_category_name" class="form-label">Category Name</label>
                            <input type="text" name="category_name" id="edit_category_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_category_description" class="form-label">Description</label>
                            <textarea name="category_description" id="edit_category_description" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-labelledby="deleteCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteCategoryModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- Form for deleting a category. Action is handled by JavaScript via AJAX. -->
                <form id="deleteCategoryForm">
                    <input type="hidden" name="action" value="delete_category"> <!-- Action for API -->
                    <input type="hidden" name="category_id" id="delete_category_id">
                    <div class="modal-body">
                        <p>Are you sure you want to delete the category "<strong id="delete_category_name"></strong>"? This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- This overlay likely corresponds to a custom loading spinner or general overlay -->
    <div class="overlay" id="overlay"></div>

    <!-- Include Bootstrap 5 JavaScript bundle (Popper.js included) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Your main.js for global JavaScript functionalities (e.g., sidebar toggling) -->
    <script src="../public/js/main.js"></script>
    <!-- NEW: Include the specific JavaScript for categories to handle AJAX operations -->
    <script src="../public/js/categories_script.js"></script>
    <script>
        // Auto-hide alerts after a few seconds for better user experience.
        // This is still useful for any non-AJAX PHP messages or general page alerts.
        document.addEventListener('DOMContentLoaded', function() {
            const alert = document.querySelector('.alert');
            if (alert) {
                setTimeout(() => {
                    const bootstrapAlert = new bootstrap.Alert(alert);
                    bootstrapAlert.close();
                }, 5000); // Alert will close after 5 seconds.
            }
        });
    </script>
</body>
</html>
