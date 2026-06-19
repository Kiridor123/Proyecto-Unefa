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

    // Verificar el estado actual
    $stmtCita = $db->prepare("SELECT estado FROM citas WHERE id = ?");
    $stmtCita->execute([$cita_id]);
    $cita = $stmtCita->fetch();

    if (!$cita) {
        throw new Exception("La cita no existe.");
    }

    $estado_actual = $cita['estado'];

    // TODO: Cuando exista un sistema de autenticación, saltar esta validación si el usuario es 'admin'.
    // if (!isAdmin()) { ... }
    if (($estado_actual === 'Cancelada' || $estado_actual === 'Completada') && $estado === 'Pendiente') {
        throw new Exception("Solo un administrador puede volver a poner como Pendiente una cita que ya fue Cancelada o Completada.");
    }
    
    $stmt = $db->prepare("UPDATE citas SET estado = ? WHERE id = ?");
    $stmt->execute([$estado, $cita_id]);
    
    echo json_encode(['success' => true, 'message' => 'Estado de la cita actualizado correctamente.']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
