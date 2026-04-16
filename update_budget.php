<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$budget_id = $_POST['budget_id'] ?? 0;
$user_id = $_POST['user_id'] ?? 0;
$category = $_POST['category'] ?? '';
$budget_limit = $_POST['budget_limit'] ?? 0;
$emoji = $_POST['emoji'] ?? '💰';
$period = $_POST['period'] ?? 'Monthly';

if (!$budget_id || !$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE budgets 
        SET category = ?, budget_limit = ?, emoji = ?, period = ?
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$category, $budget_limit, $emoji, $period, $budget_id, $user_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Budget updated successfully'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Budget not found or no changes made'
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>