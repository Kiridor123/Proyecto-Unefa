<?php
declare(strict_types=1);

/** Consulta compartida del reporte de morbilidad (vista, exportación CSV). */

const MORBILIDAD_RANGO_MAXIMO = '+5 years';

/** Valida fecha_inicio/fecha_fin con un rango máximo de 5 años. Devuelve [inicio, fin]. */
function morbilidad_rango(array $input): array
{
    $errors = [];
    [$start, $end] = Config\Validation::dateRange($input, $errors);
    if (!$errors) {
        $limit = Config\Validation::date($start)->modify(MORBILIDAD_RANGO_MAXIMO);
        if (Config\Validation::date($end) > $limit) $errors['fecha_fin'] = 'El rango del reporte no puede superar 5 años.';
    }
    api_validate($errors);
    return [$start, $end];
}

/** Filas del reporte con una proyección explícita (sin teléfono, dirección ni el resto de la historia). */
function morbilidad_filas(PDO $db, string $start, string $end): array
{
    $statement = $db->prepare('SELECT c.id, c.paciente_id, c.fecha_circunstancia, c.diagnostico, c.resumen, c.motivo_consulta,
            c.vital_ta, c.vital_peso_talla, p.cedula, p.nombres, p.apellidos, p.sexo, p.fecha_nacimiento, cat.nombre AS categoria
        FROM consultas c JOIN pacientes p ON c.paciente_id = p.id
        JOIN categorias_institucionales cat ON p.categoria_id = cat.id
        WHERE c.fecha_circunstancia BETWEEN ? AND ? ORDER BY c.fecha_circunstancia ASC, c.id ASC');
    $statement->execute([$start, $end]);
    return $statement->fetchAll();
}
