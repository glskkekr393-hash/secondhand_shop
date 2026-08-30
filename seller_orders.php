<?php

require_once __DIR__ . '/config.php';


/* =========================================================
   ตรวจสอบ LOGIN
   ========================================================= */

if (
    !isset($_SESSION['user']) ||
    !isset($_SESSION['user']['id'])
) {
    header("Location: login.php");
    exit;
}


$user_id = (int) $_SESSION['user']['id'];

$user_name = $_SESSION['user']['name'] ?? 'ผู้ขาย';


/* =========================================================
   ดึงออเดอร์ของสินค้าของผู้ขาย
   ========================================================= */

$orders = [];


$sql = "
    SELECT
        o.id,
        o.product_id,
        o.buyer_id,
        o.buyer_name,
        o.phone,
        o.address,
        o.price,
        o.payment_slip,
        o.status,
        o.shipping_company,
        o.tracking_number,
        o.created_at,
        o.updated_at,

        p.name AS product_name,
        p.image AS product_image

    FROM orders AS o

    INNER JOIN products AS p
        ON o.product_id = p.id

    WHERE p.user_id = ?

    AND LOWER(TRIM(o.status)) NOT IN (
        'ยกเลิก',
        'ยกเลิกแล้ว',
        'cancelled',
        'canceled',
        'cancel'
    )

    ORDER BY o.created_at DESC
";


$stmt = $conn->prepare($sql);


if (!$stmt) {

    die(
        "เกิดข้อผิดพลาด SQL: " .
        htmlspecialchars($conn->error)
    );

}


$stmt->bind_param(
    "i",
    $user_id
);


$stmt->execute();


$result = $stmt->get_result();


while ($row = $result->fetch_assoc()) {

    $orders[] = $row;

}


$stmt->close();


/* =========================================================
   STATUS TEXT
   ========================================================= */

function getStatusText($status)
{

    $status = trim($status);


    switch ($status) {

        case 'pending':
            return 'รอตรวจสอบ';

        case 'paid':
            return 'ชำระเงินแล้ว';

        case 'shipping':
            return 'กำลังจัดส่ง';

        case 'completed':
            return 'สำเร็จ';

        case 'cancelled':
        case 'canceled':
        case 'cancel':
            return 'ยกเลิก';

        case 'รอตรวจสอบ':
            return 'รอตรวจสอบ';

        case 'ชำระเงินแล้ว':
            return 'ชำระเงินแล้ว';

        case 'กำลังจัดส่ง':
            return 'กำลังจัดส่ง';

        case 'จัดส่งแล้ว':
            return 'จัดส่งแล้ว';

        case 'สำเร็จ':
            return 'สำเร็จ';

        case 'ยกเลิก':
        case 'ยกเลิกแล้ว':
            return 'ยกเลิก';

        default:
            return $status ?: 'รอตรวจสอบ';
    }

}


/* =========================================================
   STATUS CLASS
   ========================================================= */

function getStatusClass($status)
{

    $status = trim($status);


    switch ($status) {

        case 'paid':
        case 'ชำระเงินแล้ว':
            return 'paid';

        case 'shipping':
        case 'กำลังจัดส่ง':
        case 'จัดส่งแล้ว':
            return 'shipping';

        case 'completed':
        case 'สำเร็จ':
            return 'completed';

        case 'cancelled':
        case 'canceled':
        case 'cancel':
        case 'ยกเลิก':
        case 'ยกเลิกแล้ว':
            return 'cancelled';

        default:
            return 'pending';
    }

}

?>


<!DOCTYPE html>

<html lang="th">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    ออเดอร์ที่ได้รับ - PD Shop
</title>


<style>

/* =========================================================
   THEME
   ========================================================= */

:root {

    --bg: #f5f6fa;

    --card: #ffffff;

    --text: #333333;

    --muted: #777777;

    --border: #eeeeee;

    --input: #ffffff;

    --info: #f8f9fa;

    --customer-bg: #fff8f4;

    --customer-border: #ffe1d5;

    --shipping-bg: #f4f8ff;

    --shipping-border: #dce9ff;

    --nav: #ffffff;

    --shadow:
        0 3px 15px rgba(0,0,0,.06);

}


html[data-theme="dark"] {

    --bg: #101010;

    --card: #1c1c1c;

    --text: #f5f5f5;

    --muted: #aaaaaa;

    --border: #333333;

    --input: #252525;

    --info: #252525;

    --customer-bg: #261d19;

    --customer-border: #493127;

    --shipping-bg: #192231;

    --shipping-border: #293c5b;

    --nav: #181818;

    --shadow:
        0 3px 15px rgba(0,0,0,.35);

}


/* =========================================================
   RESET
   ========================================================= */

* {

    box-sizing: border-box;

    margin: 0;

    padding: 0;

    font-family:
        Arial,
        Tahoma,
        sans-serif;

}


/* =========================================================
   BODY
   ========================================================= */

body {

    background: var(--bg);

    color: var(--text);

    transition:
        background .2s ease,
        color .2s ease;

}


/* =========================================================
   NAVBAR
   ========================================================= */

.navbar {

    height: 65px;

    background: var(--nav);

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 0 35px;

    box-shadow:
        0 2px 10px
        rgba(0,0,0,.08);

    border-bottom:
        1px solid var(--border);

}


.logo {

    color: #ff6b35;

    text-decoration: none;

    font-size: 22px;

    font-weight: bold;

}


.nav-right {

    display: flex;

    align-items: center;

    gap: 10px;

}


.nav-right a {

    color: var(--text);

    text-decoration: none;

    padding: 9px 15px;

    border-radius: 8px;

}


.nav-right a:hover {

    background: #fff1eb;

    color: #ff6b35;

}


/* =========================================================
   THEME BUTTON
   ========================================================= */

.theme-btn {

    width: 42px;

    height: 42px;

    border-radius: 50%;

    border: 1px solid var(--border);

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


/* =========================================================
   CONTAINER
   ========================================================= */

.container {

    max-width: 1200px;

    margin: 35px auto;

    padding: 0 20px;

}


.page-title {

    margin-bottom: 25px;

}


.page-title h1 {

    font-size: 30px;

    margin-bottom: 8px;

}


.page-title p {

    color: var(--muted);

}


/* =========================================================
   ORDER
   ========================================================= */

.order-list {

    display: flex;

    flex-direction: column;

    gap: 20px;

}


.order-card {

    background: var(--card);

    color: var(--text);

    border-radius: 16px;

    padding: 22px;

    box-shadow: var(--shadow);

}


/* =========================================================
   ORDER HEADER
   ========================================================= */

.order-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    border-bottom:
        1px solid var(--border);

    padding-bottom: 15px;

    margin-bottom: 18px;

}


.order-number {

    font-size: 18px;

    font-weight: bold;

}


/* =========================================================
   STATUS
   ========================================================= */

.status {

    padding: 7px 14px;

    border-radius: 20px;

    font-size: 13px;

    font-weight: bold;

}


.status.pending {

    background: #fff3cd;

    color: #856404;

}


.status.paid {

    background: #d1ecf1;

    color: #0c5460;

}


.status.shipping {

    background: #cfe2ff;

    color: #084298;

}


.status.completed {

    background: #d1e7dd;

    color: #0f5132;

}


.status.cancelled {

    background: #f8d7da;

    color: #842029;

}


/* =========================================================
   PRODUCT
   ========================================================= */

.product {

    display: flex;

    align-items: center;

    gap: 15px;

    margin-bottom: 20px;

}


.product-image {

    width: 90px;

    height: 90px;

    object-fit: cover;

    border-radius: 10px;

    background: var(--info);

}


.no-image {

    width: 90px;

    height: 90px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: var(--info);

    border-radius: 10px;

    font-size: 35px;

}


.product-name {

    font-size: 18px;

    font-weight: bold;

}


.product-id {

    color: var(--muted);

    font-size: 13px;

    margin-top: 5px;

}


/* =========================================================
   INFO
   ========================================================= */

.info-grid {

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 15px;

    margin-bottom: 18px;

}


.info-box {

    background: var(--info);

    padding: 14px;

    border-radius: 10px;

}


.info-label {

    font-size: 13px;

    color: var(--muted);

    margin-bottom: 6px;

}


.info-value {

    font-weight: bold;

}


.price {

    color: #ff6b35;

    font-size: 20px;

}


/* =========================================================
   CUSTOMER
   ========================================================= */

.customer {

    background: var(--customer-bg);

    border:
        1px solid var(--customer-border);

    border-radius: 12px;

    padding: 16px;

    margin-bottom: 18px;

}


.customer-title {

    color: #ff6b35;

    font-weight: bold;

    margin-bottom: 10px;

}


.customer-row {

    margin-bottom: 7px;

}


.customer-row:last-child {

    margin-bottom: 0;

}


/* =========================================================
   SHIPPING
   ========================================================= */

.shipping {

    background: var(--shipping-bg);

    border:
        1px solid var(--shipping-border);

    border-radius: 12px;

    padding: 16px;

    margin-bottom: 18px;

}


.shipping-title {

    color: #2563eb;

    font-weight: bold;

    margin-bottom: 10px;

}


.shipping-row {

    margin-bottom: 7px;

}


/* =========================================================
   FOOTER
   ========================================================= */

.order-footer {

    border-top:
        1px solid var(--border);

    padding-top: 15px;

    display: flex;

    justify-content: space-between;

    align-items: center;

}


/* =========================================================
   DETAIL BUTTON
   ========================================================= */

.btn-detail {

    display: inline-block;

    background: #ff6b35;

    color: white;

    text-decoration: none;

    padding: 10px 18px;

    border-radius: 9px;

    font-weight: bold;

    transition: .2s;

}


.btn-detail:hover {

    background: #e85b28;

    transform: translateY(-1px);

}


/* =========================================================
   EMPTY
   ========================================================= */

.empty {

    background: var(--card);

    color: var(--text);

    border-radius: 16px;

    padding: 70px 20px;

    text-align: center;

    box-shadow: var(--shadow);

}


.empty-icon {

    font-size: 60px;

    margin-bottom: 15px;

}


.empty h2 {

    margin-bottom: 10px;

}


.empty p {

    color: var(--muted);

}


/* =========================================================
   MOBILE
   ========================================================= */

@media (max-width: 700px) {

    .navbar {

        padding: 0 15px;

    }


    .nav-right a {

        padding: 8px;

        font-size: 13px;

    }


    .info-grid {

        grid-template-columns: 1fr;

    }


    .order-header {

        flex-direction: column;

        align-items: flex-start;

        gap: 12px;

    }


    .order-footer {

        flex-direction: column;

        align-items: flex-start;

        gap: 12px;

    }

}

</style>


<script>

/* =========================================================
   โหลดธีมก่อนแสดงหน้า
   ========================================================= */

(function () {

    const theme =
        localStorage.getItem("theme") || "light";

    document.documentElement.setAttribute(
        "data-theme",
        theme
    );

})();


/* =========================================================
   เปลี่ยนธีม
   ========================================================= */

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


/* =========================================================
   เปลี่ยนไอคอน
   ========================================================= */

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


/* =========================================================
   PAGE LOAD
   ========================================================= */

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

<nav class="navbar">


    <a
        href="index.php"
        class="logo"
    >
        PD Shop
    </a>


    <div class="nav-right">


        <a href="index.php">
            🏠 หน้าหลัก
        </a>


        <a href="seller.php">
            🏪 ร้านค้าของฉัน
        </a>


        <!-- ปุ่มธีม -->

        <button
            type="button"
            id="themeButton"
            class="theme-btn"
            onclick="toggleTheme()"
        >
            🌙
        </button>


        <a href="logout.php">
            🚪 ออกจากระบบ
        </a>


    </div>


</nav>



<!-- =====================================================
     CONTENT
===================================================== -->

<div class="container">


    <div class="page-title">


        <h1>
            📦 ออเดอร์ที่ได้รับ
        </h1>


        <p>

            สวัสดี
            <?= htmlspecialchars($user_name) ?>

            — รายการสั่งซื้อสินค้าของคุณ

        </p>


    </div>



    <?php if (empty($orders)): ?>


        <div class="empty">


            <div class="empty-icon">
                📦
            </div>


            <h2>
                ยังไม่มีออเดอร์
            </h2>


            <p>

                เมื่อมีลูกค้าสั่งซื้อสินค้าของคุณ
                รายการออเดอร์จะแสดงที่หน้านี้

            </p>


        </div>


    <?php else: ?>


        <div class="order-list">


            <?php foreach ($orders as $order): ?>


                <?php

                $status_text =
                    getStatusText(
                        $order['status']
                    );


                $status_class =
                    getStatusClass(
                        $order['status']
                    );

                ?>


                <div class="order-card">


                    <!-- HEADER -->

                    <div class="order-header">


                        <div class="order-number">

                            📦 ออเดอร์ #

                            <?= htmlspecialchars(
                                $order['id']
                            ) ?>

                        </div>


                        <span
                            class="status <?= $status_class ?>"
                        >

                            <?= htmlspecialchars(
                                $status_text
                            ) ?>

                        </span>


                    </div>



                    <!-- PRODUCT -->

                    <div class="product">


                        <?php if (
                            !empty(
                                $order['product_image']
                            )
                        ): ?>


                            <img
                                src="uploads/<?= htmlspecialchars(
                                    $order['product_image']
                                ) ?>"
                                class="product-image"
                                alt="สินค้า"
                            >


                        <?php else: ?>


                            <div class="no-image">
                                📦
                            </div>


                        <?php endif; ?>


                        <div>


                            <div class="product-name">

                                <?= htmlspecialchars(
                                    $order['product_name']
                                ) ?>

                            </div>


                            <div class="product-id">

                                รหัสสินค้า #

                                <?= htmlspecialchars(
                                    $order['product_id']
                                ) ?>

                            </div>


                        </div>


                    </div>



                    <!-- INFO -->

                    <div class="info-grid">


                        <div class="info-box">


                            <div class="info-label">
                                ราคาสินค้า
                            </div>


                            <div class="info-value price">

                                ฿<?= number_format(
                                    (float)$order['price'],
                                    2
                                ) ?>

                            </div>


                        </div>



                        <div class="info-box">


                            <div class="info-label">
                                ผู้ซื้อ
                            </div>


                            <div class="info-value">

                                <?= htmlspecialchars(
                                    $order['buyer_name']
                                ) ?>

                            </div>


                        </div>



                        <div class="info-box">


                            <div class="info-label">
                                วันที่สั่งซื้อ
                            </div>


                            <div class="info-value">

                                <?= date(
                                    'd/m/Y H:i',
                                    strtotime(
                                        $order['created_at']
                                    )
                                ) ?>

                            </div>


                        </div>


                    </div>



                    <!-- CUSTOMER -->

                    <div class="customer">


                        <div class="customer-title">

                            👤 ข้อมูลลูกค้า

                        </div>


                        <div class="customer-row">

                            <strong>
                                ชื่อ:
                            </strong>

                            <?= htmlspecialchars(
                                $order['buyer_name']
                            ) ?>

                        </div>


                        <div class="customer-row">

                            <strong>
                                เบอร์โทร:
                            </strong>

                            <?= htmlspecialchars(
                                $order['phone']
                            ) ?>

                        </div>


                        <div class="customer-row">

                            <strong>
                                ที่อยู่:
                            </strong>

                            <?= nl2br(
                                htmlspecialchars(
                                    $order['address']
                                )
                            ) ?>

                        </div>


                    </div>



                    <!-- SHIPPING -->

                    <?php if (
                        !empty(
                            $order['shipping_company']
                        )
                        ||
                        !empty(
                            $order['tracking_number']
                        )
                    ): ?>


                        <div class="shipping">


                            <div class="shipping-title">

                                🚚 ข้อมูลการจัดส่ง

                            </div>


                            <?php if (
                                !empty(
                                    $order[
                                        'shipping_company'
                                    ]
                                )
                            ): ?>


                                <div class="shipping-row">

                                    <strong>
                                        บริษัทขนส่ง:
                                    </strong>

                                    <?= htmlspecialchars(
                                        $order[
                                            'shipping_company'
                                        ]
                                    ) ?>

                                </div>


                            <?php endif; ?>


                            <?php if (
                                !empty(
                                    $order[
                                        'tracking_number'
                                    ]
                                )
                            ): ?>


                                <div class="shipping-row">

                                    <strong>
                                        Tracking:
                                    </strong>

                                    <?= htmlspecialchars(
                                        $order[
                                            'tracking_number'
                                        ]
                                    ) ?>

                                </div>


                            <?php endif; ?>


                        </div>


                    <?php endif; ?>



                    <!-- FOOTER -->

                    <div class="order-footer">


                        <div>

                            สถานะ:

                            <strong>

                                <?= htmlspecialchars(
                                    $status_text
                                ) ?>

                            </strong>

                        </div>


                        <!-- ปุ่มรายละเอียด -->

                        <a
                            href="order_detail.php?id=<?= (int)$order['id'] ?>"
                            class="btn-detail"
                        >

                            👁️ ดูรายละเอียด

                        </a>


                    </div>


                </div>


            <?php endforeach; ?>


        </div>


    <?php endif; ?>


</div>


</body>

</html>