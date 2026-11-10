<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
use Config\Validation;

api_method('GET');
api_require('auditoria.ver');
$errors = [];
[$page, $perPage, $offset] = api_pagination($_GET, 50, 200, $errors);
$conditions = [];
$params = [];
if (isset($_GET['usuario_id']) && $_GET['usuario_id'] !== '') {
    $userId = Validation::positiveId($_GET['usuario_id']);
    if ($userId === null) $errors['usuario_id'] = 'Seleccione un usuario válido.';
    else { $conditions[] = 'a.usuario_id = ?'; $params[] = $userId; }
}
$action = Validation::text($_GET, 'accion', 50, $errors);
if ($action !== '') {
    if (!preg_match('/^[a-z_]{2,50}$/D', $action)) $errors['accion'] = 'Seleccione una acción válida.';
    else { $conditions[] = 'a.accion = ?'; $params[] = $action; }
}
$from = Validation::text($_GET, 'desde', 10, $errors);
$to = Validation::text($_GET, 'hasta', 10, $errors);
if ($from !== '' && !Validation::date($from)) $errors['desde'] = 'Indique una fecha válida (AAAA-MM-DD).';
if ($to !== '' && !Validation::date($to)) $errors['hasta'] = 'Indique una fecha válida (AAAA-MM-DD).';
if ($from !== '' && $to !== '' && !isset($errors['desde']) && !isset($errors['hasta']) && $to < $from) $errors['hasta'] = 'La fecha final debe ser igual o posterior a la inicial.';
api_validate($errors);
// Las fechas se interpretan en la zona horaria de la aplicación (fijada en la conexión).
if ($from !== '') { $conditions[] = 'a.creado_en >= CAST(? AS DATE)'; $params[] = $from; }
if ($to !== '') { $conditions[] = "a.creado_en < CAST(? AS DATE) + INTERVAL '1 day'"; $params[] = $to; }
$where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';

$db = api_db();
$statement = $db->prepare('SELECT COUNT(*) FROM auditoria a' . $where);
$statement->execute($params);
$total = (int) $statement->fetchColumn();
$statement = $db->prepare('SELECT a.id, a.usuario_id, a.usuario, u.nombre_completo, a.accion, a.entidad, a.entidad_id, a.detalle, a.ip, a.creado_en
    FROM auditoria a LEFT JOIN usuarios u ON u.id = a.usuario_id' . $where . '
    ORDER BY a.creado_en DESC, a.id DESC LIMIT ? OFFSET ?');
$statement->execute([...$params, $perPage, $offset]);
$rows = $statement->fetchAll();
foreach ($rows as &$row) {
    $decoded = json_decode((string) $row['detalle'], true);
    $row['detalle'] = is_array($decoded) ? $decoded : new stdClass();
}
unset($row);
api_json(['success' => true, 'data' => $rows, 'meta' => ['total' => $total, 'pagina' => $page, 'por_pagina' => $perPage]]);
