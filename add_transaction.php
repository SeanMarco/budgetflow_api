<?php
require_once 'db.php';
require_once 'response.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$user_id    = intval($_POST['user_id']    ?? 0);
$account_id = intval($_POST['account_id'] ?? 0);
$title      = trim($_POST['title']        ?? '');
$amount     = floatval($_POST['amount']   ?? 0);
$is_income  = intval($_POST['is_income']  ?? 0);
$category   = trim($_POST['category']     ?? 'General');
$note       = trim($_POST['note']         ?? '');
$date       = trim($_POST['date']         ?? date('Y-m-d H:i:s'));

if (!$user_id)    send_error('user_id required');
if (!$account_id) send_error('account_id required');
if ($amount <= 0) send_error('amount must be greater than 0');
if ($title === '') $title = $is_income ? 'Income' : 'Expense';

// Verify account belongs to user
$chk = $conn->prepare('SELECT id FROM accounts WHERE id = ? AND user_id = ?');
$chk->bind_param('ii', $account_id, $user_id);
$chk->execute();
if (!$chk->get_result()->fetch_assoc()) send_error('Account not found');
$chk->close();

// Insert transaction
$stmt = $conn->prepare(
    'INSERT INTO transactions (user_id, account_id, title, amount, is_income, category, note, date)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
);
$stmt->bind_param('iisdiiss', $user_id, $account_id, $title, $amount, $is_income, $category, $note, $date);
if (!$stmt->execute()) send_error('Failed to add transaction: ' . $stmt->error);
$new_id = $stmt->insert_id;
$stmt->close();

// Update account balance
$delta = $is_income ? $amount : -$amount;
$upd = $conn->prepare('UPDATE accounts SET balance = balance + ? WHERE id = ? AND user_id = ?');
$upd->bind_param('dii', $delta, $account_id, $user_id);
$upd->execute();
$upd->close();
$conn->close();

send_success(['id' => $new_id], 'Transaction added');