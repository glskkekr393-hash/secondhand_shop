<?php
require 'config.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user']['id'];


/* =====================================================
   ดึงรายการแชท
   1 คน = 1 ห้อง
   ใช้ข้อความล่าสุด
===================================================== */

function getChats($conn, $user_id)
{
    $sql = "
        SELECT
            m.id,
            m.product_id,
            m.sender_id,
            m.receiver_id,
            m.message,
            m.created_at,

            CASE
                WHEN m.sender_id = ?
                THEN m.receiver_id
                ELSE m.sender_id
            END AS other_user_id,

            u.name AS other_user_name,

            p.name AS product_name,
            p.image AS product_image,

            (
                SELECT COUNT(*)
                FROM messages unread
                WHERE unread.sender_id =
                    CASE
                        WHEN m.sender_id = ?
                        THEN m.receiver_id
                        ELSE m.sender_id
                    END
                AND unread.receiver_id = ?
                AND unread.is_read = 0
            ) AS unread_count

        FROM messages m

        JOIN users u
            ON u.id =
                CASE
                    WHEN m.sender_id = ?
                    THEN m.receiver_id
                    ELSE m.sender_id
                END

        LEFT JOIN products p
            ON p.id = m.product_id

        WHERE m.id IN (

            SELECT MAX(m2.id)

            FROM messages m2

            WHERE
                m2.sender_id = ?
                OR
                m2.receiver_id = ?

            GROUP BY
                CASE
                    WHEN m2.sender_id = ?
                    THEN m2.receiver_id
                    ELSE m2.sender_id
                END
        )

        ORDER BY m.created_at DESC
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return [];
    }

    $stmt->bind_param(
        "iiiiiii",
        $user_id,
        $user_id,
        $user_id,
        $user_id,
        $user_id,
        $user_id,
        $user_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $chats = [];

    while ($row = $result->fetch_assoc()) {

        if ((int)$row['other_user_id'] === $user_id) {
            continue;
        }

        $chats[] = $row;
    }

    $stmt->close();

    return $chats;
}


/* =====================================================
   AJAX REAL-TIME
===================================================== */

if (
    isset($_GET['ajax']) &&
    $_GET['ajax'] === '1'
) {

    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');

    echo json_encode(
        getChats($conn, $user_id),
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/* =====================================================
   โหลดครั้งแรก
===================================================== */

$chats = getChats($conn, $user_id);

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
ข้อความ - PD Shop
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

    --text: #172033;

    --muted: #6c757d;

    --border: #e5e7eb;

    --nav: rgba(255,255,255,.92);

    --shadow: rgba(0,0,0,.10);

    --hover: #fafafa;

}


html.dark-theme {

    --bg: #09090b;

    --card: #18181b;

    --text: #f4f4f5;

    --muted: #a1a1aa;

    --border: #3f3f46;

    --nav: rgba(24,24,27,.92);

    --shadow: rgba(0,0,0,.45);

    --hover: #202023;

}


/* =====================================================
   BODY
===================================================== */

body {

    background: var(--bg);

    color: var(--text);

    transition:
        background .4s ease,
        color .4s ease;

}


/* =====================================================
   NAVBAR
===================================================== */

.navbar {

    background: var(--nav) !important;

    backdrop-filter: blur(18px);

    -webkit-backdrop-filter: blur(18px);

    border-bottom:
        1px solid var(--border);

}


.navbar-brand {

    color: var(--text) !important;

    transition:
        transform .25s ease;

}


.navbar-brand:hover {

    transform:
        translateY(-2px);

}


/* =====================================================
   NAV BUTTON
===================================================== */

.nav-btn {

    color: var(--text);

    border-color: var(--border);

    transition:
        transform .2s ease,
        background .2s ease,
        color .2s ease;

}


.nav-btn:hover {

    background: var(--text);

    color: var(--bg);

    transform:
        translateY(-2px);

}


/* =====================================================
   THEME BUTTON
===================================================== */

.theme-toggle {

    width: 42px;

    height: 42px;

    border-radius: 50%;

    border:
        1px solid var(--border);

    background: var(--card);

    color: var(--text);

    cursor: pointer;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    font-size: 18px;

    transition:
        transform .3s ease,
        background .3s ease;

}


.theme-toggle:hover {

    transform:
        rotate(20deg)
        scale(1.08);

}


/* =====================================================
   CHAT HEADER
===================================================== */

.chat-header {

    position: relative;

    overflow: hidden;

    background:
        linear-gradient(
            135deg,
            #111827,
            #27272a
        );

    color: white;

    border-radius: 24px;

    padding: 30px;

    margin-bottom: 25px;

    box-shadow:
        0 18px 45px
        var(--shadow);

}


.chat-header::before {

    content: "";

    position: absolute;

    width: 190px;

    height: 190px;

    border-radius: 50%;

    right: -60px;

    top: -90px;

    background:
        rgba(99,102,241,.18);

    animation:
        headerFloat 5s ease-in-out infinite;

}


.chat-header::after {

    content: "";

    position: absolute;

    width: 100px;

    height: 100px;

    border-radius: 50%;

    left: -30px;

    bottom: -55px;

    background:
        rgba(25,135,84,.15);

}


.chat-header-content {

    position: relative;

    z-index: 2;

}


.chat-label {

    font-size: 13px;

    font-weight: 700;

    letter-spacing: 4px;

    color: #a5b4fc;

    margin-bottom: 7px;

}


@keyframes headerFloat {

    0%,100% {

        transform:
            translateY(0)
            scale(1);

    }

    50% {

        transform:
            translateY(-12px)
            scale(1.08);

    }

}


/* =====================================================
   CHAT COUNT
===================================================== */

.chat-count {

    display: inline-flex;

    align-items: center;

    gap: 7px;

    padding: 9px 15px;

    border-radius: 999px;

    background:
        rgba(255,255,255,.10);

    border:
        1px solid
        rgba(255,255,255,.18);

    font-weight: 700;

}


/* =====================================================
   CHAT LIST
===================================================== */

.chat-list {

    max-width: 900px;

    margin: auto;

}


/* =====================================================
   CHAT ITEM
===================================================== */

.chat-item {

    position: relative;

    overflow: hidden;

    background: var(--card);

    color: var(--text);

    border:
        1px solid var(--border);

    border-radius: 20px;

    padding: 17px;

    margin-bottom: 13px;

    box-shadow:
        0 7px 24px
        var(--shadow);

    transition:
        transform .3s cubic-bezier(.2,.8,.2,1),
        box-shadow .3s ease,
        border-color .3s ease,
        background .3s ease;

}


.chat-item::before {

    content: "";

    position: absolute;

    width: 4px;

    height: 0;

    left: 0;

    top: 50%;

    background: #6366f1;

    border-radius: 0 5px 5px 0;

    transition:
        height .3s ease,
        top .3s ease;

}


.chat-item:hover {

    transform:
        translateY(-5px)
        translateX(2px);

    box-shadow:
        0 18px 40px
        var(--shadow);

    border-color:
        rgba(99,102,241,.35);

}


.chat-item:hover::before {

    height: 70%;

    top: 15%;

}


/* =====================================================
   UNREAD
===================================================== */

.unread-item {

    border-color:
        rgba(220,53,69,.25);

    background:
        linear-gradient(
            90deg,
            var(--card),
            rgba(220,53,69,.04)
        );

}


.unread-item::before {

    background: #dc3545;

    height: 70%;

    top: 15%;

}


.unread-item:hover {

    border-color:
        rgba(220,53,69,.45);

}


/* =====================================================
   AVATAR
===================================================== */

.avatar {

    width: 62px;

    height: 62px;

    border-radius: 50%;

    background:
        linear-gradient(
            135deg,
            #111827,
            #4b5563
        );

    color: white;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 25px;

    flex-shrink: 0;

    box-shadow:
        0 7px 18px
        rgba(0,0,0,.16);

    transition:
        transform .3s ease;

}


.chat-item:hover .avatar {

    transform:
        scale(1.08)
        rotate(-4deg);

}


/* =====================================================
   USER NAME
===================================================== */

.user-name {

    color: var(--text);

    transition:
        color .2s ease;

}


.chat-item:hover .user-name {

    color: #6366f1;

}


/* =====================================================
   PRODUCT IMAGE
===================================================== */

.product-image {

    width: 55px;

    height: 55px;

    object-fit: cover;

    border-radius: 12px;

    flex-shrink: 0;

    transition:
        transform .3s ease;

}


.chat-item:hover .product-image {

    transform:
        scale(1.06);

}


.product-placeholder {

    width: 55px;

    height: 55px;

    border-radius: 12px;

    background:
        var(--hover);

    border:
        1px solid var(--border);

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 25px;

    flex-shrink: 0;

}


/* =====================================================
   MESSAGE
===================================================== */

.last-message {

    color: var(--muted);

    white-space: nowrap;

    overflow: hidden;

    text-overflow: ellipsis;

    transition:
        color .2s ease;

}


.unread-item .last-message {

    color: var(--text);

    font-weight: 600;

}


/* =====================================================
   TIME
===================================================== */

.time {

    color: var(--muted) !important;

    white-space: nowrap;

    font-size: 12px;

}


/* =====================================================
   UNREAD BADGE
===================================================== */

.unread-badge {

    background:
        #dc3545;

    color: white;

    border-radius: 50%;

    min-width: 29px;

    height: 29px;

    padding: 0 7px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 12px;

    font-weight: bold;

    box-shadow:
        0 5px 15px
        rgba(220,53,69,.30);

    animation:
        unreadPulse 1.8s infinite;

}


@keyframes unreadPulse {

    0%,100% {

        transform: scale(1);

    }

    50% {

        transform: scale(1.08);

    }

}


/* =====================================================
   EMPTY CHAT
===================================================== */

.empty-chat {

    background: var(--card);

    color: var(--text);

    border:
        1px solid var(--border);

    border-radius: 24px;

    padding: 65px 25px;

    box-shadow:
        0 15px 35px
        var(--shadow);

}


.empty-icon {

    font-size: 75px;

    display: inline-block;

    animation:
        emptyFloat 2.5s ease-in-out infinite;

}


@keyframes emptyFloat {

    0%,100% {

        transform:
            translateY(0);

    }

    50% {

        transform:
            translateY(-9px);

    }

}


/* =====================================================
   REVEAL
===================================================== */

.reveal {

    opacity: 0;

    transform:
        translateY(20px);

    animation:
        revealIn .55s ease forwards;

}


@keyframes revealIn {

    to {

        opacity: 1;

        transform:
            translateY(0);

    }

}


/* =====================================================
   NEW MESSAGE
===================================================== */

.new-message {

    animation:
        newMessage .6s ease;

}


@keyframes newMessage {

    0% {

        transform:
            scale(.98);

        background:
            rgba(255,193,7,.18);

    }

    100% {

        transform:
            scale(1);

        background:
            var(--card);

    }

}


/* =====================================================
   MOBILE
===================================================== */

@media(max-width:768px) {

    .chat-header {

        border-radius: 18px;

        padding: 24px;

    }


    .chat-header h1 {

        font-size: 30px;

    }


    .chat-item {

        padding: 13px;

        border-radius: 17px;

    }


    .avatar {

        width: 52px;

        height: 52px;

        font-size: 21px;

    }


    .product-image,
    .product-placeholder {

        width: 48px;

        height: 48px;

    }


    .time {

        font-size: 10px;

    }

}

</style>

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


<div class="d-flex align-items-center gap-2 flex-wrap">


<button
    type="button"
    class="theme-toggle"
    id="themeToggle"
    title="เปลี่ยนธีม"
>

🌙

</button>


<a
    href="index.php"
    class="btn nav-btn"
>

หน้าหลัก

</a>


<a
    href="notifications.php"
    class="btn btn-outline-primary"
>

🔔 แจ้งเตือน

</a>


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
     CONTENT
===================================================== -->

<div class="container py-5 chat-list">


<!-- =====================================================
     HEADER
===================================================== -->

<div class="chat-header reveal">

<div class="chat-header-content">


<div
    class="d-flex
           justify-content-between
           align-items-center
           flex-wrap
           gap-3"
>


<div>

<div class="chat-label">

MESSAGES

</div>


<h1 class="fw-bold mb-1">

💬 ข้อความ

</h1>


<p class="mb-0 text-white-50">

การสนทนาทั้งหมดของคุณ

</p>

</div>


<div class="chat-count">

💬 <?= count($chats) ?> ห้อง

</div>


</div>


</div>

</div>



<!-- =====================================================
     CHAT LIST
===================================================== -->

<div id="chatList">


<?php if (count($chats) === 0): ?>


<div
    class="empty-chat text-center reveal"
    id="emptyChat"
>


<div class="empty-icon">

💬

</div>


<h4 class="mt-3 fw-bold">

ยังไม่มีการสนทนา

</h4>


<p class="text-secondary">

เมื่อมีคนติดต่อซื้อขาย
แชทจะแสดงที่นี่

</p>


<a
    href="index.php"
    class="btn btn-dark px-4"
>

🛍️ ไปดูสินค้า

</a>


</div>


<?php else: ?>


<?php foreach ($chats as $index => $chat): ?>


<?php

$other_user_id =
    (int)$chat['other_user_id'];

$product_id =
    (int)$chat['product_id'];

$unread =
    (int)$chat['unread_count'];

?>


<a
    href="chat.php?product_id=<?= $product_id ?>&buyer_id=<?= $other_user_id ?>"
    class="text-decoration-none"
>


<div
    class="chat-item
           <?= $unread > 0 ? 'unread-item' : '' ?>
           reveal"
    style="animation-delay:<?= min($index * 0.05, .5) ?>s"
>


<div class="d-flex align-items-center">


<div class="avatar me-3">

👤

</div>


<div
    class="flex-grow-1"
    style="min-width:0"
>


<div
    class="d-flex
           justify-content-between
           align-items-center
           gap-2"
>


<h5
    class="fw-bold mb-1 user-name"
>

<?= htmlspecialchars(
    $chat['other_user_name']
) ?>

</h5>


<small class="time">

<?= htmlspecialchars(
    $chat['created_at']
) ?>

</small>


</div>


<div
    class="d-flex
           align-items-center
           mb-1"
>


<?php if (!empty($chat['product_image'])): ?>


<img
    src="<?= htmlspecialchars(
        $chat['product_image']
    ) ?>"
    class="product-image me-2"
    alt=""
>


<?php else: ?>


<div class="product-placeholder me-2">

📦

</div>


<?php endif; ?>


<div
    class="small"
    style="color:var(--muted)"
>

สินค้า:

<strong
    style="color:var(--text)"
>

<?= htmlspecialchars(
    $chat['product_name'] ?? 'สินค้า'
) ?>

</strong>

</div>


</div>


<div class="last-message">

<?= htmlspecialchars(
    $chat['message']
) ?>

</div>


</div>


<div class="ms-3 unread-area">


<?php if ($unread > 0): ?>


<div class="unread-badge">

<?= $unread ?>

</div>


<?php endif; ?>


</div>


</div>


</div>


</a>


<?php endforeach; ?>


<?php endif; ?>


</div>


</div>



<!-- =====================================================
     FOOTER
===================================================== -->

<footer
    class="text-center py-5"
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



<!-- =====================================================
     JAVASCRIPT
===================================================== -->

<script>

/* =====================================================
   THEME
===================================================== */

(function () {

    const savedTheme =
        localStorage.getItem(
            'secondhand-theme'
        );


    if (savedTheme === 'dark') {

        document.documentElement
            .classList
            .add('dark-theme');

    }


    const button =
        document.getElementById(
            'themeToggle'
        );


    function updateIcon() {

        const dark =
            document.documentElement
                .classList
                .contains('dark-theme');


        button.textContent =
            dark ? '☀️' : '🌙';

    }


    updateIcon();


    button.addEventListener(
        'click',
        function () {

            document.documentElement
                .classList
                .toggle('dark-theme');


            const dark =
                document.documentElement
                    .classList
                    .contains('dark-theme');


            localStorage.setItem(
                'secondhand-theme',
                dark ? 'dark' : 'light'
            );


            updateIcon();

        }
    );

})();


/* =====================================================
   ป้องกัน HTML
===================================================== */

function escapeHtml(text)
{
    const div =
        document.createElement("div");

    div.textContent =
        text ?? "";

    return div.innerHTML;
}


function escapeAttribute(text)
{
    return String(text ?? "")
        .replace(/&/g, "&amp;")
        .replace(/"/g, "&quot;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;");
}


/* =====================================================
   Signature
===================================================== */

function makeSignature(chats)
{
    return chats.map(function(chat) {

        return [
            chat.id,
            chat.other_user_id,
            chat.message,
            chat.created_at,
            chat.unread_count
        ].join("|");

    }).join("||");
}


/* =====================================================
   แจ้งเตือน
===================================================== */

function notifyNewMessage()
{

    try {

        if (
            "Notification" in window &&
            Notification.permission === "granted"
        ) {

            new Notification(
                "💬 PD Shop",
                {
                    body:
                        "มีข้อความใหม่เข้ามา"
                }
            );

        }

    }
    catch (e) {}

}


/* =====================================================
   เสียงแจ้งเตือน
===================================================== */

function playNotificationSound()
{

    try {

        const audio =
            new Audio(
                "data:audio/wav;base64,UklGRiQAAABXQVZFZm10IBAAAAABAAEAQB8AAIA+AAACABAAZGF0YQAAAAA="
            );

        audio.play()
            .catch(() => {});

    }
    catch (e) {}

}


/* =====================================================
   REAL-TIME
===================================================== */

let lastChatSignature = "";

let firstLoad = true;

let updateRunning = false;


async function updateChats()
{

    if (updateRunning) {

        return;

    }


    updateRunning = true;


    try {

        const response =
            await fetch(
                "messages.php?ajax=1&_="
                + Date.now(),
                {
                    method: "GET",

                    cache: "no-store",

                    headers: {
                        "Cache-Control":
                            "no-cache",

                        "Pragma":
                            "no-cache"
                    }
                }
            );


        if (!response.ok) {

            throw new Error(
                "HTTP " +
                response.status
            );

        }


        const chats =
            await response.json();


        if (!Array.isArray(chats)) {

            throw new Error(
                "ข้อมูลไม่ใช่ JSON Array"
            );

        }


        const signature =
            makeSignature(chats);


        /* แจ้งเตือนเมื่อข้อมูลเปลี่ยน */

        if (
            !firstLoad &&
            signature !== lastChatSignature
        ) {

            notifyNewMessage();

            playNotificationSound();

        }


        lastChatSignature =
            signature;

        firstLoad = false;


        const chatList =
            document.getElementById(
                "chatList"
            );


        /* =================================================
           ไม่มีแชท
        ================================================= */

        if (chats.length === 0) {

            chatList.innerHTML = `

                <div
                    class="empty-chat
                           text-center
                           reveal"
                    id="emptyChat"
                >

                    <div class="empty-icon">
                        💬
                    </div>

                    <h4 class="mt-3 fw-bold">
                        ยังไม่มีการสนทนา
                    </h4>

                    <p class="text-secondary">
                        เมื่อมีคนติดต่อซื้อขาย
                        แชทจะแสดงที่นี่
                    </p>

                    <a
                        href="index.php"
                        class="btn btn-dark px-4"
                    >
                        🛍️ ไปดูสินค้า
                    </a>

                </div>

            `;

            return;

        }


        /* =================================================
           สร้างรายการแชท
        ================================================= */

        let html = "";


        chats.forEach(function(chat, index)
        {

            const unread =
                Number(
                    chat.unread_count || 0
                );


            const productId =
                Number(
                    chat.product_id || 0
                );


            const userId =
                Number(
                    chat.other_user_id || 0
                );


            const productName =
                escapeHtml(
                    chat.product_name ||
                    "สินค้า"
                );


            const message =
                escapeHtml(
                    chat.message || ""
                );


            const username =
                escapeHtml(
                    chat.other_user_name ||
                    "ผู้ใช้"
                );


            const createdAt =
                escapeHtml(
                    chat.created_at ||
                    ""
                );


            let productHTML = "";


            if (chat.product_image) {

                productHTML = `

                    <img
                        src="${escapeAttribute(
                            chat.product_image
                        )}"
                        class="product-image me-2"
                        alt=""
                    >

                `;

            }
            else {

                productHTML = `

                    <div
                        class="product-placeholder me-2"
                    >
                        📦
                    </div>

                `;

            }


            html += `

                <a
                    href="chat.php?product_id=${productId}&buyer_id=${userId}"
                    class="text-decoration-none"
                >

                    <div
                        class="chat-item
                        ${unread > 0
                            ? "unread-item"
                            : ""
                        }
                        reveal"
                        style="
                            animation-delay:
                            ${Math.min(
                                index * 0.05,
                                .5
                            )}s
                        "
                    >

                        <div
                            class="d-flex
                            align-items-center"
                        >

                            <div
                                class="avatar me-3"
                            >
                                👤
                            </div>


                            <div
                                class="flex-grow-1"
                                style="min-width:0"
                            >

                                <div
                                    class="d-flex
                                    justify-content-between
                                    align-items-center
                                    gap-2"
                                >

                                    <h5
                                        class="fw-bold
                                               mb-1
                                               user-name"
                                    >
                                        ${username}
                                    </h5>


                                    <small
                                        class="time"
                                    >
                                        ${createdAt}
                                    </small>

                                </div>


                                <div
                                    class="d-flex
                                    align-items-center
                                    mb-1"
                                >

                                    ${productHTML}


                                    <div
                                        class="small"
                                        style="
                                            color:
                                            var(--muted)
                                        "
                                    >

                                        สินค้า:

                                        <strong
                                            style="
                                                color:
                                                var(--text)
                                            "
                                        >
                                            ${productName}
                                        </strong>

                                    </div>

                                </div>


                                <div
                                    class="last-message"
                                >
                                    ${message}
                                </div>

                            </div>


                            <div
                                class="ms-3
                                       unread-area"
                            >

                                ${
                                    unread > 0

                                    ?

                                    `
                                    <div
                                        class="unread-badge"
                                    >
                                        ${unread}
                                    </div>
                                    `

                                    :

                                    ``
                                }

                            </div>

                        </div>

                    </div>

                </a>

            `;

        });


        /*
         * อัปเดตเฉพาะเมื่อข้อมูลเปลี่ยน
         */

        if (
            chatList.innerHTML !== html
        ) {

            chatList.innerHTML =
                html;

        }

    }
    catch(error) {

        console.log(
            "Real-time chat error:",
            error
        );

    }
    finally {

        updateRunning = false;

    }

}


/* =====================================================
   ขอ Permission
===================================================== */

if (
    "Notification" in window &&
    Notification.permission === "default"
) {

    Notification.requestPermission();

}


/* =====================================================
   เริ่ม Real-time
===================================================== */

updateChats();


setInterval(
    updateChats,
    1000
);

</script>


</body>

</html>