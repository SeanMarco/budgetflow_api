<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$user_id = $_GET['user_id'] ?? 0;

if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'User ID required']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            b.*,
            COALESCE(SUM(CASE 
                WHEN t.is_income = 0 
                AND t.date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                THEN t.amount 
                ELSE 0 
            END), 0) as spent
        FROM budgets b
        LEFT JOIN transactions t 
            ON t.category = b.category 
            AND t.user_id = b.user_id
            AND t.is_income = 0
        WHERE b.user_id = ?
        GROUP BY b.id
        ORDER BY b.category
    ");
    $stmt->execute([$user_id]);
    $budgets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'data' => $budgets
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>