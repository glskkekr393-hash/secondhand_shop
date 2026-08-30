<?php
require 'config.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user']['id'];

$product_id = (int)($_GET['product_id'] ?? 0);
$buyer_id   = (int)($_GET['buyer_id'] ?? 0);

if ($product_id <= 0) {
    die("ไม่พบสินค้า");
}


/* =====================================================
   ดึงข้อมูลสินค้า
===================================================== */

$stmt = $conn->prepare("
    SELECT
        p.*,
        u.id AS seller_id,
        u.name AS seller_name
    FROM products p
    JOIN users u
        ON p.seller_id = u.id
    WHERE p.id = ?
      AND p.status IN ('approved', 'sold')
");

$stmt->bind_param("i", $product_id);
$stmt->execute();

$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    die("ไม่พบสินค้า");
}

$seller_id = (int)$product['seller_id'];


/* =====================================================
   หาคู่สนทนา
===================================================== */

if ($user_id === $seller_id) {

    $other_user_id = $buyer_id;

} else {

    $other_user_id = $seller_id;
}


if ($other_user_id <= 0) {
    die("ไม่พบผู้ใช้สำหรับการสนทนา");
}


/* =====================================================
   ดึงข้อมูลคู่สนทนา
===================================================== */

$stmt = $conn->prepare("
    SELECT id, name
    FROM users
    WHERE id = ?
");

$stmt->bind_param("i", $other_user_id);
$stmt->execute();

$other_user = $stmt->get_result()->fetch_assoc();

if (!$other_user) {
    die("ไม่พบผู้ใช้");
}


/* =====================================================
   AJAX GET MESSAGES
===================================================== */

if (
    isset($_GET['ajax']) &&
    $_GET['ajax'] === 'messages'
) {

    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');


    /*
     * อ่านข้อความที่อีกฝ่ายส่งมา
     */

    $stmt = $conn->prepare("
        UPDATE messages
        SET is_read = 1
        WHERE product_id = ?
          AND sender_id = ?
          AND receiver_id = ?
          AND is_read = 0
    ");

    $stmt->bind_param(
        "iii",
        $product_id,
        $other_user_id,
        $user_id
    );

    $stmt->execute();


    /*
     * ดึงข้อความ
     */

    $stmt = $conn->prepare("
        SELECT
            m.id,
            m.sender_id,
            m.receiver_id,
            m.message,
            m.created_at,
            u.name AS sender_name
        FROM messages m
        JOIN users u
            ON m.sender_id = u.id
        WHERE m.product_id = ?
          AND (
                (m.sender_id = ? AND m.receiver_id = ?)
                OR
                (m.sender_id = ? AND m.receiver_id = ?)
              )
        ORDER BY m.id ASC
    ");

    $stmt->bind_param(
        "iiiii",
        $product_id,
        $user_id,
        $other_user_id,
        $other_user_id,
        $user_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $data = [];

    while ($row = $result->fetch_assoc()) {

        $data[] = [
            'id' => (int)$row['id'],
            'sender_id' => (int)$row['sender_id'],
            'receiver_id' => (int)$row['receiver_id'],
            'sender_name' => $row['sender_name'],
            'message' => $row['message'],
            'created_at' => $row['created_at'],
            'is_mine' => ((int)$row['sender_id'] === $user_id)
        ];
    }

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/* =====================================================
   AJAX SEND MESSAGE
===================================================== */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['ajax_send'])
) {

    header('Content-Type: application/json; charset=utf-8');

    $message = trim($_POST['message'] ?? '');

    if ($message === '') {

        echo json_encode([
            'success' => false,
            'message' => 'กรุณาพิมพ์ข้อความ'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }


    $stmt = $conn->prepare("
        INSERT INTO messages
        (
            sender_id,
            receiver_id,
            product_id,
            message,
            is_read
        )
        VALUES (?, ?, ?, ?, 0)
    ");

    $stmt->bind_param(
        "iiis",
        $user_id,
        $other_user_id,
        $product_id,
        $message
    );


    if (!$stmt->execute()) {

        echo json_encode([
            'success' => false,
            'message' => 'ส่งข้อความไม่สำเร็จ'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }


    echo json_encode([
        'success' => true,
        'id' => $stmt->insert_id
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/* =====================================================
   อ่านข้อความตอนเปิดห้อง
===================================================== */

$stmt = $conn->prepare("
    UPDATE messages
    SET is_read = 1
    WHERE product_id = ?
      AND sender_id = ?
      AND receiver_id = ?
      AND is_read = 0
");

$stmt->bind_param(
    "iii",
    $product_id,
    $other_user_id,
    $user_id
);

$stmt->execute();


/* =====================================================
   ดึงข้อความครั้งแรก
===================================================== */

$stmt = $conn->prepare("
    SELECT
        m.*,
        u.name AS sender_name
    FROM messages m
    JOIN users u
        ON m.sender_id = u.id
    WHERE m.product_id = ?
      AND (
            (m.sender_id = ? AND m.receiver_id = ?)
            OR
            (m.sender_id = ? AND m.receiver_id = ?)
          )
    ORDER BY m.id ASC
");

$stmt->bind_param(
    "iiiii",
    $product_id,
    $user_id,
    $other_user_id,
    $other_user_id,
    $user_id
);

$stmt->execute();

$messages = $stmt->get_result();

?>


<!doctype html>

<html lang="th">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1"
>

<title>แชท - PD Shop</title>


<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<style>

body {
    background:#f5f6f8;
}

.chat-container {
    max-width:900px;
    margin:auto;
}

.chat-card {
    border:0;
    border-radius:18px;
    overflow:hidden;
}

.product-header {
    background:white;
    padding:20px;
    border-bottom:1px solid #eee;
}

.product-image {
    width:80px;
    height:80px;
    object-fit:cover;
    border-radius:12px;
    background:#e9ecef;
}

.chat-area {
    height:500px;
    overflow-y:auto;
    background:#f1f3f5;
    padding:20px;
}

.message-row {
    display:flex;
    margin-bottom:14px;
}

.message-row.mine {
    justify-content:flex-end;
}

.message-bubble {
    max-width:70%;
    padding:10px 15px;
    border-radius:18px;
    background:white;
    box-shadow:0 1px 3px rgba(0,0,0,.08);
    word-break:break-word;
}

.message-row.mine .message-bubble {
    background:#212529;
    color:white;
}

.sender-name {
    font-size:12px;
    opacity:.7;
    margin-bottom:3px;
}

.message-time {
    font-size:10px;
    opacity:.6;
    margin-top:5px;
}

.chat-input {
    background:white;
    padding:15px;
    border-top:1px solid #eee;
}

.sold {
    color:#dc3545;
    font-weight:bold;
}

.available {
    color:#198754;
    font-weight:bold;
}

#sendButton {
    min-width:80px;
}

.typing-status {
    font-size:12px;
    color:#6c757d;
    min-height:18px;
}

.new-message {
    animation:messageAppear .2s ease;
}

@keyframes messageAppear {

    from {
        opacity:0;
        transform:translateY(8px);
    }

    to {
        opacity:1;
        transform:translateY(0);
    }

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


<div>

<a
    href="messages.php"
    class="btn btn-outline-dark me-2"
>
💬 ข้อความ
</a>


<a
    href="index.php"
    class="btn btn-dark"
>
หน้าหลัก
</a>

</div>

</div>

</nav>


<!-- =====================================================
     CONTENT
===================================================== -->

<div class="container py-4">

<div class="chat-container">


<div class="card chat-card shadow-sm">


<!-- =====================================================
     PRODUCT HEADER
===================================================== -->

<div class="product-header">

<div class="d-flex align-items-center">

<?php if (!empty($product['image'])): ?>

<img
    src="<?= htmlspecialchars($product['image']) ?>"
    class="product-image me-3"
>

<?php else: ?>

<div
    class="product-image me-3 d-flex align-items-center justify-content-center"
    style="font-size:40px"
>
📦
</div>

<?php endif; ?>


<div class="flex-grow-1">

<h5 class="fw-bold mb-1">

<?= htmlspecialchars(
    $product['name']
) ?>

</h5>


<div class="text-danger fw-bold">

฿<?= number_format(
    $product['price'],
    2
) ?>

</div>


<?php if ($product['status'] === 'sold'): ?>

<div class="sold">
🔴 สินค้าขายแล้ว
</div>

<?php else: ?>

<div class="available">
🟢 สินค้ายังขายอยู่
</div>

<?php endif; ?>


<div class="text-secondary">

แชทกับ:
<strong>

<?= htmlspecialchars(
    $other_user['name']
) ?>

</strong>

</div>

</div>

</div>

</div>


<!-- =====================================================
     CHAT AREA
===================================================== -->

<div
    class="chat-area"
    id="chatArea"
>

<?php if ($messages->num_rows === 0): ?>

<div
    class="text-center text-secondary py-5"
    id="emptyMessage"
>

<div style="font-size:60px">
💬
</div>

<h5 class="mt-3">
เริ่มการสนทนา
</h5>

<p>
ส่งข้อความหา
<?= htmlspecialchars($other_user['name']) ?>
</p>

</div>

<?php else: ?>


<?php while ($m = $messages->fetch_assoc()): ?>

<?php
$is_mine =
    ((int)$m['sender_id'] === $user_id);
?>

<div
    class="message-row
    <?= $is_mine ? 'mine' : '' ?>"
    data-message-id="<?= (int)$m['id'] ?>"
>

<div class="message-bubble">

<div class="sender-name">

<?= htmlspecialchars(
    $m['sender_name']
) ?>

</div>


<div>

<?= nl2br(
    htmlspecialchars(
        $m['message']
    )
) ?>

</div>


<div class="message-time">

<?= htmlspecialchars(
    $m['created_at']
) ?>

</div>

</div>

</div>

<?php endwhile; ?>


<?php endif; ?>

</div>


<!-- =====================================================
     TYPING / STATUS
===================================================== -->

<div class="px-3 pt-2 bg-white">

<div
    id="connectionStatus"
    class="typing-status"
>
🟢 เชื่อมต่อแล้ว
</div>

</div>


<!-- =====================================================
     SEND MESSAGE
===================================================== -->

<div class="chat-input">

<form
    id="messageForm"
    class="d-flex gap-2"
>

<input
    type="text"
    name="message"
    id="messageInput"
    class="form-control"
    placeholder="พิมพ์ข้อความ..."
    autocomplete="off"
    required
>

<button
    type="submit"
    class="btn btn-dark px-4"
    id="sendButton"
>
ส่ง
</button>

</form>

</div>


</div>


<!-- =====================================================
     BACK
===================================================== -->

<div class="mt-3">

<?php if ($user_id === $seller_id): ?>

<a
    href="seller_orders.php"
    class="btn btn-outline-secondary"
>
← กลับไปออเดอร์ที่ได้รับ
</a>

<?php else: ?>

<a
    href="my_orders.php"
    class="btn btn-outline-secondary"
>
← กลับไปออเดอร์ของฉัน
</a>

<?php endif; ?>

</div>


</div>

</div>


<script>

/* =====================================================
   ตัวแปร
===================================================== */

const chatArea =
    document.getElementById("chatArea");

const messageForm =
    document.getElementById("messageForm");

const messageInput =
    document.getElementById("messageInput");

const sendButton =
    document.getElementById("sendButton");

const connectionStatus =
    document.getElementById("connectionStatus");


let lastMessageId = 0;
let firstLoad = true;
let loadingMessages = false;


/* =====================================================
   Escape HTML
===================================================== */

function escapeHtml(text)
{
    const div =
        document.createElement("div");

    div.textContent =
        text ?? "";

    return div.innerHTML;
}


/* =====================================================
   เลื่อนลงล่าง
===================================================== */

function scrollToBottom()
{
    chatArea.scrollTop =
        chatArea.scrollHeight;
}


/* =====================================================
   สร้างข้อความ
===================================================== */

function createMessage(message)
{

    const row =
        document.createElement("div");

    row.className =
        "message-row"
        + (
            message.is_mine
                ? " mine"
                : ""
        )
        + " new-message";

    row.dataset.messageId =
        message.id;


    const bubble =
        document.createElement("div");

    bubble.className =
        "message-bubble";


    const sender =
        document.createElement("div");

    sender.className =
        "sender-name";

    sender.textContent =
        message.sender_name;


    const text =
        document.createElement("div");

    text.innerHTML =
        escapeHtml(
            message.message
        ).replace(
            /\n/g,
            "<br>"
        );


    const time =
        document.createElement("div");

    time.className =
        "message-time";

    time.textContent =
        message.created_at;


    bubble.appendChild(sender);
    bubble.appendChild(text);
    bubble.appendChild(time);

    row.appendChild(bubble);

    return row;
}


/* =====================================================
   โหลดข้อความ
===================================================== */

async function loadMessages()
{

    if (loadingMessages) {
        return;
    }

    loadingMessages = true;


    try {

        const response =
            await fetch(
                "chat.php?product_id=<?= $product_id ?>&buyer_id=<?= $other_user_id ?>&ajax=messages&_="
                + Date.now(),
                {
                    method:"GET",
                    cache:"no-store",
                    headers:{
                        "Cache-Control":"no-cache",
                        "Pragma":"no-cache"
                    }
                }
            );


        if (!response.ok) {
            throw new Error(
                "HTTP " + response.status
            );
        }


        const messages =
            await response.json();


        if (!Array.isArray(messages)) {
            throw new Error(
                "ข้อมูลไม่ถูกต้อง"
            );
        }


        connectionStatus.innerHTML =
            "🟢 เชื่อมต่อแล้ว";


        /*
         * ตรวจว่าผู้ใช้กำลังอยู่ใกล้ด้านล่างหรือไม่
         */

        const distanceFromBottom =
            chatArea.scrollHeight
            - chatArea.scrollTop
            - chatArea.clientHeight;

        const isNearBottom =
            distanceFromBottom < 120;


        /*
         * โหลดครั้งแรก
         */

        if (firstLoad) {

            chatArea.innerHTML = "";

            messages.forEach(
                function(message)
                {
                    chatArea.appendChild(
                        createMessage(message)
                    );
                }
            );

            if (messages.length > 0) {

                lastMessageId =
                    Number(
                        messages[
                            messages.length - 1
                        ].id
                    );

            }

            firstLoad = false;

            scrollToBottom();

            return;
        }


        /*
         * ถ้ามีข้อความใหม่
         */

        let hasNewMessage = false;


        messages.forEach(
            function(message)
            {

                const id =
                    Number(message.id);


                if (
                    id > lastMessageId
                ) {

                    /*
                     * ลบหน้าเริ่มการสนทนา
                     */

                    const empty =
                        document.getElementById(
                            "emptyMessage"
                        );

                    if (empty) {
                        empty.remove();
                    }


                    /*
                     * ป้องกันข้อความซ้ำ
                     */

                    if (
                        !document.querySelector(
                            '[data-message-id="' +
                            id +
                            '"]'
                        )
                    ) {

                        chatArea.appendChild(
                            createMessage(message)
                        );

                        hasNewMessage = true;

                    }


                    if (
                        id > lastMessageId
                    ) {

                        lastMessageId = id;

                    }

                }

            }
        );


        /*
         * ถ้ามีข้อความใหม่
         * และเราอยู่ด้านล่าง
         */

        if (
            hasNewMessage &&
            isNearBottom
        ) {

            scrollToBottom();

        }


    }
    catch(error) {

        console.log(
            "Chat realtime error:",
            error
        );

        connectionStatus.innerHTML =
            "🔴 กำลังเชื่อมต่อใหม่...";

    }
    finally {

        loadingMessages = false;

    }

}


/* =====================================================
   ส่งข้อความ AJAX
===================================================== */

messageForm.addEventListener(
    "submit",
    async function(event)
    {

        event.preventDefault();


        const message =
            messageInput.value.trim();


        if (!message) {
            return;
        }


        sendButton.disabled = true;

        sendButton.innerText =
            "กำลังส่ง...";


        try {

            const formData =
                new FormData();

            formData.append(
                "ajax_send",
                "1"
            );

            formData.append(
                "message",
                message
            );


            const response =
                await fetch(
                    "chat.php?product_id=<?= $product_id ?>&buyer_id=<?= $other_user_id ?>",
                    {
                        method:"POST",
                        body:formData,
                        cache:"no-store"
                    }
                );


            const result =
                await response.json();


            if (!result.success) {

                alert(
                    result.message ||
                    "ส่งข้อความไม่สำเร็จ"
                );

                return;
            }


            /*
             * ล้างช่องพิมพ์
             */

            messageInput.value = "";


            /*
             * โหลดข้อความทันที
             */

            await loadMessages();


            /*
             * เลื่อนลง
             */

            scrollToBottom();


            /*
             * กลับไปโฟกัสช่องข้อความ
             */

            messageInput.focus();

        }
        catch(error) {

            console.log(
                "Send message error:",
                error
            );

            alert(
                "ไม่สามารถส่งข้อความได้"
            );

        }
        finally {

            sendButton.disabled = false;

            sendButton.innerText =
                "ส่ง";

        }

    }
);


/* =====================================================
   Enter = ส่งข้อความ
   Shift + Enter = ขึ้นบรรทัดใหม่
===================================================== */

messageInput.addEventListener(
    "keydown",
    function(event)
    {

        if (
            event.key === "Enter" &&
            !event.shiftKey
        ) {

            event.preventDefault();

            messageForm.requestSubmit();

        }

    }
);


/* =====================================================
   ขอ Notification
===================================================== */

if (
    "Notification" in window &&
    Notification.permission === "default"
) {

    Notification.requestPermission();

}


/* =====================================================
   เริ่มต้น
===================================================== */

loadMessages();

scrollToBottom();


/* =====================================================
   REAL-TIME ทุก 1 วินาที
===================================================== */

setInterval(
    loadMessages,
    1000
);

</script>


</body>

</html>
