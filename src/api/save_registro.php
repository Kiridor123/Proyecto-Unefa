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
    
    $solo_paciente = isset($_POST['solo_paciente']) && $_POST['solo_paciente'] == '1';
    
    $fecha_circunstancia = trim($_POST['fecha_circunstancia'] ?? '');
    if (empty($fecha_circunstancia)) {
        $fecha_circunstancia = date('Y-m-d');
    }
    
    // Fallback: si resumen no está en POST, usar enfermedad_actual
    $resumen = trim($_POST['resumen'] ?? $_POST['enfermedad_actual'] ?? '');
    
    $inicio_reposo = !empty($_POST['inicio_reposo']) ? trim($_POST['inicio_reposo']) : null;
    $fin_reposo = !empty($_POST['fin_reposo']) ? trim($_POST['fin_reposo']) : null;

    // Las validaciones se ejecutarán después de capturar todos los campos

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

    // -----------------------------------------
    // VALIDACIONES
    // -----------------------------------------
    $errors = [];

    // Validar Paciente
    if (empty($cedula)) {
        $errors['cedula'] = "La cédula es obligatoria.";
    } elseif (!preg_match('/^[VEJGCPNvejgcpn]-[0-9]+$/', $cedula)) {
        $errors['cedula'] = "El formato de cédula es inválido. Use letras permitidas (V, E, J, G, C, P, N) seguido de guion y números (Ej. V-12345678).";
    }

    if (empty($nombres)) $errors['nombres'] = "Los nombres son obligatorios.";
    if (empty($apellidos)) $errors['apellidos'] = "Los apellidos son obligatorios.";
    if (empty($categoria_id)) $errors['categoria'] = "La categoría es obligatoria.";
    if (empty($sexo)) $errors['sexo'] = "El sexo es obligatorio.";
    
    if (!empty($fecha_nacimiento)) {
        if (strtotime($fecha_nacimiento) > time()) {
            $errors['fecha_nacimiento'] = "La fecha de nacimiento no puede ser en el futuro.";
        }
        if (strtotime($fecha_nacimiento) < strtotime("-120 years")) {
            $errors['fecha_nacimiento'] = "La fecha de nacimiento es inválida (demasiado antigua).";
        }
    }

    if (!$solo_paciente) {
        // Validar Consulta
        if (empty($fecha_circunstancia)) {
            $errors['fecha_circunstancia'] = "La fecha de la circunstancia es obligatoria.";
        } elseif (strtotime($fecha_circunstancia) > time()) {
            $errors['fecha_circunstancia'] = "La fecha de la circunstancia no puede ser futura.";
        } elseif (!empty($fecha_nacimiento) && strtotime($fecha_circunstancia) < strtotime($fecha_nacimiento)) {
            $errors['fecha_circunstancia'] = "La fecha de la consulta no puede ser anterior a la fecha de nacimiento.";
        }

        if (empty($motivo_consulta)) $errors['motivo_consulta'] = "El motivo de la consulta es obligatorio.";
        if (empty($enfermedad_actual)) $errors['enfermedad_actual'] = "La enfermedad actual es obligatoria.";
        if (empty($diagnostico)) $errors['diagnostico'] = "El diagnóstico es obligatorio.";
        if (empty($plan_tratamiento)) $errors['plan_tratamiento'] = "El plan de tratamiento es obligatorio.";

        // Validar rango de reposo
        if (!empty($inicio_reposo) || !empty($fin_reposo)) {
            if (empty($inicio_reposo)) {
                $errors['inicio_reposo'] = "Debe indicar el inicio del reposo.";
            } elseif (empty($fin_reposo)) {
                $errors['fin_reposo'] = "Debe indicar el fin del reposo.";
            } elseif (strtotime($fin_reposo) < strtotime($inicio_reposo)) {
                $errors['fin_reposo'] = "La fecha de fin de reposo no puede ser anterior a la de inicio.";
            }
        }

        // Validar signos vitales numéricos (si no están vacíos)
        if (!empty($vital_fc) && (!is_numeric($vital_fc) || $vital_fc < 0 || $vital_fc > 300)) {
            $errors['vital_fc'] = "La Frecuencia Cardíaca debe ser un número válido entre 0 y 300.";
        }
        if (!empty($vital_fr) && (!is_numeric($vital_fr) || $vital_fr < 0 || $vital_fr > 100)) {
            $errors['vital_fr'] = "La Frecuencia Respiratoria debe ser un número válido entre 0 y 100.";
        }
        if (!empty($vital_spo2) && (!is_numeric($vital_spo2) || $vital_spo2 < 0 || $vital_spo2 > 100)) {
            $errors['vital_spo2'] = "La Saturación de Oxígeno (SpO2) debe ser un número entre 0 y 100.";
        }
        if (!empty($vital_ta) && !preg_match('/^\d{2,3}\/\d{2,3}$/', $vital_ta)) {
            $errors['vital_ta'] = "La Tensión Arterial debe tener el formato sistólica/diastólica (Ej. 120/80).";
        }
    }

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => 'Existen errores de validación', 'errors' => $errors]);
        exit;
    }

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
    if (!$solo_paciente) {
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
    } else {
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Los datos del paciente se han actualizado correctamente.']);
    }

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
