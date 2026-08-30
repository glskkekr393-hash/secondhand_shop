<?php
require 'config.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user']['id'];

$product_id = (int)($_GET['id'] ?? 0);

if ($product_id <= 0) {
    die("ไม่พบสินค้า");
}

$error = "";
$success = "";


/* =====================================================
   ดึงข้อมูลสินค้า
   ต้องเป็นสินค้าของเราเท่านั้น
===================================================== */

$stmt = $conn->prepare("
    SELECT
        p.*,
        c.name AS category_name
    FROM products p
    LEFT JOIN categories c
        ON p.category_id = c.id
    WHERE p.id = ?
      AND p.user_id = ?
");

$stmt->bind_param(
    "ii",
    $product_id,
    $user_id
);

$stmt->execute();

$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    die("ไม่พบสินค้านี้ หรือสินค้านี้ไม่ใช่ของคุณ");
}


/* =====================================================
   ดึงหมวดหมู่
===================================================== */

$categories = [];

$stmt = $conn->prepare("
    SELECT id, name
    FROM categories
    ORDER BY name ASC
");

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}


/* =====================================================
   บันทึกการแก้ไข
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name =
        trim($_POST['name'] ?? '');

    $price =
        (float)($_POST['price'] ?? 0);

    $category_id =
        (int)($_POST['category_id'] ?? 0);

    $item_condition =
        trim($_POST['item_condition'] ?? '');

    $description =
        trim($_POST['description'] ?? '');

    $image =
        $product['image'] ?? '';


    /* =================================================
       ตรวจข้อมูล
    ================================================= */

    if ($name === '') {

        $error =
            "กรุณากรอกชื่อสินค้า";

    } elseif ($price <= 0) {

        $error =
            "กรุณากรอกราคาสินค้าให้ถูกต้อง";

    } elseif ($category_id <= 0) {

        $error =
            "กรุณาเลือกหมวดหมู่สินค้า";

    } elseif ($item_condition === '') {

        $error =
            "กรุณาระบุสภาพสินค้า";

    }


    /* =================================================
       อัปโหลดรูปใหม่
    ================================================= */

    if (
        $error === '' &&
        isset($_FILES['image']) &&
        $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        if (
            $_FILES['image']['error']
            !== UPLOAD_ERR_OK
        ) {

            $error =
                "อัปโหลดรูปไม่สำเร็จ";

        } elseif (
            $_FILES['image']['size']
            > 5 * 1024 * 1024
        ) {

            $error =
                "รูปต้องมีขนาดไม่เกิน 5MB";

        } else {

            $finfo =
                new finfo(FILEINFO_MIME_TYPE);

            $mime =
                $finfo->file(
                    $_FILES['image']['tmp_name']
                );

            $allowed = [

                'image/jpeg' => 'jpg',

                'image/png' => 'png',

                'image/webp' => 'webp'

            ];

            if (!isset($allowed[$mime])) {

                $error =
                    "รองรับเฉพาะ JPG, PNG และ WEBP";

            } else {

                $extension =
                    $allowed[$mime];

                $filename =
                    'product_'
                    . $user_id
                    . '_'
                    . time()
                    . '.'
                    . $extension;


                $upload_dir =
                    __DIR__
                    . DIRECTORY_SEPARATOR
                    . 'uploads';


                if (!is_dir($upload_dir)) {

                    mkdir(
                        $upload_dir,
                        0777,
                        true
                    );
                }


                $destination =
                    $upload_dir
                    . DIRECTORY_SEPARATOR
                    . $filename;


                if (
                    move_uploaded_file(
                        $_FILES['image']['tmp_name'],
                        $destination
                    )
                ) {

                    /*
                     * ลบรูปเก่า
                     */
                    if (!empty($product['image'])) {

                        $old_file =
                            __DIR__
                            . DIRECTORY_SEPARATOR
                            . $product['image'];

                        if (
                            file_exists($old_file)
                        ) {

                            unlink($old_file);

                        }
                    }


                    $image =
                        'uploads/'
                        . $filename;

                } else {

                    $error =
                        "ไม่สามารถบันทึกรูปได้";

                }

            }

        }

    }


    /* =================================================
       UPDATE PRODUCT
    ================================================= */

    if ($error === '') {

        /*
         * ถ้าสินค้าเดิม approved
         * หลังแก้ไขให้กลับไป pending
         * เพื่อให้ระบบตรวจสอบอีกครั้ง
         *
         * ถ้าเป็น sold จะไม่สามารถเข้าหน้านี้จาก
         * my_products.php อยู่แล้ว
         */

        $new_status = $product['status'];

        if ($product['status'] === 'approved') {
            $new_status = 'pending';
        }


        $stmt = $conn->prepare("
            UPDATE products
            SET
                name = ?,
                price = ?,
                category_id = ?,
                item_condition = ?,
                description = ?,
                image = ?,
                status = ?
            WHERE id = ?
              AND user_id = ?
        ");


        $stmt->bind_param(
            "sdissssii",
            $name,
            $price,
            $category_id,
            $item_condition,
            $description,
            $image,
            $new_status,
            $product_id,
            $user_id
        );


        if ($stmt->execute()) {

            /*
             * กลับไปสินค้าของฉัน
             */
            header(
                "Location: my_products.php"
            );

            exit;

        } else {

            $error =
                "ไม่สามารถบันทึกสินค้าได้";

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
แก้ไขสินค้า - PD Shop
</title>


<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<style>

body {
    background:#f5f6f8;
}

.edit-card {
    max-width:800px;
    margin:auto;
    border:0;
    border-radius:20px;
}

.current-image {
    width:200px;
    height:200px;
    object-fit:cover;
    border-radius:15px;
    background:#e9ecef;
}

.image-placeholder {
    width:200px;
    height:200px;
    border-radius:15px;
    background:#e9ecef;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:70px;
    margin:auto;
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
🛒PD Shop
</a>


<a
    href="my_products.php"
    class="btn btn-outline-dark"
>
← สินค้าของฉัน
</a>

</div>

</nav>



<!-- =====================================================
     CONTENT
===================================================== -->

<div class="container py-5">


<div class="card edit-card shadow-sm">


<div class="card-body p-4 p-md-5">


<h2 class="fw-bold mb-1">

✏️ แก้ไขสินค้า

</h2>


<p class="text-secondary mb-4">

แก้ไขข้อมูลสินค้าของคุณ

</p>



<?php if ($error !== ''): ?>

<div class="alert alert-danger">

<?= htmlspecialchars($error) ?>

</div>

<?php endif; ?>



<!-- =================================================
     รูปปัจจุบัน
================================================= -->

<div class="text-center mb-4">

<?php if (!empty($product['image'])): ?>

<img
    src="<?= htmlspecialchars($product['image']) ?>"
    class="current-image"
    alt="รูปสินค้า"
>

<?php else: ?>

<div class="image-placeholder">

📦

</div>

<?php endif; ?>

</div>



<form
    method="post"
    enctype="multipart/form-data"
>


<!-- =================================================
     รูปใหม่
================================================= -->

<div class="mb-4">

<label class="form-label fw-bold">

🖼️ เปลี่ยนรูปสินค้า

</label>


<input
    type="file"
    name="image"
    class="form-control"
    accept="image/jpeg,image/png,image/webp"
>


<div class="form-text">

ถ้าไม่เลือกไฟล์ จะใช้รูปเดิม

<br>

รองรับ JPG, PNG, WEBP ขนาดไม่เกิน 5MB

</div>

</div>



<!-- =================================================
     ชื่อสินค้า
================================================= -->

<div class="mb-3">

<label class="form-label fw-bold">

📦 ชื่อสินค้า

</label>


<input
    type="text"
    name="name"
    class="form-control form-control-lg"
    value="<?= htmlspecialchars($product['name']) ?>"
    required
>

</div>



<!-- =================================================
     ราคา
================================================= -->

<div class="mb-3">

<label class="form-label fw-bold">

💰 ราคา

</label>


<div class="input-group input-group-lg">

<span class="input-group-text">
฿
</span>


<input
    type="number"
    name="price"
    class="form-control"
    value="<?= htmlspecialchars($product['price']) ?>"
    min="1"
    step="0.01"
    required
>

</div>

</div>



<!-- =================================================
     หมวดหมู่
================================================= -->

<div class="mb-3">

<label class="form-label fw-bold">

📂 หมวดหมู่

</label>


<select
    name="category_id"
    class="form-select form-select-lg"
    required
>

<option value="">
-- เลือกหมวดหมู่ --
</option>


<?php foreach ($categories as $category): ?>

<option
    value="<?= (int)$category['id'] ?>"
    <?= (
        (int)$category['id']
        === (int)$product['category_id']
    )
        ? 'selected'
        : ''
    ?>
>

<?= htmlspecialchars(
    $category['name']
) ?>

</option>

<?php endforeach; ?>


</select>

</div>



<!-- =================================================
     สภาพสินค้า
================================================= -->

<div class="mb-3">

<label class="form-label fw-bold">

🏷️ สภาพสินค้า

</label>


<input
    type="text"
    name="item_condition"
    class="form-control form-control-lg"
    value="<?= htmlspecialchars($product['item_condition']) ?>"
    placeholder="เช่น มือสอง สภาพดี 90%"
    required
>

</div>



<!-- =================================================
     รายละเอียด
================================================= -->

<div class="mb-4">

<label class="form-label fw-bold">

📝 รายละเอียดสินค้า

</label>


<textarea
    name="description"
    class="form-control"
    rows="6"
    placeholder="รายละเอียดสินค้า..."
><?= htmlspecialchars($product['description'] ?? '') ?></textarea>

</div>



<!-- =================================================
     BUTTON
================================================= -->

<button
    type="submit"
    class="btn btn-dark btn-lg w-100"
>

💾 บันทึกการแก้ไข

</button>


<a
    href="my_products.php"
    class="btn btn-outline-secondary btn-lg w-100 mt-2"
>

ยกเลิก

</a>


</form>


</div>

</div>

</div>



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