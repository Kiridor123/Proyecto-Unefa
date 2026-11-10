<?php
declare(strict_types=1);

namespace Config;

use DateTimeImmutable;

final class RegistroInput
{
    /** Días que el reposo puede empezar antes de la fecha de la consulta. */
    public const REPOSO_DIAS_ANTES = 7;
    /** Días que el reposo puede empezar después de la fecha de la consulta. */
    public const REPOSO_DIAS_DESPUES = 30;
    /** Duración máxima de un reposo, en días. */
    public const REPOSO_DURACION_MAXIMA = 365;
    /** Fecha mínima admitida para una consulta. */
    public const FECHA_CONSULTA_MINIMA = '1900-01-01';

    /** Campos de antecedentes del paciente que exigen permiso clínico para modificarse. */
    public const CLINICAL_PATIENT_FIELDS = [
        'tiene_tatuajes', 'compromiso_tatuajes',
        'antecedente_cardiovascular', 'antecedente_oseo', 'antecedente_respiratorio', 'antecedente_digestivo',
        'antecedente_endocrino', 'antecedente_otros', 'antecedente_quirurgico',
        'gineco_menarquia', 'gineco_sexarquia', 'gineco_aco', 'gineco_gestas', 'gineco_citologia',
        'antecedente_padre', 'antecedente_madre', 'antecedente_hermanos', 'antecedente_hijos',
    ];

    /** Columnas de la consulta que se pueden registrar o editar. */
    public const CONSULTATION_COLUMNS = [
        'fecha_circunstancia', 'motivo_consulta', 'enfermedad_actual', 'diagnostico', 'plan_tratamiento', 'resumen',
        'vital_ta', 'vital_fc', 'vital_fr', 'vital_spo2', 'vital_peso_talla',
        'fisico_piel', 'fisico_cabeza', 'fisico_cuello', 'fisico_torax', 'fisico_abdomen', 'fisico_extremidades', 'fisico_neurologico',
        'laboratorios', 'pendiente', 'fecha_inicio_reposo', 'fecha_fin_reposo',
    ];

    public static function from(array $input): array
    {
        $errors = [];
        $patient = [];
        $solo = ($input['solo_paciente'] ?? '0') === '1';
        if (isset($input['solo_paciente']) && !in_array($input['solo_paciente'], ['0', '1'], true)) $errors['solo_paciente'] = 'Indique un tipo de registro válido.';
        $patient['cedula'] = Validation::cedula(Validation::text($input, 'cedula', 40, $errors, true));
        if (!Validation::validCedula($patient['cedula'])) $errors['cedula'] = 'Use una letra permitida (V, E, J, G, C, P, N), guion y hasta 18 números.';
        $patient['nombres'] = Validation::text($input, 'nombres', 100, $errors, true);
        $patient['apellidos'] = Validation::text($input, 'apellidos', 100, $errors, true);
        $patient['categoria_id'] = Validation::positiveId($input['categoria'] ?? null);
        if (!$patient['categoria_id']) $errors['categoria'] = 'Seleccione una categoría válida.';
        $patient['sexo'] = Validation::text($input, 'sexo', 20, $errors, true);
        if (!in_array($patient['sexo'], ['Masculino', 'Femenino', 'Otro'], true)) $errors['sexo'] = 'Seleccione un sexo válido.';

        $lengths = [
            'telefono' => 50, 'direccion' => 10000, 'nivel_educativo' => 100,
            'lugar_nacimiento' => 255, 'fecha_nacimiento' => 10, 'carrera' => 150, 'semestre' => 50,
            'tiene_tatuajes' => 20, 'compromiso_tatuajes' => 10000,
            'antecedente_cardiovascular' => 10000, 'antecedente_oseo' => 10000,
            'antecedente_respiratorio' => 10000, 'antecedente_digestivo' => 10000,
            'antecedente_endocrino' => 10000, 'antecedente_otros' => 10000, 'antecedente_quirurgico' => 10000,
            'gineco_menarquia' => 50, 'gineco_sexarquia' => 50, 'gineco_aco' => 100,
            'gineco_gestas' => 50, 'gineco_citologia' => 255,
            'antecedente_padre' => 10000, 'antecedente_madre' => 10000,
            'antecedente_hermanos' => 10000, 'antecedente_hijos' => 10000,
        ];
        // Omitted optional fields must not erase an existing clinical history.
        foreach ($lengths as $key => $length) {
            if (array_key_exists($key, $input)) $patient[$key] = Validation::text($input, $key, $length, $errors);
        }
        if (isset($patient['tiene_tatuajes']) && !in_array($patient['tiene_tatuajes'], ['', 'Si', 'Sí', 'No'], true)) $errors['tiene_tatuajes'] = 'Seleccione una opción válida.';
        $today = new DateTimeImmutable('today');
        if (isset($patient['fecha_nacimiento'])) {
            if ($patient['fecha_nacimiento'] === '') {
                $patient['fecha_nacimiento'] = null;
            } else {
                $birth = Validation::date($patient['fecha_nacimiento']);
                if (!$birth || $birth > $today || $birth < $today->modify('-120 years')) $errors['fecha_nacimiento'] = 'Indique una fecha de nacimiento válida de los últimos 120 años.';
            }
        }

        $consultation = [];
        $requestId = null;
        if (!$solo) {
            $consultation = self::consultation($input, $patient['fecha_nacimiento'] ?? null, $errors);
            $requestId = self::requestId($input, $errors);
        }
        return [
            'patient' => $patient, 'consultation' => $consultation, 'solo' => $solo, 'errors' => $errors,
            'solicitud_id' => $requestId, 'confirmar_identidad' => ($input['confirmar_identidad'] ?? '0') === '1',
        ];
    }

    /**
     * Valida los campos de una consulta (registro y edición).
     * Devuelve las columnas de `consultas` listas para guardar.
     *
     * $previous es la fila guardada cuando se corrige una consulta. Si el reposo y la fecha de
     * la consulta no cambian, no se aplican las reglas de duración ni de ventana (−7/+30 días):
     * un reposo ya emitido antes de esas reglas no impide corregir otro campo.
     * Las comprobaciones estructurales (fechas válidas, ambas presentes, fin ≥ inicio) se mantienen.
     */
    public static function consultation(array $input, ?string $birthDate, array &$errors, ?array $previous = null): array
    {
        $today = new DateTimeImmutable('today');
        $consultation = [];
        $consultation['fecha_circunstancia'] = Validation::text($input, 'fecha_circunstancia', 10, $errors);
        if ($consultation['fecha_circunstancia'] === '') $consultation['fecha_circunstancia'] = $today->format('Y-m-d');
        $date = Validation::date($consultation['fecha_circunstancia']);
        if (!$date || $date > $today) {
            $errors['fecha_circunstancia'] = 'Indique una fecha de consulta válida que no sea futura.';
            $date = null;
        } elseif ($consultation['fecha_circunstancia'] < self::FECHA_CONSULTA_MINIMA) {
            $errors['fecha_circunstancia'] = 'La fecha de la consulta no puede ser anterior a 1900.';
            $date = null;
        } elseif ($birthDate !== null && $birthDate !== '' && $consultation['fecha_circunstancia'] < $birthDate) {
            $errors['fecha_circunstancia'] = 'La consulta no puede ser anterior al nacimiento.';
        }
        foreach (['motivo_consulta', 'enfermedad_actual', 'diagnostico', 'plan_tratamiento'] as $key) $consultation[$key] = Validation::text($input, $key, 20000, $errors, true);
        $consultation['resumen'] = Validation::text($input, 'resumen', 20000, $errors);
        if ($consultation['resumen'] === '') $consultation['resumen'] = $consultation['enfermedad_actual'];
        foreach (['vital_ta' => 50, 'vital_fc' => 50, 'vital_fr' => 50, 'vital_spo2' => 50, 'vital_peso_talla' => 100,
            'fisico_piel' => 10000, 'fisico_cabeza' => 10000, 'fisico_cuello' => 10000, 'fisico_torax' => 10000,
            'fisico_abdomen' => 10000, 'fisico_extremidades' => 10000, 'fisico_neurologico' => 10000,
            'laboratorios' => 20000, 'pendiente' => 10000] as $key => $length) {
            $consultation[$key] = Validation::text($input, $key, $length, $errors);
        }
        foreach (['vital_fc' => 300, 'vital_fr' => 100, 'vital_spo2' => 100] as $key => $maximum) {
            if ($consultation[$key] !== '' && !Validation::number($consultation[$key], 0, $maximum)) $errors[$key] = "Indique un número entre 0 y {$maximum}.";
        }
        if ($consultation['vital_ta'] !== '' && !preg_match('/^\d{2,3}\/\d{2,3}$/D', $consultation['vital_ta'])) $errors['vital_ta'] = 'Use el formato sistólica/diastólica (ej. 120/80).';
        $restDates = [];
        foreach (['inicio_reposo' => 'fecha_inicio_reposo', 'fin_reposo' => 'fecha_fin_reposo'] as $key => $column) {
            $value = Validation::text($input, $key, 10, $errors);
            $parsed = $value !== '' ? Validation::date($value) : null;
            if ($value !== '' && !$parsed) $errors[$key] = 'Indique una fecha válida (AAAA-MM-DD).';
            $consultation[$column] = $value !== '' ? $value : null;
            $restDates[$key] = $parsed;
        }
        $start = $consultation['fecha_inicio_reposo'];
        $end = $consultation['fecha_fin_reposo'];
        if (($start === null) !== ($end === null)) $errors[$start === null ? 'inicio_reposo' : 'fin_reposo'] = 'Indique ambas fechas del reposo.';
        $startDate = $restDates['inicio_reposo'];
        $endDate = $restDates['fin_reposo'];
        $restUnchanged = $previous !== null
            && (string) ($previous['fecha_inicio_reposo'] ?? '') === (string) ($start ?? '')
            && (string) ($previous['fecha_fin_reposo'] ?? '') === (string) ($end ?? '')
            && (string) ($previous['fecha_circunstancia'] ?? '') === $consultation['fecha_circunstancia'];
        if ($startDate && $endDate) {
            if ($endDate < $startDate) {
                $errors['fin_reposo'] = 'El reposo debe finalizar el mismo día o después de su inicio.';
            } elseif (!$restUnchanged && self::restDays($startDate, $endDate) > self::REPOSO_DURACION_MAXIMA) {
                $errors['fin_reposo'] = 'El reposo no puede superar ' . self::REPOSO_DURACION_MAXIMA . ' días.';
            }
        }
        if ($startDate && $date && !$restUnchanged && !isset($errors['inicio_reposo'])) {
            if ($startDate < $date->modify('-' . self::REPOSO_DIAS_ANTES . ' days')) {
                $errors['inicio_reposo'] = 'El reposo no puede comenzar más de ' . self::REPOSO_DIAS_ANTES . ' días antes de la consulta.';
            } elseif ($startDate > $date->modify('+' . self::REPOSO_DIAS_DESPUES . ' days')) {
                $errors['inicio_reposo'] = 'El reposo no puede comenzar más de ' . self::REPOSO_DIAS_DESPUES . ' días después de la consulta.';
            }
        }
        return $consultation;
    }

    /** Días de reposo contando el primero y el último (lo que muestra el impreso). */
    public static function restDays(DateTimeImmutable $start, DateTimeImmutable $end): int
    {
        return (int) $start->diff($end)->days + 1;
    }

    /** Identificador de solicitud (UUID) opcional para evitar consultas duplicadas. */
    public static function requestId(array $input, array &$errors): ?string
    {
        $value = Validation::text($input, 'solicitud_id', 36, $errors);
        if ($value === '') return null;
        $value = strtolower($value);
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/D', $value)) {
            $errors['solicitud_id'] = 'El identificador de la solicitud no es válido.';
            return null;
        }
        return $value;
    }

    /** Normaliza un nombre para compararlo: minúsculas, sin acentos y con espacios colapsados. */
    public static function normalizeName(?string $value): string
    {
        $value = mb_strtolower(trim((string) $value), 'UTF-8');
        $value = strtr($value, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u']);
        return (string) preg_replace('/\s+/u', ' ', $value);
    }

    /** Indica si los datos de identidad enviados difieren de los guardados. */
    public static function identityDiffers(array $stored, array $sent): bool
    {
        return self::normalizeName($stored['nombres'] ?? '') !== self::normalizeName($sent['nombres'] ?? '')
            || self::normalizeName($stored['apellidos'] ?? '') !== self::normalizeName($sent['apellidos'] ?? '')
            // Completar un sexo vacío (registros antiguos) no se considera un cambio de identidad.
            || (($stored['sexo'] ?? '') !== '' && $stored['sexo'] !== null && (string) $stored['sexo'] !== (string) ($sent['sexo'] ?? ''));
    }
}
