<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

use Config\Validation;

api_method('GET');
api_require('usuarios.gestionar');

const USUARIOS_ESTADOS = ['activo', 'inactivo', 'bloqueado'];
const USUARIOS_ORDENES = [
    'estado' => 'activo DESC, nombre_completo ASC, id ASC',
    'nombre' => 'nombre_completo ASC, id ASC',
    'usuario' => 'usuario ASC, id ASC',
    'ultimo_acceso' => 'ultimo_acceso DESC NULLS LAST, nombre_completo ASC, id ASC',
];

$errors = [];
$search = Validation::search($_GET, 'q', $errors, Validation::SEARCH_MAX_LENGTH);
$role = Validation::enum($_GET, 'rol', AUTH_ROLES, $errors, 'Seleccione un rol válido.', 20);
$status = Validation::enum($_GET, 'estado', USUARIOS_ESTADOS, $errors, 'Seleccione un estado válido.', 20);
$order = Validation::enum($_GET, 'orden', array_keys(USUARIOS_ORDENES), $errors, 'Seleccione un orden válido.', 20);
if ($order === '') $order = 'estado';

$paginated = (isset($_GET['pagina']) && $_GET['pagina'] !== '') || (isset($_GET['por_pagina']) && $_GET['por_pagina'] !== '');
$page = 1;
$perPage = 0;
$offset = 0;
if ($paginated) {
    [$page, $perPage, $offset] = api_pagination($_GET, 50, 200, $errors);
}
api_validate($errors);

$conditions = [];
$params = [];
if ($search !== '') {
    $searchable = Validation::sqlNormalize("concat_ws(' ', usuario, nombre_completo, registro_profesional)");
    foreach (Validation::searchTokens($search) as $token) {
        $conditions[] = $searchable . ' ' . Validation::SQL_LIKE_CLAUSE;
        $params[] = Validation::likePattern($token);
    }
}
if ($role !== '') {
    $conditions[] = 'rol = ?';
    $params[] = $role;
}
if ($status === 'activo') {
    $conditions[] = 'activo = TRUE';
} elseif ($status === 'inactivo') {
    $conditions[] = 'activo = FALSE';
} elseif ($status === 'bloqueado') {
    $conditions[] = 'activo = TRUE AND bloqueado_hasta IS NOT NULL AND bloqueado_hasta > now()';
}

$where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
$db = api_db();

$totalGeneral = (int) $db->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
$countStmt = $db->prepare('SELECT COUNT(*) FROM usuarios' . $where);
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();

$sql = 'SELECT id, usuario, nombre_completo, rol, registro_profesional, activo, intentos_fallidos,
        CASE WHEN bloqueado_hasta > now() THEN bloqueado_hasta END AS bloqueado_hasta,
        ultimo_acceso, creado_en, actualizado_en
    FROM usuarios' . $where . ' ORDER BY ' . USUARIOS_ORDENES[$order];
$queryParams = $params;
if ($paginated) {
    $sql .= ' LIMIT ? OFFSET ?';
    $queryParams[] = $perPage;
    $queryParams[] = $offset;
}

$statement = $db->prepare($sql);
$statement->execute($queryParams);
$rows = $statement->fetchAll();
foreach ($rows as &$row) {
    $row['rol_nombre'] = AUTH_ROLE_LABELS[$row['rol']] ?? $row['rol'];
}
unset($row);

$meta = ['total' => $total, 'total_general' => $totalGeneral];
if ($paginated) {
    $meta['pagina'] = $page;
    $meta['por_pagina'] = $perPage;
}
api_json(['success' => true, 'data' => $rows, 'roles' => AUTH_ROLE_LABELS, 'meta' => $meta]);
