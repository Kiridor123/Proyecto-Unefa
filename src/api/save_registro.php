<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/RegistroInput.php';
require_once __DIR__ . '/../config/Attachments.php';

api_method('POST');
$input = Config\RegistroInput::from($_POST);
api_validate($input['errors']);
$file = api_attachment_input($_FILES['archivo'] ?? null);
if ($input['solo'] && $file) throw new ApiException('Guarde el adjunto junto con una consulta.', 422, ['archivo' => 'El adjunto debe pertenecer a una consulta.']);
$citaId = isset($_POST['cita_id']) && $_POST['cita_id'] !== '' ? api_id($_POST['cita_id'], 'cita_id') : null;
$db = api_db();
$storedFile = null;
try {
    api_category($db, $input['patient']['categoria_id']);
    $db->beginTransaction();
    $patient = $input['patient'];
    // The unique key serializes concurrent registrations of the same person.
    $columns = array_keys($patient);
    $updates = array_map(static fn(string $column): string => "$column = EXCLUDED.$column", array_filter($columns, static fn(string $column): bool => $column !== 'cedula'));
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
            $statement = $db->prepare("UPDATE citas SET estado = 'Completada' WHERE id = ? AND paciente_id = ? AND estado = 'Pendiente' RETURNING id");
            $statement->execute([$citaId, $patientId]);
            if (!$statement->fetchColumn()) throw new ApiException('La cita seleccionada no está pendiente o pertenece a otro paciente.', 409);
        } else {
            // Complete at most one appointment on the clinical date; never future days.
            $statement = $db->prepare("UPDATE citas SET estado = 'Completada' WHERE id = (
                SELECT id FROM citas WHERE paciente_id = ? AND estado = 'Pendiente'
                AND fecha_cita >= CAST(? AS DATE) AND fecha_cita < CAST(? AS DATE) + INTERVAL '1 day'
                ORDER BY ABS(EXTRACT(EPOCH FROM (fecha_cita - LOCALTIMESTAMP))), id LIMIT 1 FOR UPDATE
            )");
            $statement->execute([$patientId, $consultation['fecha_circunstancia'], $consultation['fecha_circunstancia']]);
        }
    }
    $db->commit();
    api_json(['success' => true, 'message' => $input['solo'] ? 'Los datos del paciente se guardaron correctamente.' : 'La consulta se registró correctamente.', 'paciente_id' => $patientId, 'consulta_id' => $consultationId]);
} catch (Throwable $error) {
    if ($db->inTransaction()) $db->rollBack();
    if ($storedFile !== null && is_file($storedFile)) unlink($storedFile);
    throw $error;
}
