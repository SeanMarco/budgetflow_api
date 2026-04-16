<?php
require_once 'db.php';
require_once 'response.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$user_id = intval($_POST['user_id'] ?? 0);
$name    = trim($_POST['name']    ?? '');
$type    = trim($_POST['type']    ?? 'Cash');
$emoji   = trim($_POST['emoji']   ?? '💰');
$balance = floatval($_POST['balance'] ?? 0);
$color   = trim($_POST['color']   ?? '#0EA974');

if (!$user_id)    send_error('user_id required');
if ($name === '') send_error('name required');

$stmt = $conn->prepare(
    'INSERT INTO accounts (user_id, name, type, emoji, balance, color) VALUES (?, ?, ?, ?, ?, ?)'
);
$stmt->bind_param('isssds', $user_id, $name, $type, $emoji, $balance, $color);

if (!$stmt->execute()) send_error('Failed to create account: ' . $stmt->error);

$new_id = $stmt->insert_id;
$stmt->close();
$conn->close();

send_success([
    'id'      => $new_id,
    'name'    => $name,
    'type'    => $type,
    'emoji'   => $emoji,
    'balance' => $balance,
    'color'   => $color,
], 'Account created');