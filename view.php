<?php
require_once "config/helpers.php";?>
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
    $record = null;

    try {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT
                u.full_name, u.phone,
                c.car_appearance, c.license_plate, c.car_model, c.car_color,
                ps.spot_number,
                p.entry_time, p.exit_time,
                COALESCE(p.total_price, 0) AS total_price,
                p.is_paid,
                p.id AS parking_id,
                t.tariff_name, t.price_per_hour,
                COALESCE(p.total_price, 0) - COALESCE(
                    (SELECT SUM(amount) FROM payments WHERE parking_id = p.id), 0
                ) AS debt
            FROM parking p
            JOIN cars c ON p.car_id = c.id
            JOIN users u ON c.user_id = u.id
            JOIN parking_spots ps ON p.parking_spot_id = ps.id
            JOIN tariffs t ON p.tariffs_id = t.id
            WHERE p.id = :pid
        ");
        $stmt->execute([':pid' => $parking_id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $errors[] = 'Ошибка базы данных: ' . $e->getMessage();
    }
    ?>

    <div class="container">
        <?php if ($errors): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <?= htmlspecialchars($error) ?>
                <?php endforeach; ?>
            </div>
        <?php elseif ($record): ?>
            <div class="parking-card">
                <h1>Информация о парковке №<?= htmlspecialchars($record['parking_id']) ?></h1>

                <div class="info-row">
                    <span class="info-label">Владелец:</span>
                    <span class="info-value"><?= htmlspecialchars($record['full_name']) ?></span>
                </div>

                <div class="info-row">
                    <span class="info-label">Телефон:</span>
                    <span class="info-value"><?= htmlspecialchars($record['phone']) ?></span>
                </div>

                <div class="info-row">
                    <span class="info-label">Автомобиль:</span>
                    <span class="info-value"><?= htmlspecialchars($record['car_model']) ?>, <?= htmlspecialchars($record['car_color']) ?></span>
                </div>

                <div class="info-row">
                    <span class="info-label">Номер авто:</span>
                    <span class="info-value"><?= htmlspecialchars($record['license_plate']) ?></span>
                </div>

                <div class="info-row">
                    <span class="info-label">Повреждения:</span>
                    <span class="info-value"><?= htmlspecialchars($record['car_appearance'] ?? 'Нет') ?></span>
                </div>

                <div class="info-row">
                    <span class="info-label">Парковочное место:</span>
                    <span class="info-value">№ <?= htmlspecialchars($record['spot_number']) ?></span>
                </div>

                <div class="info-row">
                    <span class="info-label">Тариф:</span>
                    <span class="info-value"><?= htmlspecialchars($record['tariff_name']) ?> — <?= number_format($record['price_per_hour'], 2, '.', ' ') ?> руб/час</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Время въезда:</span>
                    <span class="info-value">
                        <?php
                        if (!empty($record['entry_time'])) {
                            echo htmlspecialchars((new DateTime($record['entry_time']))->format('d.m.Y H:i'));
                        } else {
                            echo '—';
                        }
                        ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Время выезда:</span>
                    <span class="info-value">
                        <?php
                        if (!empty($record['exit_time'])) {
                            echo htmlspecialchars((new DateTime($record['exit_time']))->format('d.m.Y H:i'));
                        } else {
                            echo '—';
                        }
                        ?>
                    </span>
                </div>

                <div class="info-row">
                    <span class="info-label">Общая стоимость:</span>
                    <span class="info-value"><?= number_format($record['total_price'], 0, '.', ' ') ?> ₽</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Оплачено:</span>
                    <span class="info-value"><?= number_format($record['total_price'] - $record['debt'], 0, '.', ' ') ?> ₽</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Долг:</span>
                    <span class="info-value"><?= number_format($record['debt'], 0, '.', ' ') ?> ₽</span>
                </div>

                <div class="info-row">
                    <span class="info-label">Статус оплаты:</span>
                    <span class="info-value">
                        <?php if ($record['is_paid']): ?>
                            <span class="badge badge-success">Оплачено</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Не оплачено</span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        <?php else: ?>
            <div>Запись не найдена</div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
