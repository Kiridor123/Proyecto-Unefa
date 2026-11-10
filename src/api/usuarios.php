<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

api_method('GET');
api_require('usuarios.gestionar');
$rows = api_db()->query('SELECT id, usuario, nombre_completo, rol, registro_profesional, activo, intentos_fallidos,
        CASE WHEN bloqueado_hasta > now() THEN bloqueado_hasta END AS bloqueado_hasta,
        ultimo_acceso, creado_en, actualizado_en
    FROM usuarios ORDER BY activo DESC, nombre_completo ASC, id ASC')->fetchAll();
foreach ($rows as &$row) {
    $row['rol_nombre'] = AUTH_ROLE_LABELS[$row['rol']] ?? $row['rol'];
}
unset($row);
api_json(['success' => true, 'data' => $rows, 'roles' => AUTH_ROLE_LABELS]);
