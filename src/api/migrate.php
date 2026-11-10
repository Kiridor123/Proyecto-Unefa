<?php
declare(strict_types=1);
// Schema upgrades are administrative commands, never a public HTTP endpoint.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
    echo 'Recurso no disponible.';
    exit;
}
require_once __DIR__ . '/../config/Database.php';
try {
    $db = Config\Database::getInstance()->getConnection();
    $db->beginTransaction();
    $db->query('SELECT pg_advisory_xact_lock(824319103)');
    $db->exec('CREATE TABLE IF NOT EXISTS schema_migrations (version VARCHAR(100) PRIMARY KEY, applied_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP)');
    $files = glob(__DIR__ . '/../config/migrations/*.sql');
    sort($files, SORT_STRING);
    $applied = 0;
    foreach ($files as $file) {
        $version = basename($file);
        $statement = $db->prepare('SELECT version FROM schema_migrations WHERE version = ?');
        $statement->execute([$version]);
        if ($statement->fetchColumn()) continue;
        $sql = file_get_contents($file);
        if ($sql === false) throw new RuntimeException('Migration file unavailable.');
        $db->exec($sql);
        $statement = $db->prepare('INSERT INTO schema_migrations (version) VALUES (?)');
        $statement->execute([$version]);
        $applied++;
    }
    $db->commit();
    fwrite(STDOUT, "Esquema actualizado; migraciones aplicadas: {$applied}." . PHP_EOL);
    salud_seed_admin($db);
} catch (Throwable $error) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    fwrite(STDERR, 'No se pudo actualizar el esquema. Revise la conexión y los permisos de la base de datos. Código: ' . $error->getCode() . PHP_EOL);
    exit(1);
}

/** Crea el primer administrador desde ADMIN_USER/ADMIN_PASSWORD si aún no hay usuarios. */
function salud_seed_admin(PDO $db): void
{
    $db->beginTransaction();
    try {
        $db->query('SELECT pg_advisory_xact_lock(824319104)');
        if ((int) $db->query('SELECT COUNT(*) FROM usuarios')->fetchColumn() > 0) {
            $db->commit();
            return;
        }
        $password = getenv('ADMIN_PASSWORD');
        $username = strtolower(trim((string) (getenv('ADMIN_USER') ?: 'admin')));
        $warning = null;
        if (!is_string($password) || $password === '') {
            $warning = 'no hay usuarios. Defina ADMIN_PASSWORD (mínimo 10 caracteres) y vuelva a ejecutar la migración para crear el administrador inicial.';
        } elseif (mb_strlen($password, 'UTF-8') < 10) {
            $warning = 'ADMIN_PASSWORD debe tener al menos 10 caracteres; no se creó el administrador inicial.';
        } elseif (!preg_match('/^[a-z0-9._-]{3,50}$/D', $username)) {
            $warning = 'ADMIN_USER solo admite de 3 a 50 letras minúsculas, números, punto, guion o guion bajo; no se creó el administrador inicial.';
        }
        if ($warning !== null) {
            $db->commit();
            fwrite(STDOUT, 'Aviso: ' . $warning . PHP_EOL);
            return;
        }
        $statement = $db->prepare("INSERT INTO usuarios (usuario, nombre_completo, rol, password_hash) VALUES (?, 'Administrador', 'administrador', ?)");
        $statement->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
        $statement = $db->prepare("INSERT INTO auditoria (usuario, accion, entidad, detalle) VALUES (?, 'crear_usuario', 'usuario', CAST(? AS JSONB))");
        $statement->execute([$username, json_encode(['origen' => 'migracion'])]);
        $db->commit();
        fwrite(STDOUT, "Administrador inicial creado: {$username}. Cambie la contraseña tras el primer inicio de sesión." . PHP_EOL);
    } catch (Throwable $error) {
        if ($db->inTransaction()) $db->rollBack();
        throw $error;
    }
}
