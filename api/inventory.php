<?php
// Enable CORS for AJAX requests if your frontend is on a different origin (e.g., different port)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../config/db.php'; // Include database connection

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGetRequest($pdo);
        break;
    case 'POST':
        // handlePostRequest($pdo); // For adding products
        break;
    case 'PUT':
        // handlePutRequest($pdo); // For updating products
        break;
    case 'DELETE':
        // handleDeleteRequest($pdo); // For deleting products
        break;
    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(array("message" => "Method not allowed."));
        break;
}

function handleGetRequest($pdo) {
    $searchTerm = $_GET['search'] ?? ''; // Search by product name or barcode
    $categoryId = $_GET['category_id'] ?? '';
    $limit = $_GET['limit'] ?? 10;
    $offset = $_GET['offset'] ?? 0;

    $sql = "SELECT p.id, p.name, p.barcode, p.price, p.stock_quantity, c.name as category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id";
    $conditions = [];
    $params = [];

    if (!empty($searchTerm)) {
        // Check if searchTerm is numeric and could be a barcode
        if (is_numeric($searchTerm)) {
            $conditions[] = "(p.name LIKE ? OR p.barcode = ?)";
            $params[] = "%" . $searchTerm . "%";
            $params[] = $searchTerm;
        } else {
            $conditions[] = "p.name LIKE ?";
            $params[] = "%" . $searchTerm . "%";
        }
    }
    if (!empty($categoryId)) {
        $conditions[] = "p.category_id = ?";
        $params[] = $categoryId;
    }

    if (count($conditions) > 0) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }

    $sql .= " ORDER BY p.name ASC LIMIT ? OFFSET ?"; // Add ORDER BY, LIMIT, OFFSET
    $params[] = (int)$limit;
    $params[] = (int)$offset;

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get total count for pagination
        $countSql = "SELECT COUNT(*) FROM products p";
        if (count($conditions) > 0) {
            $countSql .= " WHERE " . implode(' AND ', array_slice($conditions, 0, count($conditions) - 2)); // Exclude limit/offset params
        }
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute(array_slice($params, 0, count($params) - 2)); // Exclude limit/offset params
        $totalProducts = $countStmt->fetchColumn();

        echo json_encode(array("success" => true, "products" => $products, "total" => $totalProducts, "message" => "Products fetched successfully."));
    } catch (PDOException $e) {
        http_response_code(500); // Internal Server Error
        echo json_encode(array("success" => false, "message" => "Error fetching products: " . $e->getMessage()));
    }
}

// You'd add handlePostRequest, handlePutRequest, handleDeleteRequest functions here later
// For now, we'll focus on GET for inventory display
?>