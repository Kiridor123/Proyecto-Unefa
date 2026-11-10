<?php
declare(strict_types=1);

define('API_HTML_RESPONSE', true);
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/print_common.php';
require_once __DIR__ . '/../config/RegistroInput.php';

use Config\RegistroInput;

const REPOSO_DIAGNOSTICO_MAX_CARACTERES = 600;
const REPOSO_DIAGNOSTICO_MAX_LINEAS = 8;

api_method('GET');
api_require('clinica.ver');

$consulta_id = api_id($_GET['consulta_id'] ?? null, 'consulta_id');

$db = api_db();
$stmt = $db->prepare("
    SELECT c.id, c.paciente_id, c.fecha_registro, c.fecha_circunstancia, c.resumen, c.diagnostico,
           c.fecha_inicio_reposo, c.fecha_fin_reposo,
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

$emision = print_fecha_emision($consulta);
$medico = print_medico($consulta);
$diagnostico = print_first($consulta['diagnostico'], $consulta['resumen']);
// El reposo debe caber en una hoja (PR-14): el diagnóstico se recorta por caracteres y por
// líneas, y el texto largo usa una letra más compacta. El texto completo está en el informe.
$diagnostico_texto = trim(str_replace(["\r\n", "\r"], "\n", (string) ($diagnostico ?? '')));
$diagnostico_recortado = false;
$lineas = explode("\n", $diagnostico_texto);
if (count($lineas) > REPOSO_DIAGNOSTICO_MAX_LINEAS) {
    $diagnostico_texto = rtrim(implode("\n", array_slice($lineas, 0, REPOSO_DIAGNOSTICO_MAX_LINEAS)));
    $diagnostico_recortado = true;
}
if (mb_strlen($diagnostico_texto, 'UTF-8') > REPOSO_DIAGNOSTICO_MAX_CARACTERES) {
    $diagnostico_texto = rtrim(mb_substr($diagnostico_texto, 0, REPOSO_DIAGNOSTICO_MAX_CARACTERES, 'UTF-8'));
    $diagnostico_recortado = true;
}
if ($diagnostico_recortado) $diagnostico_texto .= '…';
$diagnostico_compacto = $diagnostico_recortado || mb_strlen($diagnostico_texto, 'UTF-8') > 350;
$texto_dias = print_numero_letras($dias_reposo) . ' (' . $dias_reposo . ') ' . ($dias_reposo === 1 ? 'día' : 'días');

api_audit('imprimir_reposo', 'consulta', (int) $consulta['id']);

print_page_start('Reposo médico - ' . $consulta['cedula'], [
    'boton' => 'Imprimir reposo',
    'margen' => '2cm 2.4cm',
    'css' => '
        .reposo-cuerpo { font-size: 11.5pt; line-height: 1.6; text-align: justify; }
        .reposo-cuerpo p { margin: 0 0 12px; }
        .datos-paciente { border: 1px solid #555; padding: 8px 12px; margin: 0 0 12px; }
        .datos-paciente div + div { margin-top: 3px; }
        .diagnostico { border: 1px solid #555; padding: 8px 12px; min-height: 70px; }
        .diagnostico.compacto { font-size: 10pt; line-height: 1.35; text-align: left; }
        .diagnostico-nota { font-size: 9pt; color: #444; margin: 4px 0 0 !important; text-align: left; }
        .reposo-cierre { break-inside: avoid; page-break-inside: avoid; }
        .expedicion { text-align: right; margin-top: 16px !important; }
        .reposo-firmas { margin-top: 32px; }
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

        <h1 class="doc-titulo">Reposo médico</h1>

        <div class="reposo-cuerpo">
            <p>
                Quien suscribe, médico del Consultorio Médico de la <?= print_e(PRINT_INSTITUCION) ?> (UNEFA),
                Núcleo Portuguesa, Extensión Acarigua, hace constar que el/la ciudadano/a:
            </p>

            <div class="datos-paciente">
                <div><span class="etq">Nombres y apellidos:</span> <?= print_e($consulta['nombres'] . ' ' . $consulta['apellidos']) ?></div>
                <div><span class="etq">Cédula de identidad:</span> <?= print_e($consulta['cedula']) ?></div>
                <div><span class="etq">Categoría:</span> <?= print_val($consulta['categoria']) ?></div>
            </div>

            <p>
                fue evaluado/a en consulta el <span class="fuerte"><?= print_fecha_corta($consulta['fecha_circunstancia']) ?></span>
                y presenta una condición clínica que amerita reposo médico por un lapso de
                <span class="fuerte"><?= print_e($texto_dias) ?></span><?= $dias_reposo === 1 ? '' : ' consecutivos' ?>,
                <?php if ($dias_reposo === 1): ?>
                    el día <span class="fuerte"><?= print_fecha_corta($inicio) ?></span>.
                <?php else: ?>
                    desde el <span class="fuerte"><?= print_fecha_corta($inicio) ?></span>
                    hasta el <span class="fuerte"><?= print_fecha_corta($fin) ?></span>, ambos inclusive.
                <?php endif; ?>
            </p>

            <p class="etq" style="margin-bottom: 4px;">Diagnóstico / resumen clínico:</p>
            <div class="diagnostico<?= $diagnostico_compacto ? ' compacto' : '' ?>"><?= print_text($diagnostico_texto, 'No registrado') ?></div>
            <?php if ($diagnostico_recortado): ?>
                <p class="diagnostico-nota">Texto abreviado para esta constancia. El diagnóstico completo consta en el informe médico de la consulta.</p>
            <?php endif; ?>
        </div>

        <!-- La línea de expedición y las firmas nunca se separan en hojas distintas. -->
        <div class="reposo-cierre">
        <div class="reposo-cuerpo">
            <p class="expedicion">
                Se expide la presente constancia a petición de la parte interesada en Acarigua, a <?= print_e(print_fecha_larga($emision)) ?>.
            </p>
        </div>

        <div class="firmas reposo-firmas">
            <div class="firma">
                <div class="linea"></div>
                <span>FIRMA Y SELLO DEL MÉDICO TRATANTE</span>
                <?php if ($medico !== null): ?>
                    <span class="detalle">Médico tratante: <?= print_e($medico) ?></span>
                <?php else: ?>
                    <span class="detalle">Médico tratante: ______________________</span>
                <?php endif; ?>
            </div>
            <div class="firma">
                <div class="linea"></div>
                <span>FIRMA DE DIRECTOR/A</span>
            </div>
        </div>
        </div>
<?php
print_page_end(print_reimpresion($emision));
