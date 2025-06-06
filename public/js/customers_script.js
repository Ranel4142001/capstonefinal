// public/js/customers_script.js
document.addEventListener('DOMContentLoaded', function () {
    loadCustomers();

    function loadCustomers() {
        fetch('../api/customers.php?action=list')
            .then(response => response.json())
            .then(data => {
                const tbody = document.querySelector('#customerTable tbody');
                tbody.innerHTML = ''; // Clear existing rows
                if (data.length > 0) {
                    data.forEach(customer => {
                        const row = `
                            <tr>
                                <td>${customer.customer_id}</td>
                                <td>${customer.first_name}</td>
                                <td>${customer.last_name}</td>
                                <td>${customer.contact_number || ''}</td>
                                <td>${customer.email || ''}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-warning edit-customer-btn"
                                        data-bs-toggle="modal" data-bs-target="#editCustomerModal"
                                        data-id="${customer.customer_id}"
                                        data-first_name="${customer.first_name}"
                                        data-last_name="${customer.last_name}"
                                        data-contact_number="${customer.contact_number || ''}"
                                        data-email="${customer.email || ''}"
                                        data-address="${customer.address || ''}">
                                        Edit
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger delete-customer-btn"
                                        data-bs-toggle="modal" data-bs-target="#deleteCustomerModal"
                                        data-id="${customer.customer_id}"
                                        data-name="${customer.first_name} ${customer.last_name}">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        `;
                        tbody.insertAdjacentHTML('beforeend', row);
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="6">No customers found.</td></tr>';
                }
            })
            .catch(error => {
                console.error("Error loading customers:", error);
                alert("Error loading customers.");
            });
    }

    // Add Customer Form Submission
    const addCustomerForm = document.getElementById('addCustomerForm');
    if (addCustomerForm) {
        addCustomerForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this); // Collects form data
            formData.append('action', 'add'); // Add action for the API

            fetch('../api/customers.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const addModal = bootstrap.Modal.getInstance(document.getElementById('addCustomerModal'));
                        if (addModal) addModal.hide();
                        addCustomerForm.reset();
                        loadCustomers();
                        alert(data.message);
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error("Error adding customer:", error);
                    alert("Error adding customer.");
                });
        });
    }

    // Edit Customer Button Click (delegated event listener for dynamically added buttons)
    document.querySelector('#customerTable tbody').addEventListener('click', function (e) {
        if (e.target.classList.contains('edit-customer-btn')) {
            const button = e.target;
            document.getElementById('edit_customer_id').value = button.dataset.id;
            document.getElementById('edit_first_name').value = button.dataset.firstName;
            document.getElementById('edit_last_name').value = button.dataset.lastName;
            document.getElementById('edit_contact_number').value = button.dataset.contactNumber;
            document.getElementById('edit_email').value = button.dataset.email;
            document.getElementById('edit_address').value = button.dataset.address;
        }
    });

    // Edit Customer Form Submission
    const editCustomerForm = document.getElementById('editCustomerForm');
    if (editCustomerForm) {
        editCustomerForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'edit'); // Add action for the API

            fetch('../api/customers.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const editModal = bootstrap.Modal.getInstance(document.getElementById('editCustomerModal'));
                        if (editModal) editModal.hide();
                        loadCustomers();
                        alert(data.message);
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error("Error updating customer:", error);
                    alert("Error updating customer.");
                });
        });
    }

    // Delete Customer Button Click (delegated event listener)
    document.querySelector('#customerTable tbody').addEventListener('click', function (e) {
        if (e.target.classList.contains('delete-customer-btn')) {
            const button = e.target;
            document.getElementById('delete_customer_id').value = button.dataset.id;
            document.getElementById('delete_customer_name').textContent = button.dataset.name;
        }
    });

    // Delete Customer Form Submission
    const deleteCustomerForm = document.getElementById('deleteCustomerForm');
    if (deleteCustomerForm) {
        deleteCustomerForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'delete'); // Add action for the API

            fetch('../api/customers.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const deleteModal = bootstrap.Modal.getInstance(document.getElementById('deleteCustomerModal'));
                        if (deleteModal) deleteModal.hide();
                        loadCustomers();
                        alert(data.message);
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error("Error deleting customer:", error);
                    alert("Error deleting customer.");
                });
        });
    }
});