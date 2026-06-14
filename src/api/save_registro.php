<?php
require_once '../config/Database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

try {
    $db = Config\Database::getInstance()->getConnection();
    
    // Obtener campos (saneamiento básico)
    $cedula = trim($_POST['cedula'] ?? '');
    $categoria_id = trim($_POST['categoria'] ?? '');
    $nombres = trim($_POST['nombres'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    
    $fecha_circunstancia = trim($_POST['fecha_circunstancia'] ?? '');
    $resumen = trim($_POST['resumen'] ?? '');
    $inicio_reposo = !empty($_POST['inicio_reposo']) ? trim($_POST['inicio_reposo']) : null;
    $fin_reposo = !empty($_POST['fin_reposo']) ? trim($_POST['fin_reposo']) : null;

    if (!$cedula || !$nombres || !$apellidos || !$categoria_id || !$fecha_circunstancia || !$resumen) {
        throw new Exception("Faltan campos obligatorios en el formulario.");
    }

    $db->beginTransaction();

    // 1. Lógica de Paciente (Crear si no existe)
    $stmt = $db->prepare("SELECT id FROM pacientes WHERE cedula = ?");
    $stmt->execute([$cedula]);
    $paciente = $stmt->fetch();

    if ($paciente) {
        $paciente_id = $paciente['id'];
        // Actualizamos por si el usuario editó nombre/apellido al registrar nueva consulta
        $stmt = $db->prepare("UPDATE pacientes SET nombres = ?, apellidos = ?, categoria_id = ? WHERE id = ?");
        $stmt->execute([$nombres, $apellidos, $categoria_id, $paciente_id]);
    } else {
        $stmt = $db->prepare("INSERT INTO pacientes (cedula, nombres, apellidos, categoria_id) VALUES (?, ?, ?, ?) RETURNING id");
        $stmt->execute([$cedula, $nombres, $apellidos, $categoria_id]);
        $paciente_id = $stmt->fetchColumn();
    }

    // 2. Insertar Consulta
    $stmt = $db->prepare("INSERT INTO consultas (paciente_id, fecha_circunstancia, resumen, fecha_inicio_reposo, fecha_fin_reposo) VALUES (?, ?, ?, ?, ?) RETURNING id");
    $stmt->execute([$paciente_id, $fecha_circunstancia, $resumen, $inicio_reposo, $fin_reposo]);
    $consulta_id = $stmt->fetchColumn();

    // 3. Manejar Archivo Adjunto (si existe)
    if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['archivo'];
        
        // Validar límite (10MB) por si bypass del frontend
        if ($file['size'] > 10 * 1024 * 1024) {
            throw new Exception("El archivo supera el tamaño máximo de 10MB.");
        }

        // Validación Estricta MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowed_mimes = ['image/jpeg', 'image/png', 'application/pdf'];
        if (!in_array($mime, $allowed_mimes)) {
            throw new Exception("El formato del archivo es inválido o el archivo está corrupto. Solo JPG, PNG, PDF.");
        }

        // Crear carpeta si no existe
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Ofuscar nombre de archivo por seguridad
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
        $destination = $upload_dir . $new_filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $stmt = $db->prepare("INSERT INTO archivos_adjuntos (consulta_id, ruta_archivo, tipo_archivo) VALUES (?, ?, ?)");
            // Guardamos ruta relativa accesible desde web o interna
            $stmt->execute([$consulta_id, 'uploads/' . $new_filename, $mime]);
        } else {
            throw new Exception("Falló la transferencia del archivo seguro al servidor.");
        }
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'El paciente y la consulta se registraron correctamente.']);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
