<?php

require 'config.php';

if (!isset($_SESSION['user'])) {
    exit;
}

$user_id = (int)$_SESSION['user']['id'];

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    exit;
}

$stmt = $conn->prepare("
    UPDATE notifications
    SET is_read = 1
    WHERE id = ?
    AND user_id = ?
");

$stmt->bind_param(
    "ii",
    $id,
    $user_id
);

$stmt->execute();

echo "OK";