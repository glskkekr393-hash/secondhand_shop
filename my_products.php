<?php
require 'config.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user']['id'];

/* =====================================================
   ลบสินค้า
===================================================== */

if (isset($_GET['delete'])) {

    $product_id = (int)$_GET['delete'];

    /* ป้องกันไม่ให้ลบสินค้าของคนอื่น */
    $stmt = $conn->prepare("
        DELETE FROM products
        WHERE id = ?
        AND user_id = ?
    ");

    $stmt->bind_param(
        "ii",
        $product_id,
        $user_id
    );

    $stmt->execute();

    header("Location: my_products.php");
    exit;
}


/* =====================================================
   ดึงสินค้าของเรา
   ⭐ ไม่กรอง status
   จึงเห็นทั้งขายอยู่ / ขายแล้ว / รอตรวจสอบ / ไม่อนุมัติ
===================================================== */

$stmt = $conn->prepare("
    SELECT
        p.*,
        c.name AS category_name
    FROM products p
    LEFT JOIN categories c
        ON p.category_id = c.id
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
");

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$products = $stmt->get_result();


/* =====================================================
   สถิติ
===================================================== */

$total_products = 0;
$approved_products = 0;
$sold_products = 0;
$pending_products = 0;

$stmt_count = $conn->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'approved') AS approved,
        SUM(status = 'sold') AS sold,
        SUM(status = 'pending') AS pending
    FROM products
    WHERE user_id = ?
");

$stmt_count->bind_param(
    "i",
    $user_id
);

$stmt_count->execute();

$stats = $stmt_count->get_result()->fetch_assoc();

$total_products = (int)($stats['total'] ?? 0);
$approved_products = (int)($stats['approved'] ?? 0);
$sold_products = (int)($stats['sold'] ?? 0);
$pending_products = (int)($stats['pending'] ?? 0);

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
สินค้าของฉัน -PD Shop
</title>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>

<style>

body {
    background:#f5f6f8;
}

.card {
    border:0;
    border-radius:16px;
    overflow:hidden;
}

.product-img {
    width:100%;
    height:220px;
    object-fit:cover;
}

.stat-card {
    border-radius:16px;
    border:0;
}

.price {
    color:#dc2626;
    font-size:22px;
    font-weight:bold;
}

.status-badge {
    font-size:13px;
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


<div class="d-flex flex-wrap gap-2">


<a
    href="messages.php"
    class="btn btn-outline-dark"
>
💬 ข้อความ
</a>


<a
    href="my_orders.php"
    class="btn btn-outline-dark"
>
📦 ออเดอร์ของฉัน
</a>


<a
    href="favorites.php"
    class="btn btn-outline-danger"
>
❤️ สินค้าที่ถูกใจ
</a>


<a
    href="seller_orders.php"
    class="btn btn-outline-dark"
>
🏪 ออเดอร์ที่ได้รับ
</a>


<a
    href="my_products.php"
    class="btn btn-dark"
>
📦 สินค้าของฉัน
</a>


<a
    href="sell.php"
    class="btn btn-outline-dark"
>
ลงขายสินค้า
</a>


<a
    href="profile.php"
    class="btn btn-outline-dark"
>
👤 <?= htmlspecialchars($_SESSION['user']['name']) ?>
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
     CONTENT
===================================================== -->

<div class="container py-5">


<!-- HEADER -->

<div class="d-flex justify-content-between align-items-center mb-4">

<div>

<h2 class="fw-bold mb-1">
📦 สินค้าของฉัน
</h2>

<p class="text-secondary mb-0">
จัดการสินค้าที่คุณลงขายทั้งหมด
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

<div class="row g-3 mb-4">


<div class="col-md-3">

<div class="card stat-card shadow-sm p-4">

<small class="text-secondary">
สินค้าทั้งหมด
</small>

<h2 class="fw-bold mb-0">
<?= $total_products ?>
</h2>

</div>

</div>


<div class="col-md-3">

<div class="card stat-card shadow-sm p-4">

<small class="text-secondary">
กำลังขาย
</small>

<h2 class="fw-bold text-success mb-0">
<?= $approved_products ?>
</h2>

</div>

</div>


<div class="col-md-3">

<div class="card stat-card shadow-sm p-4">

<small class="text-secondary">
ขายแล้ว
</small>

<h2 class="fw-bold text-danger mb-0">
<?= $sold_products ?>
</h2>

</div>

</div>


<div class="col-md-3">

<div class="card stat-card shadow-sm p-4">

<small class="text-secondary">
รอตรวจสอบ
</small>

<h2 class="fw-bold text-warning mb-0">
<?= $pending_products ?>
</h2>

</div>

</div>


</div>



<!-- =====================================================
     PRODUCTS
===================================================== -->

<?php if ($products->num_rows === 0): ?>


<div class="card shadow-sm p-5 text-center">

<div style="font-size:70px">
📦
</div>

<h4 class="mt-3">
ยังไม่มีสินค้า
</h4>

<p class="text-secondary">
คุณยังไม่ได้ลงขายสินค้า
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


<div class="card shadow-sm h-100">


<!-- =================================================
     IMAGE
================================================= -->

<?php if (!empty($p['image'])): ?>

<img
    src="<?= htmlspecialchars($p['image']) ?>"
    class="product-img"
    alt="<?= htmlspecialchars($p['name']) ?>"
>

<?php else: ?>

<div
    class="product-img d-flex align-items-center justify-content-center bg-secondary-subtle"
    style="font-size:70px"
>
📦
</div>

<?php endif; ?>



<!-- =================================================
     BODY
================================================= -->

<div class="card-body">


<!-- STATUS -->

<?php

$status = $p['status'];

if ($status === 'approved') {

    $status_text = 'กำลังขาย';
    $status_class = 'bg-success';

} elseif ($status === 'sold') {

    $status_text = 'ขายแล้ว';
    $status_class = 'bg-danger';

} elseif ($status === 'pending') {

    $status_text = 'รอตรวจสอบ';
    $status_class = 'bg-warning text-dark';

} elseif ($status === 'rejected') {

    $status_text = 'ไม่อนุมัติ';
    $status_class = 'bg-secondary';

} else {

    $status_text = $status;
    $status_class = 'bg-secondary';

}

?>


<span class="badge <?= $status_class ?> status-badge mb-2">

<?= $status_text ?>

</span>



<!-- CATEGORY -->

<small class="text-secondary d-block">

<?= htmlspecialchars(
    $p['category_name'] ?? 'สินค้า'
) ?>

</small>



<!-- NAME -->

<h5 class="mt-2">

<?= htmlspecialchars($p['name']) ?>

</h5>



<!-- PRICE -->

<div class="price">

฿<?= number_format(
    $p['price'],
    2
) ?>

</div>



<!-- CONDITION -->

<p class="mt-2 mb-3">

สภาพ:

<?= htmlspecialchars(
    $p['item_condition']
) ?>

</p>



<!-- =================================================
     BUTTONS
================================================= -->

<a
    href="product.php?id=<?= (int)$p['id'] ?>"
    class="btn btn-outline-dark w-100 mb-2"
>
👁 ดูสินค้า
</a>


<?php if ($status !== 'sold'): ?>

<a
    href="edit_product.php?id=<?= (int)$p['id'] ?>"
    class="btn btn-dark w-100 mb-2"
>
✏️ แก้ไขสินค้า
</a>

<?php endif; ?>


<a
    href="my_products.php?delete=<?= (int)$p['id'] ?>"
    class="btn btn-outline-danger w-100"
    onclick="return confirm('ต้องการลบสินค้านี้หรือไม่?');"
>
🗑 ลบสินค้า
</a>


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

🛒 <strong>PD Shop</strong>

<br>

<small>
เว็บไซต์ซื้อ–ขายสินค้ามือสอง
</small>

</footer>


</body>

</html>