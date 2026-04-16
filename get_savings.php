<?php
require_once 'db.php';
require_once 'response.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$user_id = intval($_GET['user_id'] ?? 0);
if (!$user_id) send_error('user_id required');

$stmt = $conn->prepare(
    'SELECT id, title, emoji, target, saved FROM savings_goals WHERE user_id = ? ORDER BY id ASC'
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$goals = [];
while ($row = $result->fetch_assoc()) {
    $goals[] = [
        'id'     => $row['id'],
        'title'  => $row['title'],
        'emoji'  => $row['emoji'] ?? '🎯',
        'target' => (float)$row['target'],
        'saved'  => (float)$row['saved'],
    ];
}
$stmt->close();
$conn->close();
send_success($goals);