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
    fwrite(STDOUT, "Esquema actualizado; migraciones aplicadas: {$applied}.\n");
} catch (Throwable $error) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    fwrite(STDERR, 'No se pudo actualizar el esquema. Revise la conexión y los permisos de la base de datos. Código: ' . $error->getCode() . "\n");
    exit(1);
}
