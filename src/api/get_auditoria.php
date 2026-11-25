<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

use Config\Validation;

api_method('GET');
api_require('auditoria.ver');

const AUDITORIA_ACCIONES = [
    'login_ok',
    'login_fallido',
    'logout',
    'cambiar_password',
    'cambiar_password_fallido',
    'crear_usuario',
    'editar_usuario',
    'guardar_usuario',
    'guardar_paciente',
    'crear_consulta',
    'editar_consulta',
    'ver_expediente',
    'ver_consulta',
    'descargar_adjunto',
    'crear_cita',
    'guardar_cita',
    'reprogramar_cita',
    'cambiar_estado_cita',
    'actualizar_estado_cita',
    'consultar_morbilidad',
    'exportar_morbilidad',
    'imprimir_historia',
    'imprimir_informe',
    'imprimir_reposo',
    'imprimir_recipe',
    'imprimir_receta',
    'imprimir_morbilidad',
];
const AUDITORIA_ENTIDADES = ['paciente', 'consulta', 'cita', 'usuario', 'reporte'];

$errors = [];
[$page, $perPage, $offset] = api_pagination($_GET, 50, 200, $errors);
$userId = Validation::optionalPositiveId($_GET, 'usuario_id', $errors, 'Seleccione un usuario válido.');
$action = Validation::enum($_GET, 'accion', AUDITORIA_ACCIONES, $errors, 'Seleccione una acción válida.', 50);
$entity = Validation::enum($_GET, 'entidad', AUDITORIA_ENTIDADES, $errors, 'Seleccione una entidad válida.', 30);
[$from, $to] = Validation::optionalDateRange($_GET, $errors, 'desde', 'hasta');
$search = Validation::search($_GET, 'q', $errors, Validation::SEARCH_MAX_LENGTH);
api_validate($errors);

$conditions = [];
$params = [];
if ($userId !== null) {
    $conditions[] = 'a.usuario_id = ?';
    $params[] = $userId;
}
if ($action !== '') {
    $conditions[] = 'a.accion = ?';
    $params[] = $action;
}
if ($entity !== '') {
    $conditions[] = 'a.entidad = ?';
    $params[] = $entity;
}
// Las fechas se interpretan en la zona horaria de la aplicación (fijada en la conexión).
if ($from !== '') {
    $conditions[] = 'a.creado_en >= CAST(? AS DATE)';
    $params[] = $from;
}
if ($to !== '') {
    $conditions[] = "a.creado_en < CAST(? AS DATE) + INTERVAL '1 day'";
    $params[] = $to;
}
if ($search !== '') {
    $searchable = Validation::sqlNormalize("concat_ws(' ', a.usuario, u.nombre_completo, a.accion, a.entidad, a.ip)");
    foreach (Validation::searchTokens($search) as $token) {
        $conditions[] = $searchable . ' ' . Validation::SQL_LIKE_CLAUSE;
        $params[] = Validation::likePattern($token);
    }
}

$fromSql = ' FROM auditoria a LEFT JOIN usuarios u ON u.id = a.usuario_id';
$where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';

$db = api_db();
$statement = $db->prepare('SELECT COUNT(*)' . $fromSql . $where);
$statement->execute($params);
$total = (int) $statement->fetchColumn();

$statement = $db->prepare('SELECT a.id, a.usuario_id, a.usuario, u.nombre_completo, a.accion, a.entidad, a.entidad_id, a.detalle, a.ip, a.creado_en'
    . $fromSql . $where . '
    ORDER BY a.creado_en DESC, a.id DESC LIMIT ? OFFSET ?');
$statement->execute([...$params, $perPage, $offset]);
$rows = $statement->fetchAll();
foreach ($rows as &$row) {
    $decoded = json_decode((string) $row['detalle'], true);
    $row['detalle'] = is_array($decoded) ? $decoded : new stdClass();
}
unset($row);

api_json([
    'success' => true,
    'data' => $rows,
    'meta' => ['total' => $total, 'pagina' => $page, 'por_pagina' => $perPage],
]);
