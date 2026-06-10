<?php ob_start();
require_once "config/helpers.php";?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Автостоянка</title>
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <script defer src="<?= asset('scripts/script.js') ?>"></script>
</head>
<body>
<header class="header-menu">
    <a href="<?= url(' ')?>"><h1>Автостоянка</h1></a>
</header>
<?php;
require_once "config/connectionDb.php";
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);
?>
<main class="main-content">
<?php
$parking_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$parking_id) {
        die('Не указан корректный ID записи');
    }
$errors = [];
$success = false;

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$basePath = rtrim($scriptDir, '/');

define('BASE_URL', '//'. $_SERVER['HTTP_HOST'] . $basePath);
define('PROJECT_PATH', $basePath);

try {
    $pdo = Database::getInstance();
    $pdo->beginTransaction();

    $sql_payments = "DELETE FROM payments WHERE parking_id = :id";
    $stmt_payments = $pdo->prepare($sql_payments);
    $stmt_payments->execute(["id" => $parking_id]);

    $sql_space = "UPDATE parking_spots SET is_occupied = 0
                      WHERE id = (
                          SELECT parking_spot_id FROM parking WHERE id = :id
                      )";
    $stmt_space = $pdo->prepare($sql_space);
    $stmt_space->execute([":id" => $parking_id]);

    $sql_parking = "DELETE FROM parking WHERE id = :id";
    $stmt_parking = $pdo->prepare($sql_parking);
    $result = $stmt_parking->execute(["id" => $parking_id]);

    if ($result && $stmt_parking->rowCount() > 0) {
        $success = true;
    } else {
        $errors[] = "Запись о парковке не найдена";
    }

    if ($result && $stmt_parking->rowCount() > 0) {
        $success = true;
    } else {
        $errors[] = "Запись о парковке с ID " . $parking_id . " не найдена";
    }

    $pdo->commit();
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $errors[] = "Ошибка базы данных" . $e->getMessage();
}
header("Location: " . PROJECT_PATH . "/home");
exit();
?>
</main>
</body>
</html>

