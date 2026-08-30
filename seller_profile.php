<?php
require 'config.php';

if (!isset($_GET['id'])) {
    die("ไม่พบผู้ขาย");
}

$seller_id = (int)$_GET['id'];

/* =====================================================
   ดึงข้อมูลผู้ขาย
===================================================== */

$stmt = $conn->prepare("
    SELECT
        id,
        name,
        email
    FROM users
    WHERE id = ?
");

$stmt->bind_param("i", $seller_id);
$stmt->execute();

$seller = $stmt->get_result()->fetch_assoc();

if (!$seller) {
    die("ไม่พบผู้ขาย");
}


/* =====================================================
   ดึงสินค้าของผู้ขาย
===================================================== */

$stmt = $conn->prepare("
    SELECT
        p.*,
        c.name AS category_name
    FROM products p
    LEFT JOIN categories c
        ON p.category_id = c.id
    WHERE p.user_id = ?
      AND p.status IN ('approved', 'sold')
    ORDER BY p.created_at DESC
");

$stmt->bind_param("i", $seller_id);
$stmt->execute();

$products = $stmt->get_result();


/* =====================================================
   สถิติ
===================================================== */

$stmt = $conn->prepare("
    SELECT
        COUNT(*) AS total_products,
        SUM(status = 'sold') AS sold_products
    FROM products
    WHERE user_id = ?
      AND status IN ('approved', 'sold')
");

$stmt->bind_param("i", $seller_id);
$stmt->execute();

$stats = $stmt->get_result()->fetch_assoc();

$total_products = (int)($stats['total_products'] ?? 0);
$sold_products  = (int)($stats['sold_products'] ?? 0);

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
โปรไฟล์ <?= htmlspecialchars($seller['name']) ?>
</title>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>

<style>

body {
    background:#f5f6f8;
}

.profile-card {
    border:0;
    border-radius:20px;
}

.avatar {
    width:110px;
    height:110px;
    border-radius:50%;
    background:#111827;
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:45px;
    font-weight:bold;
    margin:auto;
}

.stat-card {
    border:0;
    border-radius:15px;
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

.price {
    color:#dc2626;
    font-size:21px;
    font-weight:bold;
}

.sold-product {
    opacity:.7;
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
    class="btn btn-outline-dark"
>
    หน้าหลัก
</a>

</div>

</div>

</nav>


<!-- =====================================================
     CONTENT
===================================================== -->

<div class="container py-5">


<a
    href="javascript:history.back()"
    class="text-secondary text-decoration-none"
>
    ← กลับ
</a>


<!-- =====================================================
     PROFILE CARD
===================================================== -->

<div class="card profile-card shadow-sm mt-3">

<div class="card-body text-center p-5">


<!-- AVATAR -->

<div class="avatar">

<?= htmlspecialchars(
    mb_substr(
        $seller['name'],
        0,
        1,
        'UTF-8'
    )
) ?>

</div>


<!-- NAME -->

<h2 class="fw-bold mt-3 mb-1">

<?= htmlspecialchars(
    $seller['name']
) ?>

</h2>


<p class="text-secondary mb-2">
ผู้ขายสินค้า
</p>


<!-- EMAIL -->

<p class="text-secondary">

📧 <?= htmlspecialchars(
    $seller['email']
) ?>

</p>


<!-- =====================================================
     ⭐ ปุ่มแก้ไข เฉพาะเจ้าของ
===================================================== -->

<?php if (
    isset($_SESSION['user']) &&
    (int)$_SESSION['user']['id'] === $seller_id
): ?>

<a
    href="edit_profile.php"
    class="btn btn-dark mt-2"
>
    ✏️ แก้ไขโปรไฟล์
</a>

<?php endif; ?>


<!-- =====================================================
     STATS
===================================================== -->

<div class="row justify-content-center g-3 mt-4">


<div class="col-md-3">

<div class="card stat-card shadow-sm p-3">

<div class="text-secondary">
สินค้าทั้งหมด
</div>

<h3 class="fw-bold mb-0">

<?= $total_products ?>

</h3>

</div>

</div>


<div class="col-md-3">

<div class="card stat-card shadow-sm p-3">

<div class="text-secondary">
ขายแล้ว
</div>

<h3 class="fw-bold text-success mb-0">

<?= $sold_products ?>

</h3>

</div>

</div>


</div>

</div>

</div>


<!-- =====================================================
     PRODUCTS
===================================================== -->

<h3 class="fw-bold mt-5 mb-3">

🛍️ สินค้าของ
<?= htmlspecialchars($seller['name']) ?>

</h3>


<div class="row g-4">


<?php if ($products->num_rows === 0): ?>

<div class="col-12">

<div class="card shadow-sm p-5 text-center">

<div style="font-size:60px">
📦
</div>

<h4 class="mt-3">
ยังไม่มีสินค้า
</h4>

</div>

</div>

<?php endif; ?>


<?php while ($p = $products->fetch_assoc()): ?>

<div class="col-md-4">


<div class="card product-card shadow-sm h-100
<?= $p['status'] === 'sold'
    ? 'sold-product'
    : ''
?>">


<!-- IMAGE -->

<?php if (!empty($p['image'])): ?>

<img
    src="<?= htmlspecialchars($p['image']) ?>"
    class="product-img"
    alt="<?= htmlspecialchars($p['name']) ?>"
>

<?php else: ?>

<div
    class="product-img d-flex align-items-center justify-content-center bg-secondary-subtle"
    style="font-size:60px"
>
    📦
</div>

<?php endif; ?>


<!-- DETAIL -->

<div class="card-body">


<!-- STATUS -->

<?php if ($p['status'] === 'sold'): ?>

<span class="badge bg-danger mb-2">

🔴 ขายแล้ว

</span>

<?php else: ?>

<span class="badge bg-success mb-2">

🟢 ยังขายอยู่

</span>

<?php endif; ?>


<small class="text-secondary d-block">

<?= htmlspecialchars(
    $p['category_name'] ?? 'สินค้า'
) ?>

</small>


<h5 class="mt-2">

<?= htmlspecialchars(
    $p['name']
) ?>

</h5>


<div class="price">

฿<?= number_format(
    $p['price'],
    2
) ?>

</div>


<p class="mt-2">

สภาพ:

<?= htmlspecialchars(
    $p['item_condition']
) ?>

</p>


<a
    href="product.php?id=<?= (int)$p['id'] ?>"
    class="btn btn-dark w-100"
>
    ดูสินค้า
</a>


</div>

</div>

</div>

<?php endwhile; ?>


</div>

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