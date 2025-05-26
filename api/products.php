<?php
// Enable CORS for AJAX requests if your frontend is on a different origin (e.g., different port)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// ... (existing headers and db.php include) ...

require_once '../config/db.php';

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
        handleDeleteRequest($pdo); // For deleting products
        break;
    default:
        http_response_code(405); // Method Not Allowed
        echo json_encode(array("message" => "Method not allowed."));
        break;
}

function handleGetRequest($pdo) {
    $searchTerm = trim($_GET['search'] ?? ''); // Trim whitespace
    $categoryId = $_GET['category_id'] ?? '';
    $limit = (int)($_GET['limit'] ?? 10);
    $offset = (int)($_GET['offset'] ?? 0);

    $sql = "SELECT p.id, p.name, p.barcode, p.price, p.stock_quantity, c.name as category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id";
    $conditions = [];
    $params = []; // This will now store named parameters

    if (!empty($searchTerm)) {
        // Option 1: Prioritize exact barcode match
        // Check if the search term looks like a barcode
        // Removed the separate COUNT(*) check for simplicity and to prevent
        // an extra database call for every search. The combined LIKE/equals will work.
        // The problem is in the binding of parameters.

        // If you want to strictly prioritize exact barcode matches,
        // you could still do a separate query first.
        // For now, let's go with a combined search which is more common.
        // We'll use named parameters for everything.

        // Check if searchTerm is purely numeric and of typical barcode length for a direct barcode search.
        // This regex '/^[0-9]{6,14}$/' is an example; adjust {6,14} based on your typical barcode lengths.
        if (preg_match('/^[0-9]+$/', $searchTerm)) { // Check if it's purely numeric
            // Attempt to search by exact barcode OR by name (partial)
            $conditions[] = "(p.barcode = :searchBarcode OR p.name LIKE :searchName)";
            $params[':searchBarcode'] = $searchTerm;
            $params[':searchName'] = "%" . $searchTerm . "%";
        } else {
            // If not numeric, search by name (partial) and also partial barcode match
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

    // --- Main Query with Named Parameters ---
    $main_sql = $sql . " ORDER BY p.name ASC LIMIT :limit OFFSET :offset";

    // --- Count Query with Named Parameters ---
    $countSql = "SELECT COUNT(*) FROM products p";
    if (count($conditions) > 0) {
        $countSql .= " WHERE " . implode(' AND ', $conditions);
    }

    try {
        // Prepare and execute the count query first
        $countStmt = $pdo->prepare($countSql);
        // Bind parameters for the count query
        foreach ($params as $key => $val) {
            $countStmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $countStmt->execute();
        $totalProducts = $countStmt->fetchColumn();


        // Prepare and execute the main product query
        $stmt = $pdo->prepare($main_sql);

        // Bind all parameters (including limit and offset)
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
        http_response_code(500); // Internal Server Error
        echo json_encode(array("success" => false, "message" => "Error fetching products: " . $e->getMessage()));
    }
}

// ... (handleDeleteRequest function remains the same) ...


?>