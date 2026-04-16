<?php
require_once 'db.php';
require_once 'response.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$user_id = intval($_GET['user_id'] ?? 0);
if (!$user_id) send_error('user_id required');

$stmt = $conn->prepare(
    'SELECT id, name, type, emoji, balance, color FROM accounts WHERE user_id = ? ORDER BY created_at ASC'
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$accounts = [];
while ($row = $result->fetch_assoc()) {
    $accounts[] = [
        'id'      => $row['id'],
        'name'    => $row['name'],
        'type'    => $row['type'],
        'emoji'   => $row['emoji'],
        'balance' => (float)$row['balance'],
        'color'   => $row['color'] ?? '#0EA974',
    ];
}
$stmt->close();
$conn->close();
send_success($accounts);