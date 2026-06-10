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
    <a href="<?= url(' ')?>"><h1>Автостоянка</h1></a>
</header>
<?php
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);
?>
<main class="main-content">
    <?php
    require_once "config/connectionDb.php";
    try {
        $pdo = Database::getInstance();
        $sql = "SELECT
                u.full_name AS full_name,
                u.phone AS phone,
                COALESCE(p.total_price, 0) - COALESCE((
                    SELECT SUM(amount)
                    FROM payments
                    WHERE parking_id = p.id
                ), 0) AS debt,
                c.car_appearance AS car_appearance,
                ps.spot_number AS spot_number,
                p.entry_time AS entry_time,
                COALESCE(p.total_price, 0) AS total_price,
                p.is_paid AS is_paid,
                p.id AS parking_id
                FROM parking p
                JOIN cars c ON p.car_id = c.id
                JOIN users u ON c.user_id = u.id
                JOIN parking_spots ps ON p.parking_spot_id = ps.id
                ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Ошибка базы данных: " . $e->getMessage());
    }
    ?>
    <section class="main-table">
        <div class="table">
            <div class="table-head row-table">
                <div class="cell">Фамилия и Имя</div>
                <div class="cell">Номер телефона</div>
                <div class="cell">Долг</div>
                <div class="cell">Внешний вид машины</div>
                <div class="cell">Место на стоянке</div>
                <div class="cell">Время парковки</div>
                <div class="cell">Итоговая цена парковки</div>
                <div class="cell">Оплачена ли парковка</div>
                <div class="action cell">Действия с записью</div>
            </div>
            <?php if (!empty($records)): ?>
                <?php foreach ($records as $row): ?>
                    <div class="row-data">
                        <div class="cell"><?= htmlspecialchars(
                            $row["full_name"] ?? "",
                        ) ?></div>
                        <div class="cell"><?= htmlspecialchars(
                            $row["phone"] ?? "",
                        ) ?></div>
                        <div class="cell"><?= number_format(
                            $row["debt"] ?? 0,
                            2,
                            ",",
                            " ",
                        ) ?> руб.
                        </div>
                        <div class="cell"><?= htmlspecialchars(
                            $row["car_appearance"] ?? "Не указан",
                        ) ?></div>
                        <div class="cell"><?= htmlspecialchars(
                            $row["spot_number"] ?? "",
                        ) ?></div>
                        <div class="cell">
                            <?php
                            $entryTime = $row["entry_time"] ?? "";
                            if (!empty($entryTime)) {
                                try {
                                    $dateTime = new DateTime($entryTime);
                                    echo $dateTime->format("d.m.Y H:i");
                                } catch (Exception $e) {
                                    echo "Некорректная дата";
                                }
                            } else {
                                echo "Дата отсутствует";
                            }
                            ?>
                        </div>
                        <div class="cell"><?= number_format(
                            $row["total_price"] ?? 0,
                            2,
                            ",",
                            " ",
                        ) ?> руб.
                        </div>
                        <div class="cell">
                            <?php if ($row["is_paid"]) { ?>
                                <span class="status-paid">Оплачено</span>
                            <?php } else { ?>
                                <span class="status-unpaid">Не оплачено</span>
                            <?php } ?>
                        </div>
                        <div class="action">
                            <a class="btn btn-view" href="<?= url('view.php?id=' . (int)$row['parking_id']) ?>">Подробнее</a>
                                <a class="btn btn-edit" href="<?= url('edit.php?id=' . (int)$row['parking_id']) ?>">Редактировать</a>
                                <a class="btn btn-delete" href="<?= url('delete.php?id=' . (int)$row['parking_id']) ?>">Удалить</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div>Данные отсутствуют</div>
            <?php endif; ?>
            <div class="create-row">
                <a class="create-row" href="<?= url("create.php") ?>">Создать запись
                </a>
            </div>
        </div>
    </section>
</main>
</body>
</html>
