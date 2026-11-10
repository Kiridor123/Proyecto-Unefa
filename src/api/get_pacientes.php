<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

api_method('GET');
api_require('pacientes.ver');
$errors = [];
$search = Config\Validation::text($_GET, 'q', 100, $errors);
[$page, $perPage, $offset] = api_pagination($_GET, 25, 100, $errors);
$categoryId = null;
if (isset($_GET['categoria_id']) && $_GET['categoria_id'] !== '') {
    $categoryId = Config\Validation::positiveId($_GET['categoria_id']);
    if ($categoryId === null) $errors['categoria_id'] = 'Seleccione una categoría válida.';
}
api_validate($errors);

// Búsqueda sin extensiones: minúsculas y vocales sin acento con translate().
$normalize = static fn(string $expression): string => "translate(lower($expression), 'áéíóúüàèìòùñ', 'aeiouuaeioun')";
$conditions = [];
$params = [];
if ($search !== '') {
    $words = preg_split('/\s+/u', $search, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    foreach (array_slice($words, 0, 6) as $word) {
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
if ($categoryId !== null) {
    $conditions[] = 'p.categoria_id = ?';
    $params[] = $categoryId;
}
$where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';

$db = api_db();
$statement = $db->prepare('SELECT COUNT(*) FROM pacientes p' . $where);
$statement->execute($params);
$total = (int) $statement->fetchColumn();

$clinical = api_can('clinica.ver');
$statement = $db->prepare('SELECT p.id, p.cedula, p.nombres, p.apellidos, p.categoria_id, c.nombre AS categoria, '
    . ($clinical ? 'latest.ultima_consulta' : 'NULL AS ultima_consulta') . '
    FROM pacientes p JOIN categorias_institucionales c ON p.categoria_id = c.id
    LEFT JOIN (SELECT paciente_id, MAX(fecha_circunstancia) AS ultima_consulta FROM consultas GROUP BY paciente_id) latest ON latest.paciente_id = p.id'
    . $where . '
    ORDER BY latest.ultima_consulta DESC NULLS LAST, p.apellidos ASC, p.nombres ASC, p.id ASC
    LIMIT ? OFFSET ?');
$statement->execute([...$params, $perPage, $offset]);
api_json([
    'success' => true,
    'data' => $statement->fetchAll(),
    'meta' => ['total' => $total, 'pagina' => $page, 'por_pagina' => $perPage],
]);
