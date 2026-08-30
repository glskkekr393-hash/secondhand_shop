<?php
require 'config.php';

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    exit;
}

$user_id = (int)$_SESSION['user']['id'];
$product_id = (int)($_GET['product_id'] ?? 0);
$buyer_id = (int)($_GET['buyer_id'] ?? 0);

if ($product_id <= 0 || $buyer_id <= 0) {
    exit;
}


/* ดึงเจ้าของสินค้า */

$stmt = $conn->prepare("
    SELECT user_id
    FROM products
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $product_id);
$stmt->execute();

$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    exit;
}

$seller_id = (int)$product['user_id'];


/* ตรวจสอบสิทธิ์ */

if ($user_id != $seller_id && $user_id != $buyer_id) {
    http_response_code(403);
    exit;
}


/* ดึงข้อความของห้องนี้ */

$stmt = $conn->prepare("
    SELECT
        m.id,
        m.sender_id,
        m.receiver_id,
        m.message,
        m.created_at,
        u.name AS sender_name
    FROM messages m
    JOIN users u
        ON m.sender_id = u.id
    WHERE m.product_id = ?
    AND (
        (
            m.sender_id = ?
            AND m.receiver_id = ?
        )
        OR
        (
            m.sender_id = ?
            AND m.receiver_id = ?
        )
    )
    ORDER BY m.id ASC
");

$stmt->bind_param(
    "iiiii",
    $product_id,
    $seller_id,
    $buyer_id,
    $buyer_id,
    $seller_id
);

$stmt->execute();

$result = $stmt->get_result();

$messages = [];

while ($row = $result->fetch_assoc()) {

    $messages[] = [
        "id" => (int)$row['id'],
        "sender_id" => (int)$row['sender_id'],
        "sender_name" => $row['sender_name'],
        "message" => $row['message'],
        "created_at" => $row['created_at']
    ];

}


header('Content-Type: application/json; charset=utf-8');

echo json_encode(
    $messages,
    JSON_UNESCAPED_UNICODE
);