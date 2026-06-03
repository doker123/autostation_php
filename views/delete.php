<?php
$parking_id = $id ?? '';
$errors = [];
$record = null;

try {
    $pdo = Database::getInstance();
    $pdo->beginTransaction();

    $sql_payments = "DELETE FROM payments WHERE parking_id = :id";
    $stmt_payments = $pdo->prepare($sql_payments);
    $stmt_payments->execute(['id' => $parking_id]);

    $sql_parking = "DELETE FROM parking WHERE id = :id";
    $stmt_parking = $pdo->prepare($sql_parking);
    $result = $stmt_parking->execute(['id' => $parking_id]);

    if ($result && $stmt_parking->rowCount() > 0) {
        $success = true;
    } else {
        $errors[] = 'Запись о парковке не найдена';
    }

    $pdo->commit();
}catch (Exception $e){
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $errors[] = 'Ошибка базы данных' . $e->getMessage();
}
if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
} elseif ($success) {
    $_SESSION['success'] = 'Запись о парковке успешно удалена';
}

header("Location: ". PROJECT_PATH ."/home");
exit();