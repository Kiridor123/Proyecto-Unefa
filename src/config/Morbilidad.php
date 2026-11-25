<?php
declare(strict_types=1);

/** Consulta y filtros compartidos del reporte de morbilidad (API JSON, exportación CSV e impresión). */

use Config\Validation;

const MORBILIDAD_RANGO_MAXIMO = '+5 years';
const MORBILIDAD_SEXOS = ['Femenino', 'Masculino', 'Otro'];
const MORBILIDAD_REPOSO_OPCIONES = ['1', '0'];

/** Normaliza alias de fechas (desde/hasta -> fecha_inicio/fecha_fin) conservando las claves canónicas. */
function morbilidad_normalizar_fechas(array $input): array
{
    if (!array_key_exists('fecha_inicio', $input) && array_key_exists('desde', $input)) {
        $input['fecha_inicio'] = $input['desde'];
    }
    if (!array_key_exists('fecha_fin', $input) && array_key_exists('hasta', $input)) {
        $input['fecha_fin'] = $input['hasta'];
    }
    return $input;
}

/** Valida fecha_inicio/fecha_fin (o alias desde/hasta) con un rango máximo de 5 años. Devuelve [inicio, fin]. */
function morbilidad_rango(array $input): array
{
    $errors = [];
    $normalized = morbilidad_normalizar_fechas($input);
    [$start, $end] = Validation::dateRange($normalized, $errors);
    if (!$errors) {
        $limit = Validation::date($start)->modify(MORBILIDAD_RANGO_MAXIMO);
        if (Validation::date($end) > $limit) {
            $errors['fecha_fin'] = 'El rango del reporte no puede superar 5 años.';
        }
    }
    api_validate($errors);
    return [$start, $end];
}

/**
 * Valida todos los parámetros de filtro del reporte de morbilidad de forma consistente
 * entre consulta JSON, exportación CSV e impresión institucional.
 */
function morbilidad_filtros(array $input, PDO $db): array
{
    $errors = [];
    $normalized = morbilidad_normalizar_fechas($input);
    [$start, $end] = Validation::dateRange($normalized, $errors);
    if (!isset($errors['fecha_inicio']) && !isset($errors['fecha_fin'])) {
        $startDate = Validation::date($start);
        $endDate = Validation::date($end);
        if ($startDate && $endDate && $endDate > $startDate->modify(MORBILIDAD_RANGO_MAXIMO)) {
            $errors['fecha_fin'] = 'El rango del reporte no puede superar 5 años.';
        }
    }

    $categoriaId = Validation::optionalPositiveId($input, 'categoria_id', $errors, 'Seleccione una categoría válida.');
    $sexo = Validation::enum($input, 'sexo', MORBILIDAD_SEXOS, $errors, 'Seleccione un sexo válido.', 20);
    $conReposo = Validation::enum($input, 'con_reposo', MORBILIDAD_REPOSO_OPCIONES, $errors, 'Seleccione un filtro de reposo válido.', 5);
    $search = Validation::search($input, 'q', $errors, Validation::SEARCH_MAX_LENGTH);

    api_validate($errors);

    $categoriaNombre = null;
    if ($categoriaId !== null) {
        $stmtCategoria = $db->prepare('SELECT nombre FROM categorias_institucionales WHERE id = ?');
        $stmtCategoria->execute([$categoriaId]);
        $fetched = $stmtCategoria->fetchColumn();
        if ($fetched === false) {
            throw new ApiException('La categoría seleccionada no existe.', 422, ['categoria_id' => 'Seleccione una categoría válida.']);
        }
        $categoriaNombre = (string) $fetched;
    }

    return [
        'fecha_inicio' => $start,
        'fecha_fin' => $end,
        'categoria_id' => $categoriaId,
        'categoria_nombre' => $categoriaNombre,
        'sexo' => $sexo,
        'con_reposo' => $conReposo,
        'q' => $search,
    ];
}

/**
 * Filas del reporte con proyección explícita y filtros parametrizados.
 * Acepta el arreglo devuelto por morbilidad_filtros() o (por compatibilidad) ($db, $start, $end).
 * Cuando $includePrintColumns es false, omite teléfono y dirección.
 */
function morbilidad_filas(PDO $db, $filtersOrStart, ?string $legacyEnd = null, bool $includePrintColumns = false): array
{
    if (is_array($filtersOrStart)) {
        $filters = $filtersOrStart;
    } else {
        $filters = [
            'fecha_inicio' => (string) $filtersOrStart,
            'fecha_fin' => (string) ($legacyEnd ?? ''),
            'categoria_id' => null,
            'sexo' => '',
            'con_reposo' => '',
            'q' => '',
        ];
    }

    $columns = 'c.id, c.paciente_id, c.fecha_circunstancia, c.diagnostico, c.resumen, c.motivo_consulta,
        c.plan_tratamiento, c.fecha_inicio_reposo, c.fecha_fin_reposo,
        c.vital_ta, c.vital_peso_talla,
        p.cedula, p.nombres, p.apellidos, p.sexo, p.fecha_nacimiento, p.categoria_id, p.carrera, p.semestre,
        cat.nombre AS categoria';
    if ($includePrintColumns) {
        $columns .= ', c.vital_fc, p.telefono';
    }

    $conditions = ['c.fecha_circunstancia BETWEEN ? AND ?'];
    $params = [$filters['fecha_inicio'], $filters['fecha_fin']];

    if (!empty($filters['categoria_id'])) {
        $conditions[] = 'p.categoria_id = ?';
        $params[] = (int) $filters['categoria_id'];
    }
    if (($filters['sexo'] ?? '') !== '') {
        $conditions[] = 'p.sexo = ?';
        $params[] = $filters['sexo'];
    }
    if (($filters['con_reposo'] ?? '') === '1') {
        $conditions[] = '(c.fecha_inicio_reposo IS NOT NULL AND c.fecha_fin_reposo IS NOT NULL)';
    } elseif (($filters['con_reposo'] ?? '') === '0') {
        $conditions[] = '(c.fecha_inicio_reposo IS NULL OR c.fecha_fin_reposo IS NULL)';
    }

    $search = (string) ($filters['q'] ?? '');
    if ($search !== '') {
        $searchable = Validation::sqlNormalize(
            "concat_ws(' ', p.nombres, p.apellidos, c.diagnostico, c.motivo_consulta, c.plan_tratamiento, c.resumen, cat.nombre, p.carrera, p.semestre)"
        );
        foreach (Validation::searchTokens($search) as $token) {
            $pattern = Validation::likePattern($token);
            $cedulaToken = Validation::cedulaSearchToken($token);
            if ($cedulaToken === '') {
                $conditions[] = $searchable . ' ' . Validation::SQL_LIKE_CLAUSE;
                $params[] = $pattern;
            } else {
                $conditions[] = '(' . $searchable . ' ' . Validation::SQL_LIKE_CLAUSE
                    . " OR translate(upper(p.cedula), '-. ', '') " . Validation::SQL_LIKE_CLAUSE . ')';
                $params[] = $pattern;
                $params[] = '%' . Validation::escapeLike($cedulaToken) . '%';
            }
        }
    }

    $sql = 'SELECT ' . $columns . '
        FROM consultas c
        JOIN pacientes p ON c.paciente_id = p.id
        JOIN categorias_institucionales cat ON p.categoria_id = cat.id
        WHERE ' . implode(' AND ', $conditions) . '
        ORDER BY c.fecha_circunstancia ASC, c.id ASC';

    $statement = $db->prepare($sql);
    $statement->execute($params);
    return $statement->fetchAll();
}

/** Metadatos de auditoría sin texto clínico sensible. */
function morbilidad_audit_detail(array $filters, int $registros): array
{
    $detail = [
        'fecha_inicio' => $filters['fecha_inicio'],
        'fecha_fin' => $filters['fecha_fin'],
        'registros' => $registros,
    ];
    if ($filters['categoria_id'] !== null) $detail['categoria_id'] = $filters['categoria_id'];
    if ($filters['sexo'] !== '') $detail['sexo'] = $filters['sexo'];
    if ($filters['con_reposo'] !== '') $detail['con_reposo'] = $filters['con_reposo'] === '1';
    if ($filters['q'] !== '') $detail['con_busqueda'] = true;
    return $detail;
}
