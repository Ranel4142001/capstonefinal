<?php
// views/user_management.php

// Include authentication check to ensure the user is logged in and has appropriate permissions (e.g., admin).
require_once '../includes/auth_check.php';

// Ensure only administrators can access this page for security.
// Redirect non-admins to the dashboard.
if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

// Include common utility functions (get_db_connection, sanitize_input).
require_once '../includes/functions.php';

// Start a session if not already started (useful for messages and user role/ID).
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// PHP variables for initial page load messages (if any, though AJAX will handle most feedback).
$message = '';
$message_type = ''; // 'success' or 'danger'

// Check for session messages after redirect and display them once.
// This is for messages set before a redirect, e.g., from a login process.
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']); // Clear the message after displaying.
    unset($_SESSION['message_type']);
}

// Define allowed roles for the dropdowns. This can be expanded as needed.
// This array is used to populate the role selection dropdowns in the forms.
$allowed_roles = ['admin', 'staff', 'cashier'];

// Include the common header file for consistent page layout.
include '../includes/header.php';
?>
    <div class="dashboard-wrapper">
        <?php
        // Include the sidebar navigation for consistent UI across dashboard pages.
        include '../includes/sidebar.php';
        ?>
        <div class="main-content" id="main-content">
            <!-- Top navigation bar, consistent with other dashboard views. -->
            <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top px-3 custom-navbar-top">
                <div class="container-fluid">
                    <!-- Button to toggle sidebar on smaller screens. -->
                    <button id="sidebarToggle" class="btn btn-outline-light d-lg-none me-3" type="button">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <!-- Button to toggle sidebar on larger screens. -->
                    <button id="sidebarToggleDesktop" class="btn btn-outline-light d-none d-lg-block me-3" type="button">
                        <i class="fas fa-bars"></i> <!-- Font Awesome icon for a menu bar. -->
                    </button>
                    <a class="navbar-brand" href="#">POS System</a>
                    <div class="collapse navbar-collapse" id="navbarNav">
                        <ul class="navbar-nav me-auto mb-2 mb-lg-0"></ul>
                        <ul class="navbar-nav ms-auto">
                            <!-- Display welcome message with username and role from session. -->
                            <li class="nav-item d-flex align-items-center">
                                <span class="nav-link text-white me-2">Welcome, <?php echo htmlspecialchars($_SESSION["username"] ?? 'Guest'); ?> (Role: <?php echo htmlspecialchars($_SESSION["role"] ?? 'N/A'); ?>)</span>
                            </li>
                            <!-- Logout button. -->
                            <li class="nav-item">
                                <a class="nav-link btn btn-danger btn-sm text-white" href="../logout.php">Logout</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <div class="container-fluid dashboard-page-content mt-5 pt-3">
                <h2 class="mb-4">Manage Users</h2>

                <?php
                // Display feedback message (success or danger) if set from a previous PHP redirect.
                if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Card for adding new users. -->
                <div class="card mb-4">
                    <div class="card-header">
                        Add New User
                    </div>
                    <div class="card-body">
                        <!-- Form for adding a new user. Submission handled by JavaScript via AJAX. -->
                        <form id="addUserForm">
                            <input type="hidden" name="action" value="add"> <!-- Action for the API. -->
                            <div class="mb-3">
                                <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" name="username" id="username" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" name="password" id="password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email (Optional)</label>
                                <input type="email" name="email" id="email" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                                <select name="role" id="role" class="form-select" required>
                                    <option value="">Select Role</option>
                                    <?php foreach ($allowed_roles as $role_option): // Populate roles from PHP array ?>
                                        <option value="<?php echo htmlspecialchars($role_option); ?>">
                                            <?php echo ucwords($role_option); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Add User</button>
                        </form>
                    </div>
                </div>

                <!-- Card for displaying existing users. -->
                <div class="card">
                    <div class="card-header">
                        Existing Users
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="userTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- User data will be loaded dynamically by public/js/user_management_script.js -->
                                    <tr>
                                        <td colspan="6">Loading users...</td>
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

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- Form for editing a user. Submission handled by JavaScript via AJAX. -->
                <form id="editUserForm">
                    <input type="hidden" name="action" value="edit"> <!-- Action for the API. -->
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_username" class="form-label">Username</label>
                            <input type="text" name="username" id="edit_username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_password" class="form-label">New Password (Leave blank to keep current)</label>
                            <input type="password" name="password" id="edit_password" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email</label>
                            <input type="email" name="email" id="edit_email" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="edit_role" class="form-label">Role</label>
                            <select name="role" id="edit_role" class="form-select" required>
                                <?php foreach ($allowed_roles as $role_option): // Populate roles from PHP array ?>
                                    <option value="<?php echo htmlspecialchars($role_option); ?>">
                                        <?php echo ucwords($role_option); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
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
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteUserModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- Form for deleting a user. Submission handled by JavaScript via AJAX. -->
                <form id="deleteUserForm">
                    <input type="hidden" name="action" value="delete"> <!-- Action for the API. -->
                    <input type="hidden" name="user_id" id="delete_user_id">
                    <div class="modal-body">
                        <p>Are you sure you want to delete user "<strong id="delete_username"></strong>"? This action cannot be undone.</p>
                        <p class="text-danger">Note: You cannot delete your own account.</p>
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
    <!-- NEW: Include the specific JavaScript for user management to handle AJAX operations -->
    <script src="../public/js/user_management_script.js"></script>
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
