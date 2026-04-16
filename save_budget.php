<?php
require_once 'db.php';
require_once 'response.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$user_id  = intval($_POST['user_id']  ?? 0);
$category = trim($_POST['category']   ?? '');
$limit    = floatval($_POST['limit']  ?? 0);

if (!$user_id)        send_error('user_id required');
if ($category === '')  send_error('category required');
if ($limit <= 0)      send_error('limit must be greater than 0');

$stmt = $conn->prepare(
    'INSERT INTO budgets (user_id, category, budget_limit)
     VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE budget_limit = VALUES(budget_limit)'
);
$stmt->bind_param('isd', $user_id, $category, $limit);
if (!$stmt->execute()) send_error('Failed to save budget: ' . $stmt->error);
$new_id = $stmt->insert_id;
$stmt->close();

if ($new_id === 0) {
    $sel = $conn->prepare('SELECT id FROM budgets WHERE user_id = ? AND category = ?');
    $sel->bind_param('is', $user_id, $category);
    $sel->execute();
    $row = $sel->get_result()->fetch_assoc();
    $new_id = $row['id'] ?? 0;
    $sel->close();
}
$conn->close();

send_success(['id' => $new_id, 'category' => $category, 'limit' => $limit], 'Budget saved');