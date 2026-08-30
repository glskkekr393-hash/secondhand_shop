<?php
require 'config.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user']['id'];


/* =====================================================
   ดึงรายการสินค้าที่ถูกใจ
===================================================== */

$stmt = $conn->prepare("
    SELECT
        p.*,
        c.name AS cat
    FROM favorites f
    INNER JOIN products p
        ON f.product_id = p.id
    LEFT JOIN categories c
        ON p.category_id = c.id
    WHERE f.user_id = ?
    ORDER BY f.id DESC
");

$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$favorites = $stmt->get_result();

?>

<!doctype html>

<html lang="th">

<head>

<meta charset="utf-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1"
>

<title>สินค้าที่ถูกใจ - PD Shop</title>


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

}


html.dark-theme {

    --bg: #09090b;

    --card: #18181b;

    --text: #f4f4f5;

    --muted: #a1a1aa;

    --border: #3f3f46;

    --nav: rgba(24,24,27,.92);

    --shadow: rgba(0,0,0,.45);

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

    transition:
        background .4s ease,
        border .4s ease;

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
        background .3s ease,
        color .3s ease;

}


.theme-toggle:hover {

    transform:
        rotate(18deg)
        scale(1.08);

}


/* =====================================================
   NAV BUTTON
===================================================== */

.back-button {

    color: var(--text);

    border-color: var(--border);

    transition:
        transform .2s ease,
        background .2s ease,
        color .2s ease;

}


.back-button:hover {

    background: var(--text);

    color: var(--bg);

    transform:
        translateX(-3px);

}


/* =====================================================
   PAGE HEADER
===================================================== */

.page-header {

    position: relative;

    padding: 28px;

    margin-bottom: 30px;

    border-radius: 22px;

    overflow: hidden;

    background:
        linear-gradient(
            135deg,
            #18181b,
            #27272a
        );

    color: white;

    box-shadow:
        0 18px 45px
        var(--shadow);

}


.page-header::before {

    content: "";

    position: absolute;

    width: 180px;

    height: 180px;

    border-radius: 50%;

    right: -50px;

    top: -80px;

    background:
        rgba(220,53,69,.20);

    animation:
        floatHeart 5s ease-in-out infinite;

}


.page-header::after {

    content: "";

    position: absolute;

    width: 100px;

    height: 100px;

    border-radius: 50%;

    left: -30px;

    bottom: -50px;

    background:
        rgba(99,102,241,.16);

}


.page-header-content {

    position: relative;

    z-index: 2;

}


@keyframes floatHeart {

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
   FAVORITE COUNT
===================================================== */

.favorite-count {

    display: inline-flex;

    align-items: center;

    gap: 6px;

    padding: 8px 14px;

    border-radius: 999px;

    background:
        rgba(220,53,69,.15);

    border:
        1px solid
        rgba(220,53,69,.35);

    color: #ff7b88;

    font-weight: 700;

}


/* =====================================================
   PRODUCT CARD
===================================================== */

.product-card {

    border:
        1px solid var(--border);

    border-radius: 20px;

    overflow: hidden;

    background: var(--card);

    color: var(--text);

    box-shadow:
        0 8px 25px
        var(--shadow);

    position: relative;

    transition:
        transform .35s cubic-bezier(.2,.8,.2,1),
        box-shadow .35s ease,
        border .35s ease;

}


.product-card:hover {

    transform:
        translateY(-9px)
        scale(1.01);

    box-shadow:
        0 22px 45px
        var(--shadow);

    border-color:
        rgba(220,53,69,.35);

}


/* =====================================================
   HEART
===================================================== */

.favorite-heart {

    position: absolute;

    z-index: 5;

    top: 12px;

    right: 12px;

    width: 42px;

    height: 42px;

    border-radius: 50%;

    display: flex;

    align-items: center;

    justify-content: center;

    background:
        rgba(255,255,255,.92);

    box-shadow:
        0 6px 18px
        rgba(0,0,0,.15);

    font-size: 19px;

    transition:
        transform .25s ease;

}


.product-card:hover .favorite-heart {

    transform:
        scale(1.12)
        rotate(5deg);

}


/* =====================================================
   IMAGE
===================================================== */

.product-img {

    width: 100%;

    height: 220px;

    object-fit: cover;

    transition:
        transform .5s cubic-bezier(.2,.8,.2,1);

}


.product-card:hover .product-img {

    transform:
        scale(1.07);

}


/* =====================================================
   CARD BODY
===================================================== */

.product-card .card-body {

    background: var(--card);

    color: var(--text);

    position: relative;

    z-index: 2;

}


.category-text {

    color: var(--muted);

    font-size: 14px;

}


/* =====================================================
   PRICE
===================================================== */

.price {

    color: #dc3545;

    font-weight: 800;

}


/* =====================================================
   BUTTON DETAIL
===================================================== */

.btn-detail {

    width: 100%;

    border-radius: 10px;

    transition:
        transform .2s ease,
        box-shadow .2s ease;

}


.btn-detail:hover {

    transform:
        translateY(-3px);

    box-shadow:
        0 8px 18px
        rgba(0,0,0,.18);

}


html.dark-theme .btn-detail {

    background: #f4f4f5;

    color: #18181b;

    border-color: #f4f4f5;

}


/* =====================================================
   REMOVE BUTTON
===================================================== */

.btn-remove {

    display: block;

    width: 100%;

    padding: 10px;

    border-radius: 10px;

    text-align: center;

    text-decoration: none;

    font-weight: 600;

    color: #dc3545;

    background: transparent;

    border:
        1px solid
        #dc3545;

    transition:
        transform .2s ease,
        background .2s ease,
        color .2s ease,
        box-shadow .2s ease;

}


.btn-remove:hover {

    background: #dc3545;

    color: white;

    transform:
        translateY(-3px);

    box-shadow:
        0 8px 18px
        rgba(220,53,69,.20);

}


/* =====================================================
   ALERT
===================================================== */

html.dark-theme .alert-secondary {

    background: #27272a;

    color: #e4e4e7;

    border-color: #3f3f46;

}


html.dark-theme .alert-success {

    background: #123b28;

    color: #b7f7d0;

    border-color: #1f6b45;

}


/* =====================================================
   EMPTY
===================================================== */

.empty-card {

    background: var(--card);

    color: var(--text);

    border:
        1px solid var(--border);

    border-radius: 24px;

    padding: 60px 25px;

    box-shadow:
        0 15px 35px
        var(--shadow);

}


.empty-heart {

    font-size: 75px;

    display: inline-block;

    animation:
        heartBeat 1.8s infinite;

}


@keyframes heartBeat {

    0%,100% {

        transform:
            scale(1);

    }

    50% {

        transform:
            scale(1.12);

    }

}


/* =====================================================
   REVEAL
===================================================== */

.reveal {

    opacity: 0;

    transform:
        translateY(25px);

    transition:
        opacity .6s ease,
        transform .6s ease;

}


.reveal.show {

    opacity: 1;

    transform:
        translateY(0);

}


/* =====================================================
   FOOTER
===================================================== */

footer {

    color: var(--muted) !important;

}


/* =====================================================
   MOBILE
===================================================== */

@media(max-width:768px) {

    .page-header {

        padding: 24px;

        border-radius: 18px;

    }


    .page-header h2 {

        font-size: 28px;

    }


    .product-card:hover {

        transform:
            translateY(-5px);

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


<div class="d-flex align-items-center gap-2">


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
    class="btn back-button"
>

← กลับหน้าหลัก

</a>


</div>

</div>

</nav>



<!-- =====================================================
     MAIN
===================================================== -->

<div class="container py-5">



<!-- =====================================================
     HEADER
===================================================== -->

<div class="page-header reveal">

<div class="page-header-content">


<div
    class="d-flex
           justify-content-between
           align-items-center
           flex-wrap
           gap-3"
>


<div>

<div
    class="fw-bold text-danger mb-2"
    style="letter-spacing:3px"
>

MY FAVORITES

</div>


<h2 class="fw-bold mb-1">

❤️ สินค้าที่ถูกใจ

</h2>


<p class="mb-0 text-white-50">

รายการสินค้าที่คุณกดถูกใจไว้

</p>

</div>


<div class="favorite-count">

❤️ <?= $favorites->num_rows ?> รายการ

</div>


</div>

</div>

</div>



<!-- =====================================================
     PRODUCTS
===================================================== -->

<div class="row g-4">


<?php if ($favorites->num_rows === 0): ?>


<div class="col-12">


<div class="empty-card text-center reveal">


<div class="empty-heart">

❤️

</div>


<h3 class="mt-3 fw-bold">

ยังไม่มีสินค้าที่ถูกใจ

</h3>


<p class="text-secondary">

กด ❤️ ที่สินค้าที่คุณสนใจ
เพื่อเพิ่มลงรายการถูกใจ

</p>


<a
    href="index.php"
    class="btn btn-dark px-4 mt-2"
>

🛍️ ไปเลือกสินค้า

</a>


</div>

</div>


<?php endif; ?>



<?php while ($p = $favorites->fetch_assoc()): ?>


<div class="col-md-4 col-lg-3">


<div class="product-card h-100 reveal">


<!-- =================================================
     HEART
================================================= -->

<div class="favorite-heart">

❤️

</div>



<!-- =================================================
     IMAGE
================================================= -->

<?php if (!empty($p['image'])): ?>


<img
    src="<?= htmlspecialchars($p['image']) ?>"
    class="product-img"
    alt="<?= htmlspecialchars($p['name']) ?>"
>


<?php else: ?>


<div
    class="product-img
           d-flex
           align-items-center
           justify-content-center
           bg-secondary-subtle"
    style="font-size:60px"
>

📦

</div>


<?php endif; ?>



<!-- =================================================
     BODY
================================================= -->

<div class="card-body d-flex flex-column">


<!-- CATEGORY -->

<div class="category-text">

<?= htmlspecialchars(
    $p['cat'] ?? 'ไม่ระบุหมวดหมู่'
) ?>

</div>



<!-- NAME -->

<h5 class="fw-bold mt-1">

<?= htmlspecialchars($p['name']) ?>

</h5>



<!-- PRICE -->

<h4 class="price">

฿<?= number_format($p['price'], 2) ?>

</h4>



<!-- CONDITION -->

<p class="mb-2">

สภาพ:

<?= htmlspecialchars(
    $p['item_condition']
) ?>

</p>



<!-- STATUS -->

<?php if ($p['status'] === 'sold'): ?>


<div
    class="alert alert-secondary
           text-center
           fw-bold"
>

🔴 ขายแล้ว

</div>


<?php else: ?>


<div
    class="alert alert-success
           text-center
           fw-bold"
>

🟢 ยังมีสินค้า

</div>


<?php endif; ?>



<div class="mt-auto">


<!-- DETAIL -->

<a
    href="product.php?id=<?= (int)$p['id'] ?>"
    class="btn btn-dark btn-detail mb-2"
>

ดูรายละเอียด

</a>



<!-- REMOVE -->

<a
    href="favorite.php?id=<?= (int)$p['id'] ?>"
    class="btn-remove"
    onclick="return confirm('ต้องการเอาสินค้านี้ออกจากรายการถูกใจหรือไม่?')"
>

💔 เอาออกจากรายการถูกใจ

</a>


</div>


</div>

</div>

</div>


<?php endwhile; ?>


</div>


</div>



<!-- =====================================================
     FOOTER
===================================================== -->

<footer class="text-center py-5">


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
        localStorage.getItem('secondhand-theme');


    if (savedTheme === 'dark') {

        document.documentElement
            .classList
            .add('dark-theme');

    }


    const button =
        document.getElementById('themeToggle');


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
   SCROLL REVEAL
===================================================== */

(function () {

    const elements =
        document.querySelectorAll('.reveal');


    if (!('IntersectionObserver' in window)) {

        elements.forEach(function(element) {

            element.classList.add('show');

        });

        return;

    }


    const observer =
        new IntersectionObserver(
            function(entries) {

                entries.forEach(function(entry) {

                    if (entry.isIntersecting) {

                        entry.target
                            .classList
                            .add('show');

                        observer.unobserve(
                            entry.target
                        );

                    }

                });

            },
            {
                threshold: 0.08
            }
        );


    elements.forEach(function(element) {

        observer.observe(element);

    });

})();

</script>


</body>

</html>