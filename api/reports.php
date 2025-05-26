// Example: Daily sales
require_once 'config/db.php';
$today = date('Y-m-d');
$sql = "SELECT SUM(total_amount) as daily_sales FROM sales WHERE DATE(sale_date) = :today AND status = 'completed'";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':today', $today);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$daily_sales = $result['daily_sales'] ?? 0.00;