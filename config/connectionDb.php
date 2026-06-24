<?php

class Database
{
    private static ?\PDO $instance = null;

    public static function getInstance(): \PDO
    {
        if (self::$instance === null) {
            self::$instance = self::createConnection();
        }
        return self::$instance;
    }

    private static function createConnection(): \PDO
    {
        $config = self::loadConfig();

        $dsn = sprintf(
            "mysql:host=%s;dbname=%s;charset=%s",
            $config["DB_HOST"],
            $config["DB_NAME"],
            $config["DB_CHARSET"] ?? "utf8mb4",
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        return new \PDO($dsn, $config["DB_USERNAME"], $config["DB_PASSWORD"], $options);
    }

    private static function loadConfig(): array
    {
        $envFile = dirname(__DIR__) . '/.env';

        if (!file_exists($envFile)) {
            throw new \RuntimeException("Файл .env не найден: {$envFile}");
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $config = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }

            if (strpos($line, '=') === false) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");

            $config[$key] = $value;
        }

        $required = ['DB_HOST', 'DB_NAME', 'DB_USERNAME', 'DB_PASSWORD'];
        foreach ($required as $key) {
            if (empty($config[$key])) {
                throw new \RuntimeException("Обязательная переменная {$key} не задана в .env");
            }
        }

        return $config;
    }
}
