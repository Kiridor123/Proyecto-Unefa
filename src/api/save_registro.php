<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/RegistroInput.php';
require_once __DIR__ . '/../config/Attachments.php';

use Config\RegistroInput;

api_method('POST');
$input = RegistroInput::from($_POST);
if ($input['solo']) {
    api_require('pacientes.editar');
    // Sin permiso clínico, los antecedentes enviados se ignoran sin error.
    if (!api_can('clinica.editar')) {
        foreach (RegistroInput::CLINICAL_PATIENT_FIELDS as $field) {
            unset($input['patient'][$field], $input['errors'][$field]);
        }
    }
} else {
    api_require('clinica.editar');
}
api_validate($input['errors']);
$file = api_attachment_input($_FILES['archivo'] ?? null);
if ($input['solo'] && $file) throw new ApiException('Guarde el adjunto junto con una consulta.', 422, ['archivo' => 'El adjunto debe pertenecer a una consulta.']);
$citaId = isset($_POST['cita_id']) && $_POST['cita_id'] !== '' ? api_id($_POST['cita_id'], 'cita_id') : null;
$user = api_user();
$db = api_db();
$storedFile = null;

/** Responde con la consulta ya registrada para esta solicitud, si existe. */
$duplicate = static function (PDO $db, ?string $requestId): ?array {
    if ($requestId === null) return null;
    $statement = $db->prepare('SELECT id, paciente_id FROM consultas WHERE solicitud_id = CAST(? AS UUID)');
    $statement->execute([$requestId]);
    $row = $statement->fetch();
    return $row ?: null;
};

try {
    api_category($db, $input['patient']['categoria_id']);
    $db->beginTransaction();
    $patient = $input['patient'];
    $requestId = $input['solicitud_id'];
    if ($requestId !== null) {
        // Serializa los reintentos de la misma solicitud (doble clic, reenvío tras un corte de red).
        $statement = $db->prepare('SELECT pg_advisory_xact_lock(hashtext(?))');
        $statement->execute(['solicitud:' . $requestId]);
        $existing = $duplicate($db, $requestId);
        if ($existing) {
            $db->commit();
            api_json([
                'success' => true, 'duplicado' => true,
                'message' => 'Esta consulta ya se había registrado; no se creó un duplicado.',
                'paciente_id' => (int) $existing['paciente_id'], 'consulta_id' => (int) $existing['id'],
            ]);
        }
    }
    // Serializa los registros simultáneos de la misma cédula antes de comparar la identidad.
    $statement = $db->prepare('SELECT pg_advisory_xact_lock(hashtext(?))');
    $statement->execute(['patient:' . $patient['cedula']]);
    $statement = $db->prepare('SELECT p.id, p.nombres, p.apellidos, p.sexo, p.categoria_id, c.nombre AS categoria
        FROM pacientes p JOIN categorias_institucionales c ON c.id = p.categoria_id WHERE p.cedula = ? FOR UPDATE OF p');
    $statement->execute([$patient['cedula']]);
    $stored = $statement->fetch();
    $identityChanged = $stored && RegistroInput::identityDiffers($stored, $patient);
    if ($identityChanged && !$input['confirmar_identidad']) {
        throw new ApiException(
            'La cédula ' . $patient['cedula'] . ' ya pertenece a ' . trim($stored['nombres'] . ' ' . $stored['apellidos']) . '. Confirme si desea actualizar su identidad.',
            409,
            ['cedula' => 'Los datos de identidad no coinciden con los registrados.'],
            'identity_mismatch',
            ['actual' => [
                'id' => (int) $stored['id'], 'nombres' => $stored['nombres'], 'apellidos' => $stored['apellidos'],
                'sexo' => $stored['sexo'], 'categoria_id' => (int) $stored['categoria_id'], 'categoria' => $stored['categoria'],
            ]]
        );
    }
    // The unique key serializes concurrent registrations of the same person.
    $columns = array_keys($patient);
    $updates = array_map(static fn(string $column): string => "$column = EXCLUDED.$column", array_filter($columns, static fn(string $column): bool => $column !== 'cedula'));
    $updates[] = 'actualizado_en = now()';
    $statement = $db->prepare('INSERT INTO pacientes (' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ') ON CONFLICT (cedula) DO UPDATE SET ' . implode(', ', $updates) . ' RETURNING id, fecha_nacimiento');
    $statement->execute(array_values($patient));
    $savedPatient = $statement->fetch();
    $patientId = (int) $savedPatient['id'];
    $consultationId = null;
    if (!$input['solo']) {
        $consultation = $input['consultation'];
        if ($savedPatient['fecha_nacimiento'] && $consultation['fecha_circunstancia'] < $savedPatient['fecha_nacimiento']) {
            throw new ApiException('La consulta no puede ser anterior al nacimiento.', 422, ['fecha_circunstancia' => 'Revise la fecha de la consulta.']);
        }
        $consultation['paciente_id'] = $patientId;
        $consultation['medico_id'] = $user['id'];
        $consultation['solicitud_id'] = $requestId;
        $columns = array_keys($consultation);
        $statement = $db->prepare('INSERT INTO consultas (' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ') RETURNING id');
        $statement->execute(array_values($consultation));
        $consultationId = (int) $statement->fetchColumn();
        if ($file) {
            $storedFile = api_attachment_store($file);
            $statement = $db->prepare('INSERT INTO archivos_adjuntos (consulta_id, ruta_archivo, tipo_archivo) VALUES (?, ?, ?)');
            $statement->execute([$consultationId, 'private/' . $file['name'], $file['mime']]);
        }
        if ($citaId !== null) {
            $statement = $db->prepare("UPDATE citas SET estado = 'Completada', actualizado_en = now() WHERE id = ? AND paciente_id = ? AND estado = 'Pendiente' RETURNING id");
            $statement->execute([$citaId, $patientId]);
            if (!$statement->fetchColumn()) throw new ApiException('La cita seleccionada no está pendiente o pertenece a otro paciente.', 409, [], 'cita_no_pendiente');
        } else {
            // Complete at most one appointment on the clinical date; never future days.
            $statement = $db->prepare("UPDATE citas SET estado = 'Completada', actualizado_en = now() WHERE id = (
                SELECT id FROM citas WHERE paciente_id = ? AND estado = 'Pendiente'
                AND fecha_cita >= CAST(? AS DATE) AND fecha_cita < CAST(? AS DATE) + INTERVAL '1 day'
                ORDER BY ABS(EXTRACT(EPOCH FROM (fecha_cita - LOCALTIMESTAMP))), id LIMIT 1 FOR UPDATE
            )");
            $statement->execute([$patientId, $consultation['fecha_circunstancia'], $consultation['fecha_circunstancia']]);
        }
    }
    $patientDetail = ['nuevo' => !$stored];
    if ($identityChanged) $patientDetail['identidad_actualizada'] = true;
    api_audit('guardar_paciente', 'paciente', $patientId, $patientDetail);
    if ($consultationId !== null) {
        api_audit('crear_consulta', 'consulta', $consultationId, ['paciente_id' => $patientId, 'adjunto' => (bool) $file, 'cita_id' => $citaId]);
    }
    $db->commit();
    api_json(['success' => true, 'message' => $input['solo'] ? 'Los datos del paciente se guardaron correctamente.' : 'La consulta se registró correctamente.', 'paciente_id' => $patientId, 'consulta_id' => $consultationId]);
} catch (Throwable $error) {
    if ($db->inTransaction()) $db->rollBack();
    if ($storedFile !== null && is_file($storedFile)) unlink($storedFile);
    throw $error;
}
