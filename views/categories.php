<?php
// views/categories.php

// Include authentication check to ensure the user is logged in
require_once '../includes/auth_check.php';

// Include common utility functions (now containing get_db_connection and sanitize_input)
require_once '../includes/functions.php';

// Start a session if not already started (useful for messages)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Initialize database connection
$conn = get_db_connection(); // This function is now in functions.php

// --- Handle Category Operations (Add, Edit, Delete) ---
$message = '';
$message_type = ''; // 'success' or 'danger'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        switch ($action) {
            case 'add_category':
                $category_name = sanitize_input($_POST['category_name'] ?? '');
                $category_description = sanitize_input($_POST['category_description'] ?? '');

                if (!empty($category_name)) {
                    try {
                        $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
                        $stmt->bind_param("ss", $category_name, $category_description);
                        if ($stmt->execute()) {
                            $message = "Category '{$category_name}' added successfully!";
                            $message_type = 'success';
                        } else {
                            $message = "Error adding category: " . $stmt->error;
                            $message_type = 'danger';
                        }
                        $stmt->close();
                    } catch (mysqli_sql_exception $e) {
                        $message = "Database error: " . $e->getMessage();
                        $message_type = 'danger';
                    }
                } else {
                    $message = "Category name cannot be empty.";
                    $message_type = 'danger';
                }
                break;

            case 'edit_category':
                $category_id = (int)($_POST['category_id'] ?? 0);
                $category_name = sanitize_input($_POST['category_name'] ?? '');
                $category_description = sanitize_input($_POST['category_description'] ?? '');

                if ($category_id > 0 && !empty($category_name)) {
                    try {
                        $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
                        $stmt->bind_param("ssi", $category_name, $category_description, $category_id);
                        if ($stmt->execute()) {
                            $message = "Category updated successfully!";
                            $message_type = 'success';
                        } else {
                            $message = "Error updating category: " . $stmt->error;
                            $message_type = 'danger';
                        }
                        $stmt->close();
                    } catch (mysqli_sql_exception $e) {
                        $message = "Database error: " . $e->getMessage();
                        $message_type = 'danger';
                    }
                } else {
                    $message = "Invalid category ID or name.";
                    $message_type = 'danger';
                }
                break;

            case 'delete_category':
                $category_id = (int)($_POST['category_id'] ?? 0);

                if ($category_id > 0) {
                    // Optional: Add a check if the category is used by any products before deleting
                    try {
                        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
                        $stmt->bind_param("i", $category_id);
                        if ($stmt->execute()) {
                            $message = "Category deleted successfully!";
                            $message_type = 'success';
                        } else {
                            $message = "Error deleting category: " . $stmt->error;
                            $message_type = 'danger';
                        }
                        $stmt->close();
                    } catch (mysqli_sql_exception $e) {
                        $message = "Database error: " . $e->getMessage();
                        $message_type = 'danger';
                    }
                } else {
                    $message = "Invalid category ID.";
                    $message_type = 'danger';
                }
                break;
        }
        // Redirect to prevent form resubmission on page refresh
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $message_type;
        header("Location: categories.php");
        exit();
    }
}

// Check for session messages after redirect
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']); // Clear the message after displaying
    unset($_SESSION['message_type']);
}

// --- Fetch Categories for Display ---
$categories = [];
try {
    $sql = "SELECT id, name, description FROM categories ORDER BY name ASC";
    $result = $conn->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        $result->free();
    } else {
        $message = "Error fetching categories: " . $conn->error;
        $message_type = 'danger';
    }
} catch (mysqli_sql_exception $e) {
    $message = "Database error fetching categories: " . $e->getMessage();
    $message_type = 'danger';
}

// Close the database connection
$conn->close();

// Include the common header file
include '../includes/header.php';
?>
    <div class="dashboard-wrapper">
        <?php include '../includes/sidebar.php'; ?>
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
                                <a class="nav-link btn btn-danger btn-sm text-white" href="../index.php">Logout</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
            <div class="container-fluid dashboard-page-content mt-5 pt-3">
            <h2 class="mb-4">Category</h2>
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header">
                        Add New Category
                    </div>
                    <div class="card-body">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <input type="hidden" name="action" value="add_category">
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

                <div class="card">
                    <div class="card-header">
                        Existing Categories
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($categories)): ?>
                                        <?php foreach ($categories as $cat): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($cat['id']); ?></td>
                                                <td><?php echo htmlspecialchars($cat['name']); ?></td>
                                                <td><?php echo htmlspecialchars($cat['description']); ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-warning edit-category-btn"
                                                        data-bs-toggle="modal" data-bs-target="#editCategoryModal"
                                                        data-id="<?php echo htmlspecialchars($cat['id']); ?>"
                                                        data-name="<?php echo htmlspecialchars($cat['name']); ?>"
                                                        data-description="<?php echo htmlspecialchars($cat['description']); ?>">
                                                        Edit
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger delete-category-btn"
                                                        data-bs-toggle="modal" data-bs-target="#deleteCategoryModal"
                                                        data-id="<?php echo htmlspecialchars($cat['id']); ?>"
                                                        data-name="<?php echo htmlspecialchars($cat['name']); ?>">
                                                        Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4">No categories found.</td>
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

    <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCategoryModalLabel">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_category">
                        <input type="hidden" name="category_id" id="edit_category_id">
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

    <div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-labelledby="deleteCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteCategoryModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete_category">
                        <input type="hidden" name="category_id" id="delete_category_id">
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

    <div class="overlay" id="overlay"></div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../public/js/main.js"></script>
    <script>
        // JavaScript to populate the edit and delete modals
        document.addEventListener('DOMContentLoaded', function() {
            // Event listener for Edit buttons
            document.querySelectorAll('.edit-category-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.dataset.id;
                    const name = this.dataset.name;
                    const description = this.dataset.description;

                    document.getElementById('edit_category_id').value = id;
                    document.getElementById('edit_category_name').value = name;
                    document.getElementById('edit_category_description').value = description;
                });
            });

            // Event listener for Delete buttons
            document.querySelectorAll('.delete-category-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.dataset.id;
                    const name = this.dataset.name;

                    document.getElementById('delete_category_id').value = id;
                    document.getElementById('delete_category_name').textContent = name;
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