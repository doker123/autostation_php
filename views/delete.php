<?php
$parking_id = $id ?? "";
$errors = [];
$success = false;

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
