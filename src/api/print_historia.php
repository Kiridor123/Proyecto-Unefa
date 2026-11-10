<?php
declare(strict_types=1);

define('API_HTML_RESPONSE', true);
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/print_common.php';
api_method('GET');
api_require('clinica.ver');

$paciente_id = api_id($_GET['paciente_id'] ?? null, 'paciente_id');
// Consulta opcional elegida desde la interfaz; si no se indica, se usa la de ingreso (la más antigua).
$consulta_id = (isset($_GET['consulta_id']) && $_GET['consulta_id'] !== '') ? api_id($_GET['consulta_id'], 'consulta_id') : null;

$db = api_db();

$stmtPaciente = $db->prepare("
    SELECT p.*, cat.nombre AS categoria
    FROM pacientes p
    JOIN categorias_institucionales cat ON p.categoria_id = cat.id
    WHERE p.id = ?
");
$stmtPaciente->execute([$paciente_id]);
$paciente = $stmtPaciente->fetch();

if (!$paciente) {
    throw new ApiException('El paciente no existe.', 404);
}

$selectConsulta = "
    SELECT c.*, u.nombre_completo AS medico_nombre, u.registro_profesional AS medico_registro
    FROM consultas c
    LEFT JOIN usuarios u ON u.id = c.medico_id
";
if ($consulta_id !== null) {
    $stmtConsulta = $db->prepare($selectConsulta . ' WHERE c.id = ? AND c.paciente_id = ?');
    $stmtConsulta->execute([$consulta_id, $paciente_id]);
    $consulta = $stmtConsulta->fetch();
    if (!$consulta) {
        throw new ApiException('La consulta indicada no existe o no pertenece a este paciente.', 404);
    }
} else {
    $stmtConsulta = $db->prepare($selectConsulta . ' WHERE c.paciente_id = ? ORDER BY c.fecha_circunstancia ASC, c.id ASC LIMIT 1');
    $stmtConsulta->execute([$paciente_id]);
    $consulta = $stmtConsulta->fetch() ?: [];
}

$hay_consulta = $consulta !== [];
$fecha_evaluacion = $hay_consulta ? print_date($consulta['fecha_circunstancia']) : null;
// Edad a la fecha de la evaluación (o a hoy si aún no hay evaluación).
$edad = print_edad($paciente['fecha_nacimiento'], $fecha_evaluacion);
$emision = $hay_consulta ? print_fecha_emision($consulta) : new DateTimeImmutable('today');
$medico = $hay_consulta ? print_medico($consulta) : null;
$c = static fn (string $campo) => $consulta[$campo] ?? null;

$es_masculino = ($paciente['sexo'] ?? '') === 'Masculino';
$compromiso = print_first($paciente['compromiso_tatuajes'])
    ?? 'Yo me comprometo con la institución a no realizarme tatuajes durante el desarrollo de mi carrera.';

$spo2 = print_blank($c('vital_spo2')) ? null : rtrim(trim((string) $c('vital_spo2')), '%') . '%';

api_audit('imprimir_historia', 'paciente', (int) $paciente['id'], $hay_consulta ? ['consulta_id' => (int) $consulta['id']] : []);

print_page_start('Historia clínica - ' . $paciente['cedula'], [
    'boton' => 'Imprimir historia clínica',
    'margen' => '1.27cm',
    'css' => '
        .historia { font-size: 9pt; line-height: 1.35; }
        .historia .tabla td, .historia .tabla th { padding: 3px 5px; }
        .historia .tabla th { font-size: 8pt; text-align: center; }
        .doble { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px; }
        .doble .seccion { margin-bottom: 0; display: flex; flex-direction: column; }
        .doble .seccion .tabla { flex: 1; }
        .no-aplica { text-align: center; padding: 10px 6px !important; }
        .compromiso { font-size: 8pt; margin-top: 4px; }
        .firma-compromiso { margin: 18px auto 0; width: 70%; border-top: 1px solid #111; text-align: center; font-size: 7.5pt; font-weight: 700; padding-top: 2px; }
        .campo { margin-bottom: 4px; }
        .campo-linea { display: flex; gap: 4px; }
        .campo-linea > .etq { flex: 0 0 auto; }
        .campo .texto { padding-left: 8px; border-left: 2px solid #999; margin-top: 1px; }
        .fisico { display: grid; grid-template-columns: 1fr 1fr; gap: 2px 18px; }
        .fisico .ancho { grid-column: 1 / -1; }
        .compromiso-institucional { font-size: 8pt; text-align: justify; margin: 12px 0 0; }
        .lugar-fecha { text-align: right; margin: 8px 0 0; }
        .historia-firmas { margin-top: 30px; }
        .historia-firmas .firma .linea { height: 38px; }
    ',
]);
?>
        <?php print_header([
            'República Bolivariana de Venezuela',
            'Ministerio del Poder Popular para la Defensa',
            PRINT_INSTITUCION,
            'Vicerrectorado Académico',
            'Dirección Académica',
            'Consultorio Médico',
        ], print_logo('historia')); ?>

        <h1 class="doc-titulo" style="font-size: 12pt;">Historia clínica de ingreso — Semestre I</h1>

        <div class="historia">
            <section class="seccion">
                <div class="seccion-titulo">1. Datos personales</div>
                <table class="tabla">
                    <tr>
                        <td style="width: 58%;"><span class="etq">Nombres y apellidos:</span> <span class="fuerte"><?= print_e($paciente['nombres'] . ' ' . $paciente['apellidos']) ?></span></td>
                        <td><span class="etq">Teléfono:</span> <?= print_val($paciente['telefono']) ?></td>
                    </tr>
                    <tr>
                        <td rowspan="2"><span class="etq">Dirección:</span> <?= print_text($paciente['direccion'], 'No registrada') ?></td>
                        <td><span class="etq">Edad:</span> <?= $edad === null ? 'No registrada' : $edad . ' años' ?></td>
                    </tr>
                    <tr>
                        <td><span class="etq">Nivel educativo:</span> <?= print_val($paciente['nivel_educativo']) ?></td>
                    </tr>
                    <tr>
                        <td><span class="etq">Sexo:</span> <?= print_val($paciente['sexo']) ?></td>
                        <td><span class="etq">C.I.:</span> <?= print_e($paciente['cedula']) ?></td>
                    </tr>
                    <tr>
                        <td><span class="etq">Lugar de nacimiento:</span> <?= print_val($paciente['lugar_nacimiento']) ?></td>
                        <td><span class="etq">Fecha de nacimiento:</span> <?= print_fecha_corta($paciente['fecha_nacimiento']) ?></td>
                    </tr>
                    <tr>
                        <td><span class="etq">Carrera:</span> <?= print_val($paciente['carrera']) ?></td>
                        <td><span class="etq">Semestre:</span> <?= print_val($paciente['semestre']) ?></td>
                    </tr>
                </table>
            </section>

            <section class="seccion">
                <div class="seccion-titulo">2. Antecedentes personales patológicos</div>
                <table class="tabla">
                    <thead>
                        <tr>
                            <th scope="col">Cardiovascular</th>
                            <th scope="col">Óseo</th>
                            <th scope="col">Respiratorio</th>
                            <th scope="col">Digestivo</th>
                            <th scope="col">Endocrino / metabólico</th>
                            <th scope="col">Otros</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <?php foreach (['antecedente_cardiovascular', 'antecedente_oseo', 'antecedente_respiratorio', 'antecedente_digestivo', 'antecedente_endocrino', 'antecedente_otros'] as $campo): ?>
                                <td style="height: 34px;"><?= print_text($paciente[$campo]) ?></td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section class="seccion">
                <div class="seccion-titulo">3. Antecedentes quirúrgicos y traumatológicos</div>
                <table class="tabla">
                    <tr>
                        <td><span class="etq">Intervención / procedimiento:</span> <?= print_text($paciente['antecedente_quirurgico']) ?></td>
                    </tr>
                </table>
            </section>

            <div class="doble">
                <section class="seccion">
                    <div class="seccion-titulo">4. Antecedentes ginecobstétricos</div>
                    <table class="tabla">
                        <?php if ($es_masculino): ?>
                            <tr><td class="no-aplica vacio">No aplica.</td></tr>
                        <?php else: ?>
                            <thead>
                                <tr>
                                    <th scope="col">Menarquia</th>
                                    <th scope="col">Sexarquia</th>
                                    <th scope="col">ACO</th>
                                    <th scope="col">Gestas</th>
                                    <th scope="col">Citología</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="centro">
                                    <?php foreach (['gineco_menarquia', 'gineco_sexarquia', 'gineco_aco', 'gineco_gestas', 'gineco_citologia'] as $campo): ?>
                                        <td class="centro"><?= print_val($paciente[$campo], '—') ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            </tbody>
                        <?php endif; ?>
                    </table>
                </section>
                <section class="seccion">
                    <div class="seccion-titulo">5. Tatuajes</div>
                    <table class="tabla">
                        <tr>
                            <td>
                                <div><span class="etq">¿Tiene tatuajes?:</span> <span class="fuerte"><?= print_val($paciente['tiene_tatuajes']) ?></span></div>
                                <div class="compromiso"><span class="etq">Compromiso:</span> <?= print_e($compromiso) ?></div>
                                <div class="firma-compromiso">Firma del estudiante</div>
                            </td>
                        </tr>
                    </table>
                </section>
            </div>

            <section class="seccion">
                <div class="seccion-titulo">6. Antecedentes familiares patológicos</div>
                <table class="tabla">
                    <tr>
                        <td style="width: 50%;"><span class="etq">Padre:</span> <?= print_text($paciente['antecedente_padre']) ?></td>
                        <td><span class="etq">Madre:</span> <?= print_text($paciente['antecedente_madre']) ?></td>
                    </tr>
                    <tr>
                        <td><span class="etq">Hermanos:</span> <?= print_text($paciente['antecedente_hermanos']) ?></td>
                        <td><span class="etq">Hijos:</span> <?= print_text($paciente['antecedente_hijos']) ?></td>
                    </tr>
                </table>
            </section>

            <section class="seccion">
                <div class="seccion-titulo">7. Evaluación clínica y diagnóstico</div>
                <table class="tabla">
                    <tr>
                        <td>
                            <div class="campo"><span class="etq">Fecha de evaluación:</span>
                                <?= $fecha_evaluacion ? print_fecha_corta($fecha_evaluacion) : '<span class="vacio">Sin evaluación registrada</span>' ?>
                            </div>
                            <div class="campo">
                                <div class="etq">Motivo de consulta (MC):</div>
                                <div class="texto"><?= print_text($c('motivo_consulta')) ?></div>
                            </div>
                            <div class="campo">
                                <div class="etq">Historia de la enfermedad actual (IEA):</div>
                                <div class="texto"><?= print_text(print_first($c('enfermedad_actual'), $c('resumen')), 'No registrada') ?></div>
                            </div>
                            <div class="campo">
                                <div class="etq">Diagnóstico (DX):</div>
                                <div class="texto fuerte"><?= print_text($c('diagnostico')) ?></div>
                            </div>
                        </td>
                    </tr>
                </table>
            </section>

            <section class="seccion">
                <div class="seccion-titulo">8. Examen físico</div>
                <table class="tabla">
                    <thead>
                        <tr>
                            <th scope="col">TA</th>
                            <th scope="col">FC</th>
                            <th scope="col">FR</th>
                            <th scope="col">SpO2</th>
                            <th scope="col">Peso / talla</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="centro fuerte"><?= print_val($c('vital_ta'), '—') ?></td>
                            <td class="centro fuerte"><?= print_val($c('vital_fc'), '—') ?></td>
                            <td class="centro fuerte"><?= print_val($c('vital_fr'), '—') ?></td>
                            <td class="centro fuerte"><?= print_val($spo2, '—') ?></td>
                            <td class="centro fuerte"><?= print_val($c('vital_peso_talla'), '—') ?></td>
                        </tr>
                        <tr>
                            <td colspan="5">
                                <div class="fisico">
                                    <div><span class="etq">Piel y mucosa:</span> <?= print_text($c('fisico_piel')) ?></div>
                                    <div><span class="etq">Cabeza:</span> <?= print_text($c('fisico_cabeza')) ?></div>
                                    <div><span class="etq">Cuello:</span> <?= print_text($c('fisico_cuello')) ?></div>
                                    <div><span class="etq">Tórax:</span> <?= print_text($c('fisico_torax')) ?></div>
                                    <div><span class="etq">Abdomen:</span> <?= print_text($c('fisico_abdomen')) ?></div>
                                    <div><span class="etq">Extremidades:</span> <?= print_text($c('fisico_extremidades')) ?></div>
                                    <div class="ancho"><span class="etq">Neurológico:</span> <?= print_text($c('fisico_neurologico')) ?></div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="5">
                                <div class="campo campo-linea"><span class="etq">Laboratorios:</span> <div><?= print_text($c('laboratorios'), 'Ninguno') ?></div></div>
                                <div class="campo campo-linea"><span class="etq">Plan:</span> <div><?= print_text($c('plan_tratamiento'), 'Ninguno') ?></div></div>
                                <div class="campo campo-linea" style="margin-bottom: 0;"><span class="etq">Pendiente:</span> <div><?= print_text($c('pendiente'), 'Ninguno') ?></div></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <p class="compromiso-institucional">
                <strong>COMPROMISO INSTITUCIONAL:</strong> al firmar esta historia médica de ingreso, el estudiante asume el compromiso de cumplir
                con las normativas internas del consultorio y declara de forma verídica todas sus condiciones de salud.
            </p>
            <p class="lugar-fecha">Acarigua, <?= print_e(print_fecha_larga($emision)) ?>.</p>

            <div class="firmas historia-firmas">
                <div class="firma">
                    <div class="linea"></div>
                    <span>FIRMA DEL ESTUDIANTE</span>
                </div>
                <div class="firma">
                    <div class="linea"></div>
                    <span>SELLO MÉDICO</span>
                </div>
                <div class="firma">
                    <div class="linea"></div>
                    <span>MÉDICO EVALUADOR</span>
                    <?php if ($medico !== null): ?>
                        <span class="detalle"><?= print_e($medico) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
<?php
print_page_end(print_reimpresion($emision));
