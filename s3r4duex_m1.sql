-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Время создания: Июн 15 2026 г., 04:56
-- Версия сервера: 10.4.32-MariaDB
-- Версия PHP: 8.5.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `s3r4duex_m1`
--

-- --------------------------------------------------------

--
-- Структура таблицы `cars`
--

CREATE TABLE `cars` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `license_plate` varchar(20) NOT NULL,
  `car_model` varchar(100) DEFAULT NULL,
  `car_color` varchar(50) DEFAULT NULL,
  `car_appearance` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Дамп данных таблицы `cars`
--

INSERT INTO `cars` (`id`, `user_id`, `license_plate`, `car_model`, `car_color`, `car_appearance`, `created_at`) VALUES
(1, 1, 'A123BC777', 'Toyota Camry', 'серебристый', 'Без повреждений', '2026-05-05 02:42:26'),
(2, 2, 'B456DE777', 'Ford Focus', 'синий', 'Царапина на крыле', '2026-05-05 02:42:26'),
(3, 3, 'C789EF777', 'Honda Civic', 'белый', 'Потёртости на бампере', '2026-05-05 02:42:26'),
(4, 7, 'аываываыва', 'ываываыва', 'авываыв', 'аываыва', '2026-06-10 19:46:34'),
(6, 9, '13123123', 'вававыаыв', 'авыавыаыва', 'выаываыва', '2026-06-15 09:54:50');

-- --------------------------------------------------------

--
-- Структура таблицы `parking`
--

CREATE TABLE `parking` (
  `id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `parking_spot_id` int(11) NOT NULL,
  `entry_time` datetime DEFAULT current_timestamp(),
  `exit_time` datetime DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT 0.00,
  `is_paid` tinyint(1) DEFAULT 0,
  `payment_method` enum('cash','card','online') DEFAULT 'cash',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `tariffs_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Дамп данных таблицы `parking`
--

INSERT INTO `parking` (`id`, `car_id`, `parking_spot_id`, `entry_time`, `exit_time`, `total_price`, `is_paid`, `payment_method`, `notes`, `created_at`, `updated_at`, `tariffs_id`) VALUES
(3, 3, 3, '2023-10-01 09:15:00', '2023-10-01 18:45:00', 950.00, 1, 'cash', NULL, '2026-05-05 02:42:26', '2026-06-14 19:42:28', 1),
(6, 6, 1, '2026-06-15 09:54:50', NULL, 500.00, 1, 'cash', NULL, '2026-06-15 09:54:50', '2026-06-15 09:54:50', 1);

-- --------------------------------------------------------

--
-- Структура таблицы `parking_spots`
--

CREATE TABLE `parking_spots` (
  `id` int(11) NOT NULL,
  `spot_number` varchar(10) NOT NULL,
  `spot_type` enum('regular','disabled','family') DEFAULT 'regular',
  `is_occupied` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Дамп данных таблицы `parking_spots`
--

INSERT INTO `parking_spots` (`id`, `spot_number`, `spot_type`, `is_occupied`, `created_at`) VALUES
(1, 'A1', 'regular', 1, '2026-05-05 02:42:26'),
(2, 'A2', 'regular', 0, '2026-05-05 02:42:26'),
(3, 'B1', 'disabled', 1, '2026-05-05 02:42:26'),
(4, 'B2', 'family', 0, '2026-05-05 02:42:26'),
(5, 'C1', 'regular', 0, '2026-05-05 02:42:26'),
(6, 'C2', 'regular', 0, '2026-05-05 02:42:26'),
(7, 'D1', 'regular', 0, '2026-05-05 02:42:26'),
(8, 'D2', 'regular', 0, '2026-05-05 02:42:26');

-- --------------------------------------------------------

--
-- Структура таблицы `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `parking_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` datetime DEFAULT current_timestamp(),
  `payment_status` enum('completed','pending','failed') DEFAULT 'completed',
  `transaction_id` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Дамп данных таблицы `payments`
--

INSERT INTO `payments` (`id`, `parking_id`, `amount`, `payment_date`, `payment_status`, `transaction_id`) VALUES
(2, 3, 950.00, '2026-05-05 02:42:26', 'completed', 'TXN12346'),
(4, 6, 500.00, '2026-06-15 09:54:50', 'completed', '4234234');

-- --------------------------------------------------------

--
-- Структура таблицы `tariffs`
--

CREATE TABLE `tariffs` (
  `id` int(11) NOT NULL,
  `tariff_name` varchar(100) NOT NULL,
  `price_per_hour` decimal(8,2) NOT NULL,
  `min_price` decimal(8,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Дамп данных таблицы `tariffs`
--

INSERT INTO `tariffs` (`id`, `tariff_name`, `price_per_hour`, `min_price`, `description`, `is_active`, `created_at`) VALUES
(1, 'Стандартный', 100.00, 100.00, 'Базовый тариф — 100 руб./час', 1, '2026-05-05 02:42:26'),
(2, 'Ночной', 50.00, 200.00, 'С 22:00 до 08:00 — 50 руб./час, минимум 200 руб.', 1, '2026-05-05 02:42:26'),
(3, 'Суточный', 800.00, 800.00, 'Фиксированная цена за сутки', 1, '2026-05-05 02:42:26');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `full_name`, `phone`, `created_at`) VALUES
(1, 'Иванов Иван Иванович', '+79123456789', '2026-05-05 02:41:44'),
(2, 'Петров Пётр Петрович', '+79234567890', '2026-05-05 02:41:44'),
(3, 'Сидорова Анна Сергеевна', '+79345678901', '2026-05-05 02:41:44'),
(4, 'Иванов Иван Иванович', '+79123456789', '2026-05-05 02:42:26'),
(5, 'Петров Пётр Петрович', '+79234567890', '2026-05-05 02:42:26'),
(6, 'Сидорова Анна Сергеевна', '+79345678901', '2026-05-05 02:42:26'),
(7, 'тывтадываыааываы', 'аываываываываыва', '2026-06-10 19:46:34'),
(9, 'вфвфывфыв', '+7999999999', '2026-06-15 09:54:50');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `cars`
--
ALTER TABLE `cars`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `license_plate` (`license_plate`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `parking`
--
ALTER TABLE `parking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `car_id` (`car_id`),
  ADD KEY `parking_spot_id` (`parking_spot_id`),
  ADD KEY `tarriffs_id` (`tariffs_id`) USING BTREE;

--
-- Индексы таблицы `parking_spots`
--
ALTER TABLE `parking_spots`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `spot_number` (`spot_number`);

--
-- Индексы таблицы `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `parking_id` (`parking_id`);

--
-- Индексы таблицы `tariffs`
--
ALTER TABLE `tariffs`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `cars`
--
ALTER TABLE `cars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `parking`
--
ALTER TABLE `parking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `parking_spots`
--
ALTER TABLE `parking_spots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT для таблицы `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `tariffs`
--
ALTER TABLE `tariffs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `cars`
--
ALTER TABLE `cars`
  ADD CONSTRAINT `cars_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `parking`
--
ALTER TABLE `parking`
  ADD CONSTRAINT `parking_ibfk_1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `parking_ibfk_2` FOREIGN KEY (`parking_spot_id`) REFERENCES `parking_spots` (`id`);

--
-- Ограничения внешнего ключа таблицы `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`parking_id`) REFERENCES `parking` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
