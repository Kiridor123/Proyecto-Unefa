<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/Citas.php';
use Config\Validation;

api_method('POST');
api_require('citas.gestionar');
$input = api_input();
$id = api_id($input['cita_id'] ?? null, 'cita_id');
$errors = [];
$value = Validation::text($input, 'fecha_cita', 19, $errors, true);
$date = Validation::dateTime($value);
if (!isset($errors['fecha_cita'])) {
    $problem = cita_fecha_problema($date);
    if ($problem !== null) $errors['fecha_cita'] = $problem;
}
api_validate($errors);
$db = api_db();
try {
    $db->beginTransaction();
    $statement = $db->prepare('SELECT paciente_id FROM citas WHERE id = ?');
    $statement->execute([$id]);
    $patientId = $statement->fetchColumn();
    if (!$patientId) throw new ApiException('La cita no existe.', 404);
    // Mismo orden de bloqueo que la programación: paciente y después la cita.
    $statement = $db->prepare('SELECT id FROM pacientes WHERE id = ? FOR UPDATE');
    $statement->execute([$patientId]);
    $statement = $db->prepare('SELECT estado, fecha_cita FROM citas WHERE id = ? FOR UPDATE');
    $statement->execute([$id]);
    $current = $statement->fetch();
    if (!$current) throw new ApiException('La cita no existe.', 404);
    if ($current['estado'] !== 'Pendiente') throw new ApiException('Solo se pueden reprogramar citas pendientes. Programe una nueva cita si necesita otra atención.', 409);
    $appointmentDate = $date->format('Y-m-d H:i:s');
    cita_verificar_intervalo($db, (int) $patientId, $appointmentDate, $id);
    $statement = $db->prepare('UPDATE citas SET fecha_cita = ?, actualizado_en = now() WHERE id = ?');
    $statement->execute([$appointmentDate, $id]);
    api_audit('reprogramar_cita', 'cita', $id, [
        'paciente_id' => (int) $patientId,
        'fecha_anterior' => substr((string) $current['fecha_cita'], 0, 19),
        'fecha_nueva' => $appointmentDate,
    ]);
    $db->commit();
    api_json(['success' => true, 'message' => 'La cita se reprogramó correctamente.', 'cita_id' => $id, 'fecha_cita' => $appointmentDate]);
} catch (Throwable $error) {
    if ($db->inTransaction()) $db->rollBack();
    throw $error;
}
