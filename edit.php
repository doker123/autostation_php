<?php
require_once "config/helpers.php"; ?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Автостоянка</title>
    <link rel="stylesheet" href="<?= asset("css/style.css") ?>">
    <script defer src="<?= asset("scripts/script.js") ?>"></script>
</head>
<body>
<header class="header-menu">
    <a href="<?= url(" ") ?>"><h1>Автостоянка</h1></a>
</header>
<?php
require_once "config/connectionDb.php";
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);
?>
<main class="main-content">
    <?php
    $parking_id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
    if (!$parking_id) {
        die("Не указан корректный ID записи");
    }
    $errors = [];
    $success = false;
    $users = null;
    $payments = null;

    try {
        $pdo = Database::getInstance();

        $tariffs = $pdo->query("
            SELECT id AS tariff_id, CONCAT_WS(', ', tariff_name, description, price_per_hour) AS tariff_description
            FROM tariffs WHERE is_active = 1
        ")->fetchAll(PDO::FETCH_ASSOC);

        $spots = $pdo->query("
            SELECT id AS parking_id, spot_number
            FROM parking_spots WHERE is_occupied = 0
        ")->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("
            SELECT
                u.full_name, u.phone,
                c.car_appearance, c.car_model, c.car_color,
                p.entry_time, p.exit_time, p.id AS parking_id,
                COALESCE(p.total_price, 0) - COALESCE(
                    (SELECT SUM(amount) FROM payments WHERE parking_id = p.id), 0
                ) AS debt
            FROM parking p
            JOIN cars c ON p.car_id = c.id
            JOIN users u ON c.user_id = u.id
            WHERE p.id = :pid
        ");
        $stmt->execute([":pid" => $parking_id]);
        $users = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT amount, payment_status, transaction_id FROM payments WHERE parking_id = :pid LIMIT 1");
        $stmt->execute([":pid" => $parking_id]);
        $payments = $stmt->fetch(PDO::FETCH_ASSOC) ?: ["amount" => "", "payment_status" => "pending", "transaction_id" => ""];

        if (!$users) {
            $errors[] = "Запись с указанным ID не найдена";
        }
    } catch (PDOException $e) {
        $errors[] = "Ошибка базы данных: " . $e->getMessage();
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST" && $users) {
        $form["fio"] = trim($_POST["fio"] ?? "");
        $form["phone"] = trim($_POST["phone"] ?? "");
        $form["car_model"] = trim($_POST["car_model"] ?? "");
        $form["car_color"] = trim($_POST["car_color"] ?? "");
        $form["car_appearance"] = trim($_POST["car_appearance"] ?? "");
        $form["entry_time"] = trim($_POST["entry_time"] ?? "");
        $form["exit_time"] = trim($_POST["exit_time"] ?? "");
        $form["select_tariff"] = trim($_POST["select_tariff"] ?? "");
        $form["name_tariff"] = trim($_POST["name_tariff"] ?? "");
        $form["price_tariff"] = trim($_POST["price_tariff"] ?? "");
        $form["min_price"] = trim($_POST["min_price"] ?? "");
        $form["description"] = trim($_POST["description"] ?? "");
        $form["spot"] = trim($_POST["spot"] ?? "");
        $form["spot_number"] = trim($_POST["spot_number"] ?? "");
        $form["type_spot"] = trim($_POST["type_spot"] ?? "");
        $form["amount"] = trim($_POST["amount"] ?? "");
        $form["payment_status"] = trim($_POST["payment_status"] ?? "");
        $form["transaction_id"] = trim($_POST["transaction_id"] ?? "");

        if (
            empty($form["fio"]) ||
            empty($form["phone"]) ||
            empty($form["car_model"]) ||
            empty($form["car_color"]) ||
            empty($form["car_appearance"]) ||
            empty($form["entry_time"]) ||
            empty($form["exit_time"]) ||
            empty($form["select_tariff"]) ||
            empty($form["spot"])
        ) {
            $errors[] = "Поля должны быть заполнены";
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("UPDATE users SET full_name = :fio, phone = :phone WHERE id = (SELECT user_id FROM cars WHERE id = (SELECT car_id FROM parking WHERE id = :pid))");
                $stmt->execute([":fio" => $form["fio"], ":phone" => $form["phone"], ":pid" => $parking_id]);

                $stmt = $pdo->prepare("UPDATE cars SET car_model = :model, car_color = :color, car_appearance = :app WHERE id = (SELECT car_id FROM parking WHERE id = :pid)");
                $stmt->execute([":model" => $form["car_model"], ":color" => $form["car_color"], ":app" => $form["car_appearance"], ":pid" => $parking_id]);

                if ($form["select_tariff"] === "create_tariff") {
                    $stmt = $pdo->prepare("INSERT INTO tariffs (tariff_name, price_per_hour, min_price, description, is_active) VALUES (:name, :price, :min, :desc, 1)");
                    $stmt->execute([":name" => $form["name_tariff"], ":price" => $form["price_tariff"], ":min" => $form["min_price"], ":desc" => $form["description"]]);
                    $tariffId = $pdo->lastInsertId();
                } else {
                    $tariffId = $form["select_tariff"];
                }

                if ($form["spot"] === "create_spot") {
                    $stmt = $pdo->prepare("INSERT INTO parking_spots (spot_number, spot_type, is_occupied) VALUES (:num, :type, 1)");
                    $stmt->execute([":num" => $form["spot_number"], ":type" => $form["type_spot"]]);
                    $spotId = $pdo->lastInsertId();
                } elseif ($form["spot"] !== "default") {
                    $spotId = (int) $form["spot"];
                    $stmt = $pdo->prepare("SELECT id FROM parking_spots WHERE id = :id");
                    $stmt->execute([":id" => $spotId]);
                    if (!$stmt->fetch()) {
                        $errors[] = "Выбранное место не существует";
                    } else {
                        $stmt = $pdo->prepare("UPDATE parking_spots SET is_occupied = 0 WHERE id = (SELECT parking_spot_id FROM parking WHERE id = :pid)");
                        $stmt->execute([":pid" => $parking_id]);
                        $stmt = $pdo->prepare("UPDATE parking_spots SET is_occupied = 1 WHERE id = :sid");
                        $stmt->execute([":sid" => $spotId]);
                    }
                } else {
                    $errors[] = "Пожалуйста, выберите место или добавьте новое";
                }

                if (empty($errors)) {
                    $stmt = $pdo->prepare("UPDATE parking SET entry_time = :entry, exit_time = :exit, tariffs_id = :tid, parking_spot_id = :sid WHERE id = :pid");
                    $stmt->execute([":entry" => $form["entry_time"], ":exit" => $form["exit_time"], ":tid" => $tariffId, ":sid" => $spotId, ":pid" => $parking_id]);

                    if (!empty($form["amount"]) && (float) $form["amount"] > 0) {
                        $stmt = $pdo->prepare("UPDATE payments SET amount = :amt, payment_status = :status, transaction_id = :tid WHERE parking_id = :pid");
                        $stmt->execute([":amt" => (float) $form["amount"], ":status" => $form["payment_status"], ":tid" => $form["transaction_id"], ":pid" => $parking_id]);

                        $isPaid = $form["payment_status"] === "completed" ? 1 : 0;
                        $totalPrice = $isPaid ? (float) $form["amount"] : 0;
                        $stmt = $pdo->prepare("UPDATE parking SET is_paid = :paid, total_price = :price WHERE id = :pid");
                        $stmt->execute([":paid" => $isPaid, ":price" => $totalPrice, ":pid" => $parking_id]);
                    }
                }

                $pdo->commit();
                $success = true;
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $errors[] = "Ошибка базы данных: " . $e->getMessage();
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
                        <label for="fio">ФИО паркующегося</label>
                        <input type="text" id="fio" name="fio" placeholder="Петров Петр Петрович"
                               value="<?= htmlspecialchars($users["full_name"] ?? '') ?>">
                    </div>
                    <div class="input-phone">
                        <label for="phone">Номер телефона</label>
                        <input type="tel" autocomplete="tel" id="phone" name="phone" placeholder="+7999999999"
                               value="<?= htmlspecialchars($users["phone"] ?? '') ?>">
                    </div>
                </div>
            </div>
            <div class="cars-data">
                <h3>Данные о машине</h3>
                <div class="input-car-model">
                    <label for="car_model">Модель машины</label>
                    <input type="text" id="car_model" name="car_model" placeholder="Ford"
                           value="<?= htmlspecialchars($users["car_model"] ?? '') ?>">
                </div>
                <div class="input-car-color">
                    <label for="car_color">Цвет машины</label>
                    <input type="text" id="car_color" name="car_color" placeholder="Серый"
                           value="<?= htmlspecialchars($users["car_color"] ?? '') ?>">
                </div>
                <div class="input-car-appearence">
                    <label for="car_appearance">Повреждения на машине</label>
                    <input type="text" id="car_appearance" name="car_appearance" placeholder="царапина слева на кузове"
                           value="<?= htmlspecialchars($users["car_appearance"] ?? '') ?>">
                </div>
            </div>
            <div class="date-data">
                <h3>Дата парковки</h3>
                <div>
                    <label for="entry_time">Время въезда</label>
                    <input type="datetime-local" id="entry_time" name="entry_time"
                           value="<?= htmlspecialchars($users["entry_time"] ?? '') ?>">
                </div>
                <div>
                    <label for="exit_time">Время выезда</label>
                    <input type="datetime-local" id="exit_time" name="exit_time"
                           value="<?= htmlspecialchars($users["exit_time"] ?? '') ?>">
                </div>
            </div>
            <div class="tariff">
                <label for="select_tariff">Выберите тариф стоянки</label>
                <div class="select-wrapper">
                    <select id="select_tariff" name="select_tariff">
                        <option value="default">Выберите тариф</option>
                        <option value="create_tariff">Добавить свой тариф</option>
                        <?php foreach ($tariffs as $tariff): ?>
                            <option value="<?= htmlspecialchars($tariff["tariff_id"]) ?>">
                                <?= htmlspecialchars($tariff["tariff_description"]) ?></option>
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
            <div class="spot">
                <label for="spot">Место стоянки</label>
                <div class="select-wrapper">
                    <select id="spot" name="spot">
                        <option value="default">Выберите место стоянки</option>
                        <option value="create_spot">Добавить новое место стоянки</option>
                        <?php foreach ($spots as $spot): ?>
                            <option value="<?= htmlspecialchars($spot["parking_id"]) ?>">
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
                    <label for="amount">К оплате (руб)</label>
                    <input type="text" id="amount" name="amount" placeholder="300"
                           value="<?= htmlspecialchars($payments['amount']) ?>">
                </div>
                <div class="payment-status">
                    <label for="payment_status">Статус оплаты</label>
                    <div class="select-wrapper">
                        <select id="payment_status" name="payment_status">
                            <option value="pending" <?= ($payments['payment_status'] ?? '') === 'pending' ? 'selected' : '' ?>>В ожидании</option>
                            <option value="completed" <?= ($payments['payment_status'] ?? '') === 'completed' ? 'selected' : '' ?>>Успешно</option>
                            <option value="failed" <?= ($payments['payment_status'] ?? '') === 'failed' ? 'selected' : '' ?>>Провалено</option>
                        </select>
                    </div>
                </div>
                <div class="transaction-id">
                    <label for="transaction_id">ID Транзакции</label>
                    <input type="text" id="transaction_id" name="transaction_id" placeholder="TXN80121"
                           value="<?= htmlspecialchars($payments['transaction_id'] ?? '') ?>">
                </div>
            </div>
            <div class="error-form">
                <?php if (!empty($errors)): ?>
                    <ul class="error-list">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            <?php if ($success): ?>
                <div class="success-message">Запись успешно обновлена.</div>
            <?php endif; ?>
            <div class="wrapper-btn">
                <input class="btn-submit" type="submit" value="Отправить">
            </div>
        </form>
    </div>
</main>
</body>
</html>
