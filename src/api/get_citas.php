<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/Citas.php';

use Config\Validation;

api_method('GET');
api_require('citas.gestionar');

$errors = [];
$status = Validation::enum($_GET, 'estado', CITA_ESTADOS, $errors, 'Seleccione un estado válido.', 20);
$fecha = Validation::text($_GET, 'fecha', 10, $errors);
if ($fecha !== '' && !Validation::date($fecha)) {
    $errors['fecha'] = 'Indique una fecha válida (AAAA-MM-DD).';
}
[$desde, $hasta] = Validation::optionalDateRange($_GET, $errors, 'desde', 'hasta');
$categoryId = Validation::optionalPositiveId($_GET, 'categoria_id', $errors, 'Seleccione una categoría válida.');
$search = Validation::search($_GET, 'q', $errors, Validation::SEARCH_MAX_LENGTH);
$order = Validation::enum($_GET, 'orden', ['asc', 'desc'], $errors, 'Indique un orden válido (asc o desc).', 4);
if ($order === '') $order = 'asc';
[$page, $perPage, $offset] = api_pagination($_GET, 50, 200, $errors);
api_validate($errors);

$conditions = [];
$params = [];
if ($status !== '') {
    $conditions[] = 'c.estado = ?';
    $params[] = $status;
}
if ($fecha !== '') {
    $conditions[] = "c.fecha_cita >= CAST(? AS DATE) AND c.fecha_cita < CAST(? AS DATE) + INTERVAL '1 day'";
    array_push($params, $fecha, $fecha);
}
if ($desde !== '') {
    $conditions[] = 'c.fecha_cita >= CAST(? AS DATE)';
    $params[] = $desde;
}
if ($hasta !== '') {
    $conditions[] = "c.fecha_cita < CAST(? AS DATE) + INTERVAL '1 day'";
    $params[] = $hasta;
}
if ($categoryId !== null) {
    $conditions[] = 'p.categoria_id = ?';
    $params[] = $categoryId;
}
if ($search !== '') {
    $searchable = Validation::sqlNormalize("concat_ws(' ', p.nombres, p.apellidos, c.motivo, cat.nombre)");
    foreach (Validation::searchTokens($search) as $token) {
        $pattern = Validation::likePattern($token);
        // La cédula se compara sin guiones, puntos ni espacios: V12345678, V-12345678 y 12.345.678 coinciden.
        $cedula = Validation::cedulaSearchToken($token);
        if ($cedula === '') {
            $conditions[] = $searchable . ' ' . Validation::SQL_LIKE_CLAUSE;
            $params[] = $pattern;
        } else {
            $conditions[] = '(' . $searchable . ' ' . Validation::SQL_LIKE_CLAUSE
                . " OR translate(upper(p.cedula), '-. ', '') " . Validation::SQL_LIKE_CLAUSE . ')';
            $params[] = $pattern;
            $params[] = '%' . Validation::escapeLike($cedula) . '%';
        }
    }
}

$from = ' FROM citas c JOIN pacientes p ON c.paciente_id = p.id JOIN categorias_institucionales cat ON p.categoria_id = cat.id';
$where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
$db = api_db();
$statement = $db->prepare('SELECT COUNT(*)' . $from . $where);
$statement->execute($params);
$total = (int) $statement->fetchColumn();

$direction = $order === 'desc' ? 'DESC' : 'ASC';
$statement = $db->prepare('SELECT c.id, c.fecha_cita, c.estado, c.motivo, p.id AS paciente_id, p.cedula, p.nombres, p.apellidos, cat.nombre AS categoria'
    . $from . $where . " ORDER BY c.fecha_cita {$direction}, c.id {$direction} LIMIT ? OFFSET ?");
$statement->execute([...$params, $perPage, $offset]);
$rows = $statement->fetchAll();

// Mismo criterio por días que las vistas de citas.js: «Vencidas» = pendientes de días anteriores
// (hasta = ayer) y «Próximas» = pendientes desde hoy. Las atrasadas de hoy se ven en «Hoy».
$summary = $db->query("SELECT
    COUNT(*) FILTER (WHERE fecha_cita >= CURRENT_DATE AND fecha_cita < CURRENT_DATE + INTERVAL '1 day')::int AS hoy_pendientes,
    COUNT(*) FILTER (WHERE fecha_cita >= CURRENT_DATE)::int AS proximas_pendientes,
    COUNT(*) FILTER (WHERE fecha_cita < CURRENT_DATE)::int AS vencidas_pendientes
    FROM citas WHERE estado = 'Pendiente'")->fetch();

api_json([
    'success' => true,
    'data' => $rows,
    'meta' => ['total' => $total, 'pagina' => $page, 'por_pagina' => $perPage],
    'resumen' => array_map('intval', $summary),
]);
