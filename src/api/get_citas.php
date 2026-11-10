<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/Citas.php';
use Config\Validation;

api_method('GET');
api_require('citas.gestionar');
$errors = [];
$status = Validation::text($_GET, 'estado', 20, $errors);
if ($status !== '' && !in_array($status, CITA_ESTADOS, true)) $errors['estado'] = 'Seleccione un estado válido.';
$dates = [];
foreach (['fecha', 'desde', 'hasta'] as $key) {
    $dates[$key] = Validation::text($_GET, $key, 10, $errors);
    if ($dates[$key] !== '' && !Validation::date($dates[$key])) $errors[$key] = 'Indique una fecha válida (AAAA-MM-DD).';
}
if ($dates['desde'] !== '' && $dates['hasta'] !== '' && !isset($errors['desde']) && !isset($errors['hasta']) && $dates['hasta'] < $dates['desde']) {
    $errors['hasta'] = 'La fecha final debe ser igual o posterior a la inicial.';
}
$search = Validation::text($_GET, 'q', 100, $errors);
$order = Validation::text($_GET, 'orden', 4, $errors);
if ($order === '') $order = 'asc';
if (!in_array($order, ['asc', 'desc'], true)) $errors['orden'] = 'Indique un orden válido (asc o desc).';
[$page, $perPage, $offset] = api_pagination($_GET, 50, 200, $errors);
api_validate($errors);

$conditions = [];
$params = [];
if ($status !== '') { $conditions[] = 'c.estado = ?'; $params[] = $status; }
if ($dates['fecha'] !== '') {
    $conditions[] = "c.fecha_cita >= CAST(? AS DATE) AND c.fecha_cita < CAST(? AS DATE) + INTERVAL '1 day'";
    array_push($params, $dates['fecha'], $dates['fecha']);
}
if ($dates['desde'] !== '') { $conditions[] = 'c.fecha_cita >= CAST(? AS DATE)'; $params[] = $dates['desde']; }
if ($dates['hasta'] !== '') { $conditions[] = "c.fecha_cita < CAST(? AS DATE) + INTERVAL '1 day'"; $params[] = $dates['hasta']; }
if ($search !== '') {
    $normalize = static fn(string $expression): string => "translate(lower($expression), 'áéíóúüàèìòùñ', 'aeiouuaeioun')";
    foreach (array_slice(preg_split('/\s+/u', $search, -1, PREG_SPLIT_NO_EMPTY) ?: [], 0, 6) as $word) {
        $pattern = '%' . addcslashes($word, '%_\\') . '%';
        // La cédula se compara sin guiones, puntos ni espacios: V12345678, V-12345678 y 12.345.678 coinciden.
        $cedula = strtoupper((string) preg_replace('/[\s.\-]+/u', '', $word));
        if ($cedula === '') {
            $conditions[] = $normalize("p.nombres || ' ' || p.apellidos") . ' LIKE ' . $normalize('?');
            $params[] = $pattern;
        } else {
            $conditions[] = '(' . $normalize("p.nombres || ' ' || p.apellidos") . ' LIKE ' . $normalize('?') . " OR translate(upper(p.cedula), '-. ', '') LIKE ?)";
            array_push($params, $pattern, '%' . addcslashes($cedula, '%_\\') . '%');
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
