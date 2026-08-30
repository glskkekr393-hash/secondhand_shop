<?php
require 'config.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $u = $stmt->get_result()->fetch_assoc();

    if ($u && password_verify($pass, $u['password'])) {

        // ล้าง session เก่าก่อน
        session_regenerate_id(true);

        // ข้อมูลเดิม 100%
        $_SESSION['user'] = [
            'id'    => (int)$u['id'],
            'name'  => $u['name'],
            'email' => $u['email'],
            'role'  => $u['role']
        ];

        // ระบบเดิม
        if ($u['role'] === 'admin') {
            header("Location: admin.php");
        } else {
            header("Location: index.php");
        }

        exit;

    } else {

        $msg = "อีเมลหรือรหัสผ่านไม่ถูกต้อง";

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

<title>เข้าสู่ระบบ - PD Shop</title>


<!-- Bootstrap เดิม -->

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
    rel="stylesheet"
>


<!-- ธีม -->

<link
    rel="stylesheet"
    href="theme.css"
>


<style>

/* =====================================================
   LOGIN PAGE
===================================================== */

* {
    box-sizing: border-box;
}

body {

    margin: 0;

    min-height: 100vh;

    font-family:
        system-ui,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        sans-serif;

    display: flex;

    align-items: center;

    justify-content: center;

    overflow: hidden;

    background:
        radial-gradient(
            circle at 20% 20%,
            rgba(99,102,241,.25),
            transparent 30%
        ),
        radial-gradient(
            circle at 80% 80%,
            rgba(168,85,247,.20),
            transparent 30%
        ),
        #09090b;

    color: white;

    transition:
        background .4s ease,
        color .4s ease;

}


/* =====================================================
   LIGHT MODE
===================================================== */

html:not(.dark-theme) body {

    background:
        radial-gradient(
            circle at 20% 20%,
            rgba(99,102,241,.12),
            transparent 30%
        ),
        radial-gradient(
            circle at 80% 80%,
            rgba(168,85,247,.10),
            transparent 30%
        ),
        #f5f6f8;

    color: #18181b;

}


/* =====================================================
   THEME BUTTON
===================================================== */

.theme-toggle {

    position: fixed;

    top: 20px;

    right: 20px;

    z-index: 9999;

    width: 46px;

    height: 46px;

    border-radius: 50%;

    border: 1px solid rgba(128,128,128,.3);

    background: rgba(255,255,255,.15);

    backdrop-filter: blur(15px);

    color: white;

    font-size: 20px;

    cursor: pointer;

    display: flex;

    align-items: center;

    justify-content: center;

    transition:
        transform .3s ease,
        background .3s ease;

}

.theme-toggle:hover {

    transform:
        rotate(20deg)
        scale(1.08);

}


/* Light */

html:not(.dark-theme) .theme-toggle {

    background: white;

    color: #18181b;

    box-shadow:
        0 8px 25px rgba(0,0,0,.08);

}


/* =====================================================
   WRAPPER
===================================================== */

.login-wrapper {

    width: 100%;

    max-width: 440px;

    padding: 20px;

    position: relative;

    z-index: 2;

}


/* =====================================================
   MAGIC CARD
===================================================== */

.magic-card {

    position: relative;

    border-radius: 24px;

    padding: 1px;

    overflow: hidden;

    background:
        linear-gradient(
            135deg,
            rgba(255,255,255,.25),
            rgba(255,255,255,.05)
        );

    box-shadow:
        0 30px 80px rgba(0,0,0,.35);

    transform:
        perspective(1000px)
        rotateX(var(--rotate-x,0deg))
        rotateY(var(--rotate-y,0deg));

    transition:
        transform .15s ease-out,
        box-shadow .3s ease;

}


/* =====================================================
   MAGIC LIGHT
===================================================== */

.magic-card::before {

    content: "";

    position: absolute;

    width: 300px;

    height: 300px;

    left: var(--card-x,50%);

    top: var(--card-y,50%);

    transform:
        translate(-50%,-50%);

    background:
        radial-gradient(
            circle,
            rgba(129,140,248,.65),
            rgba(168,85,247,.30) 30%,
            transparent 70%
        );

    filter: blur(35px);

    pointer-events: none;

}


/* =====================================================
   INNER CARD
===================================================== */

.magic-card-inner {

    position: relative;

    z-index: 2;

    border-radius: 23px;

    padding: 34px;

    background:
        rgba(17,17,19,.90);

    backdrop-filter: blur(20px);

    transition:
        background .4s ease;

}


/* LIGHT CARD */

html:not(.dark-theme) .magic-card-inner {

    background:
        rgba(255,255,255,.92);

}


/* =====================================================
   LOGO
===================================================== */

.logo {

    width: 64px;

    height: 64px;

    margin:
        0 auto 18px;

    border-radius: 18px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 31px;

    background:
        linear-gradient(
            135deg,
            #6366f1,
            #a855f7
        );

    box-shadow:
        0 10px 30px
        rgba(99,102,241,.35);

}


/* =====================================================
   TITLE
===================================================== */

.title {

    text-align: center;

    font-size: 28px;

    font-weight: 800;

    margin: 0;

}


.subtitle {

    text-align: center;

    color: #a1a1aa;

    margin-top: 8px;

    margin-bottom: 28px;

}


/* =====================================================
   ERROR
===================================================== */

.error-box {

    padding: 12px 14px;

    margin-bottom: 18px;

    border-radius: 12px;

    background:
        rgba(220,53,69,.12);

    border:
        1px solid
        rgba(220,53,69,.35);

    color: #ff8b96;

    font-size: 14px;

}


/* =====================================================
   FORM
===================================================== */

.form-group {

    margin-bottom: 18px;

}


.form-label {

    display: block;

    margin-bottom: 8px;

    font-size: 14px;

    font-weight: 600;

}


.form-input {

    width: 100%;

    height: 50px;

    padding: 0 15px;

    border-radius: 12px;

    border:
        1px solid #3f3f46;

    background:
        rgba(24,24,27,.85);

    color: white;

    outline: none;

    font-size: 15px;

    transition:
        .25s ease;

}


.form-input::placeholder {

    color: #71717a;

}


.form-input:focus {

    border-color: #818cf8;

    box-shadow:
        0 0 0 3px
        rgba(99,102,241,.15);

}


/* LIGHT INPUT */

html:not(.dark-theme) .form-input {

    background: white;

    color: #18181b;

    border-color: #d4d4d8;

}


/* =====================================================
   LOGIN BUTTON
===================================================== */

.login-button {

    width: 100%;

    height: 52px;

    margin-top: 6px;

    border: 0;

    border-radius: 12px;

    color: white;

    font-size: 15px;

    font-weight: 700;

    cursor: pointer;

    background:
        linear-gradient(
            135deg,
            #6366f1,
            #8b5cf6
        );

    box-shadow:
        0 10px 25px
        rgba(99,102,241,.25);

    transition:
        transform .2s,
        box-shadow .2s;

}


.login-button:hover {

    transform:
        translateY(-2px);

    box-shadow:
        0 15px 35px
        rgba(99,102,241,.35);

}


.login-button:active {

    transform:
        translateY(0);

}


/* =====================================================
   REGISTER
===================================================== */

.register {

    text-align: center;

    margin-top: 24px;

    padding-top: 20px;

    border-top:
        1px solid #3f3f46;

    color: #a1a1aa;

}


.register a {

    color: #a5b4fc;

    text-decoration: none;

    font-weight: 700;

}


.register a:hover {

    text-decoration: underline;

}


/* =====================================================
   BRAND
===================================================== */

.brand {

    text-align: center;

    margin-top: 20px;

    color: #71717a;

    font-size: 12px;

}


/* =====================================================
   MOBILE
===================================================== */

@media (max-width: 500px) {

    .login-wrapper {

        padding: 15px;

    }

    .magic-card-inner {

        padding: 26px 20px;

    }

    .title {

        font-size: 24px;

    }

}

</style>

</head>


<body>


<!-- =====================================================
     THEME BUTTON
===================================================== -->

<button
    type="button"
    class="theme-toggle"
    id="themeToggle"
    aria-label="เปลี่ยนธีม"
>
🌙
</button>



<!-- =====================================================
     LOGIN
===================================================== -->

<div class="login-wrapper">


<div
    class="magic-card"
    id="magicCard"
>


<div class="magic-card-inner">


<!-- LOGO -->

<div class="logo">

🛒

</div>


<!-- TITLE -->

<h1 class="title">

เข้าสู่ระบบ

</h1>


<p class="subtitle">

เข้าสู่ PD Shop

</p>



<!-- ERROR -->

<?php if ($msg): ?>

<div class="error-box">

⚠️ <?= htmlspecialchars($msg) ?>

</div>

<?php endif; ?>



<!-- FORM -->

<form method="post">


<div class="form-group">

<label
    class="form-label"
    for="email"
>

อีเมล

</label>


<input
    id="email"
    name="email"
    type="email"
    class="form-input"
    placeholder="กรอกอีเมลของคุณ"
    autocomplete="email"
    required
>

</div>



<div class="form-group">

<label
    class="form-label"
    for="password"
>

รหัสผ่าน

</label>


<input
    id="password"
    name="password"
    type="password"
    class="form-input"
    placeholder="กรอกรหัสผ่าน"
    autocomplete="current-password"
    required
>

</div>



<button
    type="submit"
    class="login-button"
>

เข้าสู่ระบบ

</button>


</form>



<!-- REGISTER -->

<div class="register">

ยังไม่มีบัญชี?

<a href="register.php">

สมัครสมาชิก

</a>

</div>


</div>

</div>


<div class="brand">

🛒 PD Shop

</div>


</div>



<!-- =====================================================
     THEME + MAGIC CARD SCRIPT
===================================================== -->

<script>

(function () {

    /* ==========================================
       โหลดธีมที่เคยเลือก
    ========================================== */

    const savedTheme =
        localStorage.getItem('secondhand-theme');

    if (savedTheme === 'dark') {

        document.documentElement
            .classList
            .add('dark-theme');

    }


    /* ==========================================
       ปุ่มเปลี่ยนธีม
    ========================================== */

    const themeButton =
        document.getElementById('themeToggle');


    function updateThemeIcon() {

        const isDark =
            document.documentElement
                .classList
                .contains('dark-theme');


        themeButton.textContent =
            isDark ? '☀️' : '🌙';

    }


    updateThemeIcon();


    themeButton.addEventListener(
        'click',
        function () {

            document.documentElement
                .classList
                .toggle('dark-theme');


            const isDark =
                document.documentElement
                    .classList
                    .contains('dark-theme');


            localStorage.setItem(
                'secondhand-theme',
                isDark ? 'dark' : 'light'
            );


            updateThemeIcon();

        }
    );


    /* ==========================================
       MAGIC CARD
    ========================================== */

    const card =
        document.getElementById('magicCard');


    card.addEventListener(
        'mousemove',
        function (event) {

            const rect =
                card.getBoundingClientRect();


            const x =
                event.clientX - rect.left;

            const y =
                event.clientY - rect.top;


            const centerX =
                rect.width / 2;

            const centerY =
                rect.height / 2;


            const rotateY =
                ((x - centerX) / centerX) * 4;


            const rotateX =
                ((centerY - y) / centerY) * 4;


            card.style.setProperty(
                '--rotate-x',
                rotateX + 'deg'
            );


            card.style.setProperty(
                '--rotate-y',
                rotateY + 'deg'
            );


            card.style.setProperty(
                '--card-x',
                x + 'px'
            );


            card.style.setProperty(
                '--card-y',
                y + 'px'
            );

        }
    );


    card.addEventListener(
        'mouseleave',
        function () {

            card.style.setProperty(
                '--rotate-x',
                '0deg'
            );


            card.style.setProperty(
                '--rotate-y',
                '0deg'
            );

        }
    );

})();

</script>


</body>

</html>