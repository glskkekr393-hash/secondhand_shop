<?php

require 'config.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$seller_id = (int)$_SESSION['user']['id'];

$order_id = (int)($_POST['order_id'] ?? 0);

$status = trim($_POST['status'] ?? '');

$shipping_company = trim(
    $_POST['shipping_company'] ?? ''
);

$tracking_number = trim(
    $_POST['tracking_number'] ?? ''
);


// =====================================================
// ตรวจสอบข้อมูล
// =====================================================

if ($order_id <= 0) {
    die("ไม่พบหมายเลขออเดอร์");
}


$allowed_status = [
    'รอตรวจสอบ',
    'กำลังจัดส่ง',
    'จัดส่งแล้ว',
    'สำเร็จ'
];


if (!in_array($status, $allowed_status, true)) {

    die(
        "สถานะไม่ถูกต้อง: " .
        htmlspecialchars($status)
    );

}


// =====================================================
// ตรวจสอบว่าออเดอร์เป็นของผู้ขาย
// =====================================================

$stmt = $conn->prepare("

    SELECT
        o.id,
        o.buyer_id,
        o.product_id,
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

$order = $stmt
    ->get_result()
    ->fetch_assoc();


if (!$order) {

    die(
        "ไม่พบออเดอร์ หรือออเดอร์นี้ไม่ใช่ของคุณ"
    );

}


$buyer_id =
    (int)$order['buyer_id'];

$product_id =
    (int)$order['product_id'];

$product_name =
    $order['product_name'];


// =====================================================
// อัปเดตออเดอร์
// =====================================================

$stmt = $conn->prepare("

    UPDATE orders

    SET
        status = ?,
        shipping_company = ?,
        tracking_number = ?

    WHERE id = ?

");

$stmt->bind_param(
    "sssi",
    $status,
    $shipping_company,
    $tracking_number,
    $order_id
);


if (!$stmt->execute()) {

    die(
        "แก้ไขออเดอร์ไม่สำเร็จ: " .
        htmlspecialchars($stmt->error)
    );

}


// =====================================================
// สร้างข้อความแจ้งเตือน
// =====================================================

if ($tracking_number !== '') {

    $title = "📦 อัปเดตการจัดส่ง";

    $message =
        "สินค้า \"" .
        $product_name .
        "\" มีการอัปเดตการจัดส่ง\n\n" .
        "🚚 บริษัทขนส่ง: " .
        (
            $shipping_company !== ''
            ? $shipping_company
            : 'ยังไม่ระบุ'
        ) .
        "\n📦 เลขพัสดุ: " .
        $tracking_number .
        "\n📋 สถานะ: " .
        $status;

} else {

    $title = "🔔 อัปเดตออเดอร์";

    $message =
        "สินค้า \"" .
        $product_name .
        "\" มีการอัปเดตสถานะเป็น \"" .
        $status .
        "\"";

}


// =====================================================
// แจ้งเตือนผู้ซื้อ
// =====================================================

if ($buyer_id > 0) {

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
            is_read
        )

        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            0
        )

    ");

    if (!$stmt_notify) {

        die(
            "สร้างคำสั่งแจ้งเตือนไม่สำเร็จ: " .
            htmlspecialchars($conn->error)
        );

    }

    $stmt_notify->bind_param(
        "iiisss",
        $buyer_id,
        $product_id,
        $buyer_id,
        $title,
        $message,
        $link
    );

    if (!$stmt_notify->execute()) {

        die(
            "บันทึกแจ้งเตือนไม่สำเร็จ: " .
            htmlspecialchars($stmt_notify->error)
        );

    }

}


// =====================================================
// กลับหน้าคนขาย
// =====================================================

header(
    "Location: seller_orders.php?update=success"
);

exit;

?>