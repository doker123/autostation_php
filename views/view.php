<?php
$parking_id = $id ?? '';
$errors = [];
$record = null;


try {
    $pdo = Database::getInstance();
    $stmt = $pdo->prepare("
        SELECT
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
            WHERE p.id = :parking_id
    ");
    $stmt->execute(['parking_id' => $parking_id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = 'Ошибка базы данных' . $e->getMessage();
}

?>

<div class="container">
    <?php if ($errors): ?>
        <div class="error">
            <?php foreach ($errors as $error): ?>
            <?php echo $error; ?>
            <?php endforeach; ?>
        </div>
    <?php elseif ($record): ?>
        <div class="parking-card">
            <h1>Информация о парковке №<?php echo htmlspecialchars($record['parking_id']); ?></h1>

            <div class="info-row">
                <span class="info-label">Владелец:</span>
                <span class="info-value"><?php echo htmlspecialchars($record['full_name']); ?></span>
            </div>

            <div class="info-row">
                <span class="info-label">Телефон:</span>
                <span class="info-value"><?php echo htmlspecialchars($record['phone']); ?></span>
            </div>

            <div class="info-row">
                <span class="info-label">Автомобиль:</span>
                <span class="info-value"><?php echo htmlspecialchars($record['car_appearance']); ?></span>
            </div>

            <div class="info-row">
                <span class="info-label">Парковочное место:</span>
                <span class="info-value">№ <?php echo htmlspecialchars($record['spot_number']); ?></span>
            </div>

            <div class="info-row">
                <span class="info-label">Время въезда:</span>
                <span class="info-value"><?php echo htmlspecialchars($record['entry_time']); ?></span>
            </div>

            <div class="info-row">
                <span class="info-label">Общая стоимость:</span>
                <span class="info-value"><?php echo number_format($record['total_price'], 0, '.', ' '); ?> ₽</span>
            </div>

            <div class="info-row">
                <span class="info-label">Долг:</span>
                <span class="info-value"><?php echo number_format($record['debt'], 0, '.', ' '); ?> ₽</span>
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
    <?php endif; ?>
</div>