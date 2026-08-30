<?php
require 'config.php';

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die("ไม่พบรหัสสินค้า");
}

/* =====================================================
   ดึงข้อมูลสินค้า + ผู้ขาย
===================================================== */

$stmt = $conn->prepare("
    SELECT
        p.*,
        u.id AS seller_id,
        u.name AS seller,
        u.email AS seller_email,
        u.shop_name,
        u.shop_contact,
        u.profile_image,
        c.name AS category_name
    FROM products p
    JOIN users u
        ON p.seller_id = u.id
    LEFT JOIN categories c
        ON p.category_id = c.id
    WHERE p.id = ?
    LIMIT 1
");

if (!$stmt) {
    die("SQL Error: " . htmlspecialchars($conn->error));
}

$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$p = $result->fetch_assoc();

$stmt->close();

if (!$p) {
    die("ไม่พบสินค้านี้");
}

/* =====================================================
   ตรวจสอบว่าเราเป็นเจ้าของสินค้าหรือไม่
===================================================== */

$is_owner = false;

if (isset($_SESSION['user'])) {

    $current_user_id = (int)$_SESSION['user']['id'];

    $is_owner = (
        $current_user_id === (int)$p['seller_id']
    );
}

/* =====================================================
   ตรวจสอบรายการถูกใจ
===================================================== */

$is_favorite = false;

if (isset($_SESSION['user'])) {

    $current_user_id = (int)$_SESSION['user']['id'];

    $stmt = $conn->prepare("
        SELECT id
        FROM favorites
        WHERE user_id = ?
        AND product_id = ?
        LIMIT 1
    ");

    if ($stmt) {

        $stmt->bind_param(
            "ii",
            $current_user_id,
            $id
        );

        $stmt->execute();

        $favorite_result = $stmt->get_result();

        if ($favorite_result->num_rows > 0) {
            $is_favorite = true;
        }

        $stmt->close();
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
<?= htmlspecialchars($p['name']) ?> - PD Shop
</title>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>

<style>

:root {
    --bg: #f5f6f8;
    --card: #ffffff;
    --text: #111111;
    --secondary: #6c757d;
    --border: #e5e5e5;
    --input: #ffffff;
    --nav: #ffffff;
    --seller-bg: #f8f9fa;
}

html[data-theme="dark"] {
    --bg: #101010;
    --card: #1c1c1c;
    --text: #ffffff;
    --secondary: #aaaaaa;
    --border: #333333;
    --input: #252525;
    --nav: #181818;
    --seller-bg: #252525;
}

* {
    box-sizing: border-box;
}

body {
    background: var(--bg);
    color: var(--text);
    transition: background .2s ease, color .2s ease;
}

.navbar {
    background: var(--nav) !important;
    border-bottom: 1px solid var(--border);
}

.navbar-brand {
    font-size: 24px;
    color: var(--text) !important;
}

.product-card {
    border: 0;
    border-radius: 18px;
    overflow: hidden;
    background: var(--card);
    color: var(--text);
}

.product-image {
    width: 100%;
    max-height: 500px;
    object-fit: contain;
    background: var(--seller-bg);
    border-radius: 14px;
}

.price {
    color: #dc2626;
    font-weight: 700;
}

.seller-box {
    background: var(--seller-bg);
    border-radius: 14px;
    padding: 18px;
}

.shop-name {
    font-size: 20px;
    font-weight: 700;
}

.btn-contact {
    font-size: 18px;
    padding: 12px;
}

.sold-box {
    background: #fee2e2;
    color: #b91c1c;
    border-radius: 12px;
    padding: 15px;
    text-align: center;
    font-weight: bold;
    margin-bottom: 15px;
}

.available-box {
    background: #dcfce7;
    color: #15803d;
    border-radius: 12px;
    padding: 15px;
    text-align: center;
    font-weight: bold;
    margin-bottom: 15px;
}

.theme-btn {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    border: 1px solid var(--border);
    background: var(--card);
    color: var(--text);
    cursor: pointer;
    font-size: 19px;
}

.text-secondary {
    color: var(--secondary) !important;
}

</style>

<script>

/* =====================================================
   โหลดธีม
===================================================== */

(function () {

    const theme =
        localStorage.getItem("theme") || "light";

    document.documentElement.setAttribute(
        "data-theme",
        theme
    );

})();

/* =====================================================
   เปลี่ยนธีม
===================================================== */

function toggleTheme() {

    const current =
        document.documentElement.getAttribute(
            "data-theme"
        );

    const next =
        current === "dark"
            ? "light"
            : "dark";

    document.documentElement.setAttribute(
        "data-theme",
        next
    );

    localStorage.setItem(
        "theme",
        next
    );

    updateThemeButton();
}

/* =====================================================
   ICON ธีม
===================================================== */

function updateThemeButton() {

    const theme =
        document.documentElement.getAttribute(
            "data-theme"
        );

    const button =
        document.getElementById("themeButton");

    if (!button) return;

    if (theme === "dark") {

        button.innerHTML = "☀️";
        button.title = "เปลี่ยนเป็นโหมดสว่าง";

    } else {

        button.innerHTML = "🌙";
        button.title = "เปลี่ยนเป็นโหมดมืด";

    }
}

document.addEventListener(
    "DOMContentLoaded",
    updateThemeButton
);

</script>

</head>

<body>

<!-- =====================================================
     NAVBAR
===================================================== -->

<nav class="navbar shadow-sm">

<div class="container">

<div class="d-flex align-items-center justify-content-between w-100">

<a
class="navbar-brand fw-bold text-decoration-none"
href="index.php"

>

🛒 PD Shop </a>

<div class="d-flex align-items-center">

<a
href="index.php"
class="btn btn-outline-secondary me-2"

>

หน้าหลัก </a>

<?php if (isset($_SESSION['user'])): ?>

<a
href="favorites.php"
class="btn btn-outline-danger me-2"

>

❤️ ถูกใจ </a>

<button
type="button"
id="themeButton"
class="theme-btn me-2"
onclick="toggleTheme()"

>

🌙 </button>

<a
href="logout.php"
class="btn btn-dark"

>

ออกจากระบบ </a>

<?php else: ?>

<button
type="button"
id="themeButton"
class="theme-btn me-2"
onclick="toggleTheme()"

>

🌙 </button>

<a
href="login.php"
class="btn btn-dark"

>

เข้าสู่ระบบ </a>

<?php endif; ?>

</div>

</div>

</div>

</nav>

<!-- =====================================================
     CONTENT
===================================================== -->

<div class="container py-5">

<a
href="index.php"
class="text-decoration-none text-secondary"

>

← กลับไปเลือกซื้อสินค้า </a>

<div class="card product-card shadow-sm mt-3">

<div class="card-body p-4 p-md-5">

<div class="row g-5">

<!-- =================================================
     IMAGE
================================================= -->

<div class="col-md-6">

<?php if (!empty($p['image'])): ?>

<img
src="<?= htmlspecialchars($p['image']) ?>"
class="product-image"
alt="<?= htmlspecialchars($p['name']) ?>"

>

<?php else: ?>

<div
    class="product-image d-flex align-items-center justify-content-center"
    style="height:400px;font-size:100px;"
>
📦
</div>

<?php endif; ?>

</div>

<!-- =================================================
     DETAIL
================================================= -->

<div class="col-md-6">

<!-- CATEGORY -->

<div class="mb-2">

<span class="badge bg-secondary">

<?= htmlspecialchars(
    $p['category_name'] ?? 'สินค้า'
) ?>

</span>

</div>

<!-- STATUS -->

<?php if ($p['status'] === 'sold'): ?>

<div class="sold-box">
🔴 สินค้านี้ขายแล้ว
</div>

<?php else: ?>

<div class="available-box">
🟢 สินค้านี้ยังขายอยู่
</div>

<?php endif; ?>

<!-- CONDITION -->

<div class="text-secondary mb-2">

สภาพสินค้า:

<strong>

<?= htmlspecialchars(
    $p['item_condition'] ?? '-'
) ?>

</strong>

</div>

<!-- NAME -->

<h1 class="fw-bold mb-3">

<?= htmlspecialchars(
    $p['name']
) ?>

</h1>

<!-- PRICE -->

<h2 class="price mb-4">

฿<?= number_format(
 (float)$p['price'],
 2
) ?>

</h2>

<!-- DESCRIPTION -->

<div class="mb-4">

<h5 class="fw-bold">
รายละเอียดสินค้า
</h5>

<p class="text-secondary">

<?= nl2br(
    htmlspecialchars(
        $p['description'] ?? ''
    )
) ?>

</p>

</div>

<hr>

<!-- =================================================
     SELLER
================================================= -->

<div class="seller-box mb-4">

<h5 class="fw-bold mb-3">
🏪 ข้อมูลผู้ขาย
</h5>

<!-- รูปผู้ขาย -->

<div class="text-center mb-3">

<?php if (!empty($p['profile_image'])): ?>

<img
src="<?= htmlspecialchars($p['profile_image']) ?>"
alt="รูปผู้ขาย"
style="
width:90px;
height:90px;
object-fit:cover;
border-radius:50%;
"

>

<?php else: ?>

<div
    style="
        width:90px;
        height:90px;
        border-radius:50%;
        background:#ddd;
        display:flex;
        align-items:center;
        justify-content:center;
        margin:auto;
        font-size:40px;
    "
>
👤
</div>

<?php endif; ?>

</div>

<!-- ชื่อร้าน -->

<?php if (!empty($p['shop_name'])): ?>

<div class="shop-name mb-1">

🏪

<?= htmlspecialchars(
    $p['shop_name']
) ?>

</div>

<div class="text-secondary mb-3">

ผู้ขาย:

<?= htmlspecialchars(
    $p['seller']
) ?>

</div>

<?php else: ?>

<div class="shop-name mb-3">

👤

<?= htmlspecialchars(
    $p['seller']
) ?>

</div>

<?php endif; ?>

<!-- EMAIL -->

<p class="mb-2">

<strong>📧 อีเมล:</strong>

<?= htmlspecialchars(
    $p['seller_email']
) ?>

</p>

<!-- CONTACT -->

<?php if (!empty($p['shop_contact'])): ?>

<p class="mb-3">

<strong>📞 ติดต่อเพิ่มเติม:</strong>

<br>

<?= nl2br(
    htmlspecialchars(
        $p['shop_contact']
    )
) ?>

</p>

<?php endif; ?>

<!-- PROFILE -->

<a
href="profile.php?id=<?= (int)$p['seller_id'] ?>"
class="btn btn-outline-dark w-100"

>

👤 ดูโปรไฟล์ผู้ขาย </a>

</div>

<!-- =================================================
     OWNER BUTTONS
================================================= -->

<?php if ($is_owner): ?>

<div class="alert alert-info">

<strong>
👤 นี่คือสินค้าของคุณ
</strong>

<br>

คุณสามารถแก้ไขข้อมูลสินค้าได้

</div>

<a
href="edit_product.php?id=<?= (int)$p['id'] ?>"
class="btn btn-warning btn-contact w-100 mb-2"

>

✏️ แก้ไขสินค้า </a>

<?php endif; ?>

<!-- =================================================
     BUYER BUTTONS
================================================= -->

<?php if (!$is_owner): ?>

<?php if ($p['status'] !== 'sold'): ?>

<!-- CHAT -->

<a
href="chat.php?product_id=<?= (int)$p['id'] ?>&buyer_id=<?= isset($_SESSION['user']) ? (int)$_SESSION['user']['id'] : 0 ?>"
class="btn btn-dark btn-contact w-100 mb-2"

>

💬 ติดต่อผู้ขาย </a>

<!-- FAVORITE -->

<?php if (isset($_SESSION['user'])): ?>

<?php if ($is_favorite): ?>

<a
href="favorite_toggle.php?id=<?= (int)$p['id'] ?>"
class="btn btn-danger btn-contact w-100 mb-2"

>

❤️ อยู่ในรายการถูกใจ </a>

<?php else: ?>

<a
href="favorite_toggle.php?id=<?= (int)$p['id'] ?>"
class="btn btn-outline-danger btn-contact w-100 mb-2"

>

♡ เพิ่มรายการถูกใจ </a>

<?php endif; ?>

<?php endif; ?>

<!-- BUY -->

<a
href="buy.php?id=<?= (int)$p['id'] ?>"
class="btn btn-success btn-contact w-100 mb-2"

>

🛒 ซื้อสินค้านี้ </a>

<?php endif; ?>

<?php endif; ?>

<!-- BACK -->

<a
href="index.php"
class="btn btn-outline-secondary w-100"

>

← กลับไปเลือกซื้อสินค้า </a>

</div>

</div>

</div>

</div>

</div>

<!-- =====================================================
     FOOTER
===================================================== -->

<footer
    class="text-center text-secondary py-5"
>

🛒 <strong>PD Shop</strong>

<br>

<small>
เว็บไซต์ซื้อ–ขายสินค้ามือสอง
</small>

</footer>

</body>
</html>
