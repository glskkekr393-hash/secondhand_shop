<?php

/* =====================================================
   DATABASE - RAILWAY
===================================================== */

$host = getenv('MYSQLHOST');
$db   = getenv('MYSQLDATABASE');
$user = getenv('MYSQLUSER');
$pass = getenv('MYSQLPASSWORD');
$port = getenv('MYSQLPORT');


/* =====================================================
   ชื่อเว็บไซต์
===================================================== */

define('SITE_NAME', 'PD SHOP');


/* =====================================================
   CONNECT DATABASE
===================================================== */

$conn = new mysqli(
    $host,
    $user,
    $pass,
    $db,
    $port
);


if ($conn->connect_error) {

    die(
        "Database connection failed: "
        . $conn->connect_error
    );

}


$conn->set_charset("utf8mb4");


/* =====================================================
   SESSION
===================================================== */

session_start();

?>
