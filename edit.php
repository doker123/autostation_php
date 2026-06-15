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

        $sql = "SELECT
                    u.full_name as full_name,
                    u.phone as phone,
                    c.car_appearance as car_appearance,
                    c.car_model as car_model,
                    c.car_color as car_color,
                    p.entry_time AS entry_time,
                    p.exit_time AS exit_time
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
                            $users['entry_time'],
                            )?>">
                </div>
                <div>
                <label for="entry_time">Время въезда</label>
                <input type="datetime-local" id="entry_time" name="entry_time" 
                value="<?= htmlspecialchars(
                    $users['entry_time']
                    ) ?>">
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
                                <?= htmlspecialchars(
                                    $spot["spot_number"],
                                ) ?></option>
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
            <div class="wrapper-btn">
                <input class="btn-submit" type="submit" value="Отправить">
            </div>
        </form>
    </div>
</main>
</body>
</html>
