<?php
declare(strict_types=1);

namespace Config;

use PDO;

final class Database
{
    private static ?self $instance = null;
    private PDO $connection;

    private function __construct()
    {
        $host = getenv('DB_HOST') ?: 'db';
        $port = getenv('DB_PORT') ?: '5432';
        $name = getenv('DB_NAME') ?: 'unefa_medica';
        $user = getenv('DB_USER') ?: 'unefa_admin';
        $password = getenv('DB_PASSWORD') ?: 'unefa_secure_pass';
        $this->connection = new PDO("pgsql:host={$host};port={$port};dbname={$name};connect_timeout=5", $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]);
        $timezone = getenv('APP_TIMEZONE') ?: 'America/Caracas';
        if (!in_array($timezone, \DateTimeZone::listIdentifiers(), true)) $timezone = 'America/Caracas';
        $statement = $this->connection->prepare("SELECT set_config('TimeZone', ?, false)");
        $statement->execute([$timezone]);
    }

    private function __clone() {}

    public function __wakeup(): void
    {
        throw new \LogicException('Cannot unserialize database connection.');
    }

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }
}
