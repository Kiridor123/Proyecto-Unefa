<?php
require_once '../config/Database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

try {
    $db = Config\Database::getInstance()->getConnection();
    
    // Obtener campos básicos y normalizar cédula
    $cedula = trim($_POST['cedula'] ?? '');
    $cedula = strtoupper(preg_replace('/\s+/', '', $cedula)); // Quitar espacios y pasar a mayúsculas
    
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

    // Validar formato de fecha circunstancia
    if (strtotime($fecha_circunstancia) > time()) {
        throw new Exception("La fecha de la circunstancia no puede ser una fecha futura.");
    }

    // Validar rango de reposo
    if ($inicio_reposo && $fin_reposo) {
        if (strtotime($fin_reposo) < strtotime($inicio_reposo)) {
            throw new Exception("La fecha de fin de reposo no puede ser anterior a la de inicio.");
        }
    }

    // Nuevos campos del paciente
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $sexo = trim($_POST['sexo'] ?? '');
    $nivel_educativo = trim($_POST['nivel_educativo'] ?? '');
    $lugar_nacimiento = trim($_POST['lugar_nacimiento'] ?? '');
    $fecha_nacimiento = !empty($_POST['fecha_nacimiento']) ? trim($_POST['fecha_nacimiento']) : null;
    $carrera = trim($_POST['carrera'] ?? '');
    $semestre = trim($_POST['semestre'] ?? '');
    $tiene_tatuajes = trim($_POST['tiene_tatuajes'] ?? '');
    $compromiso_tatuajes = trim($_POST['compromiso_tatuajes'] ?? '');
    
    // Antecedentes del paciente
    $antecedente_cardiovascular = trim($_POST['antecedente_cardiovascular'] ?? '');
    $antecedente_oseo = trim($_POST['antecedente_oseo'] ?? '');
    $antecedente_respiratorio = trim($_POST['antecedente_respiratorio'] ?? '');
    $antecedente_digestivo = trim($_POST['antecedente_digestivo'] ?? '');
    $antecedente_endocrino = trim($_POST['antecedente_endocrino'] ?? '');
    $antecedente_otros = trim($_POST['antecedente_otros'] ?? '');
    $antecedente_quirurgico = trim($_POST['antecedente_quirurgico'] ?? '');
    
    // Ginecología del paciente
    $gineco_menarquia = trim($_POST['gineco_menarquia'] ?? '');
    $gineco_sexarquia = trim($_POST['gineco_sexarquia'] ?? '');
    $gineco_aco = trim($_POST['gineco_aco'] ?? '');
    $gineco_gestas = trim($_POST['gineco_gestas'] ?? '');
    $gineco_citologia = trim($_POST['gineco_citologia'] ?? '');
    
    // Antecedentes familiares
    $antecedente_padre = trim($_POST['antecedente_padre'] ?? '');
    $antecedente_madre = trim($_POST['antecedente_madre'] ?? '');
    $antecedente_hermanos = trim($_POST['antecedente_hermanos'] ?? '');
    $antecedente_hijos = trim($_POST['antecedente_hijos'] ?? '');

    // Nuevos campos de la consulta
    $motivo_consulta = trim($_POST['motivo_consulta'] ?? '');
    $enfermedad_actual = trim($_POST['enfermedad_actual'] ?? '');
    $diagnostico = trim($_POST['diagnostico'] ?? '');
    
    $vital_ta = trim($_POST['vital_ta'] ?? '');
    $vital_fc = trim($_POST['vital_fc'] ?? '');
    $vital_fr = trim($_POST['vital_fr'] ?? '');
    $vital_spo2 = trim($_POST['vital_spo2'] ?? '');
    $vital_peso_talla = trim($_POST['vital_peso_talla'] ?? '');
    
    $fisico_piel = trim($_POST['fisico_piel'] ?? '');
    $fisico_cabeza = trim($_POST['fisico_cabeza'] ?? '');
    $fisico_cuello = trim($_POST['fisico_cuello'] ?? '');
    $fisico_torax = trim($_POST['fisico_torax'] ?? '');
    $fisico_abdomen = trim($_POST['fisico_abdomen'] ?? '');
    $fisico_extremidades = trim($_POST['fisico_extremidades'] ?? '');
    $fisico_neurologico = trim($_POST['fisico_neurologico'] ?? '');
    
    $laboratorios = trim($_POST['laboratorios'] ?? '');
    $plan_tratamiento = trim($_POST['plan_tratamiento'] ?? '');
    $pendiente = trim($_POST['pendiente'] ?? '');

    $db->beginTransaction();

    // 1. Lógica de Paciente (Crear si no existe, o actualizar)
    $stmt = $db->prepare("SELECT id FROM pacientes WHERE cedula = ?");
    $stmt->execute([$cedula]);
    $paciente = $stmt->fetch();

    if ($paciente) {
        $paciente_id = $paciente['id'];
        $stmt = $db->prepare("
            UPDATE pacientes 
            SET nombres = ?, apellidos = ?, categoria_id = ?, telefono = ?, direccion = ?, 
                sexo = ?, nivel_educativo = ?, lugar_nacimiento = ?, fecha_nacimiento = ?, 
                carrera = ?, semestre = ?, tiene_tatuajes = ?, compromiso_tatuajes = ?, 
                antecedente_cardiovascular = ?, antecedente_oseo = ?, antecedente_respiratorio = ?, 
                antecedente_digestivo = ?, antecedente_endocrino = ?, antecedente_otros = ?, 
                antecedente_quirurgico = ?, gineco_menarquia = ?, gineco_sexarquia = ?, 
                gineco_aco = ?, gineco_gestas = ?, gineco_citologia = ?, 
                antecedente_padre = ?, antecedente_madre = ?, antecedente_hermanos = ?, 
                antecedente_hijos = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $nombres, $apellidos, $categoria_id, $telefono, $direccion,
            $sexo, $nivel_educativo, $lugar_nacimiento, $fecha_nacimiento,
            $carrera, $semestre, $tiene_tatuajes, $compromiso_tatuajes,
            $antecedente_cardiovascular, $antecedente_oseo, $antecedente_respiratorio,
            $antecedente_digestivo, $antecedente_endocrino, $antecedente_otros,
            $antecedente_quirurgico, $gineco_menarquia, $gineco_sexarquia,
            $gineco_aco, $gineco_gestas, $gineco_citologia,
            $antecedente_padre, $antecedente_madre, $antecedente_hermanos,
            $antecedente_hijos, $paciente_id
        ]);
    } else {
        $stmt = $db->prepare("
            INSERT INTO pacientes (
                cedula, nombres, apellidos, categoria_id, telefono, direccion, 
                sexo, nivel_educativo, lugar_nacimiento, fecha_nacimiento, 
                carrera, semestre, tiene_tatuajes, compromiso_tatuajes, 
                antecedente_cardiovascular, antecedente_oseo, antecedente_respiratorio, 
                antecedente_digestivo, antecedente_endocrino, antecedente_otros, 
                antecedente_quirurgico, gineco_menarquia, gineco_sexarquia, 
                gineco_aco, gineco_gestas, gineco_citologia, 
                antecedente_padre, antecedente_madre, antecedente_hermanos, 
                antecedente_hijos
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
            RETURNING id
        ");
        $stmt->execute([
            $cedula, $nombres, $apellidos, $categoria_id, $telefono, $direccion,
            $sexo, $nivel_educativo, $lugar_nacimiento, $fecha_nacimiento,
            $carrera, $semestre, $tiene_tatuajes, $compromiso_tatuajes,
            $antecedente_cardiovascular, $antecedente_oseo, $antecedente_respiratorio,
            $antecedente_digestivo, $antecedente_endocrino, $antecedente_otros,
            $antecedente_quirurgico, $gineco_menarquia, $gineco_sexarquia,
            $gineco_aco, $gineco_gestas, $gineco_citologia,
            $antecedente_padre, $antecedente_madre, $antecedente_hermanos,
            $antecedente_hijos
        ]);
        $paciente_id = $stmt->fetchColumn();
    }

    // 2. Insertar Consulta
    $stmt = $db->prepare("
        INSERT INTO consultas (
            paciente_id, fecha_circunstancia, resumen, fecha_inicio_reposo, fecha_fin_reposo,
            motivo_consulta, enfermedad_actual, diagnostico, vital_ta, vital_fc, vital_fr, vital_spo2, vital_peso_talla,
            fisico_piel, fisico_cabeza, fisico_cuello, fisico_torax, fisico_abdomen, fisico_extremidades, fisico_neurologico,
            laboratorios, plan_tratamiento, pendiente
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
        RETURNING id
    ");
    $stmt->execute([
        $paciente_id, $fecha_circunstancia, $resumen, $inicio_reposo, $fin_reposo,
        $motivo_consulta, $enfermedad_actual, $diagnostico, $vital_ta, $vital_fc, $vital_fr, $vital_spo2, $vital_peso_talla,
        $fisico_piel, $fisico_cabeza, $fisico_cuello, $fisico_torax, $fisico_abdomen, $fisico_extremidades, $fisico_neurologico,
        $laboratorios, $plan_tratamiento, $pendiente
    ]);
    $consulta_id = $stmt->fetchColumn();

    // 3. Manejar Archivo Adjunto (si existe)
    if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['archivo'];
        
        if ($file['size'] > 10 * 1024 * 1024) {
            throw new Exception("El archivo supera el tamaño máximo de 10MB.");
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowed_mimes = ['image/jpeg', 'image/png', 'application/pdf'];
        if (!in_array($mime, $allowed_mimes)) {
            throw new Exception("El formato del archivo es inválido o el archivo está corrupto. Solo JPG, PNG, PDF.");
        }

        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
        $destination = $upload_dir . $new_filename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $stmt = $db->prepare("INSERT INTO archivos_adjuntos (consulta_id, ruta_archivo, tipo_archivo) VALUES (?, ?, ?)");
            $stmt->execute([$consulta_id, 'uploads/' . $new_filename, $mime]);
        } else {
            throw new Exception("Falló la transferencia del archivo seguro al servidor.");
        }
    }

    // 4. Completar automáticamente citas pendientes para este paciente
    $stmtCita = $db->prepare("UPDATE citas SET estado = 'Completada' WHERE paciente_id = ? AND estado = 'Pendiente'");
    $stmtCita->execute([$paciente_id]);

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'El paciente y la consulta se registraron correctamente.']);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
