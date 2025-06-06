<?php
// api/users.php
// This file handles API requests for user management (CRUD operations).

header('Content-Type: application/json'); // Set header for JSON response

// Include common utility functions (for get_db_connection and sanitize_input).
require_once '../includes/functions.php';

// Initialize database connection.
try {
    $conn = get_db_connection();
} catch (mysqli_sql_exception $e) {
    // Log database connection error for debugging.
    error_log("Database connection error in api/users.php: " . $e->getMessage());
    // Send a generic error message to the client.
    echo json_encode(['status' => 'error', 'message' => 'Database connection error.']);
    exit(); // Terminate script execution.
}

// Determine the action based on the request method and parameters.
// Prefer POST for operations that change data (add, edit, delete) and GET for fetching (list).
$action = '';
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    $action = $_GET['action'];
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
}

switch ($action) {
    case 'list':
        $users = [];
        try {
            // Prepare and execute statement to fetch all users, ordered by username.
            // Exclude password_hash from the selection for security.
            $stmt = $conn->prepare("SELECT id, username, email, role, created_at FROM users ORDER BY username ASC");
            $stmt->execute();
            $result = $stmt->get_result(); // Get the result set.

            // Fetch all rows as associative arrays.
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
            $stmt->close(); // Close the statement.

            echo json_encode($users); // Return users data as JSON.
        } catch (mysqli_sql_exception $e) {
            error_log("Error fetching users: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Error fetching user list.']);
        }
        break;

    case 'add': // Corresponds to 'add_user' action in the form
        $username = sanitize_input($_POST['username'] ?? '');
        $password = $_POST['password'] ?? ''; // Password will be hashed, no direct sanitization here.
        $email = sanitize_input($_POST['email'] ?? '');
        $role = sanitize_input($_POST['role'] ?? '');

        // Basic validation for required fields and email format.
        if (empty($username) || empty($password) || empty($role)) {
            echo json_encode(['status' => 'error', 'message' => 'Username, password, and role cannot be empty.']);
            break;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($email)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid email format.']);
            break;
        }

        // Hash the password for secure storage.
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            // Check if username or email already exists to prevent duplicates.
            $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt_check->bind_param("ss", $username, $email);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                echo json_encode(['status' => 'error', 'message' => 'Username or email already exists.']);
            } else {
                // Insert new user into the database.
                $stmt = $conn->prepare("INSERT INTO users (username, password_hash, email, role) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $username, $hashed_password, $email, $role);

                if ($stmt->execute()) {
                    echo json_encode(['status' => 'success', 'message' => "User '{$username}' added successfully!"]);
                } else {
                    error_log("Error adding user: " . $stmt->error);
                    echo json_encode(['status' => 'error', 'message' => 'Error adding user.']);
                }
                $stmt->close();
            }
            $stmt_check->close();
        } catch (mysqli_sql_exception $e) {
            error_log("Database error adding user: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Database error during add operation.']);
        }
        break;

    case 'edit': // Corresponds to 'edit_user' action in the form
        $user_id = (int)($_POST['user_id'] ?? 0);
        $username = sanitize_input($_POST['username'] ?? '');
        $password = $_POST['password'] ?? ''; // New password (if provided)
        $email = sanitize_input($_POST['email'] ?? '');
        $role = sanitize_input($_POST['role'] ?? '');

        // Validation for edit operation.
        if ($user_id <= 0 || empty($username) || empty($role)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid user ID, username, or role.']);
            break;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($email)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid email format.']);
            break;
        }

        try {
            // Check for duplicate username/email, excluding the current user being edited.
            $stmt_check = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $stmt_check->bind_param("ssi", $username, $email, $user_id);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                echo json_encode(['status' => 'error', 'message' => 'Username or email already exists for another user.']);
            } else {
                // Dynamically build SQL query based on whether password is being updated.
                $sql = "UPDATE users SET username = ?, email = ?, role = ?";
                $params = "sss"; // Initial parameter types
                $values = [$username, $email, $role]; // Initial values

                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $sql .= ", password_hash = ?"; // Add password_hash column to update.
                    $params .= "s"; // Add string type for password_hash.
                    $values[] = $hashed_password; // Add hashed password to values.
                }

                $sql .= " WHERE id = ?"; // Add WHERE clause.
                $params .= "i"; // Add integer type for user_id.
                $values[] = $user_id; // Add user_id to values.

                // Prepare and execute the update statement.
                $stmt = $conn->prepare($sql);
                // Use splat operator (...) to pass array elements as separate arguments to bind_param.
                $stmt->bind_param($params, ...$values);

                if ($stmt->execute()) {
                    echo json_encode(['status' => 'success', 'message' => 'User updated successfully!']);
                } else {
                    error_log("Error updating user: " . $stmt->error);
                    echo json_encode(['status' => 'error', 'message' => 'Error updating user.']);
                }
                $stmt->close();
            }
            $stmt_check->close();
        } catch (mysqli_sql_exception $e) {
            error_log("Database error updating user: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Database error during update operation.']);
        }
        break;

    case 'delete': // Corresponds to 'delete_user' action in the form
        $user_id = (int)($_POST['user_id'] ?? 0);

        if ($user_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid user ID for delete.']);
            break;
        }

        // Prevent deleting the currently logged-in user for security.
        // Assumes $_SESSION['user_id'] holds the ID of the logged-in user.
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id) {
            echo json_encode(['status' => 'error', 'message' => 'You cannot delete your own account.']);
            break;
        }

        try {
            // Delete user from the database.
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);

            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'User deleted successfully!']);
            } else {
                error_log("Error deleting user: " . $stmt->error);
                echo json_encode(['status' => 'error', 'message' => 'Error deleting user.']);
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            error_log("Database error deleting user: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Database error during delete operation.']);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
        break;
}

// Close the database connection at the end of the script execution.
$conn->close();
?>
