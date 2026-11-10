<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/Citas.php';
use Config\Validation;

api_method('POST');
api_require('citas.gestionar');
$input = api_input();
$errors = [];
$patientId = null;
if (isset($input['paciente_id']) && !in_array($input['paciente_id'], ['', '0', 0], true)) {
    $patientId = Validation::positiveId($input['paciente_id']);
    if ($patientId === null) $errors['paciente_id'] = 'Seleccione un paciente válido.';
}
$cedula = Validation::cedula(Validation::text($input, 'cedula', 40, $errors));
if (!$patientId && !Validation::validCedula($cedula)) $errors['cedula'] = 'Indique una cédula válida (ej. V-12345678).';
$value = Validation::text($input, 'fecha_cita', 19, $errors, true);
$date = Validation::dateTime($value);
if (!isset($errors['fecha_cita'])) {
    $problem = cita_fecha_problema($date);
    if ($problem !== null) $errors['fecha_cita'] = $problem;
}
$reason = Validation::text($input, 'motivo', CITA_MOTIVO_MAX, $errors);
api_validate($errors);
$user = api_user();
$db = api_db();
try {
    $db->beginTransaction();
    $createdPatient = false;
    if ($patientId === null) {
        // Prevent simultaneous inline creation from racing before the patient exists.
        $statement = $db->prepare('SELECT pg_advisory_xact_lock(hashtext(?))');
        $statement->execute(['patient:' . $cedula]);
        $statement = $db->prepare('SELECT id FROM pacientes WHERE cedula = ? FOR UPDATE');
        $statement->execute([$cedula]);
        $patientId = $statement->fetchColumn();
        if (!$patientId) {
            if (!api_can('pacientes.editar')) throw new ApiException('No tiene permiso para registrar pacientes.', 403, [], 'forbidden');
            $names = Validation::text($input, 'nombres', 100, $errors, true);
            $lastNames = Validation::text($input, 'apellidos', 100, $errors, true);
            $categoryId = Validation::positiveId($input['categoria'] ?? null);
            if (!$categoryId) $errors['categoria'] = 'Seleccione una categoría válida.';
            api_validate($errors);
            api_category($db, $categoryId);
            $statement = $db->prepare('INSERT INTO pacientes (cedula, nombres, apellidos, categoria_id) VALUES (?, ?, ?, ?) ON CONFLICT (cedula) DO UPDATE SET cedula = EXCLUDED.cedula RETURNING id');
            $statement->execute([$cedula, $names, $lastNames, $categoryId]);
            $patientId = $statement->fetchColumn();
            $createdPatient = true;
        }
    }
    // Serialize appointment operations for this patient before checking the interval.
    $statement = $db->prepare('SELECT id, cedula FROM pacientes WHERE id = ? FOR UPDATE');
    $statement->execute([$patientId]);
    $lockedPatient = $statement->fetch();
    if (!$lockedPatient) throw new ApiException('El paciente no existe.', 404);
    if ($cedula !== '' && $cedula !== $lockedPatient['cedula']) throw new ApiException('La cédula no corresponde al paciente seleccionado. Vuelva a buscarlo.', 409, ['cedula' => 'Revise el paciente seleccionado.']);
    $appointmentDate = $date->format('Y-m-d H:i:s');
    cita_verificar_intervalo($db, (int) $patientId, $appointmentDate);
    $statement = $db->prepare("INSERT INTO citas (paciente_id, fecha_cita, estado, motivo, creado_por) VALUES (?, ?, 'Pendiente', ?, ?) RETURNING id");
    $statement->execute([$patientId, $appointmentDate, $reason !== '' ? $reason : null, $user['id']]);
    $appointmentId = (int) $statement->fetchColumn();
    if ($createdPatient) api_audit('guardar_paciente', 'paciente', (int) $patientId, ['nuevo' => true, 'origen' => 'cita']);
    api_audit('crear_cita', 'cita', $appointmentId, ['paciente_id' => (int) $patientId, 'fecha_cita' => $appointmentDate]);
    $db->commit();
    api_json(['success' => true, 'message' => 'La cita se programó correctamente.', 'cita_id' => $appointmentId, 'paciente_id' => (int) $patientId], 201);
} catch (Throwable $error) {
    if ($db->inTransaction()) $db->rollBack();
    throw $error;
}
