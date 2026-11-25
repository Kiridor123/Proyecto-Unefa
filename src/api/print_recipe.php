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
    SELECT c.id, c.paciente_id, c.fecha_registro, c.fecha_circunstancia,
           c.diagnostico, c.plan_tratamiento,
           p.cedula, p.nombres, p.apellidos, p.fecha_nacimiento, p.sexo,
           cat.nombre AS categoria,
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

api_audit('imprimir_recipe', 'consulta', (int) $consulta['id']);

print_page_start('Récipe médico - ' . $consulta['cedula'], [
    'boton' => 'Imprimir récipe',
    'margen' => '1.6cm 2cm',
    'css' => '
        .recipe { font-size: 10.5pt; line-height: 1.5; }
        .recipe-meta {
            display: flex; justify-content: space-between; align-items: baseline; flex-wrap: wrap; gap: 8px;
            font-size: 9.5pt; color: #334155; margin: -4px 0 12px;
        }
        .datos-paciente {
            display: grid; grid-template-columns: 1.35fr 1fr; gap: 5px 18px;
            border: 1px solid #475569; border-left: 3.5px solid #0f2c59;
            padding: 9px 12px; margin: 0 0 12px; background: #fafbfd;
            -webkit-print-color-adjust: exact; print-color-adjust: exact;
        }
        .recipe-dx {
            border: 1px solid #cbd5e1; background: #f8fafc; padding: 7px 12px; margin: 0 0 14px; font-size: 10pt;
            -webkit-print-color-adjust: exact; print-color-adjust: exact;
        }
        .recipe-prescripcion {
            border: 1.25px solid #163a70; border-radius: 3px; overflow: hidden; margin-bottom: 16px;
        }
        .recipe-prescripcion-encabezado {
            display: flex; align-items: center; gap: 10px;
            background: #edf2f8; border-bottom: 1px solid #cbd5e1; padding: 7px 12px;
            -webkit-print-color-adjust: exact; print-color-adjust: exact;
        }
        .rx-simbolo {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 34px; height: 28px; padding: 0 8px; border-radius: 3px;
            background: #0f2c59; color: #fff; border-bottom: 2px solid #c99700;
            font-family: Georgia, "Times New Roman", serif; font-size: 13pt; font-weight: 700;
            font-style: italic; letter-spacing: .02em; line-height: 1;
            -webkit-print-color-adjust: exact; print-color-adjust: exact;
        }
        .recipe-prescripcion-titulo {
            font-size: 9.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #0f2c59;
        }
        .recipe-cuerpo {
            min-height: 230px; padding: 14px 16px; font-size: 11pt; line-height: 1.7; text-align: left;
        }
        .recipe-cierre { break-inside: avoid; page-break-inside: avoid; }
        .recipe-emision { text-align: right; font-size: 9.5pt; color: #334155; margin: 0 0 16px; }
        .recipe-firmas { margin-top: 8px; }
    ',
]);
?>
        <?php print_header_institucional(); ?>

        <h1 class="doc-titulo">Récipe médico</h1>

        <div class="recipe">
            <div class="recipe-meta">
                <div><span class="etq">Consultorio Médico</span> — Extensión Acarigua</div>
                <div><span class="etq">Fecha:</span> <span class="fuerte"><?= print_fecha_corta($consulta['fecha_circunstancia']) ?></span></div>
            </div>

            <div class="datos-paciente">
                <div><span class="etq">Nombre y apellido:</span> <span class="fuerte"><?= print_e($consulta['nombres'] . ' ' . $consulta['apellidos']) ?></span></div>
                <div><span class="etq">Cédula de identidad:</span> <span class="fuerte"><?= print_e($consulta['cedula']) ?></span></div>
                <div><span class="etq">Edad:</span> <?= $edad === null ? 'No registrada' : $edad . ' años' ?></div>
                <div><span class="etq">Categoría:</span> <?= print_val($consulta['categoria']) ?></div>
            </div>

            <?php if (!print_blank($consulta['diagnostico'])): ?>
                <div class="recipe-dx">
                    <span class="etq">Diagnóstico:</span> <span class="fuerte"><?= print_text($consulta['diagnostico']) ?></span>
                </div>
            <?php endif; ?>

            <section class="recipe-prescripcion" aria-label="Prescripción e indicaciones médicas">
                <div class="recipe-prescripcion-encabezado">
                    <span class="rx-simbolo" aria-hidden="true">Rx</span>
                    <span class="recipe-prescripcion-titulo">Récipe / Tratamiento e indicaciones médicas</span>
                </div>
                <div class="recipe-cuerpo">
                    <?= print_text($consulta['plan_tratamiento'], 'Sin indicaciones registradas') ?>
                </div>
            </section>

            <div class="recipe-cierre">
                <p class="recipe-emision">
                    Acarigua, <?= print_e(print_fecha_larga($emision)) ?>.
                </p>

                <?php print_firmas_recuadros($medico, false, 'recipe-firmas'); ?>
            </div>
        </div>
<?php
print_page_end(print_reimpresion($emision));
