<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/RegistroInput.php';

use Config\RegistroInput;
use Config\Validation;

api_method('POST');
api_require('clinica.editar');
$input = api_input();
$consultationId = api_id($input['consulta_id'] ?? null, 'consulta_id');
$errors = [];
$reason = Validation::text($input, 'motivo_edicion', 500, $errors, true);
if (!isset($errors['motivo_edicion']) && mb_strlen($reason, 'UTF-8') < 5) $errors['motivo_edicion'] = 'Explique el motivo de la corrección (mínimo 5 caracteres).';
$expectedVersion = null;
if (isset($input['version_esperada']) && $input['version_esperada'] !== '') {
    $value = $input['version_esperada'];
    if ((is_string($value) || is_int($value)) && preg_match('/^(0|[1-9][0-9]{0,8})$/D', (string) $value)) $expectedVersion = (int) $value;
    else $errors['version_esperada'] = 'La versión indicada no es válida.';
}
$user = api_user();
$db = api_db();
try {
    $db->beginTransaction();
    $statement = $db->prepare('SELECT paciente_id FROM consultas WHERE id = ?');
    $statement->execute([$consultationId]);
    $patientId = $statement->fetchColumn();
    if (!$patientId) throw new ApiException('La consulta no existe.', 404);
    // Mismo orden de bloqueo que el registro: primero el paciente y después la consulta.
    $statement = $db->prepare('SELECT fecha_nacimiento FROM pacientes WHERE id = ? FOR UPDATE');
    $statement->execute([$patientId]);
    $birthDate = $statement->fetchColumn();
    $statement = $db->prepare('SELECT * FROM consultas WHERE id = ? FOR UPDATE');
    $statement->execute([$consultationId]);
    $previous = $statement->fetch();

    $consultation = RegistroInput::consultation($input, is_string($birthDate) ? $birthDate : null, $errors, $previous ?: null);
    // Al corregir no se asume la fecha de hoy: debe indicarse expresamente.
    $sentDate = $input['fecha_circunstancia'] ?? '';
    if (!isset($errors['fecha_circunstancia']) && (!is_string($sentDate) || trim($sentDate) === '')) $errors['fecha_circunstancia'] = 'Indique la fecha de la consulta.';
    api_validate($errors);

    $statement = $db->prepare('SELECT COUNT(*) FROM consulta_versiones WHERE consulta_id = ?');
    $statement->execute([$consultationId]);
    $currentVersion = (int) $statement->fetchColumn();
    if ($expectedVersion !== null && $expectedVersion !== $currentVersion) {
        throw new ApiException('Otra persona modificó esta consulta mientras la editaba. Recárguela y vuelva a aplicar sus cambios.', 409, [], 'version_conflict', ['version_actual' => $currentVersion]);
    }

    $changed = [];
    foreach (RegistroInput::CONSULTATION_COLUMNS as $column) {
        if ((string) ($previous[$column] ?? '') !== (string) ($consultation[$column] ?? '')) $changed[] = $column;
    }
    if (!$changed) throw new ApiException('No hay cambios que guardar en la consulta.', 422, [], 'no_changes');

    $statement = $db->prepare('INSERT INTO consulta_versiones (consulta_id, version, datos, motivo, editado_por) VALUES (?, ?, CAST(? AS JSONB), ?, ?)');
    $statement->execute([
        $consultationId, $currentVersion + 1,
        json_encode($previous, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR),
        $reason, $user['id'],
    ]);
    $assignments = array_map(static fn(string $column): string => "$column = ?", RegistroInput::CONSULTATION_COLUMNS);
    $values = array_map(static fn(string $column) => $consultation[$column] ?? null, RegistroInput::CONSULTATION_COLUMNS);
    $statement = $db->prepare('UPDATE consultas SET ' . implode(', ', $assignments) . ', actualizado_en = now(), actualizado_por = ? WHERE id = ?');
    $statement->execute([...$values, $user['id'], $consultationId]);
    api_audit('editar_consulta', 'consulta', $consultationId, ['paciente_id' => (int) $patientId, 'version' => $currentVersion + 1, 'campos' => $changed]);
    $db->commit();
    api_json([
        'success' => true, 'message' => 'La consulta se corrigió correctamente.',
        'consulta_id' => $consultationId, 'paciente_id' => (int) $patientId, 'version' => $currentVersion + 1, 'campos' => $changed,
    ]);
} catch (Throwable $error) {
    if ($db->inTransaction()) $db->rollBack();
    throw $error;
}
