<?php
declare(strict_types=1);

// Host: php tests/backend-validation.php
// Docker (PowerShell): Get-Content -Raw tests/backend-validation.php | docker compose exec -T web php
$sourceRoot = is_dir(__DIR__ . '/../src/config') ? __DIR__ . '/../src' : (getenv('APP_SOURCE_ROOT') ?: '/var/www/html');
require_once $sourceRoot . '/config/Validation.php';
require_once $sourceRoot . '/config/RegistroInput.php';
date_default_timezone_set('America/Caracas');

use Config\Validation;
use Config\RegistroInput;

$checks = 0;
function check(bool $condition, string $message): void
{
    global $checks;
    $checks++;
    if (!$condition) throw new RuntimeException('FAIL: ' . $message);
}

check(Validation::date('2024-02-29') !== null, 'leap day is accepted');
foreach (['2025-02-29', '2026-02-31', '2026-13-01', '2026-01-00', '0000-01-01', '20-01-01', 'tomorrow', "2026-10-20\0"] as $date) {
    check(Validation::date($date) === null, 'invalid date rejected: ' . json_encode($date));
}
check(Validation::dateTime('2028-02-29T12:30') !== null, 'datetime-local accepted');
check(Validation::dateTime('2028-02-29 12:30:59') !== null, 'SQL timestamp accepted');
foreach (['2026-02-31T10:30', '2026-10-20T24:00', '2026-10-20T12:60', 'next Monday', '2026-10-20T10:00Z', "2026-10-20T10:00\0"] as $date) {
    check(Validation::dateTime($date) === null, 'invalid datetime rejected');
}
check(Validation::positiveId('1') === 1 && Validation::positiveId(2147483647) === 2147483647, 'valid integer boundaries');
foreach ([0, -1, '1x', '1.5', '2147483648', '01', [], true, 1.1] as $id) check(Validation::positiveId($id) === null, 'invalid identifier rejected');
check(Validation::cedula(' v - 1234 ') === 'V-1234', 'identity whitespace normalization');
check(Validation::validCedula('V-12345678'), 'valid identity accepted');
check(!Validation::validCedula('V-1234567890123456789'), 'identity length matches DB capacity');
check(!Validation::validCedula('A-123'), 'identity prefix whitelist');
$errors = [];
check(Validation::text(['name' => '  María  '], 'name', 5, $errors, true) === 'María' && !$errors, 'Unicode names within limit');
Validation::text(['name' => 'Maríaa'], 'name', 5, $errors);
check(isset($errors['name']), 'Unicode length enforced');
$errors = [];
Validation::text(['name' => ['nested']], 'name', 100, $errors);
check(isset($errors['name']), 'array parameter rejected without type error');
$errors = [];
Validation::text(['name' => "test\0value"], 'name', 100, $errors);
check(isset($errors['name']), 'control characters rejected');
$errors = [];
Validation::dateRange(['fecha_inicio' => '2026-10-21', 'fecha_fin' => '2026-10-20'], $errors);
check(isset($errors['fecha_fin']), 'reverse report range rejected');
check(Validation::number('0', 0, 100), 'zero vital is validated as a number');
foreach (['-1', '101', 'NaN', 'INF', '1e2', '50abc'] as $number) check(!Validation::number($number, 0, 100), 'invalid vital rejected');

$basic = ['cedula' => ' v - 12345678 ', 'nombres' => 'María', 'apellidos' => 'García', 'categoria' => '1', 'sexo' => 'Femenino', 'solo_paciente' => '1'];
$result = RegistroInput::from($basic);
check(!$result['errors'] && $result['solo'], 'patient-only registration needs no clinical fields');
check($result['patient']['cedula'] === 'V-12345678', 'registration normalizes identity');
check(!array_key_exists('antecedente_otros', $result['patient']), 'omitted history fields do not erase stored history');
$result = RegistroInput::from(array_merge($basic, ['fecha_nacimiento' => '2026-02-31']));
check(isset($result['errors']['fecha_nacimiento']), 'registration rejects calendar overflow');
$result = RegistroInput::from(array_merge($basic, ['fecha_nacimiento' => (new DateTimeImmutable('tomorrow'))->format('Y-m-d')]));
check(isset($result['errors']['fecha_nacimiento']), 'future birth rejected');
$result = RegistroInput::from(array_merge($basic, ['nombres' => ['invalid']]));
check(isset($result['errors']['nombres']), 'nested patient input rejected');

$clinical = array_merge($basic, ['solo_paciente' => '0', 'fecha_circunstancia' => '2026-01-15', 'motivo_consulta' => 'Control',
    'enfermedad_actual' => 'Evaluación de seguimiento', 'diagnostico' => 'Control general', 'plan_tratamiento' => 'Seguimiento',
    'fecha_nacimiento' => '2000-01-01', 'vital_fc' => '0', 'vital_spo2' => '100']);
$result = RegistroInput::from($clinical);
check(!$result['errors'] && !$result['solo'], 'valid clinical encounter accepted');
check($result['consultation']['resumen'] === 'Evaluación de seguimiento', 'summary uses clinical narrative fallback');
$result = RegistroInput::from(array_merge($clinical, ['inicio_reposo' => '2026-01-15']));
check(isset($result['errors']['fin_reposo']), 'incomplete rest range rejected');
$result = RegistroInput::from(array_merge($clinical, ['inicio_reposo' => '2026-01-16', 'fin_reposo' => '2026-01-15']));
check(isset($result['errors']['fin_reposo']), 'reverse rest range rejected');
$result = RegistroInput::from(array_merge($clinical, ['inicio_reposo' => '2026-01-15', 'fin_reposo' => '2026-01-15']));
check(!$result['errors'], 'same-day rest is valid');
$result = RegistroInput::from(array_merge($clinical, ['fecha_nacimiento' => '2026-01-16']));
check(isset($result['errors']['fecha_circunstancia']), 'encounter before birth rejected');
$result = RegistroInput::from(array_merge($clinical, ['vital_spo2' => '101']));
check(isset($result['errors']['vital_spo2']), 'out of range oxygen saturation rejected');
$result = RegistroInput::from(array_merge($clinical, ['motivo_consulta' => '']));
check(isset($result['errors']['motivo_consulta']), 'required clinical data enforced');
$result = RegistroInput::from(array_merge($clinical, ['sexo' => '<script>']));
check(isset($result['errors']['sexo']), 'patient enum enforced');
echo "Backend validation: {$checks} assertions passed.\n";
