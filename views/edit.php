<?php
$parking_id = $id ?? null;
$errors = [];
$success = false;
$host = null;

if ($parking_id){
    try {

        $pdo = Database::getInstance();
        $sql = "SELECT u.full_name, u.phone 
                FROM parking
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$parking_id]);
        $users = $stmt->fetch();


    } catch (Exception $e) {
        $errors[] = 'Ошибка базы данных' . $e->getMessage();
    }
    print_r($host);
}

?>
<div class="create-form">
    <form method="POST">
        <div class="host-data">
            <h3>Данные паркующегося</h3>
            <div>
                <div class="input-fio">
                    <label for="fio">Фио паркующегося</label>
                    <input type="text" id="fio" name="fio" placeholder="Петров Петр Петрович"
                           value="<?= htmlspecialchars($host['full_name'] ?? '') ?>">
                </div>
                <div class="input-phone">
                    <label for="phone">Номер паркующегося</label>
                    <input type="tel" autocomplete="tel" id="phone" name="phone" placeholder="+7999999999">
                </div>
            </div>
        </div>
        <div class="wrapper-btn">
            <input class="btn-submit" type="submit" value="Отправить">
        </div>
    </form>
</div>

