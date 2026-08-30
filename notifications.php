<?php

require 'config.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user']['id'];


// =====================================================
// อ่านทั้งหมด
// =====================================================

if (isset($_GET['read_all'])) {

    $stmt = $conn->prepare("
        UPDATE notifications
        SET is_read = 1
        WHERE user_id = ?
    ");

    $stmt->bind_param(
        "i",
        $user_id
    );

    $stmt->execute();

    header(
        "Location: notifications.php"
    );

    exit;
}


// =====================================================
// ดึงแจ้งเตือน
// =====================================================

$stmt = $conn->prepare("
    SELECT *
    FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
");

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$notifications =
    $stmt->get_result();


// =====================================================
// จำนวนยังไม่อ่าน
// =====================================================

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM notifications
    WHERE user_id = ?
    AND is_read = 0
");

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$unread =
    (int)$stmt
        ->get_result()
        ->fetch_assoc()['total'];

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
แจ้งเตือน - PD Shop
</title>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<style>

/* =====================================================
   THEME
===================================================== */

:root {

    --bg: #f5f6f8;

    --card: #ffffff;

    --text: #111111;

    --secondary: #6c757d;

    --border: #e5e5e5;

    --nav: #ffffff;

    --info: #f8f9fa;

}


html[data-theme="dark"] {

    --bg: #101010;

    --card: #1c1c1c;

    --text: #ffffff;

    --secondary: #aaaaaa;

    --border: #333333;

    --nav: #181818;

    --info: #252525;

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
   NOTIFICATION
===================================================== */

.notification {

    border: 0;

    border-radius: 15px;

    margin-bottom: 12px;

    transition: .2s;

}


.notification:hover {

    transform:
        translateY(-2px);

}


/* =====================================================
   UNREAD
===================================================== */

.unread {

    background: #eef5ff;

    border-left:
        5px solid #0d6efd;

}


/* =====================================================
   READ
===================================================== */

.read {

    background: var(--card);

}


/* =====================================================
   DARK UNREAD
===================================================== */

html[data-theme="dark"] .unread {

    background: #172437;

    border-left:
        5px solid #0d6efd;

}


/* =====================================================
   LINK
===================================================== */

.notification-link {

    text-decoration: none;

    color: var(--text);

}


/* =====================================================
   ICON
===================================================== */

.notification-icon {

    font-size: 35px;

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
   EMPTY CARD
===================================================== */

.empty-card {

    background: var(--card);

    color: var(--text);

}


/* =====================================================
   DARK BOOTSTRAP FIX
===================================================== */

html[data-theme="dark"] .btn-outline-secondary {

    color: var(--text);

    border-color: #666;

}


html[data-theme="dark"] .btn-outline-secondary:hover {

    background: #ffffff;

    color: #111111;

}


html[data-theme="dark"] .btn-outline-dark {

    color: var(--text);

    border-color: var(--text);

}


html[data-theme="dark"] .btn-outline-dark:hover {

    background: var(--text);

    color: #111111;

}


html[data-theme="dark"] .text-bg-light {

    background: #333333 !important;

    color: #ffffff !important;

}


html[data-theme="dark"] .bg-white {

    background: var(--card) !important;

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


<div class="d-flex align-items-center">


<a
    href="index.php"
    class="navbar-brand fw-bold text-decoration-none"
>

🛒 PD Shop

</a>

</div>


<div class="d-flex align-items-center">


<a
    href="index.php"
    class="btn btn-outline-secondary me-2"
>

หน้าหลัก

</a>


<a
    href="my.php"
    class="btn btn-outline-dark me-2"
>

👤 สินค้าของฉัน

</a>


<!-- =====================================================
     ปุ่มธีม
===================================================== -->

<button
    type="button"
    id="themeButton"
    class="theme-btn me-2"
    onclick="toggleTheme()"
>

🌙

</button>


<a
    href="logout.php"
    class="btn btn-dark"
>

ออกจากระบบ

</a>


</div>


</div>

</nav>


<!-- =====================================================
     MAIN
===================================================== -->

<div class="container py-5">


<div
    class="d-flex justify-content-between align-items-center mb-4"
>


<div>


<h1 class="fw-bold">

🔔 การแจ้งเตือน

</h1>


<p class="text-secondary mb-0">

มี <?= $unread ?> รายการที่ยังไม่ได้อ่าน

</p>


</div>


<?php if ($unread > 0): ?>


<a
    href="notifications.php?read_all=1"
    class="btn btn-outline-primary"
>

✓ อ่านทั้งหมด

</a>


<?php endif; ?>


</div>


<!-- =====================================================
     ไม่มีแจ้งเตือน
===================================================== -->

<?php if ($notifications->num_rows === 0): ?>


<div
    class="
        card
        empty-card
        shadow-sm
        border-0
        p-5
        text-center
    "
>


<div class="notification-icon">

🔕

</div>


<h4 class="mt-3">

ยังไม่มีการแจ้งเตือน

</h4>


<p class="text-secondary">

เมื่อมีความเคลื่อนไหวเกี่ยวกับสินค้า
จะแสดงที่หน้านี้

</p>


</div>


<?php else: ?>


<!-- =====================================================
     รายการแจ้งเตือน
===================================================== -->

<?php while ($n = $notifications->fetch_assoc()): ?>


<?php

$notification_id =
    (int)$n['id'];

$product_id =
    (int)($n['product_id'] ?? 0);

$buyer_id =
    (int)($n['buyer_id'] ?? 0);

$title =
    $n['title'] ?? 'แจ้งเตือน';

$message =
    $n['message'] ?? '';

$link =
    trim($n['link'] ?? '');


// =====================================================
// ตรวจสอบประเภทแจ้งเตือน
// =====================================================

$is_chat =
    strpos(
        $title,
        'ข้อความใหม่'
    ) !== false;

$is_payment =
    strpos(
        $title,
        'สลิป'
    ) !== false
    ||
    strpos(
        $title,
        'ชำระเงิน'
    ) !== false;


// =====================================================
// ลิงก์
// =====================================================

if ($is_chat) {

    if (
        $product_id > 0 &&
        $buyer_id > 0
    ) {

        $link =
            "chat.php?product_id="
            . $product_id
            . "&buyer_id="
            . $buyer_id;

    }

}
elseif ($is_payment) {

    if ($link === '') {

        $link =
            "seller_orders.php";

    }

}
elseif ($link === '') {

    $link = "#";

}


// =====================================================
// ไอคอน
// =====================================================

if ($is_payment) {

    $icon = "💰";

}
elseif ($is_chat) {

    $icon = "💬";

}
elseif (
    strpos($title, 'อนุมัติ') !== false
) {

    $icon = "✅";

}
elseif (
    strpos($title, 'ไม่ผ่าน') !== false
    ||
    strpos($title, 'ปฏิเสธ') !== false
) {

    $icon = "❌";

}
elseif (
    strpos($title, 'จัดส่ง') !== false
) {

    $icon = "📦";

}
else {

    $icon = "🔔";

}


// =====================================================
// อ่านแล้วหรือยัง
// =====================================================

$is_read =
    (int)$n['is_read'] === 1;

?>


<a
    href="<?= htmlspecialchars($link) ?>"
    class="notification-link"
    onclick="markRead(<?= $notification_id ?>)"
>


<div
    class="
        card
        notification
        shadow-sm
        p-3
        <?= $is_read
            ? 'read'
            : 'unread'
        ?>
    "
>


<div class="d-flex">


<div class="notification-icon me-3">

<?= $icon ?>

</div>


<div class="flex-grow-1">


<h5 class="fw-bold mb-1">

<?= htmlspecialchars($title) ?>


<?php if (!$is_read): ?>

<span class="badge bg-primary">

ใหม่

</span>

<?php endif; ?>

</h5>


<p class="mb-1">

<?= nl2br(
    htmlspecialchars($message)
) ?>

</p>


<small class="text-secondary">

<?= htmlspecialchars(
    $n['created_at']
) ?>

</small>


<?php if ($is_payment): ?>


<div class="mt-2">

<span class="badge text-bg-success">

💰 กดเพื่อตรวจสอบสลิป

</span>

</div>


<?php elseif ($is_chat): ?>


<div class="mt-2">

<span class="badge text-bg-light">

💬 กดเพื่อเปิดแชท

</span>

</div>


<?php elseif ($link !== '#'): ?>


<div class="mt-2">

<span class="badge text-bg-light">

🔎 ดูรายละเอียด

</span>

</div>


<?php endif; ?>


</div>

</div>

</div>

</a>


<?php endwhile; ?>


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


<script>

/* =====================================================
   อ่านแจ้งเตือน
===================================================== */

function markRead(id) {

    fetch(
        'mark_notification_read.php?id=' + id
    )
    .catch(function () {});

}

</script>


</body>

</html>