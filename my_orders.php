<?php
require 'config.php';

/* =====================================================
   ต้องล็อกอินก่อน
===================================================== */

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$buyer_id = (int)$_SESSION['user']['id'];

/* =====================================================
   อัปโหลดสลิป
===================================================== */

$message = '';
$message_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_slip'])) {

    $order_id = (int)($_POST['order_id'] ?? 0);

    if ($order_id <= 0) {
        $message = 'ไม่พบรหัสคำสั่งซื้อ';
        $message_type = 'danger';
    } elseif (!isset($_FILES['payment_slip']) || $_FILES['payment_slip']['error'] !== UPLOAD_ERR_OK) {
        $message = 'กรุณาเลือกไฟล์สลิป';
        $message_type = 'danger';
    } else {

        /* ตรวจสอบว่าออเดอร์เป็นของผู้ใช้คนนี้ */
        $stmt_check = $conn->prepare("
            SELECT id
            FROM orders
            WHERE id = ?
            AND buyer_id = ?
            LIMIT 1
        ");

        $stmt_check->bind_param(
            "ii",
            $order_id,
            $buyer_id
        );

        $stmt_check->execute();

        $check_result = $stmt_check->get_result();

        if ($check_result->num_rows === 0) {

            $message = 'ไม่พบคำสั่งซื้อนี้';
            $message_type = 'danger';

        } else {

            $file = $_FILES['payment_slip'];

            /* จำกัดขนาด 5MB */
            if ($file['size'] > 5 * 1024 * 1024) {

                $message = 'ไฟล์สลิปต้องมีขนาดไม่เกิน 5MB';
                $message_type = 'danger';

            } else {

                /* ตรวจสอบนามสกุล */
                $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];

                $ext = strtolower(
                    pathinfo(
                        $file['name'],
                        PATHINFO_EXTENSION
                    )
                );

                if (!in_array($ext, $allowed_ext, true)) {

                    $message = 'รองรับเฉพาะ JPG, PNG และ WEBP';
                    $message_type = 'danger';

                } else {

                    /* สร้างโฟลเดอร์ uploads ถ้ายังไม่มี */
                    $upload_dir = __DIR__ . '/uploads/';

                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    $new_name =
                        'slip_order_' .
                        $order_id .
                        '_' .
                        time() .
                        '.' .
                        $ext;

                    $target = $upload_dir . $new_name;

                    if (move_uploaded_file($file['tmp_name'], $target)) {

                        $slip_path = 'uploads/' . $new_name;

                        /* บันทึกสลิป */
                        $stmt_update = $conn->prepare("
                            UPDATE orders
                            SET
                                payment_slip = ?,
                                payment_method = 'โอนเงิน'
                            WHERE id = ?
                            AND buyer_id = ?
                        ");

                        $stmt_update->bind_param(
                            "sii",
                            $slip_path,
                            $order_id,
                            $buyer_id
                        );

                        if ($stmt_update->execute()) {

                            $message = 'อัปโหลดสลิปเรียบร้อยแล้ว';
                            $message_type = 'success';

                        } else {

                            $message = 'ไม่สามารถบันทึกสลิปได้';
                            $message_type = 'danger';

                        }

                        $stmt_update->close();

                    } else {

                        $message = 'ไม่สามารถอัปโหลดไฟล์ได้';
                        $message_type = 'danger';

                    }
                }
            }
        }

        $stmt_check->close();
    }
}


/* =====================================================
   ดึงรายการออเดอร์
===================================================== */

$stmt = $conn->prepare("
    SELECT
        o.*,

        /* ตาราง orders ใช้ total_price */
        o.total_price AS price,

        p.name AS product_name,
        p.image,
        p.item_condition,

        u.name AS seller_name,
        u.shop_name

    FROM orders o

    LEFT JOIN products p
        ON o.product_id = p.id

    LEFT JOIN users u
        ON o.seller_id = u.id

    WHERE o.buyer_id = ?

    ORDER BY o.created_at DESC
");

$stmt->bind_param(
    "i",
    $buyer_id
);

$stmt->execute();

$orders = $stmt->get_result();

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
ออเดอร์ของฉัน - PD Shop
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
    font-size:24px;
}

.order-card {
    border:0;
    border-radius:18px;
    overflow:hidden;
}

.product-image {
    width:100%;
    height:240px;
    object-fit:cover;
    background:#f1f3f5;
    border-radius:14px;
}

.price {
    color:#dc2626;
    font-size:24px;
    font-weight:700;
}

.status {
    border-radius:10px;
    padding:8px 12px;
    display:inline-block;
    font-weight:600;
}

.status-pending {
    background:#fff3cd;
    color:#856404;
}

.status-paid {
    background:#d1e7dd;
    color:#0f5132;
}

.status-shipping {
    background:#cfe2ff;
    color:#084298;
}

.status-completed {
    background:#d1e7dd;
    color:#0f5132;
}

.status-cancelled {
    background:#f8d7da;
    color:#842029;
}

.info-box {
    background:#f8f9fa;
    border-radius:12px;
    padding:15px;
}

.slip-preview {
    width:180px;
    max-height:220px;
    object-fit:contain;
    border-radius:10px;
    border:1px solid #ddd;
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

🛒 PD Shop </a>

<div class="d-flex flex-wrap gap-2">

<a
href="index.php"
class="btn btn-outline-secondary"

>

หน้าหลัก </a>

<a
href="messages.php"
class="btn btn-outline-dark"

>

💬 ข้อความ </a>

<a
href="my_products.php"
class="btn btn-outline-dark"

>

📦 สินค้าของฉัน </a>

<a
href="profile.php"
class="btn btn-outline-dark"

>

👤 <?= htmlspecialchars($_SESSION['user']['name']) ?> </a>

<a
href="logout.php"
class="btn btn-dark"

>

ออกจากระบบ </a>

</div>

</div>

</nav>

<!-- =====================================================
     CONTENT
===================================================== -->

<div class="container py-5">

<div class="mb-4">

<h2 class="fw-bold mb-1">
📦 ออเดอร์ของฉัน
</h2>

<p class="text-secondary mb-0">
รายการสินค้าที่คุณสั่งซื้อ
</p>

</div>

<!-- MESSAGE -->

<?php if ($message !== ''): ?>

<div class="alert alert-<?= $message_type ?> alert-dismissible fade show">

<?= htmlspecialchars($message) ?>

<button
type="button"
class="btn-close"
data-bs-dismiss="alert"

> </button>

</div>

<?php endif; ?>

<!-- =====================================================
     ไม่มีออเดอร์
===================================================== -->

<?php if ($orders->num_rows === 0): ?>

<div class="card shadow-sm border-0 rounded-4 p-5 text-center">

<div style="font-size:70px;">
📦
</div>

<h4 class="mt-3">
ยังไม่มีออเดอร์
</h4>

<p class="text-secondary">
คุณยังไม่ได้สั่งซื้อสินค้า
</p>

<a
href="index.php"
class="btn btn-dark"

>

🛒 ไปเลือกซื้อสินค้า </a>

</div>

<?php else: ?>

<!-- =====================================================
     ORDER LIST
===================================================== -->

<div class="row g-4">

<?php while ($o = $orders->fetch_assoc()): ?>

<?php

$status = $o['status'] ?? 'pending';

switch ($status) {

    case 'paid':
        $status_text = 'ชำระเงินแล้ว';
        $status_class = 'status-paid';
        break;

    case 'shipping':
        $status_text = 'กำลังจัดส่ง';
        $status_class = 'status-shipping';
        break;

    case 'completed':
        $status_text = 'จัดส่งสำเร็จ';
        $status_class = 'status-completed';
        break;

    case 'cancelled':
        $status_text = 'ยกเลิก';
        $status_class = 'status-cancelled';
        break;

    default:
        $status_text = 'รอตรวจสอบ';
        $status_class = 'status-pending';
        break;
}

?>

<div class="col-12">

<div class="card order-card shadow-sm">

<div class="card-body p-4">

<div class="row g-4">

<!-- =================================================
     PRODUCT IMAGE
================================================= -->

<div class="col-md-4">

<?php if (!empty($o['image'])): ?>

<img
src="<?= htmlspecialchars($o['image']) ?>"
class="product-image"
alt="<?= htmlspecialchars($o['product_name'] ?? 'สินค้า') ?>"

>

<?php else: ?>

<div
    class="product-image d-flex align-items-center justify-content-center"
    style="font-size:80px;"
>
📦
</div>

<?php endif; ?>

</div>

<!-- =================================================
     ORDER DETAIL
================================================= -->

<div class="col-md-8">

<h4 class="fw-bold">

<?= htmlspecialchars(
    $o['product_name'] ?? 'สินค้า'
) ?>

</h4>

<div class="price mb-2">

฿<?= number_format(
 (float)($o['price'] ?? $o['total_price'] ?? 0),
 2
) ?>

</div>

<p class="mb-2">

<strong>สภาพ:</strong>

<?= htmlspecialchars(
    $o['item_condition'] ?? '-'
) ?>

</p>

<p class="text-secondary mb-3">

📅 สั่งซื้อเมื่อ

<?= !empty($o['created_at'])
    ? date(
        'd/m/Y H:i',
        strtotime($o['created_at'])
    )
    : '-'
?>

</p>

<!-- STATUS -->

<div class="mb-3">

<strong>
สถานะ:
</strong>

<span class="status <?= $status_class ?>">

<?= htmlspecialchars($status_text) ?>

</span>

</div>

<!-- =================================================
     SELLER
================================================= -->

<div class="info-box mb-3">

<strong>
🏪 ผู้ขาย
</strong>

<br>

<?php if (!empty($o['shop_name'])): ?>

<?= htmlspecialchars($o['shop_name']) ?>

<br>

<small class="text-secondary">

ผู้ขาย:

<?= htmlspecialchars($o['seller_name'] ?? '-') ?>

</small>

<?php else: ?>

<?= htmlspecialchars($o['seller_name'] ?? '-') ?>

<?php endif; ?>

</div>

<!-- =================================================
     SHIPPING
================================================= -->

<div class="info-box mb-3">

<h6 class="fw-bold">
🚚 การจัดส่ง
</h6>

<?php if (!empty($o['shipping_company'])): ?>

<div>

บริษัทขนส่ง: <strong>

<?= htmlspecialchars($o['shipping_company']) ?>

</strong>

</div>

<?php else: ?>

<div class="text-secondary">

ยังไม่ได้ระบุบริษัทขนส่ง

</div>

<?php endif; ?>

<?php if (!empty($o['tracking_number'])): ?>

<div class="mt-2">

เลขพัสดุ: <strong>

<?= htmlspecialchars($o['tracking_number']) ?>

</strong>

</div>

<?php else: ?>

<div class="text-secondary mt-2">

⏳ รอผู้ขายแจ้งเลขพัสดุ

</div>

<?php endif; ?>

</div>

<!-- =================================================
     PAYMENT
================================================= -->

<div class="info-box">

<h6 class="fw-bold">
💳 ชำระเงิน
</h6>

<?php if (!empty($o['payment_slip'])): ?>

<div class="mb-3">

<p class="text-success mb-2">

✅ แนบสลิปแล้ว

</p>

<img
src="<?= htmlspecialchars($o['payment_slip']) ?>"
class="slip-preview"
alt="สลิปการโอนเงิน"

>

</div>

<?php else: ?>

<p class="text-secondary">

โอนเงินแล้วกรุณาแนบสลิป
เพื่อให้ผู้ขายตรวจสอบ

</p>

<form
    method="post"
    enctype="multipart/form-data"
    class="mt-3"
>

<input
type="hidden"
name="order_id"
value="<?= (int)$o['id'] ?>"

>

<div class="mb-2">

<label class="form-label fw-bold">

📷 เลือกสลิป

</label>

<input
type="file"
name="payment_slip"
class="form-control"
accept=".jpg,.jpeg,.png,.webp"
required

>

</div>

<small class="text-secondary">

รองรับ JPG, PNG, WEBP ขนาดไม่เกิน 5MB

</small>

<button
type="submit"
name="upload_slip"
class="btn btn-success mt-3"

>

📤 อัปโหลดสลิป

</button>

</form>

<?php endif; ?>

</div>

<!-- =================================================
     ADDRESS
================================================= -->

<div class="info-box mt-3">

<h6 class="fw-bold">
📍 ที่อยู่จัดส่ง
</h6>

<?= nl2br(
    htmlspecialchars(
        $o['address'] ?? '-'
    )
) ?>

<br>

<strong>
📞 เบอร์โทร:
</strong>

<?= htmlspecialchars(
    $o['phone'] ?? '-'
) ?>

</div>

<!-- =================================================
     PRODUCT BUTTON
================================================= -->

<?php if (!empty($o['product_id'])): ?>

<a
href="product.php?id=<?= (int)$o['product_id'] ?>"
class="btn btn-outline-dark mt-3"

>

👁 ดูสินค้า </a>

<?php endif; ?>

</div>

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

🛒 <strong>PD Shop</strong>

<br>

<small>
เว็บไซต์ซื้อ–ขายสินค้ามือสอง
</small>

</footer>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>

</html>
