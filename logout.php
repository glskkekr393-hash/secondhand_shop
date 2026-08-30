<?php

require 'config.php';

// ล้างข้อมูล Session ทั้งหมด
$_SESSION = [];

// ลบ Cookie ของ Session
if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// ทำลาย Session
session_destroy();

// กลับหน้า Login
header("Location: login.php");
exit;

?>