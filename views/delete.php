<?php
$parking_id = $id ?? '';
$errors = [];
$record = null;

try {
    $pdo = Database::getInstance();
    $pdo->beginTransaction();

//    $sql = "DELETE id FROM parkings WHERE id = :id";
//    $stmt = $pdo->prepare($sql);
//    $stmt->execute(['id' => $parking_id]);



    $pdo->commit();
}catch (Exception $e){
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $errors[] = 'Ошибка базы данных' . $e->getMessage();
}

header("Location: ". PROJECT_PATH ."/home");
exit();