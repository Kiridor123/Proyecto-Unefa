<?php
require_once '../config/Database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

try {
    $db = Config\Database::getInstance()->getConnection();
    
    $paciente_id = isset($_POST['paciente_id']) ? (int)$_POST['paciente_id'] : 0;
    $cedula = isset($_POST['cedula']) ? trim($_POST['cedula']) : '';
    $cedula = strtoupper(preg_replace('/\s+/', '', $cedula));
    
    $fecha_cita = isset($_POST['fecha_cita']) ? trim($_POST['fecha_cita']) : '';
    
    if (empty($fecha_cita)) {
        throw new Exception("La fecha y hora de la cita es obligatoria.");
    }
    
    // Validar que la fecha sea en el futuro
    $timestamp_cita = strtotime($fecha_cita);
    if (!$timestamp_cita) {
        throw new Exception("Formato de fecha no válido.");
    }
    
    if ($timestamp_cita < time()) {
        throw new Exception("La fecha de la cita debe ser en el futuro.");
    }
    
    $db->beginTransaction();
    
    // Determinar el paciente_id
    if ($paciente_id <= 0) {
        if (empty($cedula)) {
            throw new Exception("Debe seleccionar un paciente o ingresar una cédula.");
        }
        
        // Buscar paciente por cédula
        $stmtPaciente = $db->prepare("SELECT id FROM pacientes WHERE cedula = ?");
        $stmtPaciente->execute([$cedula]);
        $paciente = $stmtPaciente->fetch();
        
        if ($paciente) {
            $paciente_id = $paciente['id'];
        } else {
            // Si el paciente no existe, podemos crearlo al vuelo si se envían nombres y categoría
            $nombres = isset($_POST['nombres']) ? trim($_POST['nombres']) : '';
            $apellidos = isset($_POST['apellidos']) ? trim($_POST['apellidos']) : '';
            $categoria_id = isset($_POST['categoria']) ? trim($_POST['categoria']) : '';
            
            if (empty($nombres) || empty($apellidos) || empty($categoria_id)) {
                throw new Exception("El paciente no está registrado. Por favor ingrese nombres, apellidos y categoría para registrarlo.");
            }
            
            $stmtInsertPac = $db->prepare("INSERT INTO pacientes (cedula, nombres, apellidos, categoria_id) VALUES (?, ?, ?, ?) RETURNING id");
            $stmtInsertPac->execute([$cedula, $nombres, $apellidos, $categoria_id]);
            $paciente_id = $stmtInsertPac->fetchColumn();
        }
    }
    
    // Verificar si ya tiene una cita pendiente para la misma hora/día para evitar duplicados
    $stmtCheck = $db->prepare("
        SELECT COUNT(*) FROM citas 
        WHERE paciente_id = ? 
          AND estado = 'Pendiente' 
          AND ABS(EXTRACT(EPOCH FROM (fecha_cita - CAST(? AS TIMESTAMP)))) < 1800
    ");
    $stmtCheck->execute([$paciente_id, $fecha_cita]);
    if ($stmtCheck->fetchColumn() > 0) {
        throw new Exception("Este paciente ya tiene una cita pendiente programada en un rango cercano a esta hora.");
    }
    
    // Insertar la cita
    $stmtCita = $db->prepare("INSERT INTO citas (paciente_id, fecha_cita, estado) VALUES (?, ?, 'Pendiente')");
    $stmtCita->execute([$paciente_id, $fecha_cita]);
    
    $db->commit();
    echo json_encode(['success' => true, 'message' => 'La cita ha sido programada correctamente.']);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
