<?php
require_once '../config/Database.php';

header('Content-Type: application/json');

try {
    $db = Config\Database::getInstance()->getConnection();
    
    $status = isset($_GET['estado']) ? trim($_GET['estado']) : '';
    
    $query = "
        SELECT 
            c.id,
            c.fecha_cita,
            c.estado,
            p.id as paciente_id,
            p.cedula,
            p.nombres,
            p.apellidos,
            cat.nombre AS categoria
        FROM citas c
        JOIN pacientes p ON c.paciente_id = p.id
        JOIN categorias_institucionales cat ON p.categoria_id = cat.id
    ";
    
    $params = [];
    if (!empty($status)) {
        $query .= " WHERE c.estado = ?";
        $params[] = $status;
    }
    
    // Si queremos ver las próximas citas, o podemos ordenar
    $query .= " ORDER BY c.fecha_cita ASC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $citas = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'data' => $citas]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al obtener las citas: ' . $e->getMessage()]);
}
