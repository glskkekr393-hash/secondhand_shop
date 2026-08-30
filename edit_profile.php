<?php
require 'config.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user']['id'];


// ดึงข้อมูลปัจจุบัน

$stmt = $conn->prepare("
    SELECT
        id,
        name,
        email,
        shop_name,
        contact_info
    FROM users
    WHERE id = ?
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    die("ไม่พบข้อมูลผู้ใช้");
}


$error = "";
$success = "";


/* บันทึก */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $shop_name = trim($_POST['shop_name'] ?? '');
    $contact_info = trim($_POST['contact_info'] ?? '');


    if ($name === '') {

        $error = "กรุณากรอกชื่อผู้ใช้";

    } else {

        $stmt = $conn->prepare("
            UPDATE users
            SET
                name = ?,
                shop_name = ?,
                contact_info = ?
            WHERE id = ?
        ");

        $stmt->bind_param(
            "sssi",
            $name,
            $shop_name,
            $contact_info,
            $user_id
        );

        if ($stmt->execute()) {

            // อัปเดต session ด้วย

            $_SESSION['user']['name'] = $name;

            $success = "บันทึกโปรไฟล์เรียบร้อยแล้ว";


            // ดึงข้อมูลใหม่

            $stmt = $conn->prepare("
                SELECT
                    id,
                    name,
                    email,
                    shop_name,
                    contact_info
                FROM users
                WHERE id = ?
            ");

            $stmt->bind_param("i", $user_id);
            $stmt->execute();

            $user =
                $stmt->get_result()->fetch_assoc();

        } else {

            $error = "ไม่สามารถบันทึกข้อมูลได้";

        }

    }

}

?>

<!doctype html>

<html lang="th">

<head>

<meta charset="utf-8">

<meta name="viewport"
content="width=device-width, initial-scale=1">

<title>
แก้ไขโปรไฟล์ร้าน
</title>

<link
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
rel="stylesheet">

<style>

body {
    background:#f5f6f8;
}

.edit-card {
    max-width:700px;
    margin:auto;
    border:0;
    border-radius:20px;
}

</style>

</head>

<body>


<nav class="navbar bg-white shadow-sm">

<div class="container">

<a
href="index.php"
class="navbar-brand fw-bold text-dark text-decoration-none"
>
🛒 PD Shop
</a>

<a
href="profile.php"
class="btn btn-outline-dark"
>
← โปรไฟล์
</a>

</div>

</nav>


<div class="container py-5">

<div class="card edit-card shadow-sm">

<div class="card-body p-4 p-md-5">


<h2 class="fw-bold mb-1">

⚙️ แก้ไขโปรไฟล์ร้าน

</h2>

<p class="text-secondary mb-4">

ตั้งชื่อร้านและข้อมูลสำหรับให้ลูกค้าติดต่อ

</p>


<?php if ($error): ?>

<div class="alert alert-danger">

<?= htmlspecialchars($error) ?>

</div>

<?php endif; ?>


<?php if ($success): ?>

<div class="alert alert-success">

<?= htmlspecialchars($success) ?>

</div>

<?php endif; ?>


<form method="post">


<!-- ชื่อผู้ใช้ -->

<div class="mb-3">

<label class="form-label fw-bold">

ชื่อผู้ขาย

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

<div class="form-text">

ชื่อนี้จะแสดงให้ลูกค้าเห็นในโปรไฟล์ร้าน

</div>

</div>


<!-- Email -->

<div class="mb-3">

<label class="form-label fw-bold">

📧 อีเมล

</label>

<input
type="email"
class="form-control"
value="<?= htmlspecialchars($user['email']) ?>"
disabled
>

<div class="form-text">

อีเมลไม่สามารถแก้ไขจากหน้านี้

</div>

</div>


<!-- ติดต่อ -->

<div class="mb-4">

<label class="form-label fw-bold">

📞 ข้อมูลติดต่อเพิ่มเติม

</label>

<textarea
name="contact_info"
class="form-control"
rows="5"
placeholder="เช่น&#10;โทร: 08x-xxx-xxxx&#10;Line: @myshop&#10;Facebook: My Shop"
><?= htmlspecialchars($user['contact_info'] ?? '') ?></textarea>

<div class="form-text">

ข้อมูลนี้จะแสดงให้ลูกค้าที่เข้ามาดูโปรไฟล์ร้านเห็น

</div>

</div>


<button
type="submit"
class="btn btn-dark btn-lg w-100"
>

💾 บันทึกโปรไฟล์

</button>


<a
href="profile.php"
class="btn btn-outline-secondary btn-lg w-100 mt-2"
>

ยกเลิก

</a>


</form>

</div>

</div>

</div>


</body>

</html>