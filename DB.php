<?php
declare(strict_types=1);

class DB
{
    private static ?PDO $pdo = null;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public static function getConnection(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        // Basisverzeichnis: dort, wo DB.php liegt
        $baseDir = __DIR__;

        $configFile        = $baseDir . '/config/config.php';
        $exampleConfigFile = $baseDir . '/config/config.example.php';

        if (file_exists($configFile)) {
            $configPath = $configFile;
        } elseif (file_exists($exampleConfigFile)) {
            $configPath = $exampleConfigFile;
        } else {
            throw new RuntimeException(
                "Konfigurationsdatei nicht gefunden. Weder '{$configFile}' noch '{$exampleConfigFile}' existiert."
            );
        }

        /** @var array<string,string> $config */
        $config = require $configPath;

        foreach (['db_host', 'db_name', 'db_user', 'db_pass', 'db_charset'] as $key) {
            if (!array_key_exists($key, $config) || $config[$key] === '') {
                throw new RuntimeException("Datenbank-Konfigurationswert '{$key}' fehlt oder ist leer.");
            }
        }

        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $config['db_host'],
            $config['db_name'],
            $config['db_charset']
        );

        try {
            self::$pdo = new PDO(
                $dsn,
                $config['db_user'],
                $config['db_pass'],
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            throw new RuntimeException(
                'Datenbankverbindung fehlgeschlagen: ' . $e->getMessage(),
                0,
                $e
            );
        }

        return self::$pdo;
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = self::query($sql, $params);
        $row  = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    public static function lastInsertId(): string
    {
        return self::getConnection()->lastInsertId();
    }
}
