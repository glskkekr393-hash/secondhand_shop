<?php
require 'config.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user']['id'];
$product_id = (int)($_GET['id'] ?? 0);

if ($product_id <= 0) {
    header("Location: index.php");
    exit;
}


/* =====================================================
   ตรวจสอบว่าสินค้ามีอยู่จริง
===================================================== */

$stmt = $conn->prepare("
    SELECT id
    FROM products
    WHERE id = ?
    AND status IN ('approved', 'sold')
");

$stmt->bind_param("i", $product_id);
$stmt->execute();

$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header("Location: index.php");
    exit;
}


/* =====================================================
   ตรวจสอบว่าถูกใจไว้แล้วหรือยัง
===================================================== */

$stmt = $conn->prepare("
    SELECT id
    FROM favorites
    WHERE user_id = ?
    AND product_id = ?
    LIMIT 1
");

$stmt->bind_param(
    "ii",
    $user_id,
    $product_id
);

$stmt->execute();

$favorite = $stmt->get_result()->fetch_assoc();


/* =====================================================
   ถ้ามีแล้ว → ลบออก
===================================================== */

if ($favorite) {

    $stmt = $conn->prepare("
        DELETE FROM favorites
        WHERE user_id = ?
        AND product_id = ?
    ");

    $stmt->bind_param(
        "ii",
        $user_id,
        $product_id
    );

    $stmt->execute();

}


/* =====================================================
   ถ้ายังไม่มี → เพิ่ม
===================================================== */

else {

    $stmt = $conn->prepare("
        INSERT INTO favorites
        (user_id, product_id)
        VALUES (?, ?)
    ");

    $stmt->bind_param(
        "ii",
        $user_id,
        $product_id
    );

    $stmt->execute();
}


/* =====================================================
   หลังจากกด → ไปหน้ารายการถูกใจ
===================================================== */

header("Location: favorites.php");
exit;

?>