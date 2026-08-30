<?php
require 'config.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user']['id'];


/* =====================================================
   1. สถิติสินค้า
===================================================== */

/* สินค้าทั้งหมดของฉัน */
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM products
    WHERE user_id = ?
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$total_products = (int)$stmt
    ->get_result()
    ->fetch_assoc()['total'];


/* สินค้าที่ยังขายอยู่ */
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM products
    WHERE user_id = ?
    AND status = 'approved'
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$available_products = (int)$stmt
    ->get_result()
    ->fetch_assoc()['total'];


/* สินค้าที่ขายแล้ว */
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM products
    WHERE user_id = ?
    AND status = 'sold'
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$sold_products = (int)$stmt
    ->get_result()
    ->fetch_assoc()['total'];


/* =====================================================
   2. จำนวนออเดอร์
===================================================== */

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM orders o
    JOIN products p
        ON o.product_id = p.id
    WHERE p.user_id = ?
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$total_orders = (int)$stmt
    ->get_result()
    ->fetch_assoc()['total'];


/* =====================================================
   3. รายได้รวม
   นับเฉพาะออเดอร์ที่ไม่ถูกยกเลิก
===================================================== */

$stmt = $conn->prepare("
    SELECT COALESCE(SUM(o.price), 0) AS total
    FROM orders o
    JOIN products p
        ON o.product_id = p.id
    WHERE p.user_id = ?
    AND o.status NOT IN ('ยกเลิก', 'cancelled', 'ยกเลิกแล้ว')
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$total_income = (float)$stmt
    ->get_result()
    ->fetch_assoc()['total'];


/* =====================================================
   4. ดึงสินค้าของฉัน
===================================================== */

$stmt = $conn->prepare("
    SELECT
        p.*,
        c.name AS category_name,

        (
            SELECT COUNT(*)
            FROM orders o
            WHERE o.product_id = p.id
        ) AS order_count

    FROM products p

    LEFT JOIN categories c
        ON p.category_id = c.id

    WHERE p.user_id = ?

    ORDER BY p.created_at DESC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$products = $stmt->get_result();

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
แดชบอร์ดผู้ขาย - PD Shop
</title>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>

<style>

body {
    background:#f5f6f8;
}

.navbar-brand {
    font-size:22px;
}

.dashboard-card {
    border:0;
    border-radius:18px;
    background:white;
    padding:25px;
    height:100%;
}

.stat-number {
    font-size:32px;
    font-weight:bold;
}

.stat-icon {
    font-size:40px;
}

.product-card {
    border:0;
    border-radius:18px;
    overflow:hidden;
}

.product-img {
    width:100%;
    height:220px;
    object-fit:cover;
}

.price {
    color:#dc3545;
    font-size:22px;
    font-weight:bold;
}

.status-badge {
    display:inline-block;
    padding:6px 12px;
    border-radius:20px;
    font-size:13px;
    font-weight:bold;
}

.status-approved {
    background:#d1e7dd;
    color:#0f5132;
}

.status-sold {
    background:#f8d7da;
    color:#842029;
}

.status-pending {
    background:#fff3cd;
    color:#664d03;
}

.status-rejected {
    background:#e2e3e5;
    color:#41464b;
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
    class="navbar-brand fw-bold text-dark text-decoration-none"
>

🛒 PD Shop

</a>


<div>

<a
    href="index.php"
    class="btn btn-outline-dark me-2"
>

หน้าหลัก

</a>


<a
    href="sell.php"
    class="btn btn-outline-dark me-2"
>

➕ ลงขายสินค้า

</a>


<a
    href="logout.php"
    class="btn btn-dark"
>

ออกจากระบบ

</a>

</div>

</div>

</nav>



<!-- =====================================================
     MAIN
===================================================== -->

<div class="container py-5">


<!-- HEADER -->

<div class="d-flex justify-content-between align-items-center mb-4">

<div>

<h1 class="fw-bold mb-1">

🏪 แดชบอร์ดผู้ขาย

</h1>

<p class="text-secondary mb-0">

จัดการสินค้าและดูสรุปการขายของคุณ

</p>

</div>


<a
    href="sell.php"
    class="btn btn-dark"
>

➕ ลงขายสินค้า

</a>

</div>



<!-- =====================================================
     STATISTICS
===================================================== -->

<div class="row g-4 mb-5">


<!-- สินค้าทั้งหมด -->

<div class="col-md-4">

<div class="dashboard-card shadow-sm">

<div class="d-flex justify-content-between">

<div>

<div class="text-secondary">

สินค้าทั้งหมด

</div>

<div class="stat-number">

<?= $total_products ?>

</div>

<div class="text-secondary">

รายการ

</div>

</div>

<div class="stat-icon">

📦

</div>

</div>

</div>

</div>



<!-- ขายอยู่ -->

<div class="col-md-4">

<div class="dashboard-card shadow-sm">

<div class="d-flex justify-content-between">

<div>

<div class="text-secondary">

สินค้าที่ยังขายอยู่

</div>

<div class="stat-number text-success">

<?= $available_products ?>

</div>

<div class="text-secondary">

รายการ

</div>

</div>

<div class="stat-icon">

🟢

</div>

</div>

</div>

</div>



<!-- ขายแล้ว -->

<div class="col-md-4">

<div class="dashboard-card shadow-sm">

<div class="d-flex justify-content-between">

<div>

<div class="text-secondary">

สินค้าที่ขายแล้ว

</div>

<div class="stat-number text-danger">

<?= $sold_products ?>

</div>

<div class="text-secondary">

รายการ

</div>

</div>

<div class="stat-icon">

✅

</div>

</div>

</div>

</div>



<!-- รายได้ -->

<div class="col-md-6">

<div class="dashboard-card shadow-sm">

<div class="d-flex justify-content-between">

<div>

<div class="text-secondary">

รายได้รวม

</div>

<div class="stat-number text-danger">

฿<?= number_format($total_income, 2) ?>

</div>

</div>

<div class="stat-icon">

💰

</div>

</div>

</div>

</div>



<!-- ออเดอร์ -->

<div class="col-md-6">

<div class="dashboard-card shadow-sm">

<div class="d-flex justify-content-between">

<div>

<div class="text-secondary">

จำนวนออเดอร์

</div>

<div class="stat-number">

<?= $total_orders ?>

</div>

<div class="text-secondary">

ออเดอร์

</div>

</div>

<div class="stat-icon">

🛒

</div>

</div>

</div>

</div>


</div>



<!-- =====================================================
     PRODUCTS
===================================================== -->

<div class="d-flex justify-content-between align-items-center mb-3">

<h3 class="fw-bold mb-0">

📦 สินค้าของฉัน

</h3>


<a
    href="sell.php"
    class="btn btn-dark"
>

➕ เพิ่มสินค้า

</a>

</div>



<?php if ($products->num_rows == 0): ?>


<div class="dashboard-card shadow-sm text-center py-5">

<div style="font-size:70px">

📦

</div>

<h4 class="mt-3">

ยังไม่มีสินค้า

</h4>

<p class="text-secondary">

เริ่มต้นลงขายสินค้าชิ้นแรกของคุณได้เลย

</p>

<a
    href="sell.php"
    class="btn btn-dark"
>

➕ ลงขายสินค้า

</a>

</div>


<?php else: ?>


<div class="row g-4">


<?php while ($p = $products->fetch_assoc()): ?>


<div class="col-md-4">


<div class="card product-card shadow-sm h-100">


<!-- รูป -->

<?php if (!empty($p['image'])): ?>

<img
    src="<?= htmlspecialchars($p['image']) ?>"
    class="product-img"
    alt="<?= htmlspecialchars($p['name']) ?>"
>

<?php else: ?>

<div
    class="product-img
    d-flex
    align-items-center
    justify-content-center
    bg-secondary-subtle"
    style="font-size:70px"
>

📦

</div>

<?php endif; ?>


<div class="card-body">


<!-- หมวด -->

<small class="text-secondary">

<?= htmlspecialchars(
    $p['category_name'] ?? 'สินค้า'
) ?>

</small>


<!-- ชื่อ -->

<h5 class="fw-bold mt-2">

<?= htmlspecialchars($p['name']) ?>

</h5>


<!-- ราคา -->

<div class="price mb-2">

฿<?= number_format(
    $p['price'],
    2
) ?>

</div>



<!-- สถานะ -->

<?php

$status = $p['status'];

if ($status === 'approved') {

    $status_text = '🟢 พร้อมขาย';
    $status_class = 'status-approved';

} elseif ($status === 'sold') {

    $status_text = '🔴 ขายแล้ว';
    $status_class = 'status-sold';

} elseif ($status === 'pending') {

    $status_text = '🟡 รออนุมัติ';
    $status_class = 'status-pending';

} elseif ($status === 'rejected') {

    $status_text = '❌ ไม่อนุมัติ';
    $status_class = 'status-rejected';

} else {

    $status_text = htmlspecialchars($status);
    $status_class = 'status-pending';

}

?>


<span
    class="status-badge <?= $status_class ?>"
>

<?= $status_text ?>

</span>



<!-- ออเดอร์ -->

<div class="mt-3 text-secondary">

🛒 ออเดอร์:

<strong>

<?= (int)$p['order_count'] ?>

</strong>

</div>



<hr>



<!-- ปุ่ม -->

<div class="d-grid gap-2">


<a
    href="product.php?id=<?= (int)$p['id'] ?>"
    class="btn btn-outline-dark"
>

👁️ ดูสินค้า

</a>


<a
    href="edit_product.php?id=<?= (int)$p['id'] ?>"
    class="btn btn-warning"
>

✏️ แก้ไขสินค้า

</a>


<a
    href="delete_product.php?id=<?= (int)$p['id'] ?>"
    class="btn btn-outline-danger"
    onclick="return confirm('ต้องการลบสินค้านี้จริงหรือไม่?');"
>

🗑️ ลบสินค้า

</a>


</div>


</div>

</div>

</div>


<?php endwhile; ?>


</div>

<?php endif; ?>


</div>



<!-- =====================================================
     FOOTER
===================================================== -->

<footer class="text-center text-secondary py-5">

🛒

<strong>
PD Shop
</strong>

<br>

<small>
เว็บไซต์ซื้อ–ขายสินค้ามือสอง
</small>

</footer>


</body>

</html>