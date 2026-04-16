<?php
require_once 'db.php';
require_once 'response.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$id      = intval($_POST['id']      ?? 0);
$user_id = intval($_POST['user_id'] ?? 0);

if (!$id)      send_error('id required');
if (!$user_id) send_error('user_id required');

// Fetch to reverse balance
$fetch = $conn->prepare('SELECT account_id, amount, is_income FROM transactions WHERE id = ? AND user_id = ?');
$fetch->bind_param('ii', $id, $user_id);
$fetch->execute();
$row = $fetch->get_result()->fetch_assoc();
$fetch->close();
if (!$row) send_error('Transaction not found');

// Reverse balance
$delta = $row['is_income'] ? -(float)$row['amount'] : (float)$row['amount'];
$rev = $conn->prepare('UPDATE accounts SET balance = balance + ? WHERE id = ? AND user_id = ?');
$rev->bind_param('dii', $delta, $row['account_id'], $user_id);
$rev->execute();
$rev->close();

// Delete
$del = $conn->prepare('DELETE FROM transactions WHERE id = ? AND user_id = ?');
$del->bind_param('ii', $id, $user_id);
if (!$del->execute()) send_error('Failed to delete: ' . $del->error);
$del->close();
$conn->close();

send_success(null, 'Transaction deleted');