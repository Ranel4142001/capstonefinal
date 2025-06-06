// public/js/user_management_script.js
document.addEventListener('DOMContentLoaded', function() {
    // Function to load and display users in the table
    function loadUsers() {
        fetch('../api/users.php?action=list') // Make a GET request to the API to list users
            .then(response => response.json()) // Parse the JSON response
            .then(data => {
                const tbody = document.querySelector('#userTable tbody');
                tbody.innerHTML = ''; // Clear existing table rows

                if (data.length > 0) {
                    // Iterate over the fetched users and create table rows
                    data.forEach(user => {
                        const row = `
                            <tr>
                                <td>${user.id}</td>
                                <td>${user.username}</td>
                                <td>${user.email || ''}</td>
                                <td>${user.role.charAt(0).toUpperCase() + user.role.slice(1)}</td>
                                <td>${user.created_at}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-warning edit-user-btn"
                                        data-bs-toggle="modal" data-bs-target="#editUserModal"
                                        data-id="${user.id}"
                                        data-username="${user.username}"
                                        data-email="${user.email || ''}"
                                        data-role="${user.role}">
                                        Edit
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger delete-user-btn"
                                        data-bs-toggle="modal" data-bs-target="#deleteUserModal"
                                        data-id="${user.id}"
                                        data-username="${user.username}">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        `;
                        tbody.insertAdjacentHTML('beforeend', row); // Add the new row to the table
                    });
                } else {
                    // Display a message if no users are found
                    tbody.innerHTML = '<tr><td colspan="6">No users found.</td></tr>';
                }
            })
            .catch(error => {
                console.error("Error loading users:", error);
                alert("Error loading users. Please try again.");
            });
    }

    // Initial load of users when the page is ready
    loadUsers();

    // Event listener for Add User Form submission
    const addUserForm = document.getElementById('addUserForm');
    if (addUserForm) {
        addUserForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent default form submission

            const formData = new FormData(this); // Collect form data
            formData.append('action', 'add'); // Add the action for the API endpoint

            fetch('../api/users.php', {
                method: 'POST', // Use POST method for adding data
                body: formData   // Send the form data
            })
            .then(response => response.json()) // Parse the JSON response
            .then(data => {
                if (data.status === 'success') {
                    // Hide the modal and reset the form on success
                    const addModal = bootstrap.Modal.getInstance(document.getElementById('addUserModal'));
                    if (addModal) addModal.hide();
                    addUserForm.reset();
                    loadUsers(); // Reload users to update the table
                    alert(data.message); // Show success message
                } else {
                    alert(data.message); // Show error message
                }
            })
            .catch(error => {
                console.error("Error adding user:", error);
                alert("Error adding user. Please check your input.");
            });
        });
    }

    // Event delegation for Edit User buttons (since they are added dynamically)
    document.querySelector('#userTable tbody').addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-user-btn')) {
            const button = e.target;
            // Populate the edit modal fields with data from the clicked button's data-attributes
            document.getElementById('edit_user_id').value = button.dataset.id;
            document.getElementById('edit_username').value = button.dataset.username;
            document.getElementById('edit_email').value = button.dataset.email;
            document.getElementById('edit_role').value = button.dataset.role;
            document.getElementById('edit_password').value = ''; // Clear password field on modal open
        }
    });

    // Event listener for Edit User Form submission
    const editUserForm = document.getElementById('editUserForm');
    if (editUserForm) {
        editUserForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent default form submission

            const formData = new FormData(this); // Collect form data
            formData.append('action', 'edit'); // Add the action for the API endpoint

            fetch('../api/users.php', {
                method: 'POST', // Use POST method for editing data
                body: formData   // Send the form data
            })
            .then(response => response.json()) // Parse the JSON response
            .then(data => {
                if (data.status === 'success') {
                    // Hide the modal on success
                    const editModal = bootstrap.Modal.getInstance(document.getElementById('editUserModal'));
                    if (editModal) editModal.hide();
                    loadUsers(); // Reload users to update the table
                    alert(data.message); // Show success message
                } else {
                    alert(data.message); // Show error message
                }
            })
            .catch(error => {
                console.error("Error updating user:", error);
                alert("Error updating user. Please try again.");
            });
        });
    }

    // Event delegation for Delete User buttons
    document.querySelector('#userTable tbody').addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-user-btn')) {
            const button = e.target;
            // Populate the delete confirmation modal
            document.getElementById('delete_user_id').value = button.dataset.id;
            document.getElementById('delete_username').textContent = button.dataset.username;
        }
    });

    // Event listener for Delete User Confirmation Form submission
    const deleteUserForm = document.getElementById('deleteUserForm');
    if (deleteUserForm) {
        deleteUserForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent default form submission

            const formData = new FormData(this); // Collect form data
            formData.append('action', 'delete'); // Add the action for the API endpoint

            fetch('../api/users.php', {
                method: 'POST', // Use POST method for deleting data
                body: formData   // Send the form data
            })
            .then(response => response.json()) // Parse the JSON response
            .then(data => {
                if (data.status === 'success') {
                    // Hide the modal on success
                    const deleteModal = bootstrap.Modal.getInstance(document.getElementById('deleteUserModal'));
                    if (deleteModal) deleteModal.hide();
                    loadUsers(); // Reload users to update the table
                    alert(data.message); // Show success message
                } else {
                    alert(data.message); // Show error message
                }
            })
            .catch(error => {
                console.error("Error deleting user:", error);
                alert("Error deleting user. Please try again.");
            });
        });
    }
});
