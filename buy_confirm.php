<?php
require 'config.php';


// =====================================================
// ต้องล็อกอินก่อนซื้อ
// =====================================================

if (!isset($_SESSION['user'])) {

    header("Location: login.php");

    exit;

}


// =====================================================
// รับข้อมูลจาก buy.php
// =====================================================

$product_id = (int)($_POST['product_id'] ?? 0);

$buyer_name = trim($_POST['buyer_name'] ?? '');

$phone = trim($_POST['phone'] ?? '');

$address = trim($_POST['address'] ?? '');

$buyer_id = (int)$_SESSION['user']['id'];


// =====================================================
// ตรวจสอบข้อมูล
// =====================================================

if (
    $product_id <= 0 ||
    $buyer_name === '' ||
    $phone === '' ||
    $address === ''
) {

    die("ข้อมูลไม่ครบ กรุณากลับไปกรอกข้อมูลใหม่");

}


// =====================================================
// ดึงข้อมูลสินค้า
// =====================================================

$stmt = $conn->prepare("
    SELECT
        p.*,
        c.name AS cat
    FROM products p
    JOIN categories c
        ON p.category_id = c.id
    WHERE p.id = ?
    AND p.status = 'approved'
    LIMIT 1
");

$stmt->bind_param(
    "i",
    $product_id
);

$stmt->execute();

$result = $stmt->get_result();

$product = $result->fetch_assoc();


if (!$product) {

    die("ไม่พบสินค้านี้");

}


// =====================================================
// บันทึกคำสั่งซื้อ
// =====================================================

$price = (float)$product['price'];

$status = "รอตรวจสอบ";

$shipping_company = null;

$tracking_number = null;


$stmt = $conn->prepare("
    INSERT INTO orders (
        product_id,
        buyer_id,
        buyer_name,
        phone,
        address,
        price,
        status,
        shipping_company,
        tracking_number
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");


$stmt->bind_param(
    "iisssdsss",
    $product_id,
    $buyer_id,
    $buyer_name,
    $phone,
    $address,
    $price,
    $status,
    $shipping_company,
    $tracking_number
);


if (!$stmt->execute()) {

    die(
        "ไม่สามารถบันทึกคำสั่งซื้อได้: "
        . $stmt->error
    );

}


$order_id = $conn->insert_id;

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
สั่งซื้อสำเร็จ - PD Shop
</title>


<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<style>

body {

    background:#f5f6f8;

}


.success-box {

    max-width:700px;

    margin:60px auto;

}


.card {

    border:0;

    border-radius:20px;

}


.success-icon {

    font-size:70px;

}


.price {

    color:#dc3545;

    font-size:28px;

    font-weight:bold;

}


.order-id {

    background:#f8f9fa;

    border-radius:12px;

    padding:15px;

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


<a
    href="my_orders.php"
    class="btn btn-outline-dark"
>

📦 การซื้อของฉัน

</a>


</div>

</nav>



<!-- =====================================================
     MAIN
===================================================== -->

<div class="container">


<div class="success-box">


<div class="card shadow-sm p-5 text-center">


<div class="success-icon">

✅

</div>


<h2 class="mt-3">

สั่งซื้อสำเร็จ!

</h2>


<p class="text-secondary">

ระบบบันทึกคำสั่งซื้อของคุณเรียบร้อยแล้ว

</p>



<!-- เลขคำสั่งซื้อ -->

<div class="order-id mt-4">

<strong>

หมายเลขคำสั่งซื้อ

</strong>

<br>

<span class="fs-4">

#<?= (int)$order_id ?>

</span>

</div>



<hr>



<h4>

<?= htmlspecialchars(
    $product['name']
) ?>

</h4>


<div class="price">

฿<?= number_format(
    $product['price'],
    2
) ?>

</div>



<div class="alert alert-warning mt-4">

📦 สถานะคำสั่งซื้อ:

<strong>
รอตรวจสอบ
</strong>

<br>

ผู้ขายจะตรวจสอบคำสั่งซื้อ
และดำเนินการจัดส่งต่อไป

</div>



<div class="d-grid gap-2 mt-4">


<a
    href="my_orders.php"
    class="btn btn-success btn-lg"
>

📦 ดูการซื้อของฉัน

</a>


<a
    href="index.php"
    class="btn btn-outline-dark btn-lg"
>

← กลับหน้าสินค้า

</a>


</div>


</div>


</div>


</div>


</body>

</html>