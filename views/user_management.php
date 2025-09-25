<?php
        // views/user_management.php
        include '../includes/auth_check.php';
        include '../includes/layout_start.php';
        include '../includes/functions.php';

// Include authentication check to ensure the user is logged in and has appropriate permissions (e.g., admin).
$is_admin = ($_SESSION['role'] === 'admin');



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

            // Only process POST requests if the user is an admin
    if ($_SERVER["REQUEST_METHOD"] == "POST" && $is_admin) {
    // Validate inputs
    // Name
    if (empty(trim($_POST["name"]))) {
        $name_err = "Please Add users.";
    } else {
        $name = trim($_POST["name"]);
    } 
        }   

// Define allowed roles for the dropdowns. This can be expanded as needed.
// This array is used to populate the role selection dropdowns in the forms.
$allowed_roles = ['admin', 'staff', 'cashier'];

// Include common layout start (head, header, sidebar, navbar open tags)

?>
    
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
                         <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <fieldset <?php echo !$is_admin ? 'disabled' : ''; ?>>
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

   <?php $isAdmin = ($_SESSION['role'] === 'admin'); ?>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="editUserForm">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="user_id" id="edit_user_id">
        <div class="modal-body">
          <?php if (!$isAdmin): ?>
            <p class="text-danger">You do not have permission to edit users.</p>
          <?php else: ?>
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
          <?php endif; ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <?php if ($isAdmin): ?>
            <button type="submit" class="btn btn-primary">Save changes</button>
          <?php endif; ?>
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

      <form id="deleteUserForm">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="user_id" id="delete_user_id">
        <div class="modal-body">
          <?php if (!$isAdmin): ?>
            <p class="text-danger">You do not have permission to delete users.</p>
          <?php else: ?>
            <p>Are you sure you want to delete user "<strong id="delete_username"></strong>"? This action cannot be undone.</p>
            <p class="text-danger">Note: You cannot delete your own account.</p>
          <?php endif; ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <?php if ($isAdmin): ?>
            <button type="submit" class="btn btn-danger">Delete</button>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Overlay -->
<div class="overlay" id="overlay"></div>

<?php include '../includes/layout_end.php'; ?>
<script src="../public/js/user_management_script.js"></script>
<script>
  // Auto-hide alerts after 5 seconds.
  document.addEventListener('DOMContentLoaded', function () {
    const alert = document.querySelector('.alert');
    if (alert) {
      setTimeout(() => {
        const bootstrapAlert = new bootstrap.Alert(alert);
        bootstrapAlert.close();
      }, 5000);
    }
  });

  // Front-end role check to prevent unauthorized modal actions
  const userRole = "<?php echo $_SESSION['role']; ?>";
  document.querySelectorAll('[data-bs-target="#editUserModal"], [data-bs-target="#deleteUserModal"]').forEach(btn => {
    btn.addEventListener('click', e => {
      if (userRole !== 'admin') {
        e.preventDefault();
        alert('You do not have permission to perform this action.');
      }
    });
  });
</script>
</body>
</html>