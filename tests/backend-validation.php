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

// Límites de reposo (BE-6), fecha mínima de consulta (BE-7) e idempotencia (BE-11).
$result = RegistroInput::from(array_merge($clinical, ['inicio_reposo' => '1900-01-01', 'fin_reposo' => '9999-12-31']));
check(isset($result['errors']['fin_reposo']) || isset($result['errors']['inicio_reposo']), 'absurd rest range rejected');
// Días inclusivos, como el impreso: del 15/01/2026 al 15/01/2027 son 366 días.
$result = RegistroInput::from(array_merge($clinical, ['inicio_reposo' => '2026-01-15', 'fin_reposo' => '2027-01-15']));
check(isset($result['errors']['fin_reposo']), 'rest of 366 days (both ends included) rejected');
$result = RegistroInput::from(array_merge($clinical, ['inicio_reposo' => '2026-01-15', 'fin_reposo' => '2027-01-14']));
check(!$result['errors'], 'rest of exactly 365 days (both ends included) accepted');
check(RegistroInput::restDays(new DateTimeImmutable('2026-01-15'), new DateTimeImmutable('2026-01-15')) === 1, 'single-day rest counts as one day');
$result = RegistroInput::from(array_merge($clinical, ['inicio_reposo' => '2026-01-07', 'fin_reposo' => '2026-01-10']));
check(isset($result['errors']['inicio_reposo']), 'rest starting more than 7 days before the encounter rejected');
$result = RegistroInput::from(array_merge($clinical, ['inicio_reposo' => '2026-01-08', 'fin_reposo' => '2026-01-10']));
check(!$result['errors'], 'rest starting 7 days before the encounter accepted');
$result = RegistroInput::from(array_merge($clinical, ['inicio_reposo' => '2026-02-15', 'fin_reposo' => '2026-02-20']));
check(isset($result['errors']['inicio_reposo']), 'rest starting more than 30 days after the encounter rejected');
$result = RegistroInput::from(array_merge($clinical, ['fecha_nacimiento' => '', 'fecha_circunstancia' => '0001-01-01']));
check(isset($result['errors']['fecha_circunstancia']), 'encounter in year 0001 rejected');
$result = RegistroInput::from(array_merge($clinical, ['fecha_nacimiento' => '', 'fecha_circunstancia' => '1899-12-31']));
check(isset($result['errors']['fecha_circunstancia']), 'encounter before 1900 rejected');
$result = RegistroInput::from(array_merge($clinical, ['solicitud_id' => '3F2504E0-4F89-41D3-9A0C-0305E82C3301']));
check(!$result['errors'] && $result['solicitud_id'] === '3f2504e0-4f89-41d3-9a0c-0305e82c3301', 'request UUID normalized');
$result = RegistroInput::from(array_merge($clinical, ['solicitud_id' => 'not-a-uuid']));
check(isset($result['errors']['solicitud_id']), 'malformed request UUID rejected');
$result = RegistroInput::from(array_merge($basic, ['solicitud_id' => 'not-a-uuid']));
check(!$result['errors'] && $result['solicitud_id'] === null, 'patient-only registration ignores the request UUID');
check(RegistroInput::from(array_merge($basic, ['confirmar_identidad' => '1']))['confirmar_identidad'] === true, 'identity confirmation flag parsed');

// Validación reutilizable para editar consultas (FN-3).
$errors = [];
$edit = RegistroInput::consultation(['fecha_circunstancia' => '2026-01-15', 'motivo_consulta' => 'Control', 'enfermedad_actual' => 'Evolución',
    'diagnostico' => 'Sano', 'plan_tratamiento' => 'Alta', 'vital_ta' => '120/80'], '2000-01-01', $errors);
check(!$errors && $edit['vital_ta'] === '120/80' && $edit['fecha_inicio_reposo'] === null, 'consultation validator usable on its own');
check(array_diff(RegistroInput::CONSULTATION_COLUMNS, array_keys($edit)) === [], 'consultation validator returns every editable column');
$errors = [];
RegistroInput::consultation(['fecha_circunstancia' => '2026-01-15', 'motivo_consulta' => 'Control', 'enfermedad_actual' => 'x',
    'diagnostico' => 'x', 'plan_tratamiento' => 'x'], '2026-02-01', $errors);
check(isset($errors['fecha_circunstancia']), 'consultation validator enforces birth date');

// Al corregir una consulta antigua sin tocar el reposo ni la fecha, no se aplican las reglas nuevas
// de ventana y duración (BE-UPD-1); si cambia alguna de las tres fechas, sí.
$legacy = ['fecha_circunstancia' => '2025-03-20', 'fecha_inicio_reposo' => '2025-03-01', 'fecha_fin_reposo' => '2025-03-30'];
$legacyInput = ['fecha_circunstancia' => '2025-03-20', 'motivo_consulta' => 'Control', 'enfermedad_actual' => 'Evolución',
    'diagnostico' => 'Diagnóstico corregido', 'plan_tratamiento' => 'Alta', 'inicio_reposo' => '2025-03-01', 'fin_reposo' => '2025-03-30'];
$errors = [];
RegistroInput::consultation($legacyInput, '2000-01-01', $errors);
check(isset($errors['inicio_reposo']), 'new consultation still enforces the rest window');
$errors = [];
RegistroInput::consultation($legacyInput, '2000-01-01', $errors, $legacy);
check(!$errors, 'editing a legacy consultation keeps its issued rest unchanged');
$errors = [];
RegistroInput::consultation(array_merge($legacyInput, ['fin_reposo' => '2025-03-31']), '2000-01-01', $errors, $legacy);
check(isset($errors['inicio_reposo']), 'changing the rest dates re-applies the rest window');
$errors = [];
RegistroInput::consultation(array_merge($legacyInput, ['inicio_reposo' => '2025-03-30', 'fin_reposo' => '2025-03-01']), '2000-01-01', $errors,
    ['fecha_circunstancia' => '2025-03-20', 'fecha_inicio_reposo' => '2025-03-30', 'fecha_fin_reposo' => '2025-03-01']);
check(isset($errors['fin_reposo']), 'structural rest checks still apply to unchanged legacy rests');
check(in_array('antecedente_quirurgico', RegistroInput::CLINICAL_PATIENT_FIELDS, true) && !in_array('telefono', RegistroInput::CLINICAL_PATIENT_FIELDS, true), 'clinical patient fields separated from contact data');

// Identidad (BE-5).
check(RegistroInput::normalizeName('  José   Pérez ') === 'jose perez', 'name normalization collapses spaces and accents');
$stored = ['nombres' => 'José Luis', 'apellidos' => 'Pérez', 'sexo' => 'Masculino'];
check(!RegistroInput::identityDiffers($stored, ['nombres' => 'jose  luis', 'apellidos' => 'PEREZ', 'sexo' => 'Masculino']), 'cosmetic identity edits are not a mismatch');
check(RegistroInput::identityDiffers($stored, ['nombres' => 'María', 'apellidos' => 'Pérez', 'sexo' => 'Masculino']), 'different name is a mismatch');
check(RegistroInput::identityDiffers($stored, ['nombres' => 'José Luis', 'apellidos' => 'Pérez', 'sexo' => 'Femenino']), 'different sex is a mismatch');
check(!RegistroInput::identityDiffers(['nombres' => 'Ana', 'apellidos' => 'Díaz', 'sexo' => null], ['nombres' => 'Ana', 'apellidos' => 'Díaz', 'sexo' => 'Femenino']), 'completing a missing sex is not a mismatch');

// Citas (BE-7).
if (!class_exists('ApiException')) {
    final class ApiException extends RuntimeException {}
}
require_once $sourceRoot . '/config/Citas.php';
check(cita_fecha_problema(null) !== null, 'missing appointment date rejected');
check(cita_fecha_problema(new DateTimeImmutable('-1 minute')) !== null, 'past appointment rejected');
check(cita_fecha_problema(new DateTimeImmutable('+1 day')) === null, 'appointment tomorrow accepted');
check(cita_fecha_problema(new DateTimeImmutable('+2 years +1 day')) !== null, 'appointment beyond two years rejected');
check(cita_fecha_problema(Validation::dateTime('9999-12-31T10:00')) !== null, 'appointment in year 9999 rejected');

// Búsqueda, escapado LIKE/ILIKE, normalización de acentos, cédula y filtros seguros.
check(
    mb_strlen(Validation::SQL_ACCENT_FROM, 'UTF-8') === mb_strlen(Validation::SQL_ACCENT_TO, 'UTF-8'),
    'SQL accent translation maps have identical character length'
);
$errors = [];
check(
    Validation::search(['q' => "  José\t\n  Peña\x00\u{200B}\u{FEFF}  "], 'q', $errors) === 'José Peña' && !$errors,
    'search trims, collapses whitespace and strips control/zero-width characters'
);
$errors = [];
Validation::search(['q' => str_repeat('á', Validation::SEARCH_MAX_LENGTH)], 'q', $errors);
check(!$errors, 'search accepts exactly max Unicode characters');
$errors = [];
Validation::search(['q' => str_repeat('á', Validation::SEARCH_MAX_LENGTH + 1)], 'q', $errors);
check(isset($errors['q']), 'search rejects Unicode strings over max length');
$errors = [];
Validation::search(['q' => str_repeat('a', 5000)], 'q', $errors);
check(isset($errors['q']), 'search rejects oversized raw payload');
$errors = [];
Validation::search(['q' => ['nested']], 'q', $errors);
check(isset($errors['q']), 'search rejects array input');
$errors = [];
Validation::search(['q' => "\xFF\xFE"], 'q', $errors);
check(isset($errors['q']), 'search rejects invalid UTF-8');

check(Validation::searchTokens('   ') === [], 'empty search produces no tokens');
check(
    Validation::searchTokens('uno dos tres cuatro cinco seis siete ocho') === ['uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis'],
    'search tokens are capped at SEARCH_MAX_TOKENS'
);
check(Validation::normalizeSearchTerm('ÁÉÍÓÚÜ Ñ Ç José') === 'aeiouu n c jose', 'normalizeSearchTerm folds case and accents');
check(Validation::escapeLike('100%_a\\b') === '100\\%\\_a\\\\b', 'escapeLike escapes backslash, percent and underscore');
check(Validation::likePattern('50%_José') === '%50\\%\\_jose%', 'likePattern folds accents and escapes wildcards');

check(Validation::cedulaSearchToken('v-12.345.678') === 'V12345678', 'cedulaSearchToken strips hyphens and dots');
check(Validation::cedulaSearchToken(' 12 345 678 ') === '12345678', 'cedulaSearchToken strips spaces');
foreach (['%', '%%', '_', 'V-123%45', "' OR '1'='1", '---', ''] as $nonCedula) {
    check(Validation::cedulaSearchToken($nonCedula) === '', 'cedulaSearchToken rejects wildcards/non-alphanumeric: ' . $nonCedula);
}

$errors = [];
check(Validation::enum(['sexo' => 'Femenino'], 'sexo', ['Femenino', 'Masculino'], $errors) === 'Femenino' && !$errors, 'valid enum accepted');
check(Validation::enum(['sexo' => ''], 'sexo', ['Femenino', 'Masculino'], $errors) === '' && !$errors, 'empty enum accepted');
Validation::enum(['sexo' => 'Invalido'], 'sexo', ['Femenino', 'Masculino'], $errors);
check(isset($errors['sexo']), 'invalid enum rejected');

$errors = [];
check(Validation::optionalPositiveId([], 'categoria_id', $errors) === null && !$errors, 'omitted optionalPositiveId returns null');
check(Validation::optionalPositiveId(['categoria_id' => ''], 'categoria_id', $errors) === null && !$errors, 'empty optionalPositiveId returns null');
check(Validation::optionalPositiveId(['categoria_id' => '42'], 'categoria_id', $errors) === 42 && !$errors, 'valid optionalPositiveId returns int');
Validation::optionalPositiveId(['categoria_id' => '0'], 'categoria_id', $errors);
check(isset($errors['categoria_id']), 'zero optionalPositiveId rejected');

$errors = [];
check(Validation::optionalDateRange(['desde' => '2026-01-01', 'hasta' => '2026-01-31'], $errors) === ['2026-01-01', '2026-01-31'] && !$errors, 'valid optionalDateRange accepted');
$errors = [];
Validation::optionalDateRange(['desde' => '2026-02-01', 'hasta' => '2026-01-01'], $errors);
check(isset($errors['hasta']), 'reversed optionalDateRange rejected');
$errors = [];
Validation::optionalDateRange(['desde' => '2026-02-30', 'hasta' => '2026-03-01'], $errors);
check(isset($errors['desde']), 'invalid calendar date in optionalDateRange rejected');
$errors = [];
Validation::optionalDateRange(['desde' => '2020-01-01', 'hasta' => '2026-01-02'], $errors, 'desde', 'hasta', '+5 years');
check(isset($errors['hasta']), 'optionalDateRange enforces maxSpan');

echo "Backend validation: {$checks} assertions passed.\n";
