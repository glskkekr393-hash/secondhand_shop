```php
/* =========================================================
   PAYMENT SLIP
   ========================================================= */

$slip = trim($order['payment_slip'] ?? '');

$slip_url = '';
$slip_exists = false;

if ($slip !== '') {

    // แปลง \ เป็น /
    $slip = str_replace('\\', '/', $slip);

    // ตัด / ด้านหน้า
    $slip = ltrim($slip, '/');

    /*
     * ฐานข้อมูลของคุณเก็บแบบ:
     * uploads/slip_order_4_1788125181.webp
     *
     * ดังนั้นใช้ path นี้ตรง ๆ
     */

    if (strpos($slip, 'uploads/') === 0) {

        $slip_url = $slip;

    } else {

        $slip_url = 'uploads/' . $slip;

    }

    /*
     * ตรวจสอบไฟล์จริงใน Railway
     *
     * __DIR__ คือ /var/www/html
     *
     * ดังนั้นจะตรวจ:
     * /var/www/html/uploads/ชื่อไฟล์
     */

    $full_slip_path = __DIR__ . '/' . $slip_url;

    if (file_exists($full_slip_path)) {

        $slip_exists = true;

    }

}
```

แล้วส่วนแสดงสลิปใน HTML ให้ใช้ชุดนี้แทนของเดิม:

```php
<!-- =====================================================
     PAYMENT SLIP
===================================================== -->

<div class="card slip-box">

    <h2>
        💳 หลักฐานการชำระเงิน
    </h2>

    <?php if ($slip !== '' && $slip_exists): ?>

        <a
            href="<?= htmlspecialchars($slip_url) ?>"
            target="_blank"
        >

            <img
                src="<?= htmlspecialchars($slip_url) ?>"
                class="slip-image"
                alt="สลิปการชำระเงิน"
            >

        </a>

        <br>

        <a
            href="<?= htmlspecialchars($slip_url) ?>"
            target="_blank"
            class="open-slip"
        >
            🔍 เปิดรูปขนาดเต็ม
        </a>


        <?php if ($payment_status === 'paid'): ?>

            <div class="payment-status payment-paid">

                ✅ ตรวจสอบการชำระเงินเสร็จแล้ว

            </div>

        <?php else: ?>

            <div class="payment-status payment-pending">

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
                    onclick="return confirm(
                        'ตรวจสอบสลิปเรียบร้อยแล้ว และได้รับเงินถูกต้องใช่หรือไม่?'
                    );"
                >

                    ✅ ตรวจสอบเสร็จแล้ว

                </button>

            </form>

        <?php endif; ?>


    <?php elseif ($slip !== ''): ?>

        <div class="warning">

            ❌ พบชื่อไฟล์สลิปในฐานข้อมูล
            แต่ไม่พบไฟล์รูปในเซิร์ฟเวอร์

            <br><br>

            <strong>
                ชื่อไฟล์:
            </strong>

            <?= htmlspecialchars($slip) ?>

            <br><br>

            <strong>
                ระบบตรวจสอบ:
            </strong>

            <br>

            <?= htmlspecialchars(
                __DIR__ . '/' . $slip_url
            ) ?>

            <br><br>

            ⚠️ ถ้ายังขึ้นข้อความนี้ แสดงว่าไฟล์สลิป
            ไม่ได้ถูกเก็บอยู่ใน Railway จริง
            หรือไฟล์ถูกลบหลังจาก Deploy ใหม่

        </div>

    <?php else: ?>

        <div class="warning">

            ⚠️ ออเดอร์นี้ยังไม่มีหลักฐานการชำระเงิน

        </div>

    <?php endif; ?>

</div>
```
