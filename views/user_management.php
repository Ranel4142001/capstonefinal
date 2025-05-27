<?php
// views/user_management.php

// Include authentication check to ensure the user is logged in and has appropriate permissions (e.g., admin)
require_once '../includes/auth_check.php';

// Ensure only administrators can access this page
if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php"); // Redirect non-admins
    exit();
}

// Include common utility functions (get_db_connection, sanitize_input)
require_once '../includes/functions.php';

// Start a session if not already started (useful for messages)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Initialize database connection
$conn = get_db_connection();

// --- Handle User Operations (Add, Edit, Delete) ---
$message = '';
$message_type = ''; // 'success' or 'danger'

// Process POST requests for adding, editing, or deleting users
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        switch ($action) {
            case 'add_user':
                $username = sanitize_input($_POST['username'] ?? '');
                $password = $_POST['password'] ?? ''; // Password will be hashed, so no direct sanitization here
                $email = sanitize_input($_POST['email'] ?? '');
                $role = sanitize_input($_POST['role'] ?? '');

                // Basic validation for required fields and email format
                if (empty($username) || empty($password) || empty($role)) {
                    $message = "Username, password, and role cannot be empty.";
                    $message_type = 'danger';
                } else if (!filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($email)) {
                    $message = "Invalid email format.";
                    $message_type = 'danger';
                } else {
                    // Hash the password for secure storage
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    try {
                        // Check if username or email already exists to prevent duplicates
                        $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                        $stmt_check->bind_param("ss", $username, $email);
                        $stmt_check->execute();
                        $stmt_check->store_result();
                        if ($stmt_check->num_rows > 0) {
                            $message = "Username or email already exists.";
                            $message_type = 'danger';
                        } else {
                            // Insert new user into the database using prepared statements for security
                            $stmt = $conn->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)");
                            $stmt->bind_param("ssss", $username, $hashed_password, $email, $role);
                            if ($stmt->execute()) {
                                $message = "User '{$username}' added successfully!";
                                $message_type = 'success';
                            } else {
                                $message = "Error adding user: " . $stmt->error;
                                $message_type = 'danger';
                            }
                            $stmt->close();
                        }
                        $stmt_check->close();
                    } catch (mysqli_sql_exception $e) {
                        $message = "Database error: " . $e->getMessage();
                        $message_type = 'danger';
                    }
                }
                break;

            case 'edit_user':
                $user_id = (int)($_POST['user_id'] ?? 0);
                $username = sanitize_input($_POST['username'] ?? '');
                $password = $_POST['password'] ?? ''; // Password will be hashed if provided
                $email = sanitize_input($_POST['email'] ?? '');
                $role = sanitize_input($_POST['role'] ?? '');

                // Validation for edit operation
                if ($user_id > 0 && !empty($username) && !empty($role)) {
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($email)) {
                        $message = "Invalid email format.";
                        $message_type = 'danger';
                    } else {
                        try {
                            // Check for duplicate username/email, excluding the current user being edited
                            $stmt_check = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
                            $stmt_check->bind_param("ssi", $username, $email, $user_id);
                            $stmt_check->execute();
                            $stmt_check->store_result();
                            if ($stmt_check->num_rows > 0) {
                                $message = "Username or email already exists for another user.";
                                $message_type = 'danger';
                            } else {
                                // Dynamically build SQL query based on whether password is being updated
                                $sql = "UPDATE users SET username = ?, email = ?, role = ?";
                                $params = "sss";
                                $values = [$username, $email, $role];

                                if (!empty($password)) {
                                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                                    $sql .= ", password = ?";
                                    $params .= "s";
                                    $values[] = $hashed_password;
                                }
                                $sql .= " WHERE id = ?";
                                $params .= "i";
                                $values[] = $user_id;

                                // Prepare and execute the update statement
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param($params, ...$values); // Use splat operator for dynamic binding

                                if ($stmt->execute()) {
                                    $message = "User updated successfully!";
                                    $message_type = 'success';
                                } else {
                                    $message = "Error updating user: " . $stmt->error;
                                    $message_type = 'danger';
                                }
                                $stmt->close();
                            }
                            $stmt_check->close();
                        } catch (mysqli_sql_exception $e) {
                            $message = "Database error: " . $e->getMessage();
                            $message_type = 'danger';
                        }
                    }
                } else {
                    $message = "Invalid user ID, username, or role.";
                    $message_type = 'danger';
                }
                break;

            case 'delete_user':
                $user_id = (int)($_POST['user_id'] ?? 0);

                if ($user_id > 0) {
                    // Prevent deleting the currently logged-in user for security
                    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id) {
                        $message = "You cannot delete your own account.";
                        $message_type = 'danger';
                    } else {
                        try {
                            // Delete user from the database using prepared statements
                            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                            $stmt->bind_param("i", $user_id);
                            if ($stmt->execute()) {
                                $message = "User deleted successfully!";
                                $message_type = 'success';
                            } else {
                                $message = "Error deleting user: " . $stmt->error;
                                $message_type = 'danger';
                            }
                            $stmt->close();
                        } catch (mysqli_sql_exception $e) {
                            $message = "Database error: " . $e->getMessage();
                            $message_type = 'danger';
                        }
                    }
                } else {
                    $message = "Invalid user ID.";
                    $message_type = 'danger';
                }
                break;
        }
        // Redirect to prevent form resubmission on page refresh (Post/Redirect/Get pattern)
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $message_type;
        header("Location: user_management.php");
        exit();
    }
}

// Check for session messages after redirect and display them
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']); // Clear the message after displaying
    unset($_SESSION['message_type']);
}

// --- Fetch Users for Display ---
$users = [];
try {
    // Fetch all users from the database to display in the table
    $sql = "SELECT id, username, email, role, created_at FROM users ORDER BY username ASC";
    $result = $conn->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        $result->free();
    } else {
        $message = "Error fetching users: " . $conn->error;
        $message_type = 'danger';
    }
} catch (mysqli_sql_exception $e) {
    $message = "Database error fetching users: " . $e->getMessage();
    $message_type = 'danger';
}

// Close the database connection to free up resources
$conn->close();

// Define allowed roles for the dropdowns (can be expanded)
$allowed_roles = ['admin', 'staff', 'cashier'];

// Include the common header file for consistent page layout
include '../includes/header.php';
?>
    <div class="dashboard-wrapper">
        <?php include '../includes/sidebar.php'; // Include sidebar navigation ?>
        <div class="main-content" id="main-content">
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
                                <a class="nav-link btn btn-danger btn-sm text-white" href="logout.php">Logout</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
            <div class="container-fluid dashboard-page-content mt-5 pt-3">
                <h2 class="mb-4">Manage Users</h2>

                <?php if (!empty($message)): // Display success or error messages ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header">
                        Add New User
                    </div>
                    <div class="card-body">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <input type="hidden" name="action" value="add_user">
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
                                    <?php foreach ($allowed_roles as $role_option): ?>
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

                <div class="card">
                    <div class="card-header">
                        Existing Users
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
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
                                    <?php if (!empty($users)): ?>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td><?php echo htmlspecialchars(ucwords($user['role'])); ?></td>
                                                <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-warning edit-user-btn"
                                                        data-bs-toggle="modal" data-bs-target="#editUserModal"
                                                        data-id="<?php echo htmlspecialchars($user['id']); ?>"
                                                        data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                                        data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                                        data-role="<?php echo htmlspecialchars($user['role']); ?>">
                                                        Edit
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger delete-user-btn"
                                                        data-bs-toggle="modal" data-bs-target="#deleteUserModal"
                                                        data-id="<?php echo htmlspecialchars($user['id']); ?>"
                                                        data-username="<?php echo htmlspecialchars($user['username']); ?>">
                                                        Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6">No users found.</td>
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

    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_user">
                        <input type="hidden" name="user_id" id="edit_user_id">
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
                                <?php foreach ($allowed_roles as $role_option): ?>
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

    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteUserModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="user_id" id="delete_user_id">
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

    <div class="overlay" id="overlay"></div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../public/js/main.js"></script>
    <script>
        // JavaScript to populate the edit and delete modals and handle alerts
        document.addEventListener('DOMContentLoaded', function() {
            // Event listener for Edit buttons
            document.querySelectorAll('.edit-user-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.dataset.id;
                    const username = this.dataset.username;
                    const email = this.dataset.email;
                    const role = this.dataset.role;

                    document.getElementById('edit_user_id').value = id;
                    document.getElementById('edit_username').value = username;
                    document.getElementById('edit_email').value = email;
                    document.getElementById('edit_role').value = role;
                    document.getElementById('edit_password').value = ''; // Clear password field on modal open
                });
            });

            // Event listener for Delete buttons
            document.querySelectorAll('.delete-user-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.dataset.id;
                    const username = this.dataset.username;

                    document.getElementById('delete_user_id').value = id;
                    document.getElementById('delete_username').textContent = username;
                });
            });

            // Auto-hide alerts after a few seconds
            const alert = document.querySelector('.alert');
            if (alert) {
                setTimeout(() => {
                    const bootstrapAlert = new bootstrap.Alert(alert);
                    bootstrapAlert.close();
                }, 5000); // 5 seconds
            }
        });
    </script>
</body>
</html>
