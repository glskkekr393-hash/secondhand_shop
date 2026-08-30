<?php

require 'config.php';


// =====================================================
// ตรวจสอบ Login
// =====================================================

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$buyer_id = (int)$_SESSION['user']['id'];


// =====================================================
// ดึงออเดอร์ของผู้ซื้อ
// =====================================================

$stmt = $conn->prepare("
    SELECT
        o.*,
        p.name AS product_name,
        p.image AS product_image,
        p.item_condition
    FROM orders o
    JOIN products p
        ON o.product_id = p.id
    WHERE o.buyer_id = ?
    AND o.status != 'ยกเลิกแล้ว'
    ORDER BY o.created_at DESC
");

$stmt->bind_param("i", $buyer_id);
$stmt->execute();

$orders = $stmt->get_result();

?>

<!doctype html>

<html lang="th">

<head>

<meta charset="utf-8">

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>ออเดอร์ของฉัน - PD Shop</title>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>

<style>

/* =====================================================
   THEME SYSTEM
===================================================== */

:root {

    --bg: #f5f6f8;
    --card: #ffffff;
    --text: #111111;
    --secondary: #6c757d;
    --border: #e5e5e5;
    --input: #ffffff;
    --info: #f8f9fa;
    --nav: #ffffff;

}

html[data-theme="dark"] {

    --bg: #101010;
    --card: #1c1c1c;
    --text: #ffffff;
    --secondary: #aaaaaa;
    --border: #333333;
    --input: #252525;
    --info: #252525;
    --nav: #181818;

}


/* =====================================================
   BODY
===================================================== */

body {

    background: var(--bg);

    color: var(--text);

    transition:
        background-color .2s ease,
        color .2s ease;

}


/* =====================================================
   NAVBAR
===================================================== */

.navbar {

    background: var(--nav) !important;

    border-bottom:
        1px solid var(--border);

}

.navbar-brand {

    color: var(--text) !important;

}


/* =====================================================
   CARD
===================================================== */

.card {

    background: var(--card);

    color: var(--text);

    border-color: var(--border);

}


/* =====================================================
   TEXT
===================================================== */

.text-secondary {

    color: var(--secondary) !important;

}


/* =====================================================
   ORDER CARD
===================================================== */

.order-card {

    border: 0;

    border-radius: 18px;

    overflow: hidden;

}


/* =====================================================
   PRODUCT IMAGE
===================================================== */

.product-image {

    width: 100%;

    height: 220px;

    object-fit: cover;

}


.no-image {

    width: 100%;

    height: 220px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 60px;

    background: var(--info);

}


/* =====================================================
   PRICE
===================================================== */

.price {

    color: #dc3545;

    font-size: 24px;

    font-weight: bold;

}


/* =====================================================
   INFO BOX
===================================================== */

.info-box {

    background: var(--info);

    border-radius: 12px;

    padding: 15px;

}


/* =====================================================
   TRACKING
===================================================== */

.tracking-number {

    font-size: 20px;

    font-weight: bold;

    letter-spacing: 1px;

}


/* =====================================================
   SLIP
===================================================== */

.slip-preview {

    width: 100%;

    max-height: 350px;

    object-fit: contain;

    border-radius: 12px;

    background: var(--info);

}


/* =====================================================
   PAYMENT BOX
===================================================== */

.payment-box {

    background: var(--info) !important;

    color: var(--text);

}


/* =====================================================
   THEME BUTTON
===================================================== */

.theme-btn {

    width: 42px;

    height: 42px;

    border-radius: 50%;

    border: 1px solid var(--border);

    background: var(--card);

    color: var(--text);

    display: flex;

    align-items: center;

    justify-content: center;

    cursor: pointer;

    font-size: 19px;

    transition: .2s;

}


.theme-btn:hover {

    transform: scale(1.06);

}


/* =====================================================
   BUTTON BACK
===================================================== */

.btn-outline-dark {

    border-color: var(--text);

    color: var(--text);

}


.btn-outline-dark:hover {

    background: var(--text);

    color: var(--card);

}


/* =====================================================
   FORM
===================================================== */

.form-control {

    background: var(--input);

    color: var(--text);

    border-color: var(--border);

}


.form-control:focus {

    background: var(--input);

    color: var(--text);

    border-color: var(--text);

}


.form-text {

    color: var(--secondary);

}


/* =====================================================
   FOOTER
===================================================== */

footer {

    color: var(--secondary) !important;

}


/* =====================================================
   DARK MODE BOOTSTRAP FIX
===================================================== */

html[data-theme="dark"] .bg-light {

    background: var(--info) !important;

    color: var(--text) !important;

}


html[data-theme="dark"] .text-dark {

    color: var(--text) !important;

}


html[data-theme="dark"] .btn-outline-dark {

    color: var(--text);

    border-color: var(--text);

}


html[data-theme="dark"] .btn-outline-dark:hover {

    background: var(--text);

    color: #111;

}


</style>


<script>

/* =====================================================
   LOAD THEME BEFORE PAGE แสดง
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
   TOGGLE THEME
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
   UPDATE ICON
===================================================== */

function updateThemeButton() {

    const theme =
        document.documentElement.getAttribute(
            "data-theme"
        );


    const button =
        document.getElementById(
            "themeButton"
        );


    if (!button) return;


    if (theme === "dark") {

        button.innerHTML = "☀️";

        button.title =
            "เปลี่ยนเป็นโหมดสว่าง";

    } else {

        button.innerHTML = "🌙";

        button.title =
            "เปลี่ยนเป็นโหมดมืด";

    }

}


/* =====================================================
   PAGE LOAD
===================================================== */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        updateThemeButton();

    }
);

</script>

</head>


<body>


<!-- =====================================================
     NAVBAR
===================================================== -->

<nav class="navbar shadow-sm">

<div class="container">

<a
    href="index.php"
    class="navbar-brand fw-bold text-decoration-none"
>
🛒 PD Shop
</a>


<div class="d-flex align-items-center gap-2">


<!-- ปุ่มเปลี่ยนธีม -->

<button
    type="button"
    id="themeButton"
    class="theme-btn"
    onclick="toggleTheme()"
>
🌙
</button>


<a
    href="index.php"
    class="btn btn-outline-dark"
>
← กลับหน้าร้าน
</a>


</div>

</div>

</nav>


<!-- =====================================================
     MAIN
===================================================== -->

<div class="container py-5">


<h2 class="mb-2">
📦 ออเดอร์ของฉัน
</h2>


<p class="text-secondary mb-4">
รายการสินค้าที่คุณสั่งซื้อ
</p>


<!-- =====================================================
     แจ้งเตือนหลังส่งสลิป
===================================================== -->

<?php if (
    isset($_GET['slip']) &&
    $_GET['slip'] === 'success'
): ?>

<div class="alert alert-success">

✅ ส่งสลิปเรียบร้อยแล้ว

<br>

ผู้ขายได้รับแจ้งเตือนแล้ว
และจะตรวจสอบการโอนเงินของคุณ

</div>

<?php endif; ?>


<!-- =====================================================
     แจ้งเตือนหลังยกเลิก
===================================================== -->

<?php if (
    isset($_GET['cancel']) &&
    $_GET['cancel'] === 'success'
): ?>

<div class="alert alert-success">

✅ ยกเลิกการสั่งซื้อเรียบร้อยแล้ว

</div>

<?php endif; ?>


<!-- =====================================================
     ไม่มีออเดอร์
===================================================== -->

<?php if ($orders->num_rows === 0): ?>


<div class="card shadow-sm border-0 p-5 text-center">

<div style="font-size:70px">
🛒
</div>


<h4 class="mt-3">
ไม่มีรายการสั่งซื้อ
</h4>


<p class="text-secondary">
คุณยังไม่มีรายการสั่งซื้อที่กำลังดำเนินการ
</p>


<a
    href="index.php"
    class="btn btn-dark"
>
เลือกซื้อสินค้า
</a>


</div>


<?php else: ?>


<!-- =====================================================
     รายการออเดอร์
===================================================== -->

<div class="row g-4">


<?php while (
    $order = $orders->fetch_assoc()
): ?>


<div class="col-md-6 col-lg-4">


<div class="card shadow-sm order-card h-100">


<!-- =====================================================
     รูปสินค้า
===================================================== -->

<?php if (
    !empty($order['product_image'])
): ?>

<img
    src="<?= htmlspecialchars($order['product_image']) ?>"
    class="product-image"
    alt="<?= htmlspecialchars($order['product_name']) ?>"
>

<?php else: ?>

<div class="no-image">
📦
</div>

<?php endif; ?>


<div class="card-body">


<!-- =====================================================
     ข้อมูลสินค้า
===================================================== -->

<h5>
<?= htmlspecialchars($order['product_name']) ?>
</h5>


<div class="price mb-2">

฿<?= number_format(
    $order['price'],
    2
) ?>

</div>


<p>

<strong>สภาพ:</strong>

<?= htmlspecialchars(
    $order['item_condition']
) ?>

</p>


<p class="text-secondary">

📅 สั่งซื้อเมื่อ

<?= date(
    'd/m/Y H:i',
    strtotime($order['created_at'])
) ?>

</p>


<!-- =====================================================
     สถานะออเดอร์
===================================================== -->

<?php

$status = $order['status'];

$status_class = 'bg-secondary';


if ($status === 'รอตรวจสอบ') {

    $status_class =
        'bg-warning text-dark';

}
elseif ($status === 'กำลังจัดส่ง') {

    $status_class =
        'bg-info text-dark';

}
elseif ($status === 'จัดส่งแล้ว') {

    $status_class =
        'bg-primary';

}
elseif ($status === 'สำเร็จ') {

    $status_class =
        'bg-success';

}

?>


<strong>
สถานะ:
</strong>


<br>


<span
    class="badge <?= $status_class ?> mt-1"
    style="font-size:14px"
>

<?= htmlspecialchars($status) ?>

</span>


<!-- =====================================================
     การจัดส่ง
===================================================== -->

<div class="info-box mt-3">


<strong>
🚚 การจัดส่ง
</strong>


<?php if (
    !empty($order['shipping_company'])
): ?>

<div class="mt-2">

บริษัทขนส่ง:

<strong>

<?= htmlspecialchars(
    $order['shipping_company']
) ?>

</strong>

</div>

<?php else: ?>

<div class="text-secondary mt-2">

ยังไม่ได้ระบุบริษัทขนส่ง

</div>

<?php endif; ?>


<?php if (
    !empty($order['tracking_number'])
): ?>

<div class="mt-2">

เลขพัสดุ:

<div class="tracking-number">

<?= htmlspecialchars(
    $order['tracking_number']
) ?>

</div>

</div>

<?php else: ?>

<div class="text-secondary mt-2">

⏳ รอผู้ขายแจ้งเลขพัสดุ

</div>

<?php endif; ?>


</div>


<!-- =====================================================
     ระบบชำระเงิน / ส่งสลิป
===================================================== -->

<?php

$payment_slip =
    trim(
        $order['payment_slip'] ?? ''
    );

?>


<?php if (
    $payment_slip === ''
): ?>


<div class="card border-0 p-3 mt-3 payment-box">


<h6 class="fw-bold">

💳 ชำระเงิน

</h6>


<p class="text-secondary mb-3">

โอนเงินแล้วกรุณาแนบสลิป
เพื่อให้ผู้ขายตรวจสอบ

</p>


<form
    action="upload_slip.php"
    method="POST"
    enctype="multipart/form-data"
>


<input
    type="hidden"
    name="order_id"
    value="<?= (int)$order['id'] ?>"
>


<label class="form-label fw-bold">

📷 เลือกสลิป

</label>


<input
    type="file"
    name="slip"
    class="form-control"
    accept="image/jpeg,image/png,image/webp"
    required
>


<div class="form-text">

รองรับ JPG, PNG, WEBP
ขนาดไม่เกิน 5MB

</div>


<button
    type="submit"
    class="btn btn-success w-100 mt-3"
>

📤 ส่งสลิปให้ผู้ขายตรวจสอบ

</button>


</form>


</div>


<?php else: ?>


<!-- =====================================================
     ส่งสลิปแล้ว / ตรวจสอบการชำระเงิน
===================================================== -->

<?php if (
    isset($order['payment_status']) &&
    $order['payment_status'] === 'paid'
): ?>


<div class="alert alert-success mt-3 mb-0">

<strong>
✅ ตรวจสอบการชำระเงินเสร็จแล้ว
</strong>

<br>

<small>

ผู้ขายตรวจสอบสลิปและยืนยันการชำระเงินเรียบร้อยแล้ว

</small>

</div>


<?php else: ?>


<div class="alert alert-warning mt-3 mb-0">

<strong>
⏳ รอตรวจสอบการชำระเงิน
</strong>

<br>

<small>

ส่งสลิปแล้ว กรุณารอผู้ขายตรวจสอบหลักฐานการโอนเงิน

</small>

</div>


<?php endif; ?>


<!-- ดูสลิป -->

<div class="mt-3">

<a
    href="<?= htmlspecialchars($payment_slip) ?>"
    target="_blank"
    class="btn btn-outline-success w-100"
>

👁️ ดูสลิปที่ส่ง

</a>

</div>


<?php endif; ?>


<!-- =====================================================
     ปุ่มยกเลิก
===================================================== -->

<?php

$can_cancel =
    $status !== 'จัดส่งแล้ว' &&
    $status !== 'สำเร็จ' &&
    $status !== 'ยกเลิกแล้ว';

?>


<?php if ($can_cancel): ?>


<a
    href="cancel_order.php?id=<?= (int)$order['id'] ?>"
    class="btn btn-outline-danger w-100 mt-3"
    onclick="return confirm('ต้องการยกเลิกการสั่งซื้อนี้ใช่หรือไม่?');"
>

❌ ยกเลิกการสั่งซื้อ

</a>


<?php endif; ?>


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

<footer
    class="text-center text-secondary py-5"
>

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