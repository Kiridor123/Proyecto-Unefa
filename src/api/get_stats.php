<?php
require_once '../config/Database.php';

header('Content-Type: application/json');

try {
    $db = Config\Database::getInstance()->getConnection();
    
    $stats = [
        'total_pacientes' => $db->query("SELECT COUNT(*) FROM pacientes")->fetchColumn(),
        'citas_proximas' => $db->query("SELECT COUNT(*) FROM citas WHERE fecha_cita >= CURRENT_DATE AND estado = 'Pendiente'")->fetchColumn(),
        'consultas_mes' => $db->query("SELECT COUNT(*) FROM consultas WHERE extract(month from fecha_registro) = extract(month from CURRENT_DATE) AND extract(year from fecha_registro) = extract(year from CURRENT_DATE)")->fetchColumn()
    ];
    
    echo json_encode(['success' => true, 'data' => $stats]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al obtener estadísticas']);
}
