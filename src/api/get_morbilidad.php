<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/Morbilidad.php';

api_method('GET');
api_require('reportes.ver');
[$start, $end] = morbilidad_rango($_GET);
$rows = morbilidad_filas(api_db(), $start, $end);
api_audit('consultar_morbilidad', 'reporte', null, ['fecha_inicio' => $start, 'fecha_fin' => $end, 'registros' => count($rows)]);
api_json(['success' => true, 'data' => $rows]);
