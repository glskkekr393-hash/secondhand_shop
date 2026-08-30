<?php
require 'config.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user']['id'];


/* =====================================
   เปลี่ยนสถานะเป็นขายแล้ว
===================================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['sold_id'])
) {

    $sold_id = (int)$_POST['sold_id'];

    $stmt = $conn->prepare("
        UPDATE products
        SET status = 'sold'
        WHERE id = ?
        AND user_id = ?
        AND status = 'approved'
    ");

    $stmt->bind_param(
        "ii",
        $sold_id,
        $user_id
    );

    $stmt->execute();

    header("Location: my.php");
    exit;
}


/* =====================================
   ลบสินค้า
===================================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['delete_id'])
) {

    $delete_id = (int)$_POST['delete_id'];

    $stmt = $conn->prepare("
        DELETE FROM products
        WHERE id = ?
        AND user_id = ?
    ");

    $stmt->bind_param(
        "ii",
        $delete_id,
        $user_id
    );

    $stmt->execute();

    header("Location: my.php");
    exit;
}


/* =====================================
   ดึงสินค้าของฉัน
===================================== */

$stmt = $conn->prepare("
    SELECT
        p.*,
        c.name AS category_name
    FROM products p
    JOIN categories c
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
สินค้าของฉัน - PD Shop
</title>


<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<style>

body {
    background: #f5f6f8;
}

.navbar-brand {
    font-size: 24px;
}

.product-card {
    border: 0;
    border-radius: 16px;
    overflow: hidden;
}

.product-img {
    width: 100%;
    height: 220px;
    object-fit: cover;
}

.status {
    font-weight: bold;
    font-size: 13px;
}

.price {
    color: #dc2626;
    font-weight: bold;
}

</style>

</head>


<body>


<!-- =====================================
     NAVBAR
===================================== -->

<nav class="navbar bg-white shadow-sm">

<div class="container">


<a
    class="navbar-brand fw-bold text-dark text-decoration-none"
    href="index.php"
>

🛒 PD Shop

</a>


<div>

<a
    href="index.php"
    class="btn btn-outline-secondary me-2"
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



<!-- =====================================
     CONTENT
===================================== -->

<div class="container py-5">


<!-- TITLE -->

<div class="d-flex justify-content-between align-items-center mb-4">

<div>

<h1 class="fw-bold mb-1">

👤 สินค้าของฉัน

</h1>

<p class="text-secondary mb-0">

จัดการประกาศสินค้าของคุณ

</p>

</div>


<a
    href="sell.php"
    class="btn btn-warning"
>

➕ ลงขายสินค้า

</a>

</div>



<!-- =====================================
     PRODUCT LIST
===================================== -->

<?php if ($products->num_rows == 0): ?>


<div class="card border-0 shadow-sm p-5 text-center">

<div style="font-size:70px;">

📦

</div>


<h3 class="mt-3">

ยังไม่มีสินค้า

</h3>


<p class="text-secondary">

คุณยังไม่ได้ลงประกาศขายสินค้า

</p>


<a
    href="sell.php"
    class="btn btn-dark"
>

เริ่มลงขายสินค้า

</a>

</div>


<?php else: ?>


<div class="row g-4">


<?php while ($p = $products->fetch_assoc()): ?>


<div class="col-md-4">


<div class="card product-card shadow-sm h-100">


<!-- =====================================
     IMAGE
===================================== -->

<?php if (!empty($p['image'])): ?>


<img
    src="<?= htmlspecialchars($p['image']) ?>"
    class="product-img"
    alt="<?= htmlspecialchars($p['name']) ?>"
>


<?php else: ?>


<div
    class="product-img bg-secondary-subtle d-flex align-items-center justify-content-center"
    style="font-size:70px;"
>

📦

</div>


<?php endif; ?>



<!-- =====================================
     BODY
===================================== -->

<div class="card-body">


<!-- CATEGORY -->

<small class="text-secondary">

<?= htmlspecialchars($p['category_name']) ?>

</small>


<!-- NAME -->

<h5 class="fw-bold mt-1">

<?= htmlspecialchars($p['name']) ?>

</h5>


<!-- PRICE -->

<h4 class="price">

฿<?= number_format($p['price'], 2) ?>

</h4>



<!-- =====================================
     STATUS
===================================== -->

<div class="mb-3">


<?php if ($p['status'] === 'pending'): ?>


<span class="badge bg-warning text-dark status">

⏳ รอตรวจสอบ

</span>


<?php elseif ($p['status'] === 'approved'): ?>


<span class="badge bg-success status">

✅ อนุมัติแล้ว

</span>


<?php elseif ($p['status'] === 'rejected'): ?>


<span class="badge bg-danger status">

❌ ไม่อนุมัติ

</span>


<?php elseif ($p['status'] === 'sold'): ?>


<span class="badge bg-secondary status">

🛒 ขายแล้ว

</span>


<?php endif; ?>


</div>



<!-- =====================================
     BUTTONS
===================================== -->


<?php if ($p['status'] === 'approved'): ?>


<!-- ดูสินค้า -->

<a
    href="product.php?id=<?= $p['id'] ?>"
    class="btn btn-dark w-100 mb-2"
>

👀 ดูสินค้า

</a>


<!-- แชต -->

<a
    href="chat.php?product_id=<?= $p['id'] ?>"
    class="btn btn-outline-primary w-100 mb-2"
>

💬 ดูแชต

</a>


<!-- ขายแล้ว -->

<form
    method="post"
    class="mb-2"
    onsubmit="return confirm('ยืนยันว่าคุณขายสินค้านี้แล้วใช่หรือไม่?');"
>

<input
    type="hidden"
    name="sold_id"
    value="<?= $p['id'] ?>"
>


<button
    type="submit"
    class="btn btn-success w-100"
>

✅ ทำเครื่องหมายว่าขายแล้ว

</button>

</form>


<?php elseif ($p['status'] === 'sold'): ?>


<!-- ขายแล้ว -->

<div class="alert alert-secondary text-center">

🛒 สินค้านี้ขายแล้ว

</div>


<?php endif; ?>



<!-- =====================================
     DELETE
===================================== -->

<form
    method="post"
    class="mt-2"
    onsubmit="return confirm('ต้องการลบประกาศนี้จริงหรือไม่?');"
>

<input
    type="hidden"
    name="delete_id"
    value="<?= $p['id'] ?>"
>


<button
    type="submit"
    class="btn btn-outline-danger w-100"
>

🗑️ ลบประกาศ

</button>

</form>


</div>

</div>

</div>


<?php endwhile; ?>


</div>


<?php endif; ?>


</div>



<!-- FOOTER -->

<footer class="text-center text-secondary py-5">

🛒 <strong>PD Shop</strong>

<br>

<small>

เว็บไซต์ซื้อ–ขายสินค้ามือสอง

</small>

</footer>


</body>

</html>