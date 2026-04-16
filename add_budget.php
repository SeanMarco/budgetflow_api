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

$user_id = $_POST['user_id'] ?? 0;
$category = $_POST['category'] ?? '';
$budget_limit = $_POST['budget_limit'] ?? 0;
$emoji = $_POST['emoji'] ?? '💰';
$period = $_POST['period'] ?? 'Monthly';

if (!$user_id || !$category || !$budget_limit) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

try {
    // Check if budget already exists for this category
    $stmt = $pdo->prepare("SELECT id FROM budgets WHERE user_id = ? AND category = ?");
    $stmt->execute([$user_id, $category]);
    
    if ($stmt->rowCount() > 0) {
        // Update existing
        $update = $pdo->prepare("
            UPDATE budgets 
            SET budget_limit = ?, emoji = ?, period = ?
            WHERE user_id = ? AND category = ?
        ");
        $update->execute([$budget_limit, $emoji, $period, $user_id, $category]);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Budget updated successfully'
        ]);
    } else {
        // Insert new
        $insert = $pdo->prepare("
            INSERT INTO budgets (user_id, category, budget_limit, emoji, period)
            VALUES (?, ?, ?, ?, ?)
        ");
        $insert->execute([$user_id, $category, $budget_limit, $emoji, $period]);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Budget created successfully',
            'budget_id' => $pdo->lastInsertId()
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