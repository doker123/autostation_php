<?php
class Database
{
    private static ?\PDO $instance = null;
    private static array $config = [
        'host' => 'mysqldb',
//        'host' => 'localhost',
        'dbname' => 's3r4duex_m1',
        'username' => 's3r4duex',
        'password' => 'oyyqwF02',
        'charset' => 'utf8mb4',

    ];
    public static function getInstance(): \PDO
    {
        if (self::$instance === null) {
            self::$instance = self::createConnection();
        }
        return self::$instance;
    }
    private static function createConnection(): \PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            self::$config['host'],
            self::$config['dbname'],
            self::$config['charset']
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        return new \PDO($dsn, self::$config['username'], self::$config['password'], $options);
    }
}