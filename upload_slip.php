```php
<?php

require 'config.php';

// =====================================================
// ตรวจสอบ Login
// =====================================================

if (!isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit;
}

$buyer_id = (int)$_SESSION['user']['id'];


// =====================================================
// รับ order_id
// =====================================================

$order_id = (int)(
    $_POST['order_id']
    ?? $_GET['order_id']
    ?? 0
);

if ($order_id <= 0) {
    die("ไม่พบหมายเลขออเดอร์");
}


// =====================================================
// ดึงข้อมูลออเดอร์
// =====================================================

$stmt = $conn->prepare("
    SELECT
        o.id,
        o.buyer_id,
        o.product_id,
        o.total_price,
        o.price,
        o.status,
        o.payment_slip,
        o.payment_status,
        p.name AS product_name
    FROM orders o
    JOIN products p
        ON o.product_id = p.id
    WHERE o.id = ?
      AND o.buyer_id = ?
    LIMIT 1
");

if (!$stmt) {
    die("เกิดข้อผิดพลาด SQL: " . htmlspecialchars($conn->error));
}

$stmt->bind_param(
    "ii",
    $order_id,
    $buyer_id
);

$stmt->execute();

$order = $stmt->get_result()->fetch_assoc();

$stmt->close();


if (!$order) {
    die("ไม่พบออเดอร์ หรือออเดอร์นี้ไม่ใช่ของคุณ");
}


$product_id = (int)$order['product_id'];

$product_name = $order['product_name'];


// =====================================================
// ราคาสินค้า
// รองรับทั้งระบบเก่า price และระบบใหม่ total_price
// =====================================================

if (
    isset($order['total_price']) &&
    $order['total_price'] !== null
) {
    $order_price = (float)$order['total_price'];
} else {
    $order_price = (float)($order['price'] ?? 0);
}


// =====================================================
// ถ้ายังไม่ได้ส่ง POST
// แสดงหน้าอัปโหลดสลิป
// =====================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST'):

?>

<!doctype html>

<html lang="th">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1"
>

<title>อัปโหลดสลิป - PD Shop</title>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>

<style>

body {
    background:#f5f6f8;
}

.upload-box {
    max-width:600px;
    margin:80px auto;
    border:0;
    border-radius:20px;
}

</style>

</head>

<body>

<div class="container">

<div class="card shadow-sm upload-box">

<div class="card-body p-5">

<h2 class="fw-bold mb-4">
💳 ชำระเงิน
</h2>

<h5>
<?= htmlspecialchars($product_name) ?>
</h5>

<div class="fs-3 fw-bold text-danger mb-4">

฿<?= number_format($order_price, 2) ?>

</div>


<form
    action="upload_slip.php"
    method="POST"
    enctype="multipart/form-data"
>

<input
    type="hidden"
    name="order_id"
    value="<?= $order_id ?>"
>


<label class="form-label fw-bold">
📷 เลือกไฟล์สลิป
</label>

<input
    type="file"
    name="slip"
    class="form-control mb-3"
    accept="image/jpeg,image/png,image/webp"
    required
>


<div class="text-secondary small mb-4">

รองรับ JPG, PNG และ WEBP<br>
ขนาดไม่เกิน 5 MB

</div>


<button
    type="submit"
    class="btn btn-success w-100"
>

📤 ส่งสลิปให้ผู้ขายตรวจสอบ

</button>


<a
    href="my_orders.php"
    class="btn btn-outline-secondary w-100 mt-2"
>

← กลับออเดอร์ของฉัน

</a>

</form>

</div>

</div>

</div>

</body>

</html>

<?php

exit;

endif;


// =====================================================
// ตรวจสอบไฟล์
// =====================================================

if (
    !isset($_FILES['slip']) ||
    $_FILES['slip']['error'] === UPLOAD_ERR_NO_FILE
) {
    die("กรุณาเลือกไฟล์สลิป");
}


$file = $_FILES['slip'];


// =====================================================
// ตรวจสอบ Upload Error
// =====================================================

if ($file['error'] !== UPLOAD_ERR_OK) {

    die(
        "อัปโหลดไฟล์ไม่สำเร็จ รหัสข้อผิดพลาด: "
        . (int)$file['error']
    );

}


// =====================================================
// ตรวจสอบขนาดไฟล์
// 5 MB
// =====================================================

$max_size = 5 * 1024 * 1024;

if ($file['size'] > $max_size) {

    die("ไฟล์ใหญ่เกิน 5 MB");

}


// =====================================================
// ตรวจสอบ MIME จริง
// =====================================================

$finfo = new finfo(FILEINFO_MIME_TYPE);

$mime = $finfo->file($file['tmp_name']);


$allowed = [

    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp'

];


if (!isset($allowed[$mime])) {

    die(
        "ไฟล์ต้องเป็น JPG, PNG หรือ WEBP เท่านั้น"
    );

}


$extension = $allowed[$mime];


// =====================================================
// โฟลเดอร์เก็บไฟล์
//
// Railway Volume:
// /var/www/html/uploads
//
// __DIR__:
// /var/www/html
// =====================================================

$upload_dir = __DIR__ . '/uploads/';


// =====================================================
// สร้างโฟลเดอร์ถ้ายังไม่มี
// =====================================================

if (!is_dir($upload_dir)) {

    if (!mkdir($upload_dir, 0777, true)) {

        die(
            "ไม่สามารถสร้างโฟลเดอร์ uploads ได้<br><br>"
            . htmlspecialchars($upload_dir)
        );

    }

}


// =====================================================
// ตรวจสอบเขียนไฟล์ได้
// =====================================================

if (!is_writable($upload_dir)) {

    die(
        "โฟลเดอร์ uploads ไม่สามารถเขียนไฟล์ได้<br><br>"
        . htmlspecialchars($upload_dir)
    );

}


// =====================================================
// สร้างชื่อไฟล์
// =====================================================

$random = bin2hex(random_bytes(8));

$filename =
    'slip_order_' .
    $order_id .
    '_' .
    time() .
    '_' .
    $random .
    '.' .
    $extension;


// =====================================================
// Path จริงบน Server
// =====================================================

$destination =
    $upload_dir .
    $filename;


// =====================================================
// ย้ายไฟล์จาก Temporary
// =====================================================

if (!move_uploaded_file(
    $file['tmp_name'],
    $destination
)) {

    die(
        "ไม่สามารถบันทึกไฟล์สลิปได้<br><br>"
        . "ตำแหน่งที่พยายามบันทึก:<br>"
        . htmlspecialchars($destination)
    );

}


// =====================================================
// Path ที่เก็บใน Database
//
// ตัวอย่าง:
// uploads/slip_order_4_1788125181_a82fd91.webp
// =====================================================

$slip_path =
    'uploads/' .
    $filename;


// =====================================================
// อัปเดต orders
// =====================================================

$payment_status = 'pending';


$stmt = $conn->prepare("
    UPDATE orders
    SET
        payment_slip = ?,
        payment_status = ?
    WHERE
        id = ?
        AND buyer_id = ?
");


if (!$stmt) {

    if (file_exists($destination)) {
        unlink($destination);
    }

    die(
        "เกิดข้อผิดพลาด SQL:<br>"
        . htmlspecialchars($conn->error)
    );

}


$stmt->bind_param(
    "ssii",
    $slip_path,
    $payment_status,
    $order_id,
    $buyer_id
);


if (!$stmt->execute()) {

    if (file_exists($destination)) {
        unlink($destination);
    }

    die(
        "บันทึกข้อมูลสลิปไม่สำเร็จ:<br>"
        . htmlspecialchars($stmt->error)
    );

}

$stmt->close();


// =====================================================
// ดึงข้อมูลผู้ขาย
// =====================================================

$stmt = $conn->prepare("
    SELECT
        p.seller_id,
        p.name AS product_name
    FROM products p
    WHERE p.id = ?
    LIMIT 1
");


if ($stmt) {

    $stmt->bind_param(
        "i",
        $product_id
    );

    $stmt->execute();

    $product = $stmt->get_result()->fetch_assoc();

    $stmt->close();

} else {

    $product = null;

}


// =====================================================
// สร้าง Notification ให้ผู้ขาย
// =====================================================

if ($product) {

    $seller_id =
        (int)$product['seller_id'];


    $title =
        "💳 มีสลิปใหม่รอตรวจสอบ";


    $message =
        "ผู้ซื้อส่งหลักฐานการชำระเงินสำหรับสินค้า \""
        . $product_name
        . "\"\n\n"
        . "💰 ยอดเงิน: ฿"
        . number_format($order_price, 2)
        . "\n"
        . "📋 กรุณาตรวจสอบสลิป";


    $link =
        "order_detail.php?id=" .
        $order_id;


    /*
     * ตรวจสอบว่าตาราง notifications
     * มีโครงสร้างตามระบบปัจจุบัน
     */

    $stmt_notify = $conn->prepare("
        INSERT INTO notifications
        (
            user_id,
            product_id,
            buyer_id,
            title,
            message,
            link,
            is_read,
            created_at
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            0,
            NOW()
        )
    ");


    if ($stmt_notify) {

        $stmt_notify->bind_param(
            "iiisss",
            $seller_id,
            $product_id,
            $buyer_id,
            $title,
            $message,
            $link
        );

        $stmt_notify->execute();

        $stmt_notify->close();

    }

}


// =====================================================
// สำเร็จ
// =====================================================

header(
    "Location: my_orders.php?slip=success"
);

exit;

?>
```
