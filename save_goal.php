<?php
require_once 'db.php';
require_once 'response.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$user_id = intval($_POST['user_id'] ?? 0);
$id      = intval($_POST['id']      ?? 0);
$title   = trim($_POST['title']     ?? '');
$emoji   = trim($_POST['emoji']     ?? '🎯');
$target  = floatval($_POST['target'] ?? 0);
$saved   = floatval($_POST['saved']  ?? 0);

if (!$user_id)    send_error('user_id required');
if ($title === '') send_error('title required');
if ($target <= 0) send_error('target must be greater than 0');

if ($id > 0) {
    $stmt = $conn->prepare(
        'UPDATE savings_goals SET title=?, emoji=?, target=?, saved=? WHERE id=? AND user_id=?'
    );
    $stmt->bind_param('ssddii', $title, $emoji, $target, $saved, $id, $user_id);
    if (!$stmt->execute()) send_error('Failed to update goal: ' . $stmt->error);
    $stmt->close();
    $conn->close();
    send_success(['id' => $id], 'Goal updated');
} else {
    $stmt = $conn->prepare(
        'INSERT INTO savings_goals (user_id, title, emoji, target, saved) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('issdd', $user_id, $title, $emoji, $target, $saved);
    if (!$stmt->execute()) send_error('Failed to create goal: ' . $stmt->error);
    $new_id = $stmt->insert_id;
    $stmt->close();
    $conn->close();
    send_success(['id' => $new_id], 'Goal created');
}