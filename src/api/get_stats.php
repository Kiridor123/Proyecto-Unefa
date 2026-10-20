<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
api_method('GET');
$db = api_db();
$counts = $db->query("SELECT
    (SELECT COUNT(*) FROM pacientes) AS total_pacientes,
    (SELECT COUNT(*) FROM citas WHERE fecha_cita >= LOCALTIMESTAMP AND estado = 'Pendiente') AS citas_proximas,
    (SELECT COUNT(*) FROM consultas WHERE fecha_circunstancia >= DATE_TRUNC('month', CURRENT_DATE)::date
        AND fecha_circunstancia < (DATE_TRUNC('month', CURRENT_DATE) + INTERVAL '1 month')::date) AS consultas_mes,
    (SELECT COUNT(DISTINCT paciente_id) FROM consultas WHERE CURRENT_DATE BETWEEN fecha_inicio_reposo AND fecha_fin_reposo) AS reposos_activos")->fetch();
$stats = array_map('intval', $counts);
$stats['categorias'] = $db->query('SELECT c.nombre, COUNT(p.id)::int AS cantidad FROM categorias_institucionales c
    LEFT JOIN pacientes p ON p.categoria_id = c.id GROUP BY c.nombre, c.id ORDER BY c.id')->fetchAll();
$history = $db->query("SELECT TO_CHAR(months.month, 'YYYY-MM') AS mes, EXTRACT(MONTH FROM months.month)::int AS numero_mes, COUNT(c.id)::int AS cantidad
    FROM generate_series(DATE_TRUNC('month', CURRENT_DATE) - INTERVAL '5 months', DATE_TRUNC('month', CURRENT_DATE), INTERVAL '1 month') AS months(month)
    LEFT JOIN consultas c ON c.fecha_circunstancia >= months.month::date AND c.fecha_circunstancia < (months.month + INTERVAL '1 month')::date
    GROUP BY months.month ORDER BY months.month")->fetchAll();
$months = [1 => 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
foreach ($history as &$month) { $month['mes_nombre'] = $months[(int) $month['numero_mes']]; unset($month['numero_mes']); }
unset($month);
$stats['historial_mensual'] = $history;
api_json(['success' => true, 'data' => $stats]);
