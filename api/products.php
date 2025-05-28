<?php
// products.php - Your API endpoint

// Enable CORS for AJAX requests if your frontend is on a different origin (e.g., different port)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Enable error reporting for debugging. IMPORTANT: DISABLE OR SET TO 0 IN PRODUCTION.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/db.php'; // Your database connection using PDO

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGetRequest($pdo);
        break;
    case 'POST':
        // handlePostRequest($pdo); // This case is for adding new products, uncomment and define if needed.
        http_response_code(405); // Method Not Allowed by default if not implemented
        echo json_encode(array("message" => "POST method not implemented."));
        break;
    case 'PUT':
        handlePutRequest($pdo); // <-- UNCOMMENT THIS LINE AND DEFINE THE FUNCTION
        break;
    case 'DELETE':
        handleDeleteRequest($pdo); // <-- UNCOMMENT THIS LINE AND DEFINE THE FUNCTION (if it's commented out in your file)
        break;
    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(array("message" => "Method not allowed."));
        break;
}

function handleGetRequest($pdo) {
    $searchTerm = trim($_GET['search'] ?? '');
    $categoryId = $_GET['category_id'] ?? '';
    $limit = (int)($_GET['limit'] ?? 10);
    $offset = (int)($_GET['offset'] ?? 0);

    $sql = "SELECT p.id, p.name, p.barcode, p.price, p.stock_quantity, c.name as category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id";
    $conditions = [];
    $params = [];

    if (!empty($searchTerm)) {
        if (preg_match('/^[0-9]+$/', $searchTerm)) {
            $conditions[] = "(p.barcode = :searchBarcode OR p.name LIKE :searchName)";
            $params[':searchBarcode'] = $searchTerm;
            $params[':searchName'] = "%" . $searchTerm . "%";
        } else {
            $conditions[] = "(p.name LIKE :searchName OR p.barcode LIKE :searchPartialBarcode)";
            $params[':searchName'] = "%" . $searchTerm . "%";
            $params[':searchPartialBarcode'] = "%" . $searchTerm . "%";
        }
    }
    if (!empty($categoryId)) {
        $conditions[] = "p.category_id = :categoryId";
        $params[':categoryId'] = $categoryId;
    }

    if (count($conditions) > 0) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }

    $main_sql = $sql . " ORDER BY p.name ASC LIMIT :limit OFFSET :offset";
    $countSql = "SELECT COUNT(*) FROM products p";
    if (count($conditions) > 0) {
        $countSql .= " WHERE " . implode(' AND ', $conditions);
    }

    try {
        $countStmt = $pdo->prepare($countSql);
        foreach ($params as $key => $val) {
            $countStmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $countStmt->execute();
        $totalProducts = $countStmt->fetchColumn();

        $stmt = $pdo->prepare($main_sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(array("success" => true, "products" => $products, "total" => $totalProducts, "message" => "Products fetched successfully."));

    } catch (PDOException $e) {
        error_log("Error fetching products: " . $e->getMessage() . "\nSQL: " . $main_sql . "\nParams: " . print_r($params, true) . "\nLimit: " . $limit . "\nOffset: " . $offset);
        http_response_code(500);
        echo json_encode(array("success" => false, "message" => "Error fetching products: " . $e->getMessage()));
    }
}

// --- DEFINE handlePutRequest FUNCTION ---
function handlePutRequest($pdo) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input for PUT.']);
        exit();
    }

    $required_fields = ['id', 'name', 'category_id', 'price', 'stock_quantity'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || ($field !== 'barcode' && $data[$field] === '')) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Missing or empty required field: {$field}"]);
            exit();
        }
    }

    try {
        $id = $data['id'];
        $name = $data['name'];
        $category_id = $data['category_id'];
        $price = $data['price'];
        $stock_quantity = $data['stock_quantity'];
        $barcode = $data['barcode'] ?? null;

        $stmt = $pdo->prepare("UPDATE products SET
                                name = ?,
                                category_id = ?,
                                price = ?,
                                stock_quantity = ?,
                                barcode = ?
                            WHERE id = ?");

        $stmt->execute([$name, $category_id, $price, $stock_quantity, $barcode, $id]);

        if ($stmt->rowCount()) {
            echo json_encode(['success' => true, 'message' => 'Product updated successfully.']);
        } else {
            http_response_code(200); // Still OK, but no change occurred
            echo json_encode(['success' => false, 'message' => 'Product not found or no changes made.']);
        }

    } catch (PDOException $e) {
        error_log("Product update error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error during update: ' . $e->getMessage()]);
    }
}

// --- DEFINE handleDeleteRequest FUNCTION ---
function handleDeleteRequest($pdo) {
    $product_id = $_GET['id'] ?? null;

    if (empty($product_id) || !is_numeric($product_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid product ID provided.']);
        exit();
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$product_id]);

        if ($stmt->rowCount()) {
            echo json_encode(['success' => true, 'message' => 'Product deleted successfully.']);
        } else {
            http_response_code(404); // Not Found
            echo json_encode(['success' => false, 'message' => 'Product not found.']);
        }

    } catch (PDOException $e) {
        error_log("Product deletion error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error during deletion: ' . $e->getMessage()]);
    }
}
?>