<?php
require_once '../config/Database.php';

header('Content-Type: application/json');

try {
    $db = Config\Database::getInstance()->getConnection();
    
    $query = "
        SELECT 
            p.cedula, 
            p.nombres, 
            p.apellidos, 
            c.nombre AS categoria,
            (SELECT MAX(fecha_registro) FROM consultas WHERE paciente_id = p.id) as ultima_consulta
        FROM pacientes p
        JOIN categorias_institucionales c ON p.categoria_id = c.id
        ORDER BY ultima_consulta DESC NULLS LAST
    ";
    
    $stmt = $db->query($query);
    $pacientes = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'data' => $pacientes]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al obtener pacientes']);
}
