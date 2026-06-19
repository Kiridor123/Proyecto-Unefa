<?php
require_once '../config/Database.php';

header('Content-Type: application/json');

$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';

if (empty($fecha_inicio) || empty($fecha_fin)) {
    echo json_encode(['success' => false, 'message' => 'Rango de fechas inválido o incompleto.']);
    exit;
}

if (strtotime($fecha_inicio) > strtotime($fecha_fin)) {
    echo json_encode(['success' => false, 'message' => 'La fecha de inicio no puede ser posterior a la fecha de fin.']);
    exit;
}

try {
    $db = Config\Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("
        SELECT 
            c.*, 
            p.cedula, p.nombres, p.apellidos, p.sexo, p.fecha_nacimiento, p.carrera, p.semestre, p.telefono, p.direccion,
            cat.nombre AS categoria
        FROM consultas c
        JOIN pacientes p ON c.paciente_id = p.id
        JOIN categorias_institucionales cat ON p.categoria_id = cat.id
        WHERE c.fecha_circunstancia BETWEEN ? AND ?
        ORDER BY c.fecha_circunstancia ASC, c.id ASC
    ");
    
    $stmt->execute([$fecha_inicio, $fecha_fin]);
    $consultas = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $consultas
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al obtener la morbilidad: ' . $e->getMessage()]);
}
