<?php
require 'config.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user']['id'];

$message = "";
$error = "";


/* =====================================================
   บันทึกโปรไฟล์
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $shop_name = trim($_POST['shop_name'] ?? '');
    $shop_contact = trim($_POST['shop_contact'] ?? '');

    if ($name === '') {
        $error = "กรุณากรอกชื่อผู้ใช้";
    } else {

        /* ---------------------------------------------
           รูปร้าน
        --------------------------------------------- */

        $shop_image = null;

        if (
            isset($_FILES['shop_image']) &&
            $_FILES['shop_image']['error'] === UPLOAD_ERR_OK
        ) {

            $file = $_FILES['shop_image'];

            $allowed = [
                'image/jpeg',
                'image/png',
                'image/webp',
                'image/gif'
            ];

            if (!in_array($file['type'], $allowed)) {

                $error = "รองรับเฉพาะ JPG, PNG, WEBP และ GIF";

            } elseif ($file['size'] > 5 * 1024 * 1024) {

                $error = "รูปภาพต้องมีขนาดไม่เกิน 5MB";

            } else {

                $upload_dir = __DIR__ . "/uploads/shop/";

                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $extension = strtolower(
                    pathinfo(
                        $file['name'],
                        PATHINFO_EXTENSION
                    )
                );

                $filename =
                    "shop_" .
                    $user_id .
                    "_" .
                    time() .
                    "." .
                    $extension;

                $target = $upload_dir . $filename;

                if (move_uploaded_file(
                    $file['tmp_name'],
                    $target
                )) {

                    $shop_image =
                        "uploads/shop/" . $filename;

                } else {

                    $error = "ไม่สามารถอัปโหลดรูปภาพได้";

                }
            }
        }


        /* ---------------------------------------------
           บันทึกข้อมูล
        --------------------------------------------- */

        if ($error === "") {

            if ($shop_image !== null) {

                $stmt = $conn->prepare("
                    UPDATE users
                    SET
                        name = ?,
                        shop_name = ?,
                        shop_contact = ?,
                        shop_image = ?
                    WHERE id = ?
                ");

                $stmt->bind_param(
                    "ssssi",
                    $name,
                    $shop_name,
                    $shop_contact,
                    $shop_image,
                    $user_id
                );

            } else {

                $stmt = $conn->prepare("
                    UPDATE users
                    SET
                        name = ?,
                        shop_name = ?,
                        shop_contact = ?
                    WHERE id = ?
                ");

                $stmt->bind_param(
                    "sssi",
                    $name,
                    $shop_name,
                    $shop_contact,
                    $user_id
                );
            }


            if ($stmt->execute()) {

                /* อัปเดต session */

                $_SESSION['user']['name'] = $name;

                $message = "บันทึกโปรไฟล์เรียบร้อยแล้ว";

            } else {

                $error = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";

            }
        }
    }
}


/* =====================================================
   ดึงข้อมูลผู้ใช้
===================================================== */

$stmt = $conn->prepare("
    SELECT
        id,
        name,
        email,
        shop_name,
        shop_contact,
        shop_image
    FROM users
    WHERE id = ?
");

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();


if (!$user) {
    die("ไม่พบข้อมูลผู้ใช้");
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
แก้ไขโปรไฟล์ - PD Shop
</title>

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

    --placeholder: #777777;

    --nav: #ffffff;

}


html[data-theme="dark"] {

    --bg: #101010;

    --card: #1c1c1c;

    --text: #ffffff;

    --secondary: #aaaaaa;

    --border: #333333;

    --input: #252525;

    --placeholder: #999999;

    --nav: #181818;

}


/* =====================================================
   BODY
===================================================== */

body {

    background: var(--bg);

    color: var(--text);

    transition:
        background .2s ease,
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
   PROFILE CARD
===================================================== */

.profile-card {

    max-width: 800px;

    margin: auto;

    border: 0;

    border-radius: 20px;

    background: var(--card);

    color: var(--text);

}


/* =====================================================
   SHOP IMAGE
===================================================== */

.shop-image {

    width: 180px;

    height: 180px;

    object-fit: cover;

    border-radius: 50%;

    border: 5px solid var(--card);

    box-shadow:
        0 4px 15px rgba(0,0,0,.15);

}


.shop-placeholder {

    width: 180px;

    height: 180px;

    border-radius: 50%;

    background: var(--border);

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 70px;

    margin: auto;

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

    border-color: #666;

}


.form-control::placeholder {

    color: var(--placeholder);

}


.form-label {

    color: var(--text);

}


.form-text {

    color: var(--secondary);

}


/* =====================================================
   TEXT
===================================================== */

.text-secondary {

    color: var(--secondary) !important;

}


/* =====================================================
   THEME BUTTON
===================================================== */

.theme-btn {

    width: 42px;

    height: 42px;

    border-radius: 50%;

    border:
        1px solid var(--border);

    background: var(--card);

    color: var(--text);

    cursor: pointer;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 19px;

    transition: .2s;

}


.theme-btn:hover {

    transform: scale(1.06);

}


/* =====================================================
   DARK BOOTSTRAP FIX
===================================================== */

html[data-theme="dark"] .btn-outline-dark {

    color: #fff;

    border-color: #fff;

}


html[data-theme="dark"] .btn-outline-dark:hover {

    background: #fff;

    color: #111;

}


html[data-theme="dark"] .btn-outline-secondary {

    color: #fff;

    border-color: #666;

}


html[data-theme="dark"] .btn-outline-secondary:hover {

    background: #fff;

    color: #111;

}


/* =====================================================
   FOOTER
===================================================== */

footer {

    color: var(--secondary) !important;

}

</style>


<script>

/* =====================================================
   โหลดธีมก่อนหน้า
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
   เปลี่ยน ICON
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
   LOAD
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


<div class="d-flex align-items-center">


<a
    href="index.php"
    class="btn btn-outline-dark me-2"
>
หน้าหลัก
</a>


<a
    href="my_orders.php"
    class="btn btn-outline-dark me-2"
>
📦 ออเดอร์
</a>


<a
    href="favorites.php"
    class="btn btn-outline-danger me-2"
>
❤️ ถูกใจ
</a>


<!-- =====================================================
     THEME BUTTON
===================================================== -->

<button
    type="button"
    id="themeButton"
    class="theme-btn me-2"
    onclick="toggleTheme()"
    title="เปลี่ยนธีม"
>

🌙

</button>


</div>

</div>

</nav>



<!-- =====================================================
     CONTENT
===================================================== -->

<div class="container py-5">


<div class="card profile-card shadow-sm">


<div class="card-body p-4 p-md-5">


<h2 class="fw-bold mb-1">
👤 โปรไฟล์ของฉัน
</h2>


<p class="text-secondary mb-4">
ตั้งค่าข้อมูลร้านและข้อมูลติดต่อของคุณ
</p>



<?php if ($message !== ""): ?>

<div class="alert alert-success">

✅ <?= htmlspecialchars($message) ?>

</div>

<?php endif; ?>


<?php if ($error !== ""): ?>

<div class="alert alert-danger">

❌ <?= htmlspecialchars($error) ?>

</div>

<?php endif; ?>



<!-- =====================================================
     รูปร้าน
===================================================== -->

<div class="text-center mb-4">


<?php if (!empty($user['shop_image'])): ?>

<img
    src="<?= htmlspecialchars($user['shop_image']) ?>"
    class="shop-image"
    alt="รูปภาพร้าน"
>

<?php else: ?>

<div class="shop-placeholder">
🏪
</div>

<?php endif; ?>


<h5 class="mt-3 fw-bold">
รูปภาพร้าน
</h5>


<p class="text-secondary">
รูปนี้จะแสดงในหน้าโปรไฟล์ของร้าน
</p>

</div>



<!-- =====================================================
     FORM
===================================================== -->

<form
    method="post"
    enctype="multipart/form-data"
>


<!-- ชื่อผู้ใช้ -->

<div class="mb-3">

<label class="form-label fw-bold">
ชื่อผู้ใช้
</label>


<input
    type="text"
    name="name"
    class="form-control form-control-lg"
    value="<?= htmlspecialchars($user['name']) ?>"
    required
>

</div>



<!-- ชื่อร้าน -->

<div class="mb-3">

<label class="form-label fw-bold">
🏪 ชื่อร้าน
</label>


<input
    type="text"
    name="shop_name"
    class="form-control form-control-lg"
    value="<?= htmlspecialchars($user['shop_name'] ?? '') ?>"
    placeholder="เช่น ร้านของมือสอง By Nd"
>

</div>



<!-- ติดต่อเพิ่มเติม -->

<div class="mb-3">

<label class="form-label fw-bold">
📞 ช่องทางติดต่อเพิ่มเติม
</label>


<input
    type="text"
    name="shop_contact"
    class="form-control form-control-lg"
    value="<?= htmlspecialchars($user['shop_contact'] ?? '') ?>"
    placeholder="เช่น Line: xxx / Facebook: xxx / โทร 08xxxxxxxx"
>

</div>



<!-- รูปภาพ -->

<div class="mb-4">

<label class="form-label fw-bold">
🖼️ รูปภาพร้าน
</label>


<input
    type="file"
    name="shop_image"
    class="form-control form-control-lg"
    accept="image/jpeg,image/png,image/webp,image/gif"
>


<div class="form-text">

รองรับ JPG, PNG, WEBP, GIF
ขนาดไม่เกิน 5MB

</div>

</div>



<!-- =====================================================
     BUTTON
===================================================== -->

<div class="d-grid gap-2">


<button
    type="submit"
    class="btn btn-dark btn-lg"
>

💾 บันทึกโปรไฟล์

</button>


<a
    href="index.php"
    class="btn btn-outline-secondary"
>

← กลับหน้าหลัก

</a>


</div>


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

🛒 <strong>PD Shop</strong>

<br>

<small>

เว็บไซต์ซื้อ–ขายสินค้ามือสอง

</small>

</footer>


</body>

</html>