<?php
require_once '../config/Database.php';

header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de paciente no válido.']);
    exit;
}

try {
    $db = Config\Database::getInstance()->getConnection();
    
    // 1. Obtener todos los datos del paciente
    $stmtPaciente = $db->prepare("
        SELECT p.*, c.nombre AS categoria 
        FROM pacientes p 
        JOIN categorias_institucionales c ON p.categoria_id = c.id 
        WHERE p.id = ?
    ");
    $stmtPaciente->execute([$id]);
    $paciente = $stmtPaciente->fetch();
    
    if (!$paciente) {
        echo json_encode(['success' => false, 'message' => 'Paciente no encontrado.']);
        exit;
    }
    
    // 2. Obtener historial completo de consultas
    $stmtConsultas = $db->prepare("
        SELECT * 
        FROM consultas 
        WHERE paciente_id = ? 
        ORDER BY fecha_registro DESC
    ");
    $stmtConsultas->execute([$id]);
    $consultas = $stmtConsultas->fetchAll();
    
    // Para cada consulta, obtener sus archivos adjuntos
    foreach ($consultas as &$consulta) {
        $stmtArchivos = $db->prepare("
            SELECT id, ruta_archivo, tipo_archivo, fecha_subida 
            FROM archivos_adjuntos 
            WHERE consulta_id = ?
        ");
        $stmtArchivos->execute([$consulta['id']]);
        $consulta['archivos'] = $stmtArchivos->fetchAll();
    }
    
    // 3. Obtener citas del paciente
    $stmtCitas = $db->prepare("
        SELECT id, fecha_cita, estado 
        FROM citas 
        WHERE paciente_id = ? 
        ORDER BY fecha_cita DESC
    ");
    $stmtCitas->execute([$id]);
    $citas = $stmtCitas->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'paciente' => $paciente,
            'consultas' => $consultas,
            'citas' => $citas
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al obtener detalles del paciente: ' . $e->getMessage()]);
}
