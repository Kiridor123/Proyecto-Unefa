<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/Morbilidad.php';

api_method('GET');
api_require('reportes.ver');

$db = api_db();
$filters = morbilidad_filtros($_GET, $db);
$rows = morbilidad_filas($db, $filters, null, false);

api_audit('consultar_morbilidad', 'reporte', null, morbilidad_audit_detail($filters, count($rows)));
api_json([
    'success' => true,
    'data' => $rows,
    'meta' => [
        'total' => count($rows),
        'fecha_inicio' => $filters['fecha_inicio'],
        'fecha_fin' => $filters['fecha_fin'],
        'categoria_id' => $filters['categoria_id'],
        'categoria' => $filters['categoria_nombre'],
        'sexo' => $filters['sexo'],
        'con_reposo' => $filters['con_reposo'],
        'q' => $filters['q'],
    ],
]);
