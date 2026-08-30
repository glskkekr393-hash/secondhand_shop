<?php
require 'config.php';


// =====================================================
// ต้องล็อกอิน
// =====================================================

if (!isset($_SESSION['user'])) {

    header("Location: login.php");

    exit;
}


$user_id = (int)$_SESSION['user']['id'];

$order_id = (int)($_GET['id'] ?? 0);


// =====================================================
// ตรวจสอบ Order
// =====================================================

if ($order_id <= 0) {

    die("ไม่พบคำสั่งซื้อ");

}


// =====================================================
// ดึงข้อมูลออเดอร์ + สินค้า
// =====================================================

$stmt = $conn->prepare("
    SELECT
        o.*,

        p.name AS product_name,
        p.user_id AS seller_id

    FROM orders o

    JOIN products p
        ON o.product_id = p.id

    WHERE o.id = ?

    LIMIT 1
");


$stmt->bind_param(
    "i",
    $order_id
);


$stmt->execute();

$order =
    $stmt
    ->get_result()
    ->fetch_assoc();


if (!$order) {

    die("ไม่พบคำสั่งซื้อนี้");

}


// =====================================================
// ตรวจสอบสิทธิ์
// คนซื้อ หรือ คนขายเท่านั้น
// =====================================================

$is_buyer =
    ((int)$order['buyer_id'] === $user_id);


$is_seller =
    ((int)$order['seller_id'] === $user_id);


if (!$is_buyer && !$is_seller) {

    die("คุณไม่มีสิทธิ์ยกเลิกคำสั่งซื้อนี้");

}


// =====================================================
// ตรวจสอบสถานะ
// ห้ามยกเลิกถ้าจัดส่งแล้ว / สำเร็จ / ยกเลิกแล้ว
// =====================================================

$status = $order['status'];


if (
    $status === 'จัดส่งแล้ว' ||
    $status === 'สำเร็จ' ||
    $status === 'ยกเลิกแล้ว'
) {

    die(
        "ไม่สามารถยกเลิกคำสั่งซื้อนี้ได้<br><br>" .
        "สถานะปัจจุบัน: "
        . htmlspecialchars($status)
    );

}


// =====================================================
// เมื่อกดยืนยัน
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    $new_status = "ยกเลิกแล้ว";


    $stmt = $conn->prepare("
        UPDATE orders

        SET status = ?

        WHERE id = ?
    ");


    $stmt->bind_param(
        "si",
        $new_status,
        $order_id
    );


    if (!$stmt->execute()) {

        die(
            "ไม่สามารถยกเลิกคำสั่งซื้อได้: "
            . $stmt->error
        );

    }


    // =================================================
    // ถ้าสินค้าถูกเปลี่ยนเป็น sold ไปแล้ว
    // ให้กลับมา approved
    // =================================================

    $stmt = $conn->prepare("
        UPDATE products

        SET status = 'approved'

        WHERE id = ?

        AND status = 'sold'
    ");


    $stmt->bind_param(
        "i",
        $order['product_id']
    );


    $stmt->execute();


    // =================================================
    // กลับตามฝั่งที่กดยกเลิก
    // =================================================

    if ($is_buyer) {

        header(
            "Location: my_orders.php?cancel=success"
        );

    } else {

        header(
            "Location: seller_orders.php?cancel=success"
        );

    }

    exit;

}

?>


<!doctype html>

<html lang="th">


<head>

<meta charset="utf-8">


<meta
    name="viewport"
    content="width=device-width, initial-scale=1"
>


<title>
ยกเลิกคำสั่งซื้อ - PD Shop
</title>


<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<style>

body {

    background:#f5f6f8;

}


.cancel-card {

    max-width:600px;

    margin:80px auto;

    border:0;

    border-radius:20px;

}


.warning-icon {

    font-size:70px;

}


</style>

</head>



<body>


<!-- =====================================================
     NAVBAR
===================================================== -->

<nav class="navbar bg-white shadow-sm">


<div class="container">


<a
    href="index.php"
    class="navbar-brand fw-bold"
>

🛒 PD Shop

</a>


</div>

</nav>



<!-- =====================================================
     MAIN
===================================================== -->

<div class="container">


<div class="card shadow-sm cancel-card p-5 text-center">


<div class="warning-icon">

⚠️

</div>


<h2 class="mt-3">

ยืนยันการยกเลิก

</h2>


<p class="text-secondary">

คุณต้องการยกเลิกคำสั่งซื้อสินค้านี้ใช่หรือไม่?

</p>



<div class="alert alert-light text-start mt-4">


<strong>

📦 <?= htmlspecialchars(
    $order['product_name']
) ?>

</strong>


<br>


💰 ราคา:

฿<?= number_format(
    $order['price'],
    2
) ?>


<br>


📋 สถานะปัจจุบัน:

<?= htmlspecialchars(
    $order['status']
) ?>


</div>



<div class="d-flex gap-2 mt-4">


<?php if ($is_buyer): ?>


<a
    href="my_orders.php"
    class="btn btn-outline-secondary btn-lg w-50"
>

← ไม่ยกเลิก

</a>


<?php else: ?>


<a
    href="seller_orders.php"
    class="btn btn-outline-secondary btn-lg w-50"
>

← ไม่ยกเลิก

</a>


<?php endif; ?>



<form
    method="post"
    class="w-50"
>


<button
    type="submit"
    class="btn btn-danger btn-lg w-100"
>

❌ ยืนยันยกเลิก

</button>


</form>


</div>


</div>


</div>



</body>

</html>