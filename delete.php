<?php
require_once __DIR__ . "/config/connectionDb.php";
require_once __DIR__ . "/config/helpers.php";

$parking_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$parking_id) {
    die('Не указан корректный ID записи');
}

try {
    $pdo = Database::getInstance();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT parking_spot_id FROM parking WHERE id = :id");
    $stmt->execute([":id" => $parking_id]);
    $spotId = $stmt->fetchColumn();

    if (!$spotId) {
        $pdo->rollBack();
        header("Location: " . url("/"));
        exit();
    }

    $pdo->prepare("DELETE FROM payments WHERE parking_id = :id")->execute([":id" => $parking_id]);
    $pdo->prepare("DELETE FROM parking WHERE id = :id")->execute([":id" => $parking_id]);
    $pdo->prepare("UPDATE parking_spots SET is_occupied = 0 WHERE id = :id")->execute([":id" => $spotId]);

    $pdo->commit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
}

header("Location: " . url("/"));
exit();
