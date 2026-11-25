<?php
declare(strict_types=1);

define('API_HTML_RESPONSE', true);
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/print_common.php';
require_once __DIR__ . '/../config/RegistroInput.php';

use Config\RegistroInput;

const REPOSO_DIAGNOSTICO_MAX_CARACTERES = 500;
const REPOSO_DIAGNOSTICO_MAX_LINEAS = 7;
const REPOSO_TRATAMIENTO_MAX_CARACTERES = 700;
const REPOSO_TRATAMIENTO_MAX_LINEAS = 9;

api_method('GET');
api_require('clinica.ver');

$consulta_id = api_id($_GET['consulta_id'] ?? null, 'consulta_id');

$db = api_db();
$stmt = $db->prepare("
    SELECT c.id, c.paciente_id, c.fecha_registro, c.fecha_circunstancia, c.resumen, c.diagnostico,
           c.plan_tratamiento, c.fecha_inicio_reposo, c.fecha_fin_reposo,
           p.cedula, p.nombres, p.apellidos, p.fecha_nacimiento, cat.nombre AS categoria,
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

$inicio = print_date($consulta['fecha_inicio_reposo']);
$fin = print_date($consulta['fecha_fin_reposo']);
if (!$inicio || !$fin) {
    throw new ApiException('Esta consulta no tiene un reposo registrado.', 422);
}
if ($fin < $inicio) {
    throw new ApiException('Revise las fechas de este reposo antes de imprimirlo: la fecha final es anterior a la inicial.', 422);
}
$dias_reposo = RegistroInput::restDays($inicio, $fin); // Ambos días incluidos, mismo criterio que al guardar.
if ($dias_reposo > RegistroInput::REPOSO_DURACION_MAXIMA) {
    throw new ApiException('Este reposo supera la duración máxima permitida (' . RegistroInput::REPOSO_DURACION_MAXIMA . ' días). Corrija la consulta antes de imprimirlo.', 422);
}
// Coherencia con la fecha de la consulta (mismas reglas que al registrar), para datos antiguos.
$fechaConsulta = print_date($consulta['fecha_circunstancia']);
if ($fechaConsulta) {
    $minimo = $fechaConsulta->modify('-' . RegistroInput::REPOSO_DIAS_ANTES . ' days');
    $maximo = $fechaConsulta->modify('+' . RegistroInput::REPOSO_DIAS_DESPUES . ' days');
    if ($inicio < $minimo || $inicio > $maximo) {
        throw new ApiException('La fecha de inicio del reposo no es coherente con la fecha de la consulta (' . $fechaConsulta->format('d/m/Y') . '). Corrija la consulta antes de imprimirlo.', 422);
    }
}

$edad = print_edad($consulta['fecha_nacimiento'], $consulta['fecha_circunstancia']);
$emision = print_fecha_emision($consulta);
$medico = print_medico($consulta);

// El reposo debe caber en una hoja (PR-14): textos extensos se acotan por caracteres y líneas.
$acotarTexto = static function (?string $texto, int $maxCaracteres, int $maxLineas): array {
    $limpio = trim(str_replace(["\r\n", "\r"], "\n", (string) ($texto ?? '')));
    $recortado = false;
    $lineas = explode("\n", $limpio);
    if (count($lineas) > $maxLineas) {
        $limpio = rtrim(implode("\n", array_slice($lineas, 0, $maxLineas)));
        $recortado = true;
    }
    if (mb_strlen($limpio, 'UTF-8') > $maxCaracteres) {
        $limpio = rtrim(mb_substr($limpio, 0, $maxCaracteres, 'UTF-8'));
        $recortado = true;
    }
    if ($recortado) $limpio .= '…';
    $compacto = $recortado || mb_strlen($limpio, 'UTF-8') > 300;
    return [$limpio, $recortado, $compacto];
};

$diagnostico = print_first($consulta['diagnostico'], $consulta['resumen']);
[$diagnostico_texto, $diagnostico_recortado, $diagnostico_compacto] = $acotarTexto(
    $diagnostico,
    REPOSO_DIAGNOSTICO_MAX_CARACTERES,
    REPOSO_DIAGNOSTICO_MAX_LINEAS
);
[$tratamiento_texto, $tratamiento_recortado, $tratamiento_compacto] = $acotarTexto(
    print_first($consulta['plan_tratamiento']),
    REPOSO_TRATAMIENTO_MAX_CARACTERES,
    REPOSO_TRATAMIENTO_MAX_LINEAS
);

$texto_dias = print_numero_letras($dias_reposo) . ' (' . $dias_reposo . ') ' . ($dias_reposo === 1 ? 'día' : 'días');
$inicio_corto = print_fecha_corta($inicio);
$fin_corto = print_fecha_corta($fin);

api_audit('imprimir_reposo', 'consulta', (int) $consulta['id']);

print_page_start('Reposo médico - ' . $consulta['cedula'], [
    'boton' => 'Imprimir reposo',
    'margen' => '1.5cm 2cm',
    'css' => '
        .reposo-cuerpo { font-size: 10.5pt; line-height: 1.5; text-align: justify; }
        .reposo-cuerpo p { margin: 0 0 10px; }
        .datos-paciente {
            display: grid; grid-template-columns: 1.35fr 1fr; gap: 4px 18px;
            border: 1px solid #475569; border-left: 3.5px solid #0f2c59;
            padding: 8px 12px; margin: 0 0 10px; background: #fafbfd; text-align: left;
            -webkit-print-color-adjust: exact; print-color-adjust: exact;
        }
        .reposo-periodo {
            width: 100%; border-collapse: collapse; table-layout: fixed; margin: 0 0 12px;
            break-inside: avoid; page-break-inside: avoid;
        }
        .reposo-periodo th, .reposo-periodo td { border: 1px solid #475569; padding: 5px 8px; text-align: center; }
        .reposo-periodo th {
            font-size: 8pt; text-transform: uppercase; letter-spacing: .04em;
            background: #edf2f8; color: #0f2c59;
            -webkit-print-color-adjust: exact; print-color-adjust: exact;
        }
        .reposo-periodo td { font-size: 10.5pt; font-weight: 700; color: #0f2c59; }
        .reposo-bloque { margin-bottom: 10px; }
        .reposo-bloque-titulo {
            font-size: 9pt; font-weight: 700; text-transform: uppercase; letter-spacing: .03em;
            color: #0f2c59; background: #edf2f8; border: 1px solid #475569; border-bottom: 0;
            padding: 3px 8px; text-align: left; break-after: avoid; page-break-after: avoid;
            -webkit-print-color-adjust: exact; print-color-adjust: exact;
        }
        .reposo-cuadro {
            border: 1px solid #475569; padding: 7px 10px; min-height: 54px; text-align: left;
        }
        .reposo-cuadro.compacto { font-size: 9.5pt; line-height: 1.35; }
        .reposo-nota { font-size: 8.5pt; color: #475569; margin: 3px 0 0 !important; text-align: left; }
        .reposo-cierre { break-inside: avoid; page-break-inside: avoid; }
        .expedicion { text-align: right; margin-top: 12px !important; font-size: 10pt; }
        .reposo-firmas { margin-top: 16px; }
    ',
]);
?>
        <?php print_header_institucional(); ?>

        <h1 class="doc-titulo">Reposo médico</h1>

        <div class="reposo-cuerpo">
            <p>
                Quien suscribe, médico del Consultorio Médico de la <?= print_e(PRINT_INSTITUCION) ?> (UNEFA),
                Núcleo Portuguesa, Extensión Acarigua, hace constar que el/la ciudadano/a:
            </p>

            <div class="datos-paciente">
                <div><span class="etq">Nombre y apellido:</span> <span class="fuerte"><?= print_e($consulta['nombres'] . ' ' . $consulta['apellidos']) ?></span></div>
                <div><span class="etq">Cédula de identidad:</span> <span class="fuerte"><?= print_e($consulta['cedula']) ?></span></div>
                <div><span class="etq">Edad:</span> <?= $edad === null ? 'No registrada' : $edad . ' años' ?></div>
                <div><span class="etq">Categoría:</span> <?= print_val($consulta['categoria']) ?></div>
            </div>

            <p>
                fue evaluado/a en consulta el <span class="fuerte"><?= print_fecha_corta($consulta['fecha_circunstancia']) ?></span>
                y presenta una condición clínica que amerita reposo médico por un lapso de
                <span class="fuerte"><?= print_e($texto_dias) ?></span><?= $dias_reposo === 1 ? '' : ' consecutivos' ?>,
                desde el <span class="fuerte"><?= print_e($inicio_corto) ?></span>
                hasta el <span class="fuerte"><?= print_e($fin_corto) ?></span>, ambos inclusive.
            </p>

            <table class="reposo-periodo" aria-label="Período de reposo">
                <thead>
                    <tr>
                        <th scope="col">Días de reposo</th>
                        <th scope="col">Desde</th>
                        <th scope="col">Hasta</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?= print_e($texto_dias) ?></td>
                        <td><?= print_e($inicio_corto) ?></td>
                        <td><?= print_e($fin_corto) ?></td>
                    </tr>
                </tbody>
            </table>

            <div class="reposo-bloque">
                <div class="reposo-bloque-titulo">Diagnóstico</div>
                <div class="reposo-cuadro<?= $diagnostico_compacto ? ' compacto' : '' ?>"><?= print_text($diagnostico_texto, 'No registrado') ?></div>
                <?php if ($diagnostico_recortado): ?>
                    <p class="reposo-nota">Texto abreviado para esta constancia. El diagnóstico completo consta en el informe médico de la consulta.</p>
                <?php endif; ?>
            </div>

            <div class="reposo-bloque">
                <div class="reposo-bloque-titulo">Tratamiento</div>
                <div class="reposo-cuadro<?= $tratamiento_compacto ? ' compacto' : '' ?>"><?= print_text($tratamiento_texto, 'No registrado') ?></div>
                <?php if ($tratamiento_recortado): ?>
                    <p class="reposo-nota">Texto abreviado para esta constancia. El tratamiento completo consta en el récipe e informe médico de la consulta.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- La línea de expedición y los recuadros de firma nunca se separan en hojas distintas. -->
        <div class="reposo-cierre">
            <div class="reposo-cuerpo">
                <p class="expedicion">
                    Se expide la presente constancia a petición de la parte interesada en Acarigua, a <?= print_e(print_fecha_larga($emision)) ?>.
                </p>
            </div>

            <?php print_firmas_recuadros($medico, true, 'reposo-firmas'); ?>
        </div>
<?php
print_page_end(print_reimpresion($emision));
