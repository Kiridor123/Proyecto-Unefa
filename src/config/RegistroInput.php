<?php
declare(strict_types=1);

namespace Config;

use DateTimeImmutable;

final class RegistroInput
{
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
        if (!$solo) {
            $consultation['fecha_circunstancia'] = Validation::text($input, 'fecha_circunstancia', 10, $errors);
            if ($consultation['fecha_circunstancia'] === '') $consultation['fecha_circunstancia'] = $today->format('Y-m-d');
            $date = Validation::date($consultation['fecha_circunstancia']);
            if (!$date || $date > $today) $errors['fecha_circunstancia'] = 'Indique una fecha de consulta válida que no sea futura.';
            if ($date && !empty($patient['fecha_nacimiento']) && $consultation['fecha_circunstancia'] < $patient['fecha_nacimiento']) $errors['fecha_circunstancia'] = 'La consulta no puede ser anterior al nacimiento.';
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
            foreach (['inicio_reposo' => 'fecha_inicio_reposo', 'fin_reposo' => 'fecha_fin_reposo'] as $key => $column) {
                $value = Validation::text($input, $key, 10, $errors);
                if ($value !== '' && !Validation::date($value)) $errors[$key] = 'Indique una fecha válida (AAAA-MM-DD).';
                $consultation[$column] = $value !== '' ? $value : null;
            }
            $start = $consultation['fecha_inicio_reposo'];
            $end = $consultation['fecha_fin_reposo'];
            if (($start === null) !== ($end === null)) $errors[$start === null ? 'inicio_reposo' : 'fin_reposo'] = 'Indique ambas fechas del reposo.';
            if ($start && $end && $end < $start) $errors['fin_reposo'] = 'El reposo debe finalizar el mismo día o después de su inicio.';
        }
        return ['patient' => $patient, 'consultation' => $consultation, 'solo' => $solo, 'errors' => $errors];
    }
}
