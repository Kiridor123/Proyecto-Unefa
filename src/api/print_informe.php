<?php
declare(strict_types=1);

define('API_HTML_RESPONSE', true);
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/print_common.php';
api_method('GET');
api_require('clinica.ver');

$consulta_id = api_id($_GET['consulta_id'] ?? null, 'consulta_id');

$db = api_db();
$stmt = $db->prepare("
    SELECT c.*, p.cedula, p.nombres, p.apellidos, p.fecha_nacimiento, p.sexo, p.telefono, p.direccion,
           p.carrera, p.semestre, p.nivel_educativo, cat.nombre AS categoria,
           u.nombre_completo AS medico_nombre, u.registro_profesional AS medico_registro
    FROM consultas c
    JOIN pacientes p ON c.paciente_id = p.id
    JOIN categorias_institucionales cat ON p.categoria_id = cat.id
    LEFT JOIN usuarios u ON u.id = c.medico_id
    WHERE c.id = ?
");
$stmt->execute([$consulta_id]);
$consulta = $stmt->fetch();

if (!$consulta) {
    throw new ApiException('La consulta no existe.', 404);
}

$edad = print_edad($consulta['fecha_nacimiento'], $consulta['fecha_circunstancia']);
$emision = print_fecha_emision($consulta);
$medico = print_medico($consulta);

// Carrera y semestre: el paréntesis solo aparece si hay semestre.
$carrera = print_first($consulta['carrera']);
$semestre = print_first($consulta['semestre']);
$carrera_semestre = $carrera === null ? null : $carrera . ($semestre !== null ? ' (' . $semestre . ')' : '');

$vitales = [
    'Tensión arterial (TA)' => $consulta['vital_ta'],
    'Frec. cardíaca (FC)' => $consulta['vital_fc'],
    'Frec. respiratoria (FR)' => $consulta['vital_fr'],
    'Saturación (SpO2)' => print_blank($consulta['vital_spo2']) ? null : rtrim(trim((string) $consulta['vital_spo2']), '%') . '%',
    'Peso / talla' => $consulta['vital_peso_talla'],
];
$fisicos = array_filter([
    'Piel y mucosa' => $consulta['fisico_piel'],
    'Cabeza' => $consulta['fisico_cabeza'],
    'Cuello' => $consulta['fisico_cuello'],
    'Tórax' => $consulta['fisico_torax'],
    'Abdomen' => $consulta['fisico_abdomen'],
    'Extremidades' => $consulta['fisico_extremidades'],
    'Neurológico' => $consulta['fisico_neurologico'],
], static fn ($valor) => !print_blank($valor));

api_audit('imprimir_informe', 'consulta', (int) $consulta['id']);

print_page_start('Informe médico - ' . $consulta['cedula'], [
    'boton' => 'Imprimir informe',
    'margen' => '1.4cm 1.8cm',
    'css' => '
        .informe { font-size: 10pt; }
        .informe h2 { font-size: 10pt; text-transform: uppercase; margin: 9px 0 5px; padding-bottom: 2px; border-bottom: 1px solid #555; break-after: avoid; page-break-after: avoid; }
        .rejilla { display: grid; grid-template-columns: 1fr 1fr; gap: 3px 20px; }
        .rejilla .ancho { grid-column: 1 / -1; }
        .bloque { margin-bottom: 6px; }
        .bloque .texto { padding-left: 8px; text-align: justify; }
        .vitales { width: 100%; border-collapse: collapse; table-layout: fixed; text-align: center; break-inside: avoid; page-break-inside: avoid; }
        .vitales th, .vitales td { border: 1px solid #555; padding: 3px 4px; }
        .vitales th { font-size: 8pt; text-transform: uppercase; background: #e5e7eb; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .vitales td { font-weight: 700; }
        .fecha-consulta { text-align: center; margin: -6px 0 6px; font-size: 10pt; }
        .emision { margin-top: 14px; text-align: right; font-size: 9pt; }
        .informe-firmas { margin-top: 30px; }
    ',
]);
?>
        <?php print_header([
            'República Bolivariana de Venezuela',
            'Ministerio del Poder Popular para la Defensa',
            PRINT_INSTITUCION,
            'Vicerrectorado Región Los Llanos',
            'Núcleo Portuguesa — Extensión Acarigua',
        ], print_logo('unefa'), print_logo('mppd')); ?>

        <h1 class="doc-titulo">Informe médico</h1>
        <p class="fecha-consulta"><span class="etq">Fecha de la consulta:</span> <?= print_fecha_corta($consulta['fecha_circunstancia']) ?></p>

        <div class="informe">
            <section>
                <h2>1. Identificación del paciente</h2>
                <div class="rejilla">
                    <div><span class="etq">Nombres y apellidos:</span> <?= print_e($consulta['nombres'] . ' ' . $consulta['apellidos']) ?></div>
                    <div><span class="etq">Cédula:</span> <?= print_e($consulta['cedula']) ?></div>
                    <div><span class="etq">Edad:</span> <?= $edad === null ? 'No registrada' : $edad . ' años' ?></div>
                    <div><span class="etq">Sexo:</span> <?= print_val($consulta['sexo']) ?></div>
                    <div><span class="etq">Categoría:</span> <?= print_val($consulta['categoria']) ?></div>
                    <div><span class="etq">Teléfono:</span> <?= print_val($consulta['telefono']) ?></div>
                    <?php if ($carrera_semestre !== null): ?>
                        <div class="ancho"><span class="etq">Carrera / semestre:</span> <?= print_e($carrera_semestre) ?></div>
                    <?php endif; ?>
                    <div class="ancho"><span class="etq">Dirección:</span> <?= print_text($consulta['direccion'], 'No registrada') ?></div>
                </div>
            </section>

            <section>
                <h2>2. Motivo de consulta y enfermedad actual</h2>
                <div class="bloque">
                    <div class="etq">Motivo de consulta:</div>
                    <div class="texto"><?= print_text($consulta['motivo_consulta']) ?></div>
                </div>
                <div class="bloque">
                    <div class="etq">Historia de la enfermedad actual (HEA):</div>
                    <div class="texto"><?= print_text(print_first($consulta['enfermedad_actual'], $consulta['resumen']), 'No registrada') ?></div>
                </div>
            </section>

            <section>
                <h2>3. Examen físico y signos vitales</h2>
                <table class="vitales">
                    <thead>
                        <tr>
                            <?php foreach (array_keys($vitales) as $nombre): ?>
                                <th scope="col"><?= print_e($nombre) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <?php foreach ($vitales as $valor): ?>
                                <td><?= print_val($valor, '—') ?></td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
                <?php if ($fisicos): ?>
                    <div class="rejilla" style="margin-top: 6px;">
                        <?php foreach ($fisicos as $nombre => $valor): ?>
                            <div><span class="etq"><?= print_e($nombre) ?>:</span> <?= print_text($valor) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="vacio" style="margin: 6px 0 0;">Examen físico por sistemas no registrado.</p>
                <?php endif; ?>
            </section>

            <section>
                <h2>4. Diagnóstico clínico e indicaciones</h2>
                <div class="bloque">
                    <div class="etq">Impresión diagnóstica (DX):</div>
                    <div class="texto fuerte"><?= print_text($consulta['diagnostico']) ?></div>
                </div>
                <div class="bloque">
                    <div class="etq">Plan de tratamiento / indicaciones médicas:</div>
                    <div class="texto"><?= print_text($consulta['plan_tratamiento']) ?></div>
                </div>
                <?php if (!print_blank($consulta['laboratorios'])): ?>
                    <div class="bloque">
                        <div class="etq">Laboratorios / exámenes solicitados:</div>
                        <div class="texto"><?= print_text($consulta['laboratorios']) ?></div>
                    </div>
                <?php endif; ?>
                <?php if (!print_blank($consulta['pendiente'])): ?>
                    <div class="bloque">
                        <div class="etq">Pendiente / próximos pasos:</div>
                        <div class="texto"><?= print_text($consulta['pendiente']) ?></div>
                    </div>
                <?php endif; ?>
                <?php if (!print_blank($consulta['fecha_inicio_reposo']) && !print_blank($consulta['fecha_fin_reposo'])): ?>
                    <div class="bloque">
                        <span class="etq">Reposo indicado:</span>
                        del <?= print_fecha_corta($consulta['fecha_inicio_reposo']) ?> al <?= print_fecha_corta($consulta['fecha_fin_reposo']) ?>.
                    </div>
                <?php endif; ?>
            </section>

            <p class="emision">
                Documento emitido en el Consultorio Médico UNEFA — Extensión Acarigua, el <?= print_e(print_fecha_larga($emision)) ?>.
            </p>
        </div>

        <div class="firmas informe-firmas">
            <div class="firma">
                <div class="linea"></div>
                <span>SELLO MÉDICO</span>
            </div>
            <div class="firma">
                <div class="linea"></div>
                <span>FIRMA DEL MÉDICO TRATANTE</span>
                <?php if ($medico !== null): ?>
                    <span class="detalle">Médico tratante: <?= print_e($medico) ?></span>
                <?php else: ?>
                    <span class="detalle">Médico tratante: ______________________</span>
                <?php endif; ?>
            </div>
        </div>
<?php
print_page_end(print_reimpresion($emision));
