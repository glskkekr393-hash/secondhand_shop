<?php
require 'config.php';

/* =====================================================
   ค้นหา
===================================================== */

$q = trim($_GET['q'] ?? '');
$cat = (int)($_GET['cat'] ?? 0);


/* =====================================================
   ดึงสินค้า
   แสดงเฉพาะสินค้าที่ยังขายอยู่
===================================================== */

$sql = "
    SELECT
        p.*,
        c.name AS cat
    FROM products p
    JOIN categories c
        ON p.category_id = c.id
    WHERE p.status = 'approved'
";

$params = [];
$types = "";


if ($q !== '') {

    $sql .= " AND p.name LIKE ?";

    $params[] = "%" . $q . "%";

    $types .= "s";
}


if ($cat > 0) {

    $sql .= " AND p.category_id = ?";

    $params[] = $cat;

    $types .= "i";
}


$sql .= " ORDER BY p.created_at DESC";


$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("SQL Error: " . htmlspecialchars($conn->error));
}


if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}


$stmt->execute();

$products = $stmt->get_result();


/* =====================================================
   ดึงหมวดหมู่
===================================================== */

$cats = $conn->query("
    SELECT *
    FROM categories
    ORDER BY name ASC
");


/* =====================================================
   จำนวนข้อความที่ยังไม่ได้อ่าน
===================================================== */

$unread_messages = 0;

if (isset($_SESSION['user'])) {

    $user_id = (int)$_SESSION['user']['id'];

    $check = $conn->query("
        SHOW COLUMNS
        FROM messages
        LIKE 'is_read'
    ");

    if ($check && $check->num_rows > 0) {

        $stmt = $conn->prepare("
            SELECT COUNT(*) AS total
            FROM messages
            WHERE receiver_id = ?
            AND is_read = 0
        ");

        $stmt->bind_param("i", $user_id);

        $stmt->execute();

        $unread_messages =
            (int)$stmt
            ->get_result()
            ->fetch_assoc()['total'];
    }
}


/* =====================================================
   ตรวจรายการถูกใจของผู้ใช้
===================================================== */

$user_favorites = [];

if (isset($_SESSION['user'])) {

    $user_id = (int)$_SESSION['user']['id'];

    $fav_stmt = $conn->prepare("
        SELECT product_id
        FROM favorites
        WHERE user_id = ?
    ");

    if ($fav_stmt) {

        $fav_stmt->bind_param("i", $user_id);

        $fav_stmt->execute();

        $fav_result = $fav_stmt->get_result();

        while ($fav = $fav_result->fetch_assoc()) {

            $user_favorites[(int)$fav['product_id']] = true;
        }
    }
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
SecondHand Shop
</title>


<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<style>

/* =====================================================
   BODY
===================================================== */

body {

    background:#f5f6f8;

    color:#172033;

}


/* =====================================================
   NAVBAR
===================================================== */

.navbar {

    background:white;

}


.navbar-brand {

    font-size:21px;

}


/* =====================================================
   HERO
===================================================== */

.hero {

    background:
        radial-gradient(
            circle at 85% 20%,
            rgba(255,193,7,.20),
            transparent 25%
        ),
        radial-gradient(
            circle at 55% 30%,
            rgba(25,135,84,.20),
            transparent 20%
        ),
        #111827;

    color:white;

    border-radius:28px;

    position:relative;

    overflow:hidden;

    box-shadow:0 20px 50px rgba(0,0,0,.15);

}


.hero::before {

    content:"";

    position:absolute;

    width:280px;

    height:280px;

    border-radius:50%;

    background:rgba(255,193,7,.15);

    right:-80px;

    top:-100px;

}


.hero-content {

    position:relative;

    z-index:2;

}


/* =====================================================
   SEARCH BOX
===================================================== */

.search-box {

    background:rgba(255,255,255,.12);

    border:1px solid rgba(255,255,255,.18);

    border-radius:20px;

    padding:14px;

    backdrop-filter:blur(10px);

}


/* =====================================================
   PRODUCT CARD
===================================================== */

.product-card {

    border:0;

    border-radius:20px;

    overflow:hidden;

    background:white;

    transition:
        transform .25s ease,
        box-shadow .25s ease;

}


.product-card:hover {

    transform:translateY(-8px);

    box-shadow:0 18px 40px rgba(0,0,0,.12);

}


.product-img {

    width:100%;

    height:220px;

    object-fit:cover;

    transition:transform .4s ease;

}


.product-card:hover .product-img {

    transform:scale(1.04);

}


/* =====================================================
   BUTTON
===================================================== */

.btn-buy {

    display:block;

    width:100%;

    margin-top:10px;

    padding:10px 15px;

    background:#198754;

    color:white;

    text-align:center;

    text-decoration:none;

    border-radius:10px;

    font-weight:600;

    transition:.2s;

}


.btn-buy:hover {

    background:#157347;

    color:white;

    transform:translateY(-2px);

}


/* =====================================================
   FAVORITE
===================================================== */

.btn-favorite {

    display:block;

    width:100%;

    margin-top:10px;

    padding:10px 15px;

    background:white;

    color:#dc3545;

    border:1px solid #dc3545;

    text-align:center;

    text-decoration:none;

    border-radius:10px;

    font-weight:600;

    transition:.2s;

}


.btn-favorite:hover {

    background:#dc3545;

    color:white;

    transform:translateY(-2px);

}


.btn-favorite.active {

    background:#dc3545;

    color:white;

}


/* =====================================================
   PROFILE
===================================================== */

.profile-button {

    display:inline-flex;

    align-items:center;

    gap:6px;

    text-decoration:none;

}


/* =====================================================
   CATEGORY
===================================================== */

.category-text {

    color:#6c757d;

    font-size:14px;

}


/* =====================================================
   MOBILE
===================================================== */

@media(max-width:768px) {

    .hero {

        border-radius:18px;

    }

    .hero h1 {

        font-size:38px;

    }

}

</style>

</head>


<body>


<!-- =====================================================
     NAVBAR
===================================================== -->

<nav class="navbar navbar-expand-lg shadow-sm">

<div class="container">


<a
    class="navbar-brand fw-bold"
    href="index.php"
>

🛒 SecondHand Shop

</a>


<div class="d-flex flex-wrap gap-2">


<?php if (isset($_SESSION['user'])): ?>


<!-- ข้อความ -->

<a
    class="btn btn-outline-dark chat-button"
    href="messages.php"
>

💬 ข้อความ

<?php if ($unread_messages > 0): ?>

<span class="badge bg-danger rounded-pill">

<?= $unread_messages ?>

</span>

<?php endif; ?>

</a>


<!-- ออเดอร์ของฉัน -->

<a
    class="btn btn-outline-dark"
    href="my_orders.php"
>

📦 ออเดอร์ของฉัน

</a>


<!-- ❤️ รายการถูกใจ -->

<a
    class="btn btn-outline-danger"
    href="favorites.php"
>

❤️ สินค้าที่ถูกใจ

</a>


<!-- ออเดอร์ที่ได้รับ -->

<a
    class="btn btn-outline-dark"
    href="seller_orders.php"
>

🏪 ออเดอร์ที่ได้รับ

</a>


<!-- ลงขาย -->

<a
    class="btn btn-outline-dark"
    href="sell.php"
>

ลงขายสินค้า

</a>


<!-- แจ้งเตือน -->

<a
    class="btn btn-outline-dark"
    href="notifications.php"
>

🔔

</a>


<!-- โปรไฟล์ -->

<a
    href="profile.php"
    class="btn btn-outline-dark profile-button"
>

👤

<?= htmlspecialchars($_SESSION['user']['name']) ?>

</a>


<!-- Logout -->

<a
    class="btn btn-dark"
    href="logout.php"
>

ออกจากระบบ

</a>


<?php else: ?>


<a
    class="btn btn-outline-dark"
    href="sell.php"
>

ลงขายสินค้า

</a>


<a
    class="btn btn-dark"
    href="login.php"
>

เข้าสู่ระบบ

</a>


<?php endif; ?>


</div>

</div>

</nav>



<!-- =====================================================
     MAIN
===================================================== -->

<div class="container py-4">


<!-- =====================================================
     HERO
===================================================== -->

<div class="hero p-5 mb-5">


<div class="hero-content">


<div
    class="fw-bold text-warning mb-3"
    style="letter-spacing:4px"
>

SECONDHAND MARKET

</div>


<h1
    class="display-2 fw-bold mb-3"
>

ของมือสอง

</h1>


<p class="fs-5 mb-4">

ค้นหาของดี ราคาคุ้ม จากผู้ขายจริง
ซื้อขายง่าย และปลอดภัยใน SecondHand Shop

</p>


<!-- SEARCH -->

<form
    method="get"
    class="search-box row g-2"
>


<div class="col-md-7">

<input
    name="q"
    value="<?= htmlspecialchars($q) ?>"
    class="form-control form-control-lg"
    placeholder="🔍 ค้นหาสินค้า..."
>

</div>


<div class="col-md-3">

<select
    name="cat"
    class="form-select form-select-lg"
>

<option value="0">

ทุกหมวดหมู่

</option>


<?php if ($cats): ?>

<?php while ($c = $cats->fetch_assoc()): ?>

<option
    value="<?= (int)$c['id'] ?>"
    <?= $cat == $c['id'] ? 'selected' : '' ?>
>

<?= htmlspecialchars($c['name']) ?>

</option>

<?php endwhile; ?>

<?php endif; ?>

</select>

</div>


<div class="col-md-2">

<button
    type="submit"
    class="btn btn-warning btn-lg w-100 fw-bold"
>

ค้นหา

</button>

</div>


</form>

</div>

</div>



<!-- =====================================================
     PRODUCT TITLE
===================================================== -->

<div
    class="d-flex justify-content-between align-items-center mb-3"
>

<div>

<h3 class="fw-bold mb-1">

🛍️ สินค้ามาใหม่

</h3>

<p class="text-secondary mb-0">

สินค้าที่กำลังรอคุณค้นพบ

</p>

</div>

</div>



<!-- =====================================================
     PRODUCTS
===================================================== -->

<div class="row g-4">


<?php if ($products->num_rows == 0): ?>


<div class="col-12">

<div
    class="card shadow-sm p-5 text-center"
>

<div style="font-size:60px">

📦

</div>


<h4 class="mt-3">

ไม่พบสินค้า

</h4>


<p class="text-secondary">

ลองค้นหาด้วยคำอื่น
หรือเลือกหมวดหมู่อื่น

</p>

</div>

</div>


<?php endif; ?>



<?php while ($p = $products->fetch_assoc()): ?>


<div class="col-md-4 col-lg-3">


<div class="product-card shadow-sm h-100">


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
    class="product-img
    d-flex
    align-items-center
    justify-content-center
    bg-secondary-subtle
    fs-1"
>

📦

</div>

<?php endif; ?>


<!-- =================================================
     BODY
================================================= -->

<div class="card-body">


<div class="category-text">

<?= htmlspecialchars($p['cat']) ?>

</div>


<h5 class="mt-1 fw-bold">

<?= htmlspecialchars($p['name']) ?>

</h5>


<h4 class="text-danger fw-bold">

฿<?= number_format($p['price'], 2) ?>

</h4>


<p class="mb-2">

สภาพ:

<?= htmlspecialchars($p['item_condition']) ?>

</p>



<!-- =================================================
     STATUS
================================================= -->

<div
    class="alert alert-success text-center fw-bold mb-2"
>

🟢 ยังมีสินค้า

</div>



<!-- =================================================
     DETAIL
================================================= -->

<a
    class="btn btn-dark w-100"
    href="product.php?id=<?= (int)$p['id'] ?>"
>

ดูรายละเอียด

</a>



<!-- =================================================
     FAVORITE
================================================= -->

<?php if (isset($_SESSION['user'])): ?>


<?php
$is_favorite =
    isset($user_favorites[(int)$p['id']]);
?>


<a
    class="btn-favorite <?= $is_favorite ? 'active' : '' ?>"
    href="favorite.php?id=<?= (int)$p['id'] ?>"
>

<?= $is_favorite ? '❤️ ถูกใจแล้ว' : '♡ เพิ่มในรายการถูกใจ' ?>

</a>


<?php endif; ?>



<!-- =================================================
     BUY
================================================= -->


<a
    class="btn-buy"
    href="buy.php?id=<?= (int)$p['id'] ?>"
>

🛒 ซื้อสินค้า

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

<footer
    class="text-center text-secondary py-5"
>

🛒

<strong>
SecondHand Shop
</strong>

<br>

<small>
เว็บไซต์ซื้อ–ขายสินค้ามือสอง
</small>

</footer>



</body>

</html>