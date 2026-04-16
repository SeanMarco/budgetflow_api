<?php
require_once 'db.php';
require_once 'response.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$id         = intval($_POST['id']         ?? 0);
$user_id    = intval($_POST['user_id']    ?? 0);
$account_id = intval($_POST['account_id'] ?? 0);
$title      = trim($_POST['title']        ?? '');
$amount     = floatval($_POST['amount']   ?? 0);
$is_income  = intval($_POST['is_income']  ?? 0);
$category   = trim($_POST['category']     ?? 'General');
$note       = trim($_POST['note']         ?? '');

if (!$id)         send_error('id required');
if (!$user_id)    send_error('user_id required');
if (!$account_id) send_error('account_id required');
if ($amount <= 0) send_error('amount must be greater than 0');
if ($title === '') $title = $is_income ? 'Income' : 'Expense';

// Fetch original transaction to reverse balance
$orig = $conn->prepare('SELECT account_id, amount, is_income FROM transactions WHERE id = ? AND user_id = ?');
$orig->bind_param('ii', $id, $user_id);
$orig->execute();
$old = $orig->get_result()->fetch_assoc();
$orig->close();
if (!$old) send_error('Transaction not found');

// Reverse old balance
$old_delta = $old['is_income'] ? -(float)$old['amount'] : (float)$old['amount'];
$rev = $conn->prepare('UPDATE accounts SET balance = balance + ? WHERE id = ? AND user_id = ?');
$rev->bind_param('dii', $old_delta, $old['account_id'], $user_id);
$rev->execute();
$rev->close();

// Update transaction
$upd = $conn->prepare(
    'UPDATE transactions SET account_id=?, title=?, amount=?, is_income=?, category=?, note=? WHERE id=? AND user_id=?'
);
$upd->bind_param('isdiisii', $account_id, $title, $amount, $is_income, $category, $note, $id, $user_id);
if (!$upd->execute()) send_error('Failed to update transaction: ' . $upd->error);
$upd->close();

// Apply new balance
$new_delta = $is_income ? $amount : -$amount;
$apply = $conn->prepare('UPDATE accounts SET balance = balance + ? WHERE id = ? AND user_id = ?');
$apply->bind_param('dii', $new_delta, $account_id, $user_id);
$apply->execute();
$apply->close();
$conn->close();

send_success(null, 'Transaction updated');