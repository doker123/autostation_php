<?php ob_start();
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
    $host = null;

    if ($parking_id) {
        try {
            $pdo = Database::getInstance();
            $sql = "SELECT
                    u.full_name,
                    u.phone
                    FROM parking p
                    JOIN cars c ON p.car_id = c.id
                    JOIN users u ON c.user_id = u.id
                    JOIN parking_spots ps ON p.spot_id = ps.id
                    WHERE p.id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$parking_id]);
            $users = $stmt->fetch();
        } catch (Exception $e) {
            $errors[] = "Ошибка базы данных" . $e->getMessage();
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
                               value="<?= htmlspecialchars(
                                   $host["full_name"] ?? "",
                               ) ?>">
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
</main>
</body>
</html>
