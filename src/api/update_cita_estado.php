<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

api_method('POST');
$id = api_id($_POST['cita_id'] ?? null, 'cita_id');
$errors = [];
$status = Config\Validation::text($_POST, 'estado', 20, $errors, true);
if (!in_array($status, ['Pendiente', 'Completada', 'Cancelada'], true)) $errors['estado'] = 'Seleccione un estado válido.';
api_validate($errors);
$db = api_db();
try {
    $db->beginTransaction();
    $statement = $db->prepare('SELECT paciente_id FROM citas WHERE id = ?');
    $statement->execute([$id]);
    $patientId = $statement->fetchColumn();
    if (!$patientId) throw new ApiException('La cita no existe.', 404);
    // Use the same lock order as registration and scheduling.
    $statement = $db->prepare('SELECT id FROM pacientes WHERE id = ? FOR UPDATE');
    $statement->execute([$patientId]);
    $statement = $db->prepare('SELECT estado FROM citas WHERE id = ? FOR UPDATE');
    $statement->execute([$id]);
    $current = $statement->fetchColumn();
    if ($current !== $status && $current !== 'Pendiente') throw new ApiException('La cita ya fue cerrada. Programe una nueva cita si necesita otra atención.', 409);
    $statement = $db->prepare('UPDATE citas SET estado = ? WHERE id = ?');
    $statement->execute([$status, $id]);
    $db->commit();
    api_json(['success' => true, 'message' => 'El estado de la cita se actualizó correctamente.']);
} catch (Throwable $error) {
    if ($db->inTransaction()) $db->rollBack();
    throw $error;
}
