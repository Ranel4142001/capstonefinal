<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
include '../config/db.php'; 

// Set header for JSON response
header('Content-Type: application/json');

// Get the HTTP request method
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGetSuppliersRequest();
        break;
    case 'POST':
        handleAddSupplierRequest();
        break;
    case 'PUT':
        handleUpdateSupplierRequest();
        break;
    case 'DELETE':
        handleDeleteSupplierRequest();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
        http_response_code(405); // Method Not Allowed
        break;
}

function handleGetSuppliersRequest() {
    global $pdo; // Access the PDO object from db.php

    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $search = isset($_GET['search']) ? $_GET['search'] : '';

    $sql = "SELECT id, name, contact_person, phone, email, address FROM suppliers";
    $countSql = "SELECT COUNT(*) FROM suppliers";
    $params = [];

    if (!empty($search)) {
        $sql .= " WHERE name LIKE :search OR contact_person LIKE :search OR phone LIKE :search OR email LIKE :search OR address LIKE :search";
        $countSql .= " WHERE name LIKE :search OR contact_person LIKE :search OR phone LIKE :search OR email LIKE :search OR address LIKE :search";
        $params[':search'] = '%' . $search . '%';
    }

    $sql .= " ORDER BY name ASC LIMIT :limit OFFSET :offset";

    try {
        // Get total count
        $stmtCount = $pdo->prepare($countSql);
        foreach ($params as $key => &$val) {
            $stmtCount->bindParam($key, $val);
        }
        $stmtCount->execute();
        $total = $stmtCount->fetchColumn();

        // Get paginated suppliers
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => &$val) {
            $stmt->bindParam($key, $val);
        }
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'suppliers' => $suppliers, 'total' => $total]);
    } catch (PDOException $e) {
        error_log("Error fetching suppliers: " . $e->getMessage()); // Log error to server error log
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        http_response_code(500); // Internal Server Error
    }
}

function handleAddSupplierRequest() {
    global $pdo; // Access the PDO object from db.php

    $data = json_decode(file_get_contents('php://input'), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input.']);
        http_response_code(400); // Bad Request
        return;
    }

    $name = trim($data['name'] ?? '');
    $contact_person = trim($data['contact_person'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $email = trim($data['email'] ?? '');
    $address = trim($data['address'] ?? '');

    // Basic validation
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Supplier Name is required.']);
        http_response_code(400);
        return;
    }

    // Prepare SQL to insert supplier
    $sql = "INSERT INTO suppliers (name, contact_person, phone, email, address) VALUES (:name, :contact_person, :phone, :email, :address)";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':contact_person', $contact_person);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':address', $address);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Supplier added successfully!', 'id' => $pdo->lastInsertId()]);
        } else {
            $errorInfo = $stmt->errorInfo();
            error_log("Error adding supplier: " . implode(" - ", $errorInfo));
            echo json_encode(['success' => false, 'message' => 'Failed to add supplier: ' . ($errorInfo[2] ?? 'Unknown error.')]);
            http_response_code(500);
        }
    } catch (PDOException $e) {
        error_log("PDO Error adding supplier: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        http_response_code(500);
    }
}

function handleUpdateSupplierRequest() {
    global $pdo; // Access the PDO object from db.php

    $data = json_decode(file_get_contents('php://input'), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input.']);
        http_response_code(400); // Bad Request
        return;
    }

    $id = (int)($data['id'] ?? 0);
    $name = trim($data['name'] ?? '');
    $contact_person = trim($data['contact_person'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $email = trim($data['email'] ?? '');
    $address = trim($data['address'] ?? '');

    // Basic validation
    if ($id <= 0 || empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Invalid supplier ID or Name is required.']);
        http_response_code(400);
        return;
    }

    $sql = "UPDATE suppliers SET name = :name, contact_person = :contact_person, phone = :phone, email = :email, address = :address WHERE id = :id";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':contact_person', $contact_person);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':address', $address);

        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Supplier updated successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No supplier found with that ID or no changes made.']);
            }
        } else {
            $errorInfo = $stmt->errorInfo();
            error_log("Error updating supplier: " . implode(" - ", $errorInfo));
            echo json_encode(['success' => false, 'message' => 'Failed to update supplier: ' . ($errorInfo[2] ?? 'Unknown error.')]);
            http_response_code(500);
        }
    } catch (PDOException $e) {
        error_log("PDO Error updating supplier: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        http_response_code(500);
    }
}

function handleDeleteSupplierRequest() {
    global $pdo; // Access the PDO object from db.php

    // For DELETE, method is typically GET with ID in query string or via JSON body
    // We'll primarily expect it in the query string for simplicity.
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    // If ID is not in query string, try parsing from JSON body (less common for DELETE)
    if ($id === 0) {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int)($data['id'] ?? 0);
    }

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid supplier ID.']);
        http_response_code(400); // Bad Request
        return;
    }

    $sql = "DELETE FROM suppliers WHERE id = :id";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Supplier deleted successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No supplier found with that ID.']);
            }
        } else {
            $errorInfo = $stmt->errorInfo();
            error_log("Error deleting supplier: " . implode(" - ", $errorInfo));
            echo json_encode(['success' => false, 'message' => 'Failed to delete supplier: ' . ($errorInfo[2] ?? 'Unknown error.')]);
            http_response_code(500);
        }
    } catch (PDOException $e) {
        error_log("PDO Error deleting supplier: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        http_response_code(500);
    }
}
?>