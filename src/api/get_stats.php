<?php
require_once '../config/Database.php';

header('Content-Type: application/json');

try {
    $db = Config\Database::getInstance()->getConnection();
    
    // Obtener contadores principales
    $total_pacientes = $db->query("SELECT COUNT(*) FROM pacientes")->fetchColumn();
    $citas_proximas = $db->query("SELECT COUNT(*) FROM citas WHERE fecha_cita >= CURRENT_DATE AND estado = 'Pendiente'")->fetchColumn();
    
    // Consultas del mes
    $consultas_mes = $db->query("
        SELECT COUNT(*) FROM consultas 
        WHERE EXTRACT(MONTH FROM fecha_registro) = EXTRACT(MONTH FROM CURRENT_DATE) 
          AND EXTRACT(YEAR FROM fecha_registro) = EXTRACT(YEAR FROM CURRENT_DATE)
    ")->fetchColumn();

    // Reposos activos
    $reposos_activos = $db->query("
        SELECT COUNT(*) FROM consultas 
        WHERE CURRENT_DATE BETWEEN fecha_inicio_reposo AND fecha_fin_reposo
    ")->fetchColumn();

    // Distribución por categorías institucionales
    $stmtCats = $db->query("
        SELECT c.nombre, COUNT(p.id) as cantidad
        FROM categorias_institucionales c
        LEFT JOIN pacientes p ON p.categoria_id = c.id
        GROUP BY c.nombre, c.id
        ORDER BY c.id
    ");
    $categorias_dist = $stmtCats->fetchAll();

    // Historial mensual de consultas (últimos 6 meses) usando generate_series de PostgreSQL
    $stmtHistorial = $db->query("
        SELECT 
            TO_CHAR(d, 'YYYY-MM') as mes,
            TO_CHAR(d, 'TMMonth') as mes_nombre,
            COUNT(c.id) as cantidad
        FROM generate_series(
            DATE_TRUNC('month', CURRENT_DATE - INTERVAL '5 months'),
            DATE_TRUNC('month', CURRENT_DATE),
            '1 month'::interval
        ) d
        LEFT JOIN consultas c ON DATE_TRUNC('month', c.fecha_registro) = d
        GROUP BY d
        ORDER BY d ASC
    ");
    $historial_mensual = $stmtHistorial->fetchAll();

    $stats = [
        'total_pacientes' => (int)$total_pacientes,
        'citas_proximas' => (int)$citas_proximas,
        'consultas_mes' => (int)$consultas_mes,
        'reposos_activos' => (int)$reposos_activos,
        'categorias' => $categorias_dist,
        'historial_mensual' => $historial_mensual
    ];
    
    echo json_encode(['success' => true, 'data' => $stats]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al obtener estadísticas']);
}
