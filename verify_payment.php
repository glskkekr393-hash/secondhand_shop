<?php

require 'config.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$seller_id = (int)$_SESSION['user']['id'];

$order_id = (int)($_POST['order_id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($order_id <= 0) {
    die("ไม่พบออเดอร์");
}

if (!in_array($action, ['approve', 'reject'], true)) {
    die("คำสั่งไม่ถูกต้อง");
}


/* =====================================================
   ดึงออเดอร์
===================================================== */

$stmt = $conn->prepare("
    SELECT
        o.id,
        o.buyer_id,
        o.product_id,
        o.price,
        o.status,
        o.payment_slip,
        o.payment_status,
        p.name AS product_name
    FROM orders o
    JOIN products p
        ON o.product_id = p.id
    WHERE o.id = ?
    AND p.user_id = ?
    LIMIT 1
");

$stmt->bind_param(
    "ii",
    $order_id,
    $seller_id
);

$stmt->execute();

$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die("ไม่พบออเดอร์ หรือออเดอร์นี้ไม่ใช่ของคุณ");
}

$buyer_id = (int)$order['buyer_id'];
$product_id = (int)$order['product_id'];
$product_name = $order['product_name'];


/* =====================================================
   ยืนยันการชำระเงิน
===================================================== */

if ($action === 'approve') {

    $payment_status = 'ยืนยันแล้ว';

    /*
     * หลังยืนยันเงิน
     * เปลี่ยนสถานะเป็นกำลังจัดส่ง
     */

    $new_status = 'กำลังจัดส่ง';

    $stmt = $conn->prepare("
        UPDATE orders
        SET
            payment_status = ?,
            status = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "ssi",
        $payment_status,
        $new_status,
        $order_id
    );

    if (!$stmt->execute()) {
        die("อัปเดตการชำระเงินไม่สำเร็จ");
    }


    /* =================================================
       แจ้งเตือนผู้ซื้อ
    ================================================= */

    $title = "✅ ตรวจสอบการชำระเงินแล้ว";

    $message =
        "ผู้ขายยืนยันการชำระเงินสำหรับสินค้า \"" .
        $product_name .
        "\" แล้ว\n\n" .
        "💰 ยอดเงิน: ฿" .
        number_format($order['price'], 2) .
        "\n📦 สถานะออเดอร์: กำลังจัดส่ง";


    $link = "my_orders.php";


    $stmt_notify = $conn->prepare("
        INSERT INTO notifications
        (
            user_id,
            product_id,
            buyer_id,
            title,
            message,
            link,
            is_read,
            created_at
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            0,
            NOW()
        )
    ");

    $stmt_notify->bind_param(
        "iiisss",
        $buyer_id,
        $product_id,
        $buyer_id,
        $title,
        $message,
        $link
    );

    $stmt_notify->execute();


    header(
        "Location: seller_orders.php?payment=approved"
    );

    exit;
}


/* =====================================================
   ปฏิเสธสลิป
===================================================== */

if ($action === 'reject') {

    $payment_status = 'ปฏิเสธ';

    $new_status = 'รอตรวจสอบ';

    $stmt = $conn->prepare("
        UPDATE orders
        SET
            payment_status = ?,
            status = ?
        WHERE id = ?
    ");

    $stmt->bind_param(
        "ssi",
        $payment_status,
        $new_status,
        $order_id
    );

    if (!$stmt->execute()) {
        die("ปฏิเสธสลิปไม่สำเร็จ");
    }


    /* =================================================
       แจ้งเตือนผู้ซื้อ
    ================================================= */

    $title = "❌ ไม่สามารถยืนยันการชำระเงิน";

    $message =
        "สลิปการโอนเงินสำหรับสินค้า \"" .
        $product_name .
        "\" ไม่ผ่านการตรวจสอบ\n\n" .
        "กรุณาตรวจสอบสลิปและส่งหลักฐานใหม่";


    $link = "my_orders.php";


    $stmt_notify = $conn->prepare("
        INSERT INTO notifications
        (
            user_id,
            product_id,
            buyer_id,
            title,
            message,
            link,
            is_read,
            created_at
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            0,
            NOW()
        )
    ");

    $stmt_notify->bind_param(
        "iiisss",
        $buyer_id,
        $product_id,
        $buyer_id,
        $title,
        $message,
        $link
    );

    $stmt_notify->execute();


    header(
        "Location: seller_orders.php?payment=rejected"
    );

    exit;
}

?>