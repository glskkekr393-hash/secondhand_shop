<?php
require 'config.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user']['id'];

$error = "";
$success = "";


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
   เพิ่มสินค้า
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $category_id = (int)($_POST['category_id'] ?? 0);
    $item_condition = trim($_POST['item_condition'] ?? '');
    $description = trim($_POST['description'] ?? '');

    $image = "";


    /* =================================================
       ตรวจข้อมูล
    ================================================= */

    if ($name === '') {

        $error = "กรุณากรอกชื่อสินค้า";

    } elseif ($price <= 0) {

        $error = "กรุณากรอกราคาให้ถูกต้อง";

    } elseif ($category_id <= 0) {

        $error = "กรุณาเลือกหมวดหมู่สินค้า";

    } elseif (
        $item_condition !== 'มือ 1' &&
        $item_condition !== 'มือ 2'
    ) {

        $error = "กรุณาเลือกสภาพสินค้า";

    } elseif ($description === '') {

        $error = "กรุณากรอกรายละเอียดสินค้า";

    }


    /* =================================================
       อัปโหลดรูป
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

            $error = "อัปโหลดรูปไม่สำเร็จ";

        } elseif (
            $_FILES['image']['size']
            > 5 * 1024 * 1024
        ) {

            $error = "รูปต้องมีขนาดไม่เกิน 5MB";

        } else {

            $finfo = new finfo(FILEINFO_MIME_TYPE);

            $mime = $finfo->file(
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
                    . '_'
                    . mt_rand(1000, 9999)
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
       เพิ่มสินค้าเข้า Database
    ================================================= */

    if ($error === '') {

        $stmt = $conn->prepare("
            INSERT INTO products
            (
                user_id,
                name,
                price,
                category_id,
                item_condition,
                description,
                image,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
        ");


        if (!$stmt) {

            $error =
                "เกิดข้อผิดพลาดในการเตรียมข้อมูล: "
                . $conn->error;

        } else {

            $stmt->bind_param(
                "isdisss",
                $user_id,
                $name,
                $price,
                $category_id,
                $item_condition,
                $description,
                $image
            );


            if ($stmt->execute()) {

                header(
                    "Location: my_products.php"
                );

                exit;

            } else {

                $error =
                    "ไม่สามารถเพิ่มสินค้าได้: "
                    . $stmt->error;

            }

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
ลงขายสินค้า - PD Shop
</title>


<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<style>

body {
    background:#f5f6f8;
}

.sell-card {
    max-width:800px;
    margin:auto;
    border:0;
    border-radius:20px;
}

.preview {
    width:220px;
    height:220px;
    object-fit:cover;
    border-radius:15px;
    display:none;
    margin:auto;
}

.image-placeholder {
    width:220px;
    height:220px;
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
🛒 PD Shop
</a>


<div class="d-flex gap-2">

<a
    href="my_products.php"
    class="btn btn-outline-dark"
>
📦 สินค้าของฉัน
</a>


<a
    href="profile.php"
    class="btn btn-outline-dark"
>
👤 โปรไฟล์
</a>

</div>

</div>

</nav>



<!-- =====================================================
     CONTENT
===================================================== -->

<div class="container py-5">


<div class="card sell-card shadow-sm">


<div class="card-body p-4 p-md-5">


<h2 class="fw-bold mb-1">

➕ ลงขายสินค้า

</h2>


<p class="text-secondary mb-4">

กรอกข้อมูลสินค้าเพื่อส่งให้ตรวจสอบก่อนเผยแพร่

</p>



<?php if ($error !== ''): ?>

<div class="alert alert-danger">

<?= htmlspecialchars($error) ?>

</div>

<?php endif; ?>



<!-- =================================================
     FORM
================================================= -->

<form
    method="post"
    enctype="multipart/form-data"
>


<!-- =================================================
     รูปสินค้า
================================================= -->

<div class="mb-4">


<label class="form-label fw-bold">

🖼️ รูปสินค้า

</label>


<div class="text-center mb-3">


<div
    id="imagePlaceholder"
    class="image-placeholder"
>
📦
</div>


<img
    id="imagePreview"
    class="preview"
    alt="ตัวอย่างรูปสินค้า"
>


</div>


<input
    type="file"
    name="image"
    id="imageInput"
    class="form-control"
    accept="image/jpeg,image/png,image/webp"
>


<div class="form-text">

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
    value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
    placeholder="เช่น iPhone 13 128GB"
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
    value="<?= htmlspecialchars($_POST['price'] ?? '') ?>"
    min="1"
    step="0.01"
    placeholder="0.00"
    required
>

</div>

</div>



<!-- =================================================
     หมวดหมู่
================================================= -->

<div class="mb-3">


<label class="form-label fw-bold">

📂 หมวดหมู่สินค้า

</label>


<select
    name="category_id"
    class="form-select form-select-lg"
    required
>


<option value="">
-- เลือกหมวดหมู่สินค้า --
</option>


<?php foreach ($categories as $category): ?>

<option
    value="<?= (int)$category['id'] ?>"
    <?= (
        (int)($_POST['category_id'] ?? 0)
        === (int)$category['id']
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


<div class="form-text">

เลือกหมวดหมู่ที่ตรงกับสินค้ามากที่สุด

</div>

</div>



<!-- =================================================
     สภาพสินค้า
================================================= -->

<div class="mb-3">


<label class="form-label fw-bold">

🏷️ สภาพสินค้า

</label>


<select
    name="item_condition"
    class="form-select form-select-lg"
    required
>


<option value="">
-- เลือกสภาพสินค้า --
</option>


<option
    value="มือ 1"
    <?= (
        ($_POST['item_condition'] ?? '')
        === 'มือ 1'
    )
        ? 'selected'
        : ''
    ?>
>
🆕 มือ 1
</option>


<option
    value="มือ 2"
    <?= (
        ($_POST['item_condition'] ?? '')
        === 'มือ 2'
    )
        ? 'selected'
        : ''
    ?>
>
♻️ มือ 2
</option>


</select>

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
    placeholder="ระบุรายละเอียดสินค้า เช่น อายุการใช้งาน ตำหนิ อุปกรณ์ที่ได้รับ..."
    required
><?= htmlspecialchars(
    $_POST['description'] ?? ''
) ?></textarea>


<div class="form-text">

ยิ่งใส่รายละเอียดชัดเจน ผู้ซื้อจะตัดสินใจได้ง่ายขึ้น

</div>

</div>



<!-- =================================================
     BUTTON
================================================= -->

<button
    type="submit"
    class="btn btn-dark btn-lg w-100"
>

🚀 ลงขายสินค้า

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



<script>

/* =====================================================
   Preview รูปก่อนอัปโหลด
===================================================== */

const imageInput =
    document.getElementById("imageInput");

const imagePreview =
    document.getElementById("imagePreview");

const imagePlaceholder =
    document.getElementById("imagePlaceholder");


imageInput.addEventListener(
    "change",
    function()
    {

        const file =
            this.files[0];

        if (!file) {

            imagePreview.style.display =
                "none";

            imagePlaceholder.style.display =
                "flex";

            imagePreview.src =
                "";

            return;
        }


        const allowedTypes = [
            "image/jpeg",
            "image/png",
            "image/webp"
        ];


        if (
            !allowedTypes.includes(
                file.type
            )
        ) {

            alert(
                "รองรับเฉพาะ JPG, PNG และ WEBP"
            );

            this.value = "";

            return;
        }


        if (
            file.size >
            5 * 1024 * 1024
        ) {

            alert(
                "รูปต้องมีขนาดไม่เกิน 5MB"
            );

            this.value = "";

            return;
        }


        const reader =
            new FileReader();


        reader.onload =
            function(event)
            {

                imagePreview.src =
                    event.target.result;

                imagePreview.style.display =
                    "block";

                imagePlaceholder.style.display =
                    "none";

            };


        reader.readAsDataURL(file);

    }
);

</script>


</body>

</html>