<?php ob_start();?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Автостоянка</title>
    <link rel="stylesheet" href="/css/style.css">
    <script defer src="/scripts/script.js"></script>
</head>
<body>
<header class="header-menu">
    <a href="/home"><h1>Автостоянка</h1></a>
</header>
<?php
require_once "config/router.php";
require_once "config/connectionDb.php";
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);
$url = $_SERVER['REQUEST_URI'];
?>
<main class="main-content">
    <?php route($url); ?>
</main>
</body>
</html>
