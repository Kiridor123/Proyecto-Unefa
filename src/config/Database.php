<?php
namespace Config;

use PDO;
use PDOException;

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        // Se obtienen las credenciales de variables de entorno de Docker
        $host = getenv('DB_HOST') ?: 'db';
        $port = getenv('DB_PORT') ?: '5432';
        $db   = getenv('DB_NAME') ?: 'unefa_medica';
        $user = getenv('DB_USER') ?: 'unefa_admin';
        $pass = getenv('DB_PASSWORD') ?: 'unefa_secure_pass';

        $dsn = "pgsql:host=$host;port=$port;dbname=$db";

        try {
            $this->connection = new PDO($dsn, $user, $pass);
            
            // Atributos de seguridad y robustez
            // Lanzar excepciones para manejar errores adecuadamente
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Deshabilitar la emulación de sentencias preparadas (Seguridad contra inyección SQL)
            $this->connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
            // Retornar arreglos asociativos por defecto
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            // En entorno de producción, registrar en un log en lugar de mostrar en pantalla
            error_log("Error de conexión PDO: " . $e->getMessage());
            die("Fallo crítico: No se pudo conectar a la base de datos.");
        }
    }

    // Prevenir la clonación de la instancia
    private function __clone() {}

    // Prevenir la deserialización de la instancia
    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }

    // Método estático para obtener la única instancia de la conexión
    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    // Retorna la conexión activa
    public function getConnection() {
        return $this->connection;
    }
}
