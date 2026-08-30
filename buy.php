<?php
require 'config.php';


// ตรวจสอบว่ามี ID สินค้าหรือไม่
$product_id = (int)($_GET['id'] ?? 0);

if ($product_id <= 0) {
    die("ไม่พบสินค้าที่ต้องการซื้อ");
}


// ดึงข้อมูลสินค้า
$stmt = $conn->prepare("
    SELECT
        p.*,
        c.name AS cat
    FROM products p
    JOIN categories c
        ON p.category_id = c.id
    WHERE p.id = ?
    AND p.status = 'approved'
    LIMIT 1
");

$stmt->bind_param("i", $product_id);

$stmt->execute();

$result = $stmt->get_result();

$product = $result->fetch_assoc();


if (!$product) {
    die("ไม่พบสินค้านี้");
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
ซื้อสินค้า -PD Shop
</title>


<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<style>

body {

    background:#f5f6f8;

}


.buy-box {

    max-width:800px;

    margin:auto;

}


.product-image {

    width:100%;

    height:350px;

    object-fit:cover;

    border-radius:15px;

}


.card {

    border:0;

    border-radius:18px;

}


.price {

    color:#dc3545;

    font-size:28px;

    font-weight:bold;

}


/* =====================================================
   กล่องข้อมูลบัญชี
===================================================== */

.bank-box {

    background:#fff8e1;

    border:1px solid #ffe082;

    border-radius:14px;

    padding:20px;

}


.account-number {

    font-size:24px;

    font-weight:bold;

    letter-spacing:1px;

    color:#111827;

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


<a
    href="index.php"
    class="btn btn-outline-dark"
>

← กลับหน้าสินค้า

</a>


</div>

</nav>



<!-- =====================================================
     MAIN
===================================================== -->

<div class="container py-5">


<div class="buy-box">


<div class="card shadow-sm p-4">


<h3 class="mb-4">

🛒 ยืนยันการซื้อสินค้า

</h3>



<!-- =================================================
     รูปสินค้า
================================================= -->

<?php if (!empty($product['image'])): ?>

<img
    src="<?= htmlspecialchars($product['image']) ?>"
    class="product-image mb-4"
    alt="<?= htmlspecialchars($product['name']) ?>"
>

<?php else: ?>

<div
    class="product-image
    bg-secondary-subtle
    d-flex
    align-items-center
    justify-content-center
    fs-1
    mb-4"
>

📦

</div>

<?php endif; ?>



<!-- =================================================
     รายละเอียดสินค้า
================================================= -->

<h4>

<?= htmlspecialchars($product['name']) ?>

</h4>


<p class="text-secondary">

หมวดหมู่:
<?= htmlspecialchars($product['cat']) ?>

</p>


<div class="price mb-3">

฿<?= number_format(
    $product['price'],
    2
) ?>

</div>


<p>

<strong>สภาพสินค้า:</strong>

<?= htmlspecialchars(
    $product['item_condition']
) ?>

</p>


<hr>



<!-- =================================================
     ข้อมูลการชำระเงิน
================================================= -->

<div class="bank-box mb-4">


<h5 class="mb-3">

🏦 ช่องทางการชำระเงิน

</h5>


<p class="mb-3">

กรุณาโอนเงินตามข้อมูลบัญชีด้านล่าง

</p>



<div class="bg-white rounded p-3 border">


<div class="mb-2">

<strong>
ธนาคาร:
</strong>

กสิกรไทย

</div>



<div class="mb-2">

<strong>
ชื่อบัญชี:
</strong>

PD Shop

</div>



<div>

<strong>
เลขบัญชี:
</strong>

<div class="account-number mt-1">

123-4-56789-0

</div>

</div>


</div>



<div class="alert alert-info mt-3 mb-0">

📌 หลังโอนเงิน กรุณาเก็บหลักฐานการโอนไว้

</div>


</div>



<!-- =================================================
     ข้อมูลผู้ซื้อ
================================================= -->

<h5 class="mb-3">

📦 ข้อมูลการจัดส่ง

</h5>



<form
    method="post"
    action="buy_confirm.php"
>


<input
    type="hidden"
    name="product_id"
    value="<?= (int)$product['id'] ?>"
>



<!-- ชื่อ -->

<div class="mb-3">

<label class="form-label">

ชื่อผู้ซื้อ

</label>

<input
    type="text"
    name="buyer_name"
    class="form-control"
    required
    value="<?= isset($_SESSION['user'])
        ? htmlspecialchars($_SESSION['user']['name'])
        : ''
    ?>"
>

</div>



<!-- เบอร์โทร -->

<div class="mb-3">

<label class="form-label">

เบอร์โทรศัพท์

</label>

<input
    type="text"
    name="phone"
    class="form-control"
    placeholder="กรอกเบอร์โทรศัพท์"
    required
>

</div>



<!-- ที่อยู่ -->

<div class="mb-3">

<label class="form-label">

ที่อยู่สำหรับจัดส่ง

</label>

<textarea
    name="address"
    class="form-control"
    rows="4"
    placeholder="กรอกที่อยู่สำหรับจัดส่ง"
    required
></textarea>

</div>



<!-- =================================================
     ปุ่มยืนยัน
================================================= -->

<div class="d-grid">

<button
    type="submit"
    class="btn btn-success btn-lg"
>

🛒 ยืนยันการซื้อ

</button>

</div>


</form>


</div>


</div>


</div>


</body>

</html>