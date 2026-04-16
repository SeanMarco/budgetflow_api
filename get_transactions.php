<?php
require_once 'db.php';
require_once 'response.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$user_id = intval($_GET['user_id'] ?? 0);
if (!$user_id) send_error('user_id required');

$stmt = $conn->prepare(
    'SELECT t.id, t.title, t.amount, t.is_income, t.category, t.note, t.date,
            t.account_id,
            a.name  AS account_name,
            a.emoji AS account_emoji
     FROM transactions t
     LEFT JOIN accounts a ON a.id = t.account_id
     WHERE t.user_id = ?
     ORDER BY t.date DESC'
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$transactions = [];
while ($row = $result->fetch_assoc()) {
    $transactions[] = [
        'id'           => $row['id'],
        'title'        => $row['title'],
        'amount'       => (float)$row['amount'],
        'isIncome'     => (bool)$row['is_income'],
        'category'     => $row['category'],
        'accountId'    => $row['account_id'],
        'accountName'  => $row['account_name']  ?? '',
        'accountEmoji' => $row['account_emoji'] ?? '',
        'note'         => $row['note'] ?? '',
        'date'         => $row['date'],
    ];
}
$stmt->close();
$conn->close();
send_success($transactions);