<?php
require 'config.php';

if (
    !isset($_SESSION['user']) ||
    $_SESSION['user']['role'] !== 'admin'
) {
    header("Location: login.php");
    exit;
}


/* =====================================================
   FUNCTION
===================================================== */

function e($text)
{
    return htmlspecialchars(
        (string)$text,
        ENT_QUOTES,
        'UTF-8'
    );
}


/* =====================================================
   TAB
===================================================== */

$tab = $_GET['tab'] ?? 'dashboard';

$view_order = (int)($_GET['view_order'] ?? 0);


/* =====================================================
   ACTION - PRODUCT
===================================================== */

if (isset($_GET['approve_product'])) {

    $id = (int)$_GET['approve_product'];

    $stmt = $conn->prepare("
        UPDATE products
        SET status = 'approved'
        WHERE id = ?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: admin.php?tab=products&msg=approved");
    exit;
}


if (isset($_GET['reject_product'])) {

    $id = (int)$_GET['reject_product'];

    $stmt = $conn->prepare("
        UPDATE products
        SET status = 'rejected'
        WHERE id = ?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: admin.php?tab=products&msg=rejected");
    exit;
}


if (isset($_GET['delete_product'])) {

    $id = (int)$_GET['delete_product'];

    $stmt = $conn->prepare("
        DELETE FROM products
        WHERE id = ?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: admin.php?tab=products&msg=deleted");
    exit;
}


/* =====================================================
   ACTION - PAYMENT
===================================================== */

if (isset($_GET['approve_payment'])) {

    $id = (int)$_GET['approve_payment'];

    $stmt = $conn->prepare("
        UPDATE orders
        SET payment_status = 'paid'
        WHERE id = ?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: admin.php?tab=orders&view_order=".$id."&msg=payment_approved");
    exit;
}


if (isset($_GET['reject_payment'])) {

    $id = (int)$_GET['reject_payment'];

    $stmt = $conn->prepare("
        UPDATE orders
        SET payment_status = 'rejected'
        WHERE id = ?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: admin.php?tab=orders&view_order=".$id."&msg=payment_rejected");
    exit;
}


/* =====================================================
   DASHBOARD STATS
===================================================== */

$totalUsers = 0;
$totalProducts = 0;
$approved = 0;
$sold = 0;
$pending = 0;
$rejected = 0;
$totalOrders = 0;
$waitingOrders = 0;
$messages = 0;


/* USERS */

$result = $conn->query("
    SELECT COUNT(*) AS n
    FROM users
    WHERE role = 'user'
");

if ($result) {
    $totalUsers = (int)$result->fetch_assoc()['n'];
}


/* PRODUCTS */

$result = $conn->query("
    SELECT COUNT(*) AS n
    FROM products
");

if ($result) {
    $totalProducts = (int)$result->fetch_assoc()['n'];
}


/* APPROVED */

$result = $conn->query("
    SELECT COUNT(*) AS n
    FROM products
    WHERE status = 'approved'
");

if ($result) {
    $approved = (int)$result->fetch_assoc()['n'];
}


/* SOLD */

$result = $conn->query("
    SELECT COUNT(*) AS n
    FROM products
    WHERE status = 'sold'
");

if ($result) {
    $sold = (int)$result->fetch_assoc()['n'];
}


/* PENDING */

$result = $conn->query("
    SELECT COUNT(*) AS n
    FROM products
    WHERE status = 'pending'
");

if ($result) {
    $pending = (int)$result->fetch_assoc()['n'];
}


/* REJECTED */

$result = $conn->query("
    SELECT COUNT(*) AS n
    FROM products
    WHERE status = 'rejected'
");

if ($result) {
    $rejected = (int)$result->fetch_assoc()['n'];
}


/* ORDERS */

$result = $conn->query("
    SELECT COUNT(*) AS n
    FROM orders
");

if ($result) {
    $totalOrders = (int)$result->fetch_assoc()['n'];
}


/* WAITING ORDERS */

$result = $conn->query("
    SELECT COUNT(*) AS n
    FROM orders
    WHERE status = 'รอตรวจสอบ'
");

if ($result) {
    $waitingOrders = (int)$result->fetch_assoc()['n'];
}


/* MESSAGES */

$result = $conn->query("
    SELECT COUNT(*) AS n
    FROM messages
");

if ($result) {
    $messages = (int)$result->fetch_assoc()['n'];
}


/* =====================================================
   DASHBOARD DATA
===================================================== */

$recentProducts = $conn->query("
    SELECT
        p.id,
        p.name,
        p.price,
        p.status,
        p.created_at,
        u.name AS seller
    FROM products p
    JOIN users u
        ON p.user_id = u.id
    ORDER BY p.created_at DESC
    LIMIT 8
");


$pendingProducts = $conn->query("
    SELECT
        p.id,
        p.name,
        p.price,
        p.status,
        p.created_at,
        u.name AS seller,
        c.name AS category_name
    FROM products p
    JOIN users u
        ON p.user_id = u.id
    LEFT JOIN categories c
        ON p.category_id = c.id
    WHERE p.status = 'pending'
    ORDER BY p.created_at DESC
");


/* =====================================================
   USERS
===================================================== */

$users = null;

if ($tab === 'users') {

    $users = $conn->query("
        SELECT
            id,
            name,
            email,
            role
        FROM users
        ORDER BY id DESC
    ");
}


/* =====================================================
   PRODUCTS
===================================================== */

$allProducts = null;

if ($tab === 'products') {

    $allProducts = $conn->query("
        SELECT
            p.id,
            p.name,
            p.price,
            p.status,
            p.image,
            p.created_at,
            u.name AS seller,
            c.name AS category_name
        FROM products p
        JOIN users u
            ON p.user_id = u.id
        LEFT JOIN categories c
            ON p.category_id = c.id
        ORDER BY p.created_at DESC
    ");
}


/* =====================================================
   CATEGORIES
===================================================== */

$categories = null;

if ($tab === 'categories') {

    $categories = $conn->query("
        SELECT
            c.id,
            c.name,
            COUNT(p.id) AS product_count
        FROM categories c
        LEFT JOIN products p
            ON p.category_id = c.id
        GROUP BY c.id, c.name
        ORDER BY c.name ASC
    ");
}


/* =====================================================
   ORDERS
===================================================== */

$orders = null;

if ($tab === 'orders') {

    /*
     * ดึงชื่อผู้ซื้อ + ผู้ขาย
     */

    $orders = $conn->query("
        SELECT
            o.*,

            p.name AS product_name,
            p.image AS product_image,
            p.user_id AS seller_id,

            buyer.name AS buyer_user_name,
            buyer.email AS buyer_email,

            seller.name AS seller_name,
            seller.email AS seller_email

        FROM orders o

        LEFT JOIN products p
            ON o.product_id = p.id

        LEFT JOIN users buyer
            ON o.buyer_id = buyer.id

        LEFT JOIN users seller
            ON p.user_id = seller.id

        ORDER BY o.created_at DESC
    ");
}


/* =====================================================
   ORDER DETAIL
===================================================== */

$orderDetail = null;

if ($tab === 'orders' && $view_order > 0) {

    $stmt = $conn->prepare("
        SELECT
            o.*,

            p.name AS product_name,
            p.image AS product_image,
            p.description AS product_description,

            buyer.id AS buyer_user_id,
            buyer.name AS buyer_user_name,
            buyer.email AS buyer_email,

            seller.id AS seller_user_id,
            seller.name AS seller_name,
            seller.email AS seller_email

        FROM orders o

        LEFT JOIN products p
            ON o.product_id = p.id

        LEFT JOIN users buyer
            ON o.buyer_id = buyer.id

        LEFT JOIN users seller
            ON p.user_id = seller.id

        WHERE o.id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $view_order);
    $stmt->execute();

    $orderDetail =
        $stmt->get_result()->fetch_assoc();
}


/* =====================================================
   MESSAGES
===================================================== */

$recentMessages = null;

if ($tab === 'messages') {

    $recentMessages = $conn->query("
        SELECT
            m.*,
            s.name AS sender_name,
            r.name AS receiver_name,
            p.name AS product_name
        FROM messages m
        LEFT JOIN users s
            ON m.sender_id = s.id
        LEFT JOIN users r
            ON m.receiver_id = r.id
        LEFT JOIN products p
            ON m.product_id = p.id
        ORDER BY m.created_at DESC
        LIMIT 100
    ");
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
Admin Dashboard - PD Shop
</title>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>

<style>

* {
    box-sizing:border-box;
}

body {
    margin:0;
    background:#f4f6f9;
    color:#1f2937;
}


/* SIDEBAR */

.sidebar {
    position:fixed;
    left:0;
    top:0;
    bottom:0;
    width:260px;
    background:#111827;
    color:white;
    padding:25px 15px;
    z-index:1000;
}

.logo {
    font-size:22px;
    font-weight:bold;
    padding:10px 15px 25px;
}

.admin-box {
    background:#1f2937;
    border-radius:15px;
    padding:15px;
    margin-bottom:20px;
}

.admin-box small {
    color:#9ca3af;
}

.menu-title {
    color:#6b7280;
    font-size:12px;
    font-weight:bold;
    padding:10px 15px;
}

.menu-link {
    display:flex;
    align-items:center;
    gap:12px;
    color:#d1d5db;
    text-decoration:none;
    padding:12px 15px;
    border-radius:12px;
    margin-bottom:5px;
    transition:.2s;
}

.menu-link:hover {
    background:#1f2937;
    color:white;
}

.menu-link.active {
    background:white;
    color:#111827;
    font-weight:bold;
}

.menu-icon {
    width:25px;
    text-align:center;
}


/* MAIN */

.main {
    margin-left:260px;
    min-height:100vh;
    padding:30px;
}


/* TOP */

.topbar {
    background:white;
    border-radius:18px;
    padding:20px 25px;
    margin-bottom:25px;
    box-shadow:0 3px 15px rgba(0,0,0,.05);
}


/* STAT */

.stat-card {
    border:0;
    border-radius:18px;
    background:white;
    padding:22px;
    height:100%;
    box-shadow:0 3px 15px rgba(0,0,0,.05);
}

.stat-icon {
    width:50px;
    height:50px;
    border-radius:14px;
    background:#f3f4f6;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:25px;
}

.stat-number {
    font-size:30px;
    font-weight:bold;
}


/* PANEL */

.panel {
    background:white;
    border-radius:18px;
    padding:25px;
    box-shadow:0 3px 15px rgba(0,0,0,.05);
}


/* TABLE */

.table {
    vertical-align:middle;
}

.table img {
    width:50px;
    height:50px;
    object-fit:cover;
    border-radius:10px;
}


/* BADGE */

.status {
    padding:7px 12px;
    border-radius:20px;
    font-size:12px;
    font-weight:bold;
}

.status-approved {
    background:#dcfce7;
    color:#166534;
}

.status-pending {
    background:#fef3c7;
    color:#92400e;
}

.status-sold {
    background:#fee2e2;
    color:#991b1b;
}

.status-rejected {
    background:#e5e7eb;
    color:#374151;
}


/* ORDER */

.order-detail {
    border-radius:16px;
    background:#f8f9fa;
    padding:20px;
}

.person-box {
    background:white;
    border:1px solid #eee;
    border-radius:15px;
    padding:18px;
    height:100%;
}

.person-icon {
    width:45px;
    height:45px;
    border-radius:50%;
    background:#f1f3f5;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:22px;
}

.slip-box {
    background:#f8f9fa;
    border-radius:15px;
    padding:15px;
    text-align:center;
}

.slip-image {
    max-width:100%;
    max-height:500px;
    object-fit:contain;
    border-radius:12px;
}


/* RESPONSIVE */

@media(max-width:900px) {

    .sidebar {
        position:relative;
        width:100%;
        min-height:auto;
    }

    .main {
        margin-left:0;
    }

}

</style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<aside class="sidebar">

<div class="logo">
🛒 PD Shop
</div>


<div class="admin-box">

<div class="fw-bold">
👑 ผู้ดูแลระบบ
</div>

<small>
<?= e($_SESSION['user']['name']) ?>
</small>

</div>


<div class="menu-title">
จัดการระบบ
</div>


<a
    href="admin.php?tab=dashboard"
    class="menu-link <?= $tab === 'dashboard' ? 'active' : '' ?>"
>
<span class="menu-icon">📊</span>
Dashboard
</a>


<a
    href="admin.php?tab=users"
    class="menu-link <?= $tab === 'users' ? 'active' : '' ?>"
>
<span class="menu-icon">👥</span>
จัดการสมาชิก
</a>


<a
    href="admin.php?tab=products"
    class="menu-link <?= $tab === 'products' ? 'active' : '' ?>"
>
<span class="menu-icon">📦</span>
จัดการสินค้า

<?php if ($pending > 0): ?>

<span class="badge bg-danger ms-auto">
<?= $pending ?>
</span>

<?php endif; ?>

</a>


<a
    href="admin.php?tab=categories"
    class="menu-link <?= $tab === 'categories' ? 'active' : '' ?>"
>
<span class="menu-icon">🏷️</span>
หมวดหมู่สินค้า
</a>


<a
    href="admin.php?tab=orders"
    class="menu-link <?= $tab === 'orders' ? 'active' : '' ?>"
>
<span class="menu-icon">🛒</span>
จัดการออเดอร์

<?php if ($waitingOrders > 0): ?>

<span class="badge bg-danger ms-auto">
<?= $waitingOrders ?>
</span>

<?php endif; ?>

</a>


<a
    href="admin.php?tab=messages"
    class="menu-link <?= $tab === 'messages' ? 'active' : '' ?>"
>
<span class="menu-icon">💬</span>
ข้อความ
</a>


<div class="menu-title mt-3">
เว็บไซต์
</div>


<a
    href="index.php"
    class="menu-link"
>
<span class="menu-icon">🏠</span>
หน้าเว็บไซต์
</a>


<a
    href="logout.php"
    class="menu-link"
>
<span class="menu-icon">🚪</span>
ออกจากระบบ
</a>

</aside>



<!-- =====================================================
     MAIN
===================================================== -->

<main class="main">


<div class="topbar">

<div class="d-flex justify-content-between align-items-center">

<div>

<h3 class="fw-bold mb-1">

<?php

if ($tab === 'dashboard') echo "📊 Dashboard";
elseif ($tab === 'users') echo "👥 จัดการสมาชิก";
elseif ($tab === 'products') echo "📦 จัดการสินค้า";
elseif ($tab === 'categories') echo "🏷️ หมวดหมู่สินค้า";
elseif ($tab === 'orders') echo "🛒 จัดการออเดอร์";
elseif ($tab === 'messages') echo "💬 ข้อความ";

?>

</h3>

<small class="text-secondary">
ระบบจัดการ PD Shop
</small>

</div>


<div>

<a
    href="index.php"
    class="btn btn-outline-dark"
>
🏠 ดูเว็บไซต์
</a>

</div>

</div>

</div>



<!-- =====================================================
     DASHBOARD
===================================================== -->

<?php if ($tab === 'dashboard'): ?>


<div class="row g-4 mb-4">


<div class="col-lg-3 col-md-6">

<div class="stat-card">

<div class="d-flex justify-content-between">

<div>

<small class="text-secondary">
สมาชิก
</small>

<div class="stat-number">
<?= $totalUsers ?>
</div>

</div>

<div class="stat-icon">
👥
</div>

</div>

</div>

</div>


<div class="col-lg-3 col-md-6">

<div class="stat-card">

<div class="d-flex justify-content-between">

<div>

<small class="text-secondary">
สินค้าทั้งหมด
</small>

<div class="stat-number">
<?= $totalProducts ?>
</div>

</div>

<div class="stat-icon">
📦
</div>

</div>

</div>

</div>


<div class="col-lg-3 col-md-6">

<div class="stat-card">

<div class="d-flex justify-content-between">

<div>

<small class="text-secondary">
รอตรวจสอบ
</small>

<div class="stat-number text-warning">
<?= $pending ?>
</div>

</div>

<div class="stat-icon">
⏳
</div>

</div>

</div>

</div>


<div class="col-lg-3 col-md-6">

<div class="stat-card">

<div class="d-flex justify-content-between">

<div>

<small class="text-secondary">
ขายแล้ว
</small>

<div class="stat-number text-danger">
<?= $sold ?>
</div>

</div>

<div class="stat-icon">
💰
</div>

</div>

</div>

</div>


</div>



<div class="row g-4">


<div class="col-lg-8">

<div class="panel">

<div class="d-flex justify-content-between mb-3">

<h5 class="fw-bold">
⏳ สินค้ารอตรวจสอบ
</h5>

<a
    href="admin.php?tab=products"
    class="btn btn-sm btn-outline-dark"
>
ดูทั้งหมด
</a>

</div>


<div class="table-responsive">

<table class="table">

<thead>

<tr>

<th>สินค้า</th>
<th>ผู้ขาย</th>
<th>ราคา</th>
<th>จัดการ</th>

</tr>

</thead>

<tbody>

<?php if ($pendingProducts && $pendingProducts->num_rows > 0): ?>

<?php while ($p = $pendingProducts->fetch_assoc()): ?>

<tr>

<td>

<div class="fw-bold">
<?= e($p['name']) ?>
</div>

<small class="text-secondary">
<?= e($p['category_name'] ?? 'ไม่ระบุหมวดหมู่') ?>
</small>

</td>


<td>
<?= e($p['seller']) ?>
</td>


<td>
฿<?= number_format($p['price'],2) ?>
</td>


<td>

<a
    href="?approve_product=<?= $p['id'] ?>"
    class="btn btn-success btn-sm"
>
✓
</a>

<a
    href="?reject_product=<?= $p['id'] ?>"
    class="btn btn-danger btn-sm"
>
✕
</a>

</td>

</tr>

<?php endwhile; ?>

<?php else: ?>

<tr>

<td
    colspan="4"
    class="text-center text-secondary py-4"
>
ไม่มีสินค้ารอตรวจสอบ
</td>

</tr>

<?php endif; ?>

</tbody>

</table>

</div>

</div>

</div>


<div class="col-lg-4">

<div class="panel">

<h5 class="fw-bold mb-4">
📈 สรุประบบ
</h5>


<div class="d-flex justify-content-between mb-3">
<span>🟢 กำลังขาย</span>
<strong><?= $approved ?></strong>
</div>


<div class="d-flex justify-content-between mb-3">
<span>🔴 ขายแล้ว</span>
<strong><?= $sold ?></strong>
</div>


<div class="d-flex justify-content-between mb-3">
<span>⏳ รอตรวจสอบ</span>
<strong><?= $pending ?></strong>
</div>


<div class="d-flex justify-content-between mb-3">
<span>⚫ ไม่อนุมัติ</span>
<strong><?= $rejected ?></strong>
</div>


<hr>


<div class="d-flex justify-content-between mb-3">
<span>🛒 ออเดอร์ทั้งหมด</span>
<strong><?= $totalOrders ?></strong>
</div>


<div class="d-flex justify-content-between mb-3">
<span>💬 ข้อความ</span>
<strong><?= $messages ?></strong>
</div>


</div>

</div>


</div>


<?php endif; ?>



<!-- =====================================================
     USERS
===================================================== -->

<?php if ($tab === 'users'): ?>

<div class="panel">

<h5 class="fw-bold mb-4">
👥 สมาชิกทั้งหมด
</h5>


<div class="table-responsive">

<table class="table table-hover">

<thead>

<tr>
<th>ID</th>
<th>ชื่อ</th>
<th>Email</th>
<th>สิทธิ์</th>
</tr>

</thead>

<tbody>

<?php if ($users): ?>

<?php while ($u = $users->fetch_assoc()): ?>

<tr>

<td>
#<?= $u['id'] ?>
</td>

<td class="fw-bold">
<?= e($u['name']) ?>
</td>

<td>
<?= e($u['email']) ?>
</td>

<td>

<?php if ($u['role'] === 'admin'): ?>

<span class="badge bg-dark">
Admin
</span>

<?php else: ?>

<span class="badge bg-secondary">
สมาชิก
</span>

<?php endif; ?>

</td>

</tr>

<?php endwhile; ?>

<?php endif; ?>

</tbody>

</table>

</div>

</div>

<?php endif; ?>



<!-- =====================================================
     PRODUCTS
===================================================== -->

<?php if ($tab === 'products'): ?>

<div class="panel">

<h5 class="fw-bold mb-4">
📦 สินค้าทั้งหมด
</h5>


<div class="table-responsive">

<table class="table table-hover">

<thead>

<tr>

<th>สินค้า</th>
<th>ผู้ขาย</th>
<th>หมวดหมู่</th>
<th>ราคา</th>
<th>สถานะ</th>
<th>จัดการ</th>

</tr>

</thead>

<tbody>

<?php if ($allProducts): ?>

<?php while ($p = $allProducts->fetch_assoc()): ?>

<tr>

<td>

<div class="d-flex align-items-center gap-2">

<?php if (!empty($p['image'])): ?>

<img src="<?= e($p['image']) ?>">

<?php else: ?>

<div
    class="bg-secondary-subtle rounded"
    style="width:50px;height:50px;display:flex;align-items:center;justify-content:center"
>
📦
</div>

<?php endif; ?>


<div>

<div class="fw-bold">
<?= e($p['name']) ?>
</div>

<small class="text-secondary">
#<?= $p['id'] ?>
</small>

</div>

</div>

</td>


<td>
<?= e($p['seller']) ?>
</td>


<td>
<?= e($p['category_name'] ?? '-') ?>
</td>


<td>
฿<?= number_format($p['price'],2) ?>
</td>


<td>

<?php

$statusClass = 'status-pending';

if ($p['status'] === 'approved') {
    $statusClass = 'status-approved';
}

if ($p['status'] === 'sold') {
    $statusClass = 'status-sold';
}

if ($p['status'] === 'rejected') {
    $statusClass = 'status-rejected';
}

$statusText = [

    'pending' => 'รอตรวจสอบ',

    'approved' => 'กำลังขาย',

    'sold' => 'ขายแล้ว',

    'rejected' => 'ไม่อนุมัติ'

][$p['status']] ?? $p['status'];

?>

<span class="status <?= $statusClass ?>">
<?= $statusText ?>
</span>

</td>


<td>

<?php if ($p['status'] === 'pending'): ?>

<a
    href="?approve_product=<?= $p['id'] ?>"
    class="btn btn-success btn-sm"
>
อนุมัติ
</a>

<a
    href="?reject_product=<?= $p['id'] ?>"
    class="btn btn-warning btn-sm"
>
ปฏิเสธ
</a>

<?php endif; ?>


<a
    href="?delete_product=<?= $p['id'] ?>"
    class="btn btn-outline-danger btn-sm"
    onclick="return confirm('ต้องการลบสินค้านี้หรือไม่?')"
>
ลบ
</a>

</td>

</tr>

<?php endwhile; ?>

<?php endif; ?>

</tbody>

</table>

</div>

</div>

<?php endif; ?>



<!-- =====================================================
     CATEGORIES
===================================================== -->

<?php if ($tab === 'categories'): ?>

<div class="panel">

<h5 class="fw-bold mb-4">
🏷️ หมวดหมู่สินค้า
</h5>


<div class="table-responsive">

<table class="table table-hover">

<thead>

<tr>

<th>ID</th>
<th>ชื่อหมวดหมู่</th>
<th>จำนวนสินค้า</th>

</tr>

</thead>

<tbody>

<?php if ($categories): ?>

<?php while ($c = $categories->fetch_assoc()): ?>

<tr>

<td>
#<?= $c['id'] ?>
</td>

<td class="fw-bold">
🏷️ <?= e($c['name']) ?>
</td>

<td>

<span class="badge bg-dark">
<?= $c['product_count'] ?> สินค้า
</span>

</td>

</tr>

<?php endwhile; ?>

<?php endif; ?>

</tbody>

</table>

</div>

</div>

<?php endif; ?>



<!-- =====================================================
     ORDERS
===================================================== -->

<?php if ($tab === 'orders'): ?>


<?php if ($orderDetail): ?>

<!-- =====================================================
     ORDER DETAIL
===================================================== -->

<div class="panel mb-4">

<div class="d-flex justify-content-between align-items-center mb-4">

<div>

<h5 class="fw-bold mb-1">
🧾 รายละเอียดออเดอร์ #<?= $orderDetail['id'] ?>
</h5>

<small class="text-secondary">
<?= e($orderDetail['created_at']) ?>
</small>

</div>


<a
    href="admin.php?tab=orders"
    class="btn btn-outline-secondary"
>
← กลับรายการออเดอร์
</a>

</div>


<!-- PRODUCT -->

<div class="order-detail mb-4">

<div class="row g-3 align-items-center">

<div class="col-md-2">

<?php if (!empty($orderDetail['product_image'])): ?>

<img
    src="<?= e($orderDetail['product_image']) ?>"
    class="img-fluid rounded"
    style="max-height:130px;object-fit:cover"
>

<?php else: ?>

<div
    class="bg-secondary-subtle rounded d-flex align-items-center justify-content-center"
    style="height:120px;font-size:50px"
>
📦
</div>

<?php endif; ?>

</div>


<div class="col-md-7">

<h5 class="fw-bold">
<?= e($orderDetail['product_name'] ?? 'ไม่พบสินค้า') ?>
</h5>

<div class="text-danger fw-bold fs-5">
฿<?= number_format($orderDetail['price'],2) ?>
</div>

</div>


<div class="col-md-3 text-md-end">

<div class="mb-2">

<strong>
สถานะออเดอร์
</strong>

</div>

<span class="badge bg-secondary fs-6">
<?= e($orderDetail['status']) ?>
</span>

</div>

</div>

</div>



<!-- BUYER / SELLER -->

<div class="row g-4 mb-4">


<div class="col-md-6">

<div class="person-box">

<div class="d-flex align-items-center gap-3 mb-3">

<div class="person-icon">
👤
</div>

<div>

<div class="text-secondary small">
ผู้ซื้อ
</div>

<div class="fw-bold fs-5">
<?= e($orderDetail['buyer_user_name'] ?? $orderDetail['buyer_name'] ?? '-') ?>
</div>

</div>

</div>


<hr>


<div class="mb-2">
<strong>ชื่อผู้ซื้อ:</strong>
<?= e($orderDetail['buyer_name'] ?? '-') ?>
</div>


<div class="mb-2">
<strong>Email:</strong>
<?= e($orderDetail['buyer_email'] ?? '-') ?>
</div>


<div class="mb-2">
<strong>เบอร์โทร:</strong>
<?= e($orderDetail['phone'] ?? '-') ?>
</div>


<div>
<strong>ที่อยู่:</strong><br>
<?= nl2br(e($orderDetail['address'] ?? '-')) ?>
</div>

</div>

</div>



<div class="col-md-6">

<div class="person-box">

<div class="d-flex align-items-center gap-3 mb-3">

<div class="person-icon">
🏪
</div>

<div>

<div class="text-secondary small">
ผู้ขาย
</div>

<div class="fw-bold fs-5">
<?= e($orderDetail['seller_name'] ?? '-') ?>
</div>

</div>

</div>


<hr>


<div class="mb-2">
<strong>ชื่อผู้ขาย:</strong>
<?= e($orderDetail['seller_name'] ?? '-') ?>
</div>


<div class="mb-2">
<strong>Email:</strong>
<?= e($orderDetail['seller_email'] ?? '-') ?>
</div>


<div>
<strong>Seller ID:</strong>
#<?= e($orderDetail['seller_user_id'] ?? '-') ?>
</div>

</div>

</div>

</div>



<!-- PAYMENT -->

<div class="row g-4">


<div class="col-md-6">

<div class="panel border">

<h5 class="fw-bold mb-3">
💳 การชำระเงิน
</h5>


<div class="mb-3">

<strong>
สถานะการชำระเงิน:
</strong>

<?php

$paymentStatus =
    $orderDetail['payment_status'] ?? 'pending';

if ($paymentStatus === 'paid'):

?>

<span class="badge bg-success ms-2">
ชำระเงินแล้ว
</span>

<?php elseif ($paymentStatus === 'rejected'): ?>

<span class="badge bg-danger ms-2">
ไม่ผ่านการตรวจสอบ
</span>

<?php else: ?>

<span class="badge bg-warning text-dark ms-2">
รอตรวจสอบ
</span>

<?php endif; ?>

</div>


<div class="fs-4 fw-bold text-danger mb-4">

฿<?= number_format(
    $orderDetail['price'],
    2
) ?>

</div>


<?php if ($paymentStatus !== 'paid'): ?>

<a
    href="?approve_payment=<?= $orderDetail['id'] ?>"
    class="btn btn-success"
    onclick="return confirm('ยืนยันว่าตรวจสอบสลิปแล้วและถูกต้องใช่หรือไม่?')"
>
✓ ยืนยันการชำระเงิน
</a>

<?php endif; ?>


<?php if ($paymentStatus !== 'rejected'): ?>

<a
    href="?reject_payment=<?= $orderDetail['id'] ?>"
    class="btn btn-outline-danger"
    onclick="return confirm('ต้องการปฏิเสธการชำระเงินรายการนี้หรือไม่?')"
>
✕ ปฏิเสธ
</a>

<?php endif; ?>


</div>

</div>



<div class="col-md-6">

<div class="slip-box">

<h5 class="fw-bold mb-3">
🧾 หลักฐานการโอนเงิน
</h5>


<?php if (!empty($orderDetail['payment_slip'])): ?>

<img
    src="<?= e($orderDetail['payment_slip']) ?>"
    class="slip-image"
    alt="สลิปการโอนเงิน"
>


<div class="mt-3">

<a
    href="<?= e($orderDetail['payment_slip']) ?>"
    target="_blank"
    class="btn btn-outline-dark"
>
🔍 เปิดสลิปขนาดเต็ม
</a>

</div>

<?php else: ?>

<div class="text-secondary py-5">

<div style="font-size:50px">
📄
</div>

<div class="mt-2">
ยังไม่มีการแนบสลิป
</div>

</div>

<?php endif; ?>

</div>

</div>


</div>



<!-- SHIPPING -->

<div class="panel border mt-4">

<h5 class="fw-bold mb-3">
🚚 ข้อมูลการจัดส่ง
</h5>


<div class="row g-3">

<div class="col-md-6">

<strong>
บริษัทขนส่ง
</strong>

<div>
<?= e($orderDetail['shipping_company'] ?? '-') ?>
</div>

</div>


<div class="col-md-6">

<strong>
เลขพัสดุ
</strong>

<div>
<?= e($orderDetail['tracking_number'] ?? '-') ?>
</div>

</div>


</div>

</div>


</div>


<?php else: ?>


<!-- =====================================================
     ORDER LIST
===================================================== -->

<div class="panel">

<div class="d-flex justify-content-between align-items-center mb-4">

<h5 class="fw-bold mb-0">
🛒 ออเดอร์ทั้งหมด
</h5>

<span class="badge bg-dark">
<?= $totalOrders ?> ออเดอร์
</span>

</div>


<div class="table-responsive">

<table class="table table-hover">

<thead>

<tr>

<th>ID</th>
<th>สินค้า</th>
<th>ผู้ซื้อ</th>
<th>ผู้ขาย</th>
<th>ราคา</th>
<th>สถานะ</th>
<th>การชำระเงิน</th>
<th>วันที่</th>
<th>ดู</th>

</tr>

</thead>


<tbody>

<?php if ($orders && $orders->num_rows > 0): ?>

<?php while ($o = $orders->fetch_assoc()): ?>

<tr>

<td>
<strong>
#<?= $o['id'] ?>
</strong>
</td>


<td>

<div class="d-flex align-items-center gap-2">

<?php if (!empty($o['product_image'])): ?>

<img
    src="<?= e($o['product_image']) ?>"
    style="width:50px;height:50px;object-fit:cover;border-radius:10px"
>

<?php else: ?>

<div
    class="bg-secondary-subtle rounded"
    style="width:50px;height:50px;display:flex;align-items:center;justify-content:center"
>
📦
</div>

<?php endif; ?>


<div>

<div class="fw-bold">
<?= e($o['product_name'] ?? 'ไม่พบสินค้า') ?>
</div>

<small class="text-secondary">
สินค้า #<?= e($o['product_id']) ?>
</small>

</div>

</div>

</td>


<td>

<div class="fw-bold">
👤 <?= e($o['buyer_user_name'] ?? $o['buyer_name'] ?? '-') ?>
</div>

<small class="text-secondary">
<?= e($o['phone'] ?? '-') ?>
</small>

</td>


<td>

<div class="fw-bold">
🏪 <?= e($o['seller_name'] ?? '-') ?>
</div>

<small class="text-secondary">
ID #<?= e($o['seller_id'] ?? '-') ?>
</small>

</td>


<td>

<strong>
฿<?= number_format($o['price'],2) ?>
</strong>

</td>


<td>

<span class="badge bg-secondary">
<?= e($o['status']) ?>
</span>

</td>


<td>

<?php

$paymentStatus =
    $o['payment_status'] ?? 'pending';

if ($paymentStatus === 'paid'):

?>

<span class="badge bg-success">
✓ ชำระแล้ว
</span>

<?php elseif ($paymentStatus === 'rejected'): ?>

<span class="badge bg-danger">
✕ ไม่ผ่าน
</span>

<?php else: ?>

<span class="badge bg-warning text-dark">
⏳ รอตรวจสอบ
</span>

<?php endif; ?>

</td>


<td>
<small>
<?= e($o['created_at']) ?>
</small>
</td>


<td>

<a
    href="admin.php?tab=orders&view_order=<?= $o['id'] ?>"
    class="btn btn-dark btn-sm"
>
👁️ ดูออเดอร์
</a>

</td>

</tr>

<?php endwhile; ?>

<?php else: ?>

<tr>

<td
    colspan="9"
    class="text-center text-secondary py-5"
>

<div style="font-size:50px">
🛒
</div>

<div class="mt-2">
ยังไม่มีออเดอร์
</div>

</td>

</tr>

<?php endif; ?>

</tbody>

</table>

</div>

</div>

<?php endif; ?>

<?php endif; ?>



<!-- =====================================================
     MESSAGES
===================================================== -->

<?php if ($tab === 'messages'): ?>

<div class="panel">

<h5 class="fw-bold mb-4">
💬 ข้อความล่าสุด
</h5>


<div class="table-responsive">

<table class="table table-hover">

<thead>

<tr>

<th>ผู้ส่ง</th>
<th>ผู้รับ</th>
<th>สินค้า</th>
<th>ข้อความ</th>
<th>อ่านแล้ว</th>
<th>วันที่</th>

</tr>

</thead>

<tbody>

<?php if ($recentMessages): ?>

<?php while ($m = $recentMessages->fetch_assoc()): ?>

<tr>

<td>
<?= e($m['sender_name'] ?? '-') ?>
</td>

<td>
<?= e($m['receiver_name'] ?? '-') ?>
</td>

<td>
<?= e($m['product_name'] ?? '-') ?>
</td>

<td style="max-width:300px">

<?= e($m['message']) ?>

</td>

<td>

<?php if ((int)$m['is_read'] === 1): ?>

<span class="badge bg-success">
อ่านแล้ว
</span>

<?php else: ?>

<span class="badge bg-warning text-dark">
ยังไม่อ่าน
</span>

<?php endif; ?>

</td>

<td>
<?= e($m['created_at']) ?>
</td>

</tr>

<?php endwhile; ?>

<?php endif; ?>

</tbody>

</table>

</div>

</div>

<?php endif; ?>


</main>


</body>

</html>