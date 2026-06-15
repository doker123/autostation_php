<?php
require_once "config/helpers.php";
?>
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
    $parking_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$parking_id) {
        die('Не указан корректный ID записи');
    }
    $errors = [];
    $success = false;
    $record = null;
    $form = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $form['fio'] = trim($_POST['fio'] ?? '');
        $form['phone'] = trim($_POST['phone'] ?? '');

        if (empty($form['fio']) || empty($form['phone'])) {
            $errors[] = 'ФИО и номер телефона обязательны';
        }
        
    }

    try {
        $pdo = Database::getInstance();
        $sql = "SELECT
                    u.full_name as full_name,
                    u.phone as phone,
                    c.car_appearance as car_appearance,
                    c.car_model as car_model,
                    c.car_color as car_color,
                    p.entry_time AS entry_time,
                    p.exit_time AS exit_time,
                    t.tariff_name AS tariff_name,
                    t.price_per_hour AS tariff_price
                    FROM parking p
                    JOIN cars c ON p.car_id = c.id
                    JOIN users u ON c.user_id = u.id
                    JOIN parking_spots ps ON p.parking_spot_id = ps.id
                    JOIN tariffs t ON p.tariffs_id = t.id
                    WHERE p.id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$parking_id]);
        $users = $stmt->fetch();

        if (!$users) {
            $errors[] = 'Запись с указанным ID не найдена';
        }

    } catch (Exception $e) {
        $errors[] = "Ошибка базы данных" . $e->getMessage();
    }
    ?>
    <?php if (!empty($errors)): ?>
        <div class="error-messages">
            <?php foreach ($errors as $error): ?>
                <p class="error"><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <div class="create-form">
        <form method="POST">
            <div class="host-data">
                <h3>Данные паркующегося</h3>
                <div>
                    <div class="input-fio">
                        <label for="fio">Фио паркующегося</label>
                        <input type="text" id="fio" name="fio" placeholder="Петров Петр Петрович"
                               value="<?= htmlspecialchars(
                                   $users["full_name"],
                               ) ?>">
                    </div>
                    <div class="input-phone">
                        <label for="phone">Номер паркующегося</label>
                        <input type="tel" autocomplete="tel" id="phone" name="phone" placeholder="+7999999999"
                               value="<?= htmlspecialchars(
                                   $users["phone"],
                               ) ?>">
                    </div>
                </div>
            </div>
            <div class ="cars-data">
                <h3>Данные о машине</h3>
                <div class="input-car-model">
                    <label for="car_model">Модель машины</label>
                    <input type="text" id="car_model" name="car_model" placeholder="Ford"
                    value ="<?= htmlspecialchars(
                            $users["car_model"],
                            )?>">
                </div>
                <div class="input-car-color">
                    <label for="car_color">Цвет машины</label>
                    <input type="text" id="car_color" name="car_color" placeholder="Серый"
                    value ="<?= htmlspecialchars(
                            $users["car_color"],
                            )?>">
                </div>
                <div class="input-car-appearence">
                    <label for="car_appearance">Повреждения на машине</label>
                    <input type="text" id="car_appearance" name="car_appearance" placeholder="царапина слева на кузове с переди"
                    value ="<?= htmlspecialchars(
                            $users["car_appearance"],
                            )?>">
                </div>
            </div>
            <div class = "date-data">
                <h3>Дата парковки</h3>
                <div class="">
                    <label for="entry_time">Время вьезда</label>
                <input type="datetime-local" id="entry_time" name="entry_time"
                    value ="<?= htmlspecialchars(
                            htmlspecialchars($users['entry_time'] ?? '', ENT_QUOTES, 'UTF-8')
                            )?>">
                </div>
                <div>
                    <label for="exit_time">Время выезда</label>
                <input type="datetime-local" id="exit_time" name="exit_time"
                    value ="<?= htmlspecialchars(
                            $users["exit_time"],
                            )?>">
                </div>
            </div>
            <div class="wrapper-btn">
                <input class="btn-submit" type="submit" value="Отправить">
            </div>
        </form>
    </div>
</main>
</body>
</html>
