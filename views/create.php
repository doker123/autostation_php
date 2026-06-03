<?php

$errors = [];
$success = "";
$form = [];

try {
    $pdo = Database::getInstance();
    $sql = "SELECT
            id AS tariff_id,
            CONCAT_WS(', ',tariff_name, description, price_per_hour) AS tariff_description
            FROM tariffs
            WHERE is_active = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $tariffs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sql = "SELECT
            id AS parking_id,
            spot_number AS spot_number
            FROM parking_spots
            WHERE is_occupied = 0";
    $stmt1 = $pdo->prepare($sql);
    $stmt1->execute();
    $spots = $stmt1->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = 'Ошибка базы данных' . $e->getMessage();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $form["fio"] = trim($_POST["fio"] ?? "");
    $form["phone"] = trim($_POST["phone"] ?? "");
    $form["select_tariff"] = trim($_POST["select_tariff"] ?? "");
    $form["name_tariff"] = trim($_POST["name_tariff"] ?? "");
    $form["tariff_price"] = trim($_POST["tariff_price"] ?? "");
    $form["min_price"] = trim($_POST["min_price"] ?? "");
    $form["description"] = trim($_POST["description"] ?? "");
    $form["license_plate"] = trim($_POST["license_plate"] ?? "");
    $form["car_color"] = trim($_POST["car_color"] ?? "");
    $form["car_model"] = trim($_POST["car_model"] ?? "");
    $form["car_appearance"] = trim($_POST["car_appearance"] ?? "");
    $form["spot"] = trim($_POST["spot"] ?? "");
    $form["spot_number"] = trim($_POST["spot_number"] ?? "");
    $form["type_spot"] = trim($_POST["type_spot"] ?? "");
    $form["amount"] = trim($_POST["amount"] ?? "");
    $form["payment_status"] = trim($_POST["payment_status"] ?? "");
    $form["transaction_id"] = trim($_POST["transaction_id"] ?? "");

    if (
        empty($form["fio"]) ||
        empty($form["phone"]) ||
        empty($form["select_tariff"]) ||
        empty($form["license_plate"]) ||
        empty($form["car_color"]) ||
        empty($form["car_model"]) ||
        empty($form["car_appearance"]) ||
        empty($form["spot"])
    ) {
        $errors[] = "Обязательные поля не заполнены это ФИО, телефон, тариф,
        номер машины и характеристики машины и место парковки.";
    }
    if ($form["select_tariff"] === "create_tariff") {
        if (empty($form["name_tariff"]) || empty($form["min_price"]) || empty($form["description"])
        ) {
            $errors[] = "Если вы хатите создать тарифф в системе заполните обязательные поля: имя тарифа, минимальная оплата, описание тарифа.";
        }
    }

    if ($form["spot"] === "create_spot") {
        if (empty($form["spot_number"]) || empty($form["type_spot"])) {
            $errors[] = "Если вы хотите создать новое место стоянки то должны заполнить обязательные поля: номер места, тип места.";
        }
    }

    if (empty($errors)) {
        try {
            $pdo = Database::getInstance();
            $pdo->beginTransaction();
            if ($form["select_tariff"] === "create_tariff") {
                $sql = "INSERT INTO tariffs (tarriff_name, price_per_hour, min_price, description, is_active)
                        VALUES (:name, :price, :min, :desc, 1) ";
                $stmtNewTariff = $pdo->prepare($sql);
                $stmtNewTariff->execute([
                    'name' => $form["name_tariff"],
                    'price' => $form["min_price"],
                    'min' => $form["min_price"],
                    'desc' => $form["description"],
                ]);
                $tariffId = $pdo->lastInsertId();
            } else {
                $tariffId = (int)$form["select_tariff"];
            }
            if ($form["spot"] === "create_spot") {
                $stmtCheckSpot = $pdo->prepare("SELECT id FROM parking_spots WHERE spot_number = :num");
                $stmtCheckSpot->execute([':num' => $form["spot_number"]]);
                if ($stmtCheckSpot->fetch()) {
                    throw new RuntimeException("Парковочное место с номером {$form[spot_number]} уже существует.");
                }
                $stmtNewSpot = $pdo->prepare("INSERT INTO parking_spots (spot_number, spot_type, is_occupied)
                                             VALUES (:num, :type, 0)");
                $stmtNewSpot->execute([
                    'num' => $form["spot_number"],
                    'type' => $tariffId,
                ]);
                $spotId = $pdo->lastInsertId();
            } else {
                $spotId = (int)$form["spot"];
                $stmtLockSpot = $pdo->prepare("SELECT is_occupied FROM parking_spots WHERE id = :id FOR UPDATE");
                $stmtLockSpot->execute([':id' => $spotId]);
                $spotStatus = $stmtLockSpot->fetchColumn();
                if ($spotStatus === 1) {
                    throw new RuntimeException("Выбранное парковочное место уже занято Выбирете другое место.");
                }
            }
            $stmtUser = $pdo->prepare("INSERT INTO users (full_name, phone) VALUES (:name, :phone)");
            $stmtUser->execute([
                "name" => $form["fio"],
                "phone" => $form["phone"]
            ]);
            $userId = $pdo->lastInsertId();
            $stmtCar = $pdo->prepare("INSERT INTO cars (user_id, license_plate, car_model, car_color, car_appearance)
                                            VALUES (:uid, :plate, :model, :color, :app) ");
            $stmtCar->execute([
                'uid' => $userId,
                'plate' => $form["license_plate"],
                'model' => $form["car_model"],
                'color' => $form["car_color"],
                'app' => $form["car_appearance"]
            ]);
            $carId = $pdo->lastInsertId();
            $stmtParking = $pdo->prepare("INSERT INTO parking (car_id, parking_spot_id, entry_time, is_paid, payment_method, total_price)
                                                VALUES (:cid, :sid, NOW(), 0,'cash', 0.00)");
            $stmtParking->execute([
                'cid' => $carId,
                'sid' => $spotId,
            ]);
            $parkingId = $pdo->lastInsertId();
            if (!empty($form["amount"]) && (float)$form["amount"] > 0) {
                $stmtPayment = $pdo->prepare("INSERT INTO payments (parking_id, amount, payment_status, transaction_id)
                                                    VALUES (:pid, :amount, :status, :tid)");
                $stmtPayment->execute([
                    'pid' => $parkingId,
                    'amount' => $form["amount"],
                    'status' => $form["payment_status"],
                    'tid' => $form["transaction_id"]
                ]);
                if ($form["payment_status"] === "completed") {
                    $stmtUpdatePaid = $pdo->prepare("UPDATE parking SET is_paid = 1, total_price = :amt WHERE id = :pid");
                    $stmtUpdatePaid->execute([
                        ":amt" => (float)$form["amount"],
                        ":pid" => $parkingId
                    ]);
                }
                $stmtOccupy = $pdo->prepare("UPDATE parking_spots SET is_occupied = 1 WHERE id = :sid");
                $stmtOccupy->execute([
                    ":sid" => $spotId
                ]);
            };
            $pdo->commit();
            $success = "Сессия парковки успешно создана и зафиксированна в системе(ID сессии:{$parkingId}).}";
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'Ошибка базы данных ' . 'Ошибка в строке ' . $e->getLine() .' '. $e->getMessage();
        }
    }
}
?>
<div class="create-form">
    <form method="POST">
        <div class="host-data">
            <h3>Данные паркующегося</h3>
            <div>
                <div class="input-fio">
                    <label for="fio">Фио паркующегося</label>
                    <input type="text" id="fio" name="fio" placeholder="Петров Петр Петрович">
                </div>
                <div class="input-phone">
                    <label for="phone">Номер паркующегося</label>
                    <input type="tel" autocomplete="tel" id="phone" name="phone" placeholder="+7999999999">
                </div>
            </div>
        </div>
        <div class="tariff">
            <label for="select_tariff">Выберите тариф стаянки</label>
            <div class="select-wrapper">
                <select id="select_tariff" name="select_tariff">
                    <option value="default">Выберите тариф</option>
                    <option value="create_tariff">Добавить свой тариф</option>
                    <?php foreach ($tariffs as $tariff): ?>
                        <option value="<?= htmlspecialchars(
                            $tariff["tariff_id"],
                        ) ?>">
                            <?= htmlspecialchars(
                                $tariff["tariff_description"],
                            ) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="input-tariff">
                <div class="name-tariff">
                    <label for="name_tariff">Название тарифа</label>
                    <input type="text" id="name_tariff" name="name_tariff" placeholder="Дневной">
                </div>
                <div class="price-tariff">
                    <label for="price_tariff">Цена тарифа</label>
                    <input type="text" id="price_tariff" name="price_tariff" placeholder="100">
                </div>
                <div class="min-price">
                    <label for="min_price">Минимальная оплата</label>
                    <input type="text" id="min_price" name="min_price" placeholder="100">
                </div>
                <div class="description">
                    <label for="description">Описание</label>
                    <input type="text" id="description" name="description" placeholder="Ночной - 50руб/ч">
                </div>
            </div>
        </div>
        <div class="host-car">
            <div>
                <label for="license_plate">Номер машины</label>
                <input type="text" id="license_plate" name="license_plate" placeholder="B123EX70RUS">
            </div>
            <div>
                <label for="car_model">Модель машины</label>
                <input type="text" id="car_model" name="car_model" placeholder="Ford Focus">
            </div>
            <div>
                <label for="car_color">Цвет машины</label>
                <input type="text" id="car_color" name="car_color" placeholder="Серебристый">
            </div>
            <div>
                <label for="car_appearance">Повреждения на машине</label>
                <input type="text" id="car_appearance" name="car_appearance"
                       placeholder="Опешите повреждения если их нет напишите нет">
            </div>
        </div>
        <div class="spot">
            <label for="spot">Место стоянки</label>
            <div class="select-wrapper">
                <select id="spot" name="spot">
                    <option value="default">Выберите место стоянки</option>
                    <option value="create_spot">Добавить новое место стоянки</option>
                    <?php foreach ($spots as $spot): ?>
                        <option value="<?= htmlspecialchars(
                            $spot["parking_id"],
                        ) ?>">
                            <?= htmlspecialchars($spot["spot_number"]) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="input-spot">
                <div>
                    <label for="spot_number">Номер места</label>
                    <input type="text" id="spot_number" name="spot_number" placeholder="A1">
                </div>
                <div>
                    <label for="type_spot">Тип парковочного места</label>
                    <select id="type_spot" name="type_spot">
                        <option value="regular" selected>Доступно</option>
                        <option value="disabled">Недоступно</option>
                        <option value="family">Служебный</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="payment">
            <div class="amount">
                <label for="amount">К оплате(руб)</label>
                <input type="text" id="amount" name="amount" placeholder="300руб">
            </div>
            <div class="payment-status">
                <label for="payment_status">Статус оплаты</label>
                <div class="select-wrapper">
                    <select id="payment_status" name="payment_status">
                        <option value="pending" selected>В ожидании</option>
                        <option value="completed">Успешно</option>
                        <option value="failed">Провально</option>
                    </select>
                </div>
            </div>
            <div class="transaction-id">
                <label for="transaction_id">ID Транзакции</label>
                <input type="text" id="transaction_id" name="transaction_id" placeholder="TXN80121">
            </div>
        </div>
        <div class="error-form">
            <?php if (!empty($errors)): ?>
                <ul class="error-list">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <div class="wrapper-btn">
            <input class="btn-submit" type="submit" value="Отправить">
        </div>
    </form>
</div>
