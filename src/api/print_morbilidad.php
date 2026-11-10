<?php
declare(strict_types=1);

define('API_HTML_RESPONSE', true);
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/print_common.php';
api_method('GET');
api_require('reportes.ver');
api_require('clinica.ver');

const MORBILIDAD_MAX_ANIOS = 5;
const MORBILIDAD_TOP_DIAGNOSTICOS = 10;

$errors = [];
[$fecha_inicio, $fecha_fin] = Config\Validation::dateRange($_GET, $errors);
if (!$errors && (new DateTimeImmutable($fecha_inicio))->modify('+' . MORBILIDAD_MAX_ANIOS . ' years') < new DateTimeImmutable($fecha_fin)) {
    $errors['fecha_fin'] = 'El rango no puede superar ' . MORBILIDAD_MAX_ANIOS . ' años.';
}
api_validate($errors);

$db = api_db();

// Filtro opcional por categoría institucional (p. ej. solo estudiantes para una jornada estudiantil).
$categoria_id = (isset($_GET['categoria_id']) && $_GET['categoria_id'] !== '') ? api_id($_GET['categoria_id'], 'categoria_id') : null;
$categoria_nombre = null;
if ($categoria_id !== null) {
    $stmtCategoria = $db->prepare('SELECT nombre FROM categorias_institucionales WHERE id = ?');
    $stmtCategoria->execute([$categoria_id]);
    $categoria_nombre = $stmtCategoria->fetchColumn();
    if ($categoria_nombre === false) {
        throw new ApiException('La categoría seleccionada no existe.', 422, ['categoria_id' => 'Seleccione una categoría válida.']);
    }
}

$sql = "
    SELECT c.id, c.paciente_id, c.fecha_circunstancia, c.diagnostico, c.resumen,
           c.vital_ta, c.vital_fc, c.vital_peso_talla,
           p.cedula, p.nombres, p.apellidos, p.sexo, p.fecha_nacimiento, p.carrera, p.semestre, p.telefono,
           cat.nombre AS categoria
    FROM consultas c
    JOIN pacientes p ON c.paciente_id = p.id
    JOIN categorias_institucionales cat ON p.categoria_id = cat.id
    WHERE c.fecha_circunstancia BETWEEN ? AND ?
";
$params = [$fecha_inicio, $fecha_fin];
if ($categoria_id !== null) {
    $sql .= ' AND p.categoria_id = ?';
    $params[] = $categoria_id;
}
$sql .= ' ORDER BY c.fecha_circunstancia ASC, c.id ASC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$consultas = $stmt->fetchAll();

// --- Agregados del período ---------------------------------------------------------------
$total_consultas = count($consultas);
$pacientes_unicos = count(array_unique(array_map(static fn ($fila) => (int) $fila['paciente_id'], $consultas)));

$sexos = ['Femenino', 'Masculino', 'Otro', 'Sin dato'];
$por_categoria = []; // categoría => ['sexos' => [...], 'total' => n, 'pacientes' => [id => true]]
$totales_sexo = array_fill_keys($sexos, 0);
$diagnosticos = []; // clave normalizada => ['texto' => forma más frecuente, 'total' => n, 'formas' => [texto => n]]
$sin_diagnostico = 0;

foreach ($consultas as $fila) {
    $categoria = print_first($fila['categoria']) ?? 'Sin categoría';
    $sexo = in_array($fila['sexo'], ['Femenino', 'Masculino', 'Otro'], true) ? $fila['sexo'] : 'Sin dato';
    $por_categoria[$categoria] ??= ['sexos' => array_fill_keys($sexos, 0), 'total' => 0, 'pacientes' => []];
    $por_categoria[$categoria]['sexos'][$sexo]++;
    $por_categoria[$categoria]['total']++;
    $por_categoria[$categoria]['pacientes'][(int) $fila['paciente_id']] = true;
    $totales_sexo[$sexo]++;

    $texto = print_first($fila['diagnostico']);
    if ($texto === null) {
        $sin_diagnostico++;
        continue;
    }
    $texto = preg_replace('/\s+/u', ' ', $texto) ?? $texto;
    $clave = rtrim(mb_strtolower($texto, 'UTF-8'), " .;,");
    if ($clave === '') {
        $sin_diagnostico++;
        continue;
    }
    $diagnosticos[$clave] ??= ['total' => 0, 'formas' => []];
    $diagnosticos[$clave]['total']++;
    $diagnosticos[$clave]['formas'][$texto] = ($diagnosticos[$clave]['formas'][$texto] ?? 0) + 1;
}
ksort($por_categoria, SORT_LOCALE_STRING);
uasort($diagnosticos, static fn ($a, $b) => $b['total'] <=> $a['total']);
$top_diagnosticos = array_slice($diagnosticos, 0, MORBILIDAD_TOP_DIAGNOSTICOS, true);
foreach ($top_diagnosticos as &$item) {
    arsort($item['formas']);
    $item['texto'] = (string) array_key_first($item['formas']);
}
unset($item);
$sexos_visibles = array_values(array_filter($sexos, static fn ($sexo) => $totales_sexo[$sexo] > 0 || in_array($sexo, ['Femenino', 'Masculino'], true)));

$fecha_inicio_f = print_fecha_corta($fecha_inicio);
$fecha_fin_f = print_fecha_corta($fecha_fin);

api_audit('imprimir_morbilidad', null, null, array_filter([
    'fecha_inicio' => $fecha_inicio,
    'fecha_fin' => $fecha_fin,
    'categoria_id' => $categoria_id,
    'registros' => $total_consultas,
], static fn ($valor) => $valor !== null));

print_page_start('Morbilidad ' . $fecha_inicio_f . ' al ' . $fecha_fin_f, [
    'boton' => 'Imprimir reporte',
    'orientacion' => 'landscape',
    'margen' => '1.27cm',
    'css' => '
        .morbilidad { font-size: 8pt; line-height: 1.25; }
        .morbilidad .doc-membrete { font-size: 8pt; }
        .encabezado-reporte { margin: 8px 0 8px; }
        .encabezado-reporte h1 { font-size: 11pt; margin: 0; text-transform: uppercase; }
        .encabezado-reporte p { margin: 2px 0 0; font-size: 9pt; }
        .listado th { font-size: 7.5pt; text-transform: uppercase; text-align: center; vertical-align: middle; }
        .listado td { font-size: 8pt; }
        .listado thead { display: table-header-group; }
        .listado tr { break-inside: avoid; page-break-inside: avoid; }
        .resumen { display: grid; grid-template-columns: 1.1fr 1fr; gap: 14px; margin-top: 12px; break-inside: avoid; page-break-inside: avoid; }
        .resumen h2 { font-size: 9pt; text-transform: uppercase; margin: 0 0 4px; }
        .resumen .tabla th, .resumen .tabla td { font-size: 8pt; }
        .resumen .tabla td.num, .resumen .tabla th.num { text-align: right; }
        .totales { display: flex; gap: 24px; font-size: 9.5pt; margin-top: 12px; }
        .nota { font-size: 7.5pt; color: #444; margin: 4px 0 0; }
        .morbilidad-firmas { margin-top: 36px; justify-content: flex-end; }
    ',
]);
?>
        <div class="morbilidad">
            <?php print_header([
                'República Bolivariana de Venezuela',
                'Ministerio del Poder Popular para la Defensa',
                PRINT_INSTITUCION,
                'Vicerrectorado Académico',
                'Dirección UNEFA — Extensión Acarigua',
            ], print_logo('unefa')); ?>

            <div class="encabezado-reporte">
                <h1>Control de asistencia médica</h1>
                <p>
                    <span class="etq">Período:</span> <?= print_e($fecha_inicio_f) ?> al <?= print_e($fecha_fin_f) ?>
                    <?php if ($categoria_nombre !== null): ?>
                        · <span class="etq">Categoría:</span> <?= print_e($categoria_nombre) ?>
                    <?php else: ?>
                        · <span class="etq">Categoría:</span> todas
                    <?php endif; ?>
                </p>
            </div>

            <table class="tabla listado">
                <colgroup>
                    <col style="width: 6.5%"><col style="width: 13%"><col style="width: 8%"><col style="width: 4%">
                    <col style="width: 6%"><col style="width: 7.5%"><col style="width: 5.5%"><col style="width: 10%">
                    <col style="width: 8%"><col style="width: 7%"><col style="width: 5.5%"><col style="width: 4%">
                    <col style="width: 15%">
                </colgroup>
                <thead>
                    <tr>
                        <th scope="col">Fecha</th>
                        <th scope="col">Nombre y apellido</th>
                        <th scope="col">Cédula</th>
                        <th scope="col">Edad</th>
                        <th scope="col">Sexo</th>
                        <th scope="col">Categoría</th>
                        <th scope="col">Semestre</th>
                        <th scope="col">Carrera</th>
                        <th scope="col">Teléfono</th>
                        <th scope="col">Peso / talla</th>
                        <th scope="col">TA</th>
                        <th scope="col">FC</th>
                        <th scope="col">Diagnóstico</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($total_consultas === 0): ?>
                        <tr>
                            <td colspan="13" class="centro vacio" style="padding: 16px;">No se encontraron consultas en el período seleccionado.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($consultas as $fila):
                            $edad = print_edad($fila['fecha_nacimiento'], $fila['fecha_circunstancia']);
                        ?>
                            <tr>
                                <td class="centro"><?= print_fecha_corta($fila['fecha_circunstancia'], '—') ?></td>
                                <td><?= print_e($fila['nombres'] . ' ' . $fila['apellidos']) ?></td>
                                <td class="centro"><?= print_e($fila['cedula']) ?></td>
                                <td class="centro"><?= $edad === null ? '—' : $edad ?></td>
                                <td class="centro"><?= print_val($fila['sexo'], '—') ?></td>
                                <td><?= print_val($fila['categoria'], '—') ?></td>
                                <td class="centro"><?= print_val($fila['semestre'], '—') ?></td>
                                <td><?= print_val($fila['carrera'], '—') ?></td>
                                <td class="centro"><?= print_val($fila['telefono'], '—') ?></td>
                                <td class="centro"><?= print_val($fila['vital_peso_talla'], '—') ?></td>
                                <td class="centro"><?= print_val($fila['vital_ta'], '—') ?></td>
                                <td class="centro"><?= print_val($fila['vital_fc'], '—') ?></td>
                                <td class="fuerte"><?= print_text(print_first($fila['diagnostico'], $fila['resumen']), '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="totales">
                <div><span class="etq">Total de consultas:</span> <span class="fuerte"><?= $total_consultas ?></span></div>
                <div><span class="etq">Pacientes distintos atendidos:</span> <span class="fuerte"><?= $pacientes_unicos ?></span></div>
            </div>

            <?php if ($total_consultas > 0): ?>
                <div class="resumen">
                    <section>
                        <h2>Consultas por categoría y sexo</h2>
                        <table class="tabla">
                            <thead>
                                <tr>
                                    <th scope="col" style="width: 30%;">Categoría</th>
                                    <?php foreach ($sexos_visibles as $sexo): ?>
                                        <th scope="col" class="num"><?= print_e($sexo) ?></th>
                                    <?php endforeach; ?>
                                    <th scope="col" class="num">Consultas</th>
                                    <th scope="col" class="num">Pacientes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($por_categoria as $categoria => $datos): ?>
                                    <tr>
                                        <td><?= print_e($categoria) ?></td>
                                        <?php foreach ($sexos_visibles as $sexo): ?>
                                            <td class="num"><?= $datos['sexos'][$sexo] ?></td>
                                        <?php endforeach; ?>
                                        <td class="num fuerte"><?= $datos['total'] ?></td>
                                        <td class="num"><?= count($datos['pacientes']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <th scope="row">Total</th>
                                    <?php foreach ($sexos_visibles as $sexo): ?>
                                        <th class="num"><?= $totales_sexo[$sexo] ?></th>
                                    <?php endforeach; ?>
                                    <th class="num"><?= $total_consultas ?></th>
                                    <th class="num"><?= $pacientes_unicos ?></th>
                                </tr>
                            </tbody>
                        </table>
                    </section>
                    <section>
                        <h2>Diagnósticos más frecuentes</h2>
                        <?php if ($top_diagnosticos): ?>
                            <table class="tabla">
                                <thead>
                                    <tr>
                                        <th scope="col" style="width: 8%;" class="num">N.º</th>
                                        <th scope="col">Diagnóstico</th>
                                        <th scope="col" style="width: 18%;" class="num">Consultas</th>
                                        <th scope="col" style="width: 14%;" class="num">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $posicion = 0; foreach ($top_diagnosticos as $item): $posicion++; ?>
                                        <tr>
                                            <td class="num"><?= $posicion ?></td>
                                            <td><?= print_e(mb_strlen($item['texto'], 'UTF-8') > 160 ? rtrim(mb_substr($item['texto'], 0, 160, 'UTF-8'), ' .,;:') . '…' : $item['texto']) ?></td>
                                            <td class="num"><?= $item['total'] ?></td>
                                            <td class="num"><?= number_format($item['total'] * 100 / $total_consultas, 1, ',', '.') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="vacio">Ninguna consulta del período tiene diagnóstico registrado.</p>
                        <?php endif; ?>
                        <p class="nota">
                            Agrupación aproximada por el texto del diagnóstico (sin distinguir mayúsculas ni espacios); no usa codificación CIE-10.
                            <?php if ($sin_diagnostico > 0): ?>
                                Consultas sin diagnóstico registrado: <?= $sin_diagnostico ?>.
                            <?php endif; ?>
                        </p>
                    </section>
                </div>
            <?php endif; ?>

            <div class="firmas morbilidad-firmas">
                <div class="firma">
                    <div class="linea"></div>
                    <span>FIRMA DEL MÉDICO / COORDINADOR DE LA JORNADA</span>
                </div>
            </div>
        </div>
<?php
print_page_end('Generado el ' . (new DateTimeImmutable('now'))->format('d/m/Y \a \l\a\s H:i') . '.');
