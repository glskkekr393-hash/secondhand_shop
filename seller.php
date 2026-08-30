<?php
require 'config.php';

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user']['id'];
$user_name = $_SESSION['user']['name'] ?? 'ผู้ขาย';


/* =====================================================
   ดึงสินค้าของผู้ขายคนนี้
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

$stmt->bind_param("i", $user_id);
$stmt->execute();

$products = $stmt->get_result();

?>


<!doctype html>

<html lang="th">

<head>

<meta charset="utf-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>สินค้าของฉัน - PD Shop</title>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>

<style>

body {
    background:#f5f6f8;
}

.product-card {
    border:0;
    border-radius:16px;
    overflow:hidden;
}

.product-img {
    width:100%;
    height:220px;
    object-fit:cover;
}

.no-image {
    width:100%;
    height:220px;
    background:#e9ecef;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:60px;
}

.price {
    color:#dc3545;
    font-size:23px;
    font-weight:bold;
}

.status {
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
    class="navbar-brand fw-bold"
>
🛒 PD Shop
</a>

<div>

<a
    href="index.php"
    class="btn btn-outline-dark me-2"
>
← หน้าหลัก
</a>

<a
    href="sell.php"
    class="btn btn-success"
>
➕ ลงขายสินค้า
</a>

</div>

</div>

</nav>


<!-- =====================================================
     MAIN
===================================================== -->

<div class="container py-5">


<div class="d-flex justify-content-between align-items-center mb-4">

<div>

<h2 class="mb-1">
🏪 สินค้าของฉัน
</h2>

<p class="text-secondary mb-0">

สวัสดี
<strong>
<?= htmlspecialchars($user_name) ?>
</strong>

นี่คือสินค้าที่คุณลงขาย

</p>

</div>

</div>


<?php if ($products->num_rows === 0): ?>


<!-- ไม่มีสินค้า -->

<div class="card border-0 shadow-sm p-5 text-center">

<div style="font-size:70px;">
📦
</div>

<h4 class="mt-3">
ยังไม่มีสินค้าที่ลงขาย
</h4>

<p class="text-secondary">
คุณยังไม่ได้ลงขายสินค้า
</p>

<a
    href="sell.php"
    class="btn btn-success"
>
➕ ลงขายสินค้าชิ้นแรก
</a>

</div>


<?php else: ?>


<!-- =====================================================
     PRODUCT LIST
===================================================== -->

<div class="row g-4">


<?php while ($p = $products->fetch_assoc()): ?>


<div class="col-md-6 col-lg-4">


<div class="card product-card shadow-sm h-100">


<!-- รูป -->

<?php if (!empty($p['image'])): ?>

<img
    src="<?= htmlspecialchars($p['image']) ?>"
    class="product-img"
    alt="<?= htmlspecialchars($p['name']) ?>"
>

<?php else: ?>

<div class="no-image">
📦
</div>

<?php endif; ?>


<div class="card-body">


<!-- หมวดหมู่ -->

<?php if (!empty($p['category_name'])): ?>

<small class="text-secondary">

<?= htmlspecialchars(
    $p['category_name']
) ?>

</small>

<?php endif; ?>


<!-- ชื่อ -->

<h5 class="mt-1">

<?= htmlspecialchars(
    $p['name']
) ?>

</h5>


<!-- ราคา -->

<div class="price">

฿<?= number_format(
    (float)$p['price'],
    2
) ?>

</div>


<!-- สภาพ -->

<p class="mt-2 mb-2">

<strong>
สภาพ:
</strong>

<?= htmlspecialchars(
    $p['item_condition'] ?? '-'
) ?>

</p>


<!-- สถานะ -->

<?php

$product_status =
    strtolower(
        trim($p['status'] ?? '')
    );


if ($product_status === 'approved') {

    $status_text = '🟢 กำลังขาย';

    $status_class = 'bg-success';

}
elseif ($product_status === 'pending') {

    $status_text = '🟡 รอตรวจสอบ';

    $status_class = 'bg-warning text-dark';

}
elseif (
    $product_status === 'rejected'
) {

    $status_text = '🔴 ไม่ผ่านการอนุมัติ';

    $status_class = 'bg-danger';

}
else {

    $status_text =
        $p['status'] ?? 'ไม่ระบุ';

    $status_class =
        'bg-secondary';

}

?>


<span
    class="badge <?= $status_class ?> status"
>

<?= htmlspecialchars(
    $status_text
) ?>

</span>


<!-- ปุ่ม -->

<div class="d-grid gap-2 mt-3">


<a
    href="product.php?id=<?= (int)$p['id'] ?>"
    class="btn btn-dark"
>

👁️ ดูสินค้า

</a>


<a
    href="edit_product.php?id=<?= (int)$p['id'] ?>"
    class="btn btn-outline-primary"
>

✏️ แก้ไขสินค้า

</a>


</div>


</div>


</div>


</div>


<?php endwhile; ?>


</div>


<?php endif; ?>


</div>


<footer class="text-center text-secondary py-5">

🛒 <strong>PD Shop</strong>

<br>

<small>
เว็บไซต์ซื้อ–ขายสินค้ามือสอง
</small>

</footer>


</body>

</html>