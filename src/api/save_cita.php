<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
use Config\Validation;

api_method('POST');
$errors = [];
$patientId = null;
if (isset($_POST['paciente_id']) && !in_array($_POST['paciente_id'], ['', '0', 0], true)) {
    $patientId = Validation::positiveId($_POST['paciente_id']);
    if ($patientId === null) $errors['paciente_id'] = 'Seleccione un paciente válido.';
}
$cedula = Validation::cedula(Validation::text($_POST, 'cedula', 40, $errors));
if (!$patientId && !Validation::validCedula($cedula)) $errors['cedula'] = 'Indique una cédula válida (ej. V-12345678).';
$value = Validation::text($_POST, 'fecha_cita', 19, $errors, true);
$date = Validation::dateTime($value);
if (!$date || $date <= new DateTimeImmutable()) $errors['fecha_cita'] = 'Indique una fecha y hora válidas en el futuro.';
api_validate($errors);
$db = api_db();
try {
    $db->beginTransaction();
    if ($patientId === null) {
        // Prevent simultaneous inline creation from racing before the patient exists.
        $statement = $db->prepare('SELECT pg_advisory_xact_lock(hashtext(?))');
        $statement->execute(['patient:' . $cedula]);
        $statement = $db->prepare('SELECT id FROM pacientes WHERE cedula = ? FOR UPDATE');
        $statement->execute([$cedula]);
        $patientId = $statement->fetchColumn();
        if (!$patientId) {
            $names = Validation::text($_POST, 'nombres', 100, $errors, true);
            $lastNames = Validation::text($_POST, 'apellidos', 100, $errors, true);
            $categoryId = Validation::positiveId($_POST['categoria'] ?? null);
            if (!$categoryId) $errors['categoria'] = 'Seleccione una categoría válida.';
            api_validate($errors);
            api_category($db, $categoryId);
            $statement = $db->prepare('INSERT INTO pacientes (cedula, nombres, apellidos, categoria_id) VALUES (?, ?, ?, ?) ON CONFLICT (cedula) DO UPDATE SET cedula = EXCLUDED.cedula RETURNING id');
            $statement->execute([$cedula, $names, $lastNames, $categoryId]);
            $patientId = $statement->fetchColumn();
        }
    }
    // Serialize appointment operations for this patient before checking the interval.
    $statement = $db->prepare('SELECT id, cedula FROM pacientes WHERE id = ? FOR UPDATE');
    $statement->execute([$patientId]);
    $lockedPatient = $statement->fetch();
    if (!$lockedPatient) throw new ApiException('El paciente no existe.', 404);
    if ($cedula !== '' && $cedula !== $lockedPatient['cedula']) throw new ApiException('La cédula no corresponde al paciente seleccionado. Vuelva a buscarlo.', 409, ['cedula' => 'Revise el paciente seleccionado.']);
    $appointmentDate = $date->format('Y-m-d H:i:s');
    $statement = $db->prepare("SELECT id FROM citas WHERE paciente_id = ? AND estado = 'Pendiente'
        AND fecha_cita > CAST(? AS TIMESTAMP) - INTERVAL '30 minutes'
        AND fecha_cita < CAST(? AS TIMESTAMP) + INTERVAL '30 minutes' LIMIT 1");
    $statement->execute([$patientId, $appointmentDate, $appointmentDate]);
    if ($statement->fetchColumn()) throw new ApiException('El paciente ya tiene una cita pendiente a menos de 30 minutos de esta hora.', 409, ['fecha_cita' => 'Seleccione otra hora.']);
    $statement = $db->prepare("INSERT INTO citas (paciente_id, fecha_cita, estado) VALUES (?, ?, 'Pendiente') RETURNING id");
    $statement->execute([$patientId, $appointmentDate]);
    $appointmentId = (int) $statement->fetchColumn();
    $db->commit();
    api_json(['success' => true, 'message' => 'La cita se programó correctamente.', 'cita_id' => $appointmentId, 'paciente_id' => (int) $patientId], 201);
} catch (Throwable $error) {
    if ($db->inTransaction()) $db->rollBack();
    throw $error;
}
