<?php
require_once __DIR__ . "/config/connectionDb.php";
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);
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