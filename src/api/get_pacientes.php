<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

use Config\Validation;

api_method('GET');
api_require('pacientes.ver');

const PACIENTES_SEXOS = ['Femenino', 'Masculino', 'Otro'];
const PACIENTES_ORDENES = [
    'reciente' => 'latest.ultima_consulta DESC NULLS LAST, p.apellidos ASC, p.nombres ASC, p.id ASC',
    'nombre' => 'p.apellidos ASC, p.nombres ASC, p.id ASC',
    'cedula' => 'p.cedula ASC, p.id ASC',
];

$errors = [];
$search = Validation::search($_GET, 'q', $errors, Validation::SEARCH_MAX_LENGTH);
[$page, $perPage, $offset] = api_pagination($_GET, 25, 100, $errors);
$categoryId = Validation::optionalPositiveId($_GET, 'categoria_id', $errors, 'Seleccione una categoría válida.');
$sexo = Validation::enum($_GET, 'sexo', PACIENTES_SEXOS, $errors, 'Seleccione un sexo válido.', 20);
$orden = Validation::enum($_GET, 'orden', array_keys(PACIENTES_ORDENES), $errors, 'Seleccione un orden válido.', 20);
if ($orden === '') $orden = 'reciente';
api_validate($errors);

$conditions = [];
$params = [];
if ($search !== '') {
    $searchable = Validation::sqlNormalize("concat_ws(' ', p.nombres, p.apellidos)");
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
if ($categoryId !== null) {
    $conditions[] = 'p.categoria_id = ?';
    $params[] = $categoryId;
}
if ($sexo !== '') {
    $conditions[] = 'p.sexo = ?';
    $params[] = $sexo;
}
$where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';

$db = api_db();
$statement = $db->prepare('SELECT COUNT(*) FROM pacientes p' . $where);
$statement->execute($params);
$total = (int) $statement->fetchColumn();

$clinical = api_can('clinica.ver');
$orderSql = PACIENTES_ORDENES[$orden];
$statement = $db->prepare('SELECT p.id, p.cedula, p.nombres, p.apellidos, p.categoria_id, c.nombre AS categoria, '
    . ($clinical ? 'latest.ultima_consulta' : 'NULL AS ultima_consulta') . '
    FROM pacientes p JOIN categorias_institucionales c ON p.categoria_id = c.id
    LEFT JOIN (SELECT paciente_id, MAX(fecha_circunstancia) AS ultima_consulta FROM consultas GROUP BY paciente_id) latest ON latest.paciente_id = p.id'
    . $where . '
    ORDER BY ' . $orderSql . '
    LIMIT ? OFFSET ?');
$statement->execute([...$params, $perPage, $offset]);
api_json([
    'success' => true,
    'data' => $statement->fetchAll(),
    'meta' => ['total' => $total, 'pagina' => $page, 'por_pagina' => $perPage],
]);
