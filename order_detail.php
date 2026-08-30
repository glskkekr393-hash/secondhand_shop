<?php

require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user']['id'];

$order_id = (int) (
    $_GET['id']
    ?? $_POST['order_id']
    ?? 0
);

if ($order_id <= 0) {
    die("ไม่พบออเดอร์");
}


/* =========================================================
   ตรวจสอบว่าออเดอร์เป็นของผู้ขาย
   ========================================================= */

function checkSellerOrder($conn, $order_id, $user_id)
{
    $sql = "
        SELECT
            o.id
        FROM orders o

        INNER JOIN products p
            ON o.product_id = p.id

        WHERE
            o.id = ?
            AND p.user_id = ?

        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die(
            "เกิดข้อผิดพลาด SQL: " .
            htmlspecialchars($conn->error)
        );
    }

    $stmt->bind_param(
        "ii",
        $order_id,
        $user_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $order = $result->fetch_assoc();

    $stmt->close();

    return $order;
}


/* =========================================================
   จัดการปุ่มตรวจสอบสลิป
   ========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['action'])
) {

    $action = $_POST['action'];


    /* ตรวจสอบสิทธิ์ */

    $check = checkSellerOrder(
        $conn,
        $order_id,
        $user_id
    );

    if (!$check) {
        die("คุณไม่มีสิทธิ์จัดการออเดอร์นี้");
    }


    /* =====================================================
       ตรวจสอบเสร็จแล้ว
       ===================================================== */

    if ($action === 'verify_payment') {

        $sql = "
            UPDATE orders

            SET
                payment_status = 'paid',
                status = 'paid'

            WHERE
                id = ?
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
            $order_id
        );

        $stmt->execute();

        $stmt->close();


        header(
            "Location: order_detail.php?id=" .
            $order_id .
            "&verified=1"
        );

        exit;
    }


    /* =====================================================
       บันทึกสถานะ + บริษัทขนส่ง + Tracking
       ===================================================== */

    if ($action === 'update_order') {

        $status =
            trim(
                $_POST['status']
                ?? 'รอตรวจสอบ'
            );

        $shipping_company =
            trim(
                $_POST['shipping_company']
                ?? ''
            );

        $tracking_number =
            trim(
                $_POST['tracking_number']
                ?? ''
            );


        /* สถานะที่อนุญาต */

        $allowed_status = [

            'รอตรวจสอบ',

            'paid',

            'shipping',

            'completed',

            'cancelled'

        ];


        if (
            !in_array(
                $status,
                $allowed_status,
                true
            )
        ) {

            $status = 'รอตรวจสอบ';

        }


        $sql = "
            UPDATE orders

            SET
                status = ?,
                shipping_company = ?,
                tracking_number = ?

            WHERE
                id = ?
        ";


        $stmt =
            $conn->prepare($sql);


        if (!$stmt) {

            die(
                "เกิดข้อผิดพลาด SQL: " .
                htmlspecialchars(
                    $conn->error
                )
            );

        }


        $stmt->bind_param(
            "sssi",
            $status,
            $shipping_company,
            $tracking_number,
            $order_id
        );


        $stmt->execute();

        $stmt->close();


        header(
            "Location: order_detail.php?id=" .
            $order_id .
            "&updated=1"
        );

        exit;
    }

}


/* =========================================================
   ดึงข้อมูลออเดอร์
   ========================================================= */

$sql = "

    SELECT

        o.*,

        p.name AS product_name,

        p.image AS product_image,

        p.description AS product_description

    FROM orders o

    INNER JOIN products p
        ON o.product_id = p.id

    WHERE

        o.id = ?

        AND p.user_id = ?

    LIMIT 1
";


$stmt = $conn->prepare($sql);


if (!$stmt) {

    die(
        "เกิดข้อผิดพลาด SQL: " .
        htmlspecialchars(
            $conn->error
        )
    );

}


$stmt->bind_param(
    "ii",
    $order_id,
    $user_id
);


$stmt->execute();


$result =
    $stmt->get_result();


$order =
    $result->fetch_assoc();


$stmt->close();


if (!$order) {

    die("

        <div style='
            font-family:Arial;
            text-align:center;
            padding:60px;
        '>

            <h2>
                ❌ ไม่พบออเดอร์
            </h2>

            <p>
                ออเดอร์นี้ไม่มีอยู่
                หรือไม่ใช่ออเดอร์ของร้านคุณ
            </p>

            <br>

            <a href='seller_orders.php'>
                ← กลับไปออเดอร์ที่ได้รับ
            </a>

        </div>

    ");

}


/* =========================================================
   STATUS
   ========================================================= */

$status =
    trim(
        $order['status']
        ?? ''
    );


$status_text =
    $status;


if ($status === 'pending') {

    $status_text =
        'รอตรวจสอบ';

}

elseif ($status === 'paid') {

    $status_text =
        'ชำระเงินแล้ว';

}

elseif ($status === 'shipping') {

    $status_text =
        'กำลังจัดส่ง';

}

elseif ($status === 'completed') {

    $status_text =
        'สำเร็จ';

}

elseif (
    $status === 'cancelled'
    ||
    $status === 'canceled'
    ||
    $status === 'cancel'
) {

    $status_text =
        'ยกเลิก';

}

elseif ($status === '') {

    $status_text =
        'รอตรวจสอบ';

}


/* STATUS CLASS */

$status_class =
    'pending';


if (
    $status === 'paid'
    ||
    $status === 'ชำระเงินแล้ว'
) {

    $status_class =
        'paid';

}

elseif (
    $status === 'shipping'
    ||
    $status === 'กำลังจัดส่ง'
    ||
    $status === 'จัดส่งแล้ว'
) {

    $status_class =
        'shipping';

}

elseif (
    $status === 'completed'
    ||
    $status === 'สำเร็จ'
) {

    $status_class =
        'completed';

}

elseif (
    $status === 'cancelled'
    ||
    $status === 'canceled'
    ||
    $status === 'cancel'
    ||
    $status === 'ยกเลิก'
    ||
    $status === 'ยกเลิกแล้ว'
) {

    $status_class =
        'cancelled';

}


/* =========================================================
   PRODUCT IMAGE
   ========================================================= */

$product_image =
    trim(
        $order['product_image']
        ?? ''
    );


$product_url = '';


if ($product_image !== '') {

    $product_image =
        str_replace(
            '\\',
            '/',
            $product_image
        );

    $product_image =
        ltrim(
            $product_image,
            '/'
        );


    if (
        strpos(
            $product_image,
            'uploads/'
        ) === 0
    ) {

        $product_url =
            $product_image;

    }

    else {

        $product_url =
            'uploads/' .
            $product_image;

    }

}


/* =========================================================
   SLIP IMAGE
   ========================================================= */

$slip =
    trim(
        $order['payment_slip']
        ?? ''
    );


$slip_url = '';

$slip_exists = false;


if ($slip !== '') {

    $slip =
        str_replace(
            '\\',
            '/',
            $slip
        );

    $slip =
        ltrim(
            $slip,
            '/'
        );


    if (
        strpos(
            $slip,
            'uploads/'
        ) === 0
    ) {

        $slip_url =
            $slip;

    }

    elseif (
        strpos(
            $slip,
            'payment_slips/'
        ) === 0
    ) {

        $slip_url =
            'uploads/' .
            $slip;

    }

    else {

        $slip_url =
            'uploads/payment_slips/' .
            $slip;

    }


    $full_slip_path =
        __DIR__ .
        '/' .
        $slip_url;


    if (
        file_exists(
            $full_slip_path
        )
    ) {

        $slip_exists =
            true;

    }

}


/* =========================================================
   PAYMENT STATUS
   ========================================================= */

$payment_status =
    trim(
        $order['payment_status']
        ?? 'pending'
    );

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
    รายละเอียดออเดอร์
    #<?= (int)$order['id'] ?>
</title>


<style>

* {
    box-sizing:border-box;
    font-family:
        Arial,
        Tahoma,
        sans-serif;
}


body {

    margin:0;

    background:#f5f6fa;

    color:#333;

}


.navbar {

    height:65px;

    background:#fff;

    display:flex;

    align-items:center;

    justify-content:space-between;

    padding:0 30px;

    box-shadow:
        0 2px 10px
        rgba(0,0,0,.08);

}


.logo {

    color:#ff6b35;

    font-size:22px;

    font-weight:bold;

    text-decoration:none;

}


.navbar a {

    text-decoration:none;

    color:#555;

    margin-left:15px;

}


.container {

    max-width:900px;

    margin:30px auto;

    padding:0 20px;

}


.back {

    display:inline-block;

    margin-bottom:20px;

    color:#ff6b35;

    text-decoration:none;

    font-weight:bold;

}


.card {

    background:#fff;

    padding:25px;

    margin-bottom:20px;

    border-radius:15px;

    box-shadow:
        0 3px 15px
        rgba(0,0,0,.06);

}


.header {

    display:flex;

    justify-content:space-between;

    align-items:center;

    border-bottom:1px solid #eee;

    padding-bottom:15px;

    margin-bottom:20px;

}


.header h1 {

    margin:0;

    font-size:24px;

}


.status {

    padding:8px 15px;

    border-radius:20px;

    font-size:14px;

    font-weight:bold;

}


.pending {

    background:#fff3cd;

    color:#856404;

}


.paid {

    background:#d1ecf1;

    color:#0c5460;

}


.shipping {

    background:#cfe2ff;

    color:#084298;

}


.completed {

    background:#d1e7dd;

    color:#0f5132;

}


.cancelled {

    background:#f8d7da;

    color:#842029;

}


/* PRODUCT */

.product {

    display:flex;

    align-items:center;

    gap:20px;

}


.product-image {

    width:150px;

    height:150px;

    object-fit:cover;

    border-radius:12px;

    background:#eee;

}


.no-image {

    width:150px;

    height:150px;

    background:#eee;

    border-radius:12px;

    display:flex;

    align-items:center;

    justify-content:center;

    font-size:50px;

}


.product-name {

    font-size:22px;

    font-weight:bold;

    margin-bottom:10px;

}


.price {

    color:#ff6b35;

    font-size:25px;

    font-weight:bold;

}


/* INFO */

.info {

    display:grid;

    grid-template-columns:
        1fr 1fr;

    gap:15px;

}


.info-box {

    background:#f8f9fa;

    padding:15px;

    border-radius:10px;

}


.label {

    color:#888;

    font-size:13px;

    margin-bottom:5px;

}


.value {

    font-weight:bold;

}


/* CUSTOMER */

.customer {

    background:#fff8f4;

    border:1px solid #ffe1d5;

}


.customer h2 {

    color:#ff6b35;

}


.customer p {

    margin:8px 0;

}


/* SHIPPING */

.shipping-box {

    background:#f4f8ff;

    border:1px solid #dce9ff;

}


.shipping-box h2 {

    color:#2563eb;

}


/* FORM */

.edit-box {

    background:#f8f9fa;

    padding:20px;

    border-radius:12px;

}


.form-group {

    margin-bottom:16px;

}


.form-group label {

    display:block;

    font-weight:bold;

    margin-bottom:7px;

}


.form-group input,
.form-group select {

    width:100%;

    padding:12px;

    border:1px solid #ddd;

    border-radius:8px;

    font-size:15px;

    background:#fff;

}


.form-group input:focus,
.form-group select:focus {

    outline:none;

    border-color:#ff6b35;

}


.save-button {

    width:100%;

    border:none;

    background:#ff6b35;

    color:white;

    padding:13px;

    border-radius:9px;

    font-size:16px;

    font-weight:bold;

    cursor:pointer;

}


.save-button:hover {

    background:#e85b28;

}


/* SLIP */

.slip-box {

    text-align:center;

}


.slip-image {

    display:block;

    max-width:100%;

    max-height:700px;

    margin:20px auto;

    border-radius:12px;

    border:1px solid #ddd;

    box-shadow:
        0 3px 15px
        rgba(0,0,0,.1);

}


.open-slip {

    display:inline-block;

    background:#ff6b35;

    color:#fff;

    padding:10px 18px;

    border-radius:8px;

    text-decoration:none;

    font-weight:bold;

}


/* PAYMENT */

.payment-status {

    margin-top:20px;

    padding:15px;

    border-radius:10px;

    font-weight:bold;

}


.payment-pending {

    background:#fff3cd;

    color:#856404;

}


.payment-paid {

    background:#d1e7dd;

    color:#0f5132;

}


.verify-button {

    width:100%;

    margin-top:15px;

    border:none;

    background:#198754;

    color:white;

    padding:14px;

    border-radius:9px;

    font-size:16px;

    font-weight:bold;

    cursor:pointer;

}


.verify-button:hover {

    background:#157347;

}


.warning {

    background:#fff3cd;

    color:#856404;

    padding:15px;

    border-radius:10px;

    text-align:left;

}


/* MOBILE */

@media(max-width:700px) {

    .navbar {

        padding:0 15px;

    }


    .product {

        flex-direction:column;

        align-items:flex-start;

    }


    .info {

        grid-template-columns:1fr;

    }


    .header {

        flex-direction:column;

        align-items:flex-start;

        gap:10px;

    }

}

</style>

</head>


<body>


<nav class="navbar">

    <a
        href="index.php"
        class="logo"
    >
        PD Shop
    </a>


    <div>

        <a href="seller_orders.php">
            📦 ออเดอร์
        </a>

        <a href="index.php">
            🏠 หน้าหลัก
        </a>

    </div>

</nav>


<div class="container">


<a
    href="seller_orders.php"
    class="back"
>
    ← กลับไปออเดอร์ที่ได้รับ
</a>


<?php if (isset($_GET['verified'])): ?>

    <div
        style="
            background:#d1e7dd;
            color:#0f5132;
            padding:15px;
            border-radius:10px;
            margin-bottom:20px;
            font-weight:bold;
        "
    >
        ✅ ตรวจสอบสลิปและยืนยันการชำระเงินเรียบร้อยแล้ว
    </div>

<?php endif; ?>


<?php if (isset($_GET['updated'])): ?>

    <div
        style="
            background:#d1e7dd;
            color:#0f5132;
            padding:15px;
            border-radius:10px;
            margin-bottom:20px;
            font-weight:bold;
        "
    >
        ✅ บันทึกสถานะและข้อมูลการจัดส่งเรียบร้อยแล้ว
    </div>

<?php endif; ?>


<!-- =====================================================
     ORDER
     ===================================================== -->

<div class="card">


    <div class="header">

        <h1>
            📦 ออเดอร์
            #<?= (int)$order['id'] ?>
        </h1>


        <span
            class="status
            <?= htmlspecialchars(
                $status_class
            ) ?>"
        >

            <?= htmlspecialchars(
                $status_text
            ) ?>

        </span>

    </div>


    <div class="product">


        <?php if ($product_url !== ''): ?>

            <img
                src="<?= htmlspecialchars(
                    $product_url
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


            <div>

                รหัสสินค้า:
                #<?= (int)$order['product_id'] ?>

            </div>


            <br>


            <div class="price">

                ฿<?= number_format(
                    (float)$order['price'],
                    2
                ) ?>

            </div>

        </div>

    </div>

</div>


<!-- =====================================================
     ORDER STATUS / SHIPPING
     ===================================================== -->

<div class="card">


    <h2>
        ✏️ จัดการออเดอร์
    </h2>


    <br>


    <div class="edit-box">


        <form
            method="POST"
            action="order_detail.php?id=<?= (int)$order['id'] ?>"
        >


            <input
                type="hidden"
                name="order_id"
                value="<?= (int)$order['id'] ?>"
            >


            <input
                type="hidden"
                name="action"
                value="update_order"
            >


            <div class="form-group">

                <label>
                    🔄 สถานะออเดอร์
                </label>


                <select name="status">


                    <option
                        value="รอตรวจสอบ"
                        <?= (
                            $status ===
                            'รอตรวจสอบ'
                            ||
                            $status ===
                            'pending'
                        )
                            ? 'selected'
                            : ''
                        ?>
                    >
                        รอตรวจสอบ
                    </option>


                    <option
                        value="paid"
                        <?= $status === 'paid'
                            ? 'selected'
                            : ''
                        ?>
                    >
                        ชำระเงินแล้ว
                    </option>


                    <option
                        value="shipping"
                        <?= (
                            $status ===
                            'shipping'
                        )
                            ? 'selected'
                            : ''
                        ?>
                    >
                        กำลังจัดส่ง
                    </option>


                    <option
                        value="completed"
                        <?= (
                            $status ===
                            'completed'
                        )
                            ? 'selected'
                            : ''
                        ?>
                    >
                        สำเร็จ
                    </option>


                    <option
                        value="cancelled"
                        <?= (
                            $status ===
                            'cancelled'
                        )
                            ? 'selected'
                            : ''
                        ?>
                    >
                        ยกเลิก
                    </option>


                </select>

            </div>


            <div class="form-group">

                <label>
                    🚚 บริษัทขนส่ง
                </label>


                <input
                    type="text"
                    name="shipping_company"
                    value="<?= htmlspecialchars(
                        $order[
                            'shipping_company'
                        ] ?? ''
                    ) ?>"
                    placeholder="เช่น Flash Express"
                >

            </div>


            <div class="form-group">

                <label>
                    📦 เลขพัสดุ
                </label>


                <input
                    type="text"
                    name="tracking_number"
                    value="<?= htmlspecialchars(
                        $order[
                            'tracking_number'
                        ] ?? ''
                    ) ?>"
                    placeholder="กรอกเลขพัสดุ"
                >

            </div>


            <button
                type="submit"
                class="save-button"
                onclick="
                    return confirm(
                        'ยืนยันการบันทึกข้อมูลออเดอร์?'
                    );
                "
            >

                💾 บันทึกข้อมูลออเดอร์

            </button>


        </form>

    </div>

</div>


<!-- =====================================================
     CUSTOMER
     ===================================================== -->

<div class="card customer">


    <h2>
        👤 ข้อมูลผู้ซื้อ
    </h2>


    <br>


    <p>

        <strong>
            ชื่อ:
        </strong>

        <?= htmlspecialchars(
            $order['buyer_name']
        ) ?>

    </p>


    <p>

        <strong>
            เบอร์โทร:
        </strong>

        <?= htmlspecialchars(
            $order['phone']
        ) ?>

    </p>


    <p>

        <strong>
            ที่อยู่:
        </strong>

        <?= nl2br(
            htmlspecialchars(
                $order['address']
            )
        ) ?>

    </p>


</div>


<!-- =====================================================
     SHIPPING DISPLAY
     ===================================================== -->

<div class="card shipping-box">


    <h2>
        🚚 ข้อมูลการจัดส่ง
    </h2>


    <br>


    <p>

        <strong>
            บริษัทขนส่ง:
        </strong>

        <?= !empty(
            $order['shipping_company']
        )

            ? htmlspecialchars(
                $order[
                    'shipping_company'
                ]
            )

            : 'ยังไม่ได้ระบุ'
        ?>

    </p>


    <p>

        <strong>
            เลขพัสดุ:
        </strong>

        <?= !empty(
            $order['tracking_number']
        )

            ? htmlspecialchars(
                $order[
                    'tracking_number'
                ]
            )

            : 'ยังไม่ได้ระบุ'
        ?>

    </p>


</div>


<!-- =====================================================
     PAYMENT SLIP
     ===================================================== -->

<div class="card slip-box">


    <h2>
        💳 หลักฐานการชำระเงิน
    </h2>


    <?php if (
        $slip !== ''
        &&
        $slip_exists
    ): ?>


        <a
            href="<?= htmlspecialchars(
                $slip_url
            ) ?>"
            target="_blank"
        >

            <img
                src="<?= htmlspecialchars(
                    $slip_url
                ) ?>"
                class="slip-image"
                alt="สลิปการชำระเงิน"
            >

        </a>


        <a
            href="<?= htmlspecialchars(
                $slip_url
            ) ?>"
            target="_blank"
            class="open-slip"
        >

            🔍 เปิดรูปขนาดเต็ม

        </a>


        <?php if (
            $payment_status === 'paid'
        ): ?>


            <div
                class="
                    payment-status
                    payment-paid
                "
            >

                ✅ ตรวจสอบการชำระเงินเสร็จแล้ว

            </div>


        <?php else: ?>


            <div
                class="
                    payment-status
                    payment-pending
                "
            >

                ⏳ รอตรวจสอบสลิป

            </div>


            <form
                method="POST"
                action="order_detail.php?id=<?= (int)$order['id'] ?>"
            >

                <input
                    type="hidden"
                    name="order_id"
                    value="<?= (int)$order['id'] ?>"
                >


                <input
                    type="hidden"
                    name="action"
                    value="verify_payment"
                >


                <button
                    type="submit"
                    class="verify-button"
                    onclick="
                        return confirm(
                            'ตรวจสอบสลิปเรียบร้อยแล้ว และได้รับเงินถูกต้องใช่หรือไม่?'
                        );
                    "
                >

                    ✅ ตรวจสอบเสร็จแล้ว

                </button>

            </form>


        <?php endif; ?>


    <?php elseif ($slip !== ''): ?>


        <div class="warning">

            ❌ พบข้อมูลสลิปในฐานข้อมูล
            แต่ไม่พบไฟล์รูป

            <br><br>

            <strong>
                ชื่อไฟล์:
            </strong>

            <?= htmlspecialchars(
                $slip
            ) ?>

        </div>


    <?php else: ?>


        <div class="warning">

            ⚠️ ออเดอร์นี้ยังไม่มีหลักฐานการชำระเงิน

        </div>


    <?php endif; ?>


</div>


</div>


</body>

</html>