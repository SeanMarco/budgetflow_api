<?php
require_once 'db.php';
require_once 'response.php';

function getUserFromToken($conn) {
    $headers = getallheaders();
    $token = $headers['Authorization'] ?? '';

    if (!$token) {
        send_error("No token provided");
    }

    $stmt = $conn->prepare("SELECT id, email, first_name, last_name FROM users WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        send_error("Invalid token");
    }

    return $result->fetch_assoc();
}
?>