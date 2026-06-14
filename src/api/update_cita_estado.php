<?php
require_once '../config/Database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

try {
    $db = Config\Database::getInstance()->getConnection();
    
    $cita_id = isset($_POST['cita_id']) ? (int)$_POST['cita_id'] : 0;
    $estado = isset($_POST['estado']) ? trim($_POST['estado']) : '';
    
    if ($cita_id <= 0) {
        throw new Exception("ID de cita no válido.");
    }
    
    $estados_permitidos = ['Pendiente', 'Completada', 'Cancelada'];
    if (!in_array($estado, $estados_permitidos)) {
        throw new Exception("Estado de cita no válido.");
    }
    
    $stmt = $db->prepare("UPDATE citas SET estado = ? WHERE id = ?");
    $stmt->execute([$estado, $cita_id]);
    
    echo json_encode(['success' => true, 'message' => 'Estado de la cita actualizado correctamente.']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
