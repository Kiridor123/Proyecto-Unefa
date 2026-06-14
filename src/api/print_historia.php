<?php
require_once '../config/Database.php';

$paciente_id = isset($_GET['paciente_id']) ? (int)$_GET['paciente_id'] : 0;

if ($paciente_id <= 0) {
    die("ID de paciente inválido.");
}

try {
    $db = Config\Database::getInstance()->getConnection();
    
    // 1. Obtener datos del paciente
    $stmtPaciente = $db->prepare("
        SELECT p.*, cat.nombre AS categoria
        FROM pacientes p
        JOIN categorias_institucionales cat ON p.categoria_id = cat.id
        WHERE p.id = ?
    ");
    $stmtPaciente->execute([$paciente_id]);
    $paciente = $stmtPaciente->fetch();
    
    if (!$paciente) {
        die("Paciente no encontrado.");
    }
    
    // Calcular edad
    $edad = 'No especificada';
    if ($paciente['fecha_nacimiento']) {
        $fecha_nac = new DateTime($paciente['fecha_nacimiento']);
        $hoy = new DateTime();
        $edad = $fecha_nac->diff($hoy)->y . ' años';
    }
    
    // 2. Obtener la última consulta del paciente para autocompletar la sección clínica
    $stmtConsulta = $db->prepare("
        SELECT *
        FROM consultas
        WHERE paciente_id = ?
        ORDER BY fecha_circunstancia DESC, id DESC
        LIMIT 1
    ");
    $stmtConsulta->execute([$paciente_id]);
    $consulta = $stmtConsulta->fetch() ?: [];

    // Formatear fechas en español
    $meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
        7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    
    $hoy = new DateTime();
    $dia_hoy = $hoy->format('d');
    $mes_hoy = $meses[(int)$hoy->format('m')];
    $anio_hoy = $hoy->format('Y');

} catch (Exception $e) {
    die("Error de base de datos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historia Clínica - <?php echo htmlspecialchars($paciente['cedula']); ?></title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body {
                background: white;
                color: black;
                padding: 0;
                margin: 0;
            }
            .no-print {
                display: none;
            }
            .print-area {
                border: none;
                box-shadow: none;
                margin: 0;
                padding: 1cm;
                width: 100%;
                min-height: auto;
            }
        }
        body {
            font-family: 'Arial', sans-serif;
        }
        table, th, td {
            border: 1px solid #94a3b8;
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen flex flex-col items-center p-4 sm:p-8">
    <!-- Floating Print Button -->
    <div class="no-print mb-6 flex gap-3">
        <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2.5 rounded-xl shadow-md transition flex items-center gap-2 text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Imprimir Historia Clínica
        </button>
        <button onclick="window.close()" class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold px-5 py-2.5 rounded-xl transition text-sm">
            Cerrar
        </button>
    </div>

    <!-- Paper Container -->
    <div class="print-area bg-white w-full max-w-[21cm] p-10 border border-slate-300 shadow-xl text-slate-800 text-[11px] leading-relaxed">
        
        <!-- Header -->
        <div class="flex justify-between items-center border-b border-slate-900 pb-3 mb-4">
            <img src="../uploads/logo_unefa_1.png" class="h-14 w-auto" alt="Logo MPPD">
            <div class="text-center font-bold text-[9px] uppercase tracking-tight leading-tight flex-1 px-4">
                República Bolivariana de Venezuela<br>
                Ministerio del Poder Popular para la Defensa<br>
                Universidad Nacional Experimental de la Fuerza Armada Nacional Bolivariana<br>
                Vicerrectorado Académico - Dirección Académica<br>
                Consultorio Médico - Acarigua
            </div>
            <img src="../uploads/logo_unefa_2.png" class="h-14 w-auto" alt="Logo UNEFA">
        </div>

        <!-- Title -->
        <div class="text-center my-3">
            <h1 class="text-sm font-bold tracking-wider uppercase underline underline-offset-4">Historia Clínica de Ingreso - Semestre I</h1>
        </div>

        <!-- Section 1: Datos Personales -->
        <div class="mb-4">
            <div class="bg-slate-100 px-3 py-1 font-bold text-[11px] uppercase border border-slate-400 border-b-0">1. Datos Personales</div>
            <table class="w-full text-left table-fixed border-collapse">
                <tr>
                    <td class="p-1.5 w-7/12"><span class="font-semibold text-slate-500">Nombres y Apellidos:</span> <span class="font-bold"><?php echo htmlspecialchars($paciente['nombres'] . ' ' . $paciente['apellidos']); ?></span></td>
                    <td class="p-1.5 w-5/12"><span class="font-semibold text-slate-500">Teléfono:</span> <?php echo htmlspecialchars($paciente['telefono'] ?: 'No registrado'); ?></td>
                </tr>
                <tr>
                    <td class="p-1.5" rowspan="2" valign="top"><span class="font-semibold text-slate-500">Dirección:</span> <?php echo htmlspecialchars($paciente['direccion'] ?: 'No registrada'); ?></td>
                    <td class="p-1.5"><span class="font-semibold text-slate-500">Edad:</span> <?php echo $edad; ?></td>
                </tr>
                <tr>
                    <td class="p-1.5"><span class="font-semibold text-slate-500">Nivel Educativo:</span> <?php echo htmlspecialchars($paciente['nivel_educativo'] ?: 'N/A'); ?></td>
                </tr>
                <tr>
                    <td class="p-1.5"><span class="font-semibold text-slate-500">Sexo:</span> <?php echo htmlspecialchars($paciente['sexo'] ?: 'No especificado'); ?></td>
                    <td class="p-1.5"><span class="font-semibold text-slate-500">C.I:</span> <?php echo htmlspecialchars($paciente['cedula']); ?></td>
                </tr>
                <tr>
                    <td class="p-1.5"><span class="font-semibold text-slate-500">Lugar de Nacimiento:</span> <?php echo htmlspecialchars($paciente['lugar_nacimiento'] ?: 'No registrado'); ?></td>
                    <td class="p-1.5"><span class="font-semibold text-slate-500">Fecha de Nacimiento:</span> <?php echo $paciente['fecha_nacimiento'] ? date('d/m/Y', strtotime($paciente['fecha_nacimiento'])) : 'No registrada'; ?></td>
                </tr>
                <tr>
                    <td class="p-1.5"><span class="font-semibold text-slate-500">Carrera:</span> <?php echo htmlspecialchars($paciente['carrera'] ?: 'N/A'); ?></td>
                    <td class="p-1.5"><span class="font-semibold text-slate-500">Semestre:</span> <?php echo htmlspecialchars($paciente['semestre'] ?: 'N/A'); ?></td>
                </tr>
            </table>
        </div>

        <!-- Section 2: Antecedentes Personales -->
        <div class="mb-4">
            <div class="bg-slate-100 px-3 py-1 font-bold text-[11px] uppercase border border-slate-400 border-b-0">2. Antecedentes Personales Patológicos</div>
            <table class="w-full text-left table-fixed border-collapse">
                <tr class="text-center font-semibold bg-slate-50 text-[10px]">
                    <td class="p-1">Cardiovascular</td>
                    <td class="p-1">Óseo</td>
                    <td class="p-1">Respiratorio</td>
                    <td class="p-1">Digestivo</td>
                    <td class="p-1">Endocrino / Metab.</td>
                    <td class="p-1">Otros</td>
                </tr>
                <tr class="text-slate-700 italic text-[10px]">
                    <td class="p-1.5 h-12" valign="top"><?php echo htmlspecialchars($paciente['antecedente_cardiovascular'] ?: 'Negados'); ?></td>
                    <td class="p-1.5 h-12" valign="top"><?php echo htmlspecialchars($paciente['antecedente_oseo'] ?: 'Negados'); ?></td>
                    <td class="p-1.5 h-12" valign="top"><?php echo htmlspecialchars($paciente['antecedente_respiratorio'] ?: 'Negados'); ?></td>
                    <td class="p-1.5 h-12" valign="top"><?php echo htmlspecialchars($paciente['antecedente_digestivo'] ?: 'Negados'); ?></td>
                    <td class="p-1.5 h-12" valign="top"><?php echo htmlspecialchars($paciente['antecedente_endocrino'] ?: 'Negados'); ?></td>
                    <td class="p-1.5 h-12" valign="top"><?php echo htmlspecialchars($paciente['antecedente_otros'] ?: 'Negados'); ?></td>
                </tr>
            </table>
        </div>

        <!-- Section 3: Quirúrgicos -->
        <div class="mb-4">
            <div class="bg-slate-100 px-3 py-1 font-bold text-[11px] uppercase border border-slate-400 border-b-0">3. Antecedentes Quirúrgicos y Traumatológicos</div>
            <table class="w-full text-left border-collapse">
                <tr>
                    <td class="p-2 text-slate-700 font-medium italic min-h-[35px]">
                        <span class="font-semibold text-slate-500 not-italic">Procedimientos / Intervenciones:</span> 
                        <?php echo htmlspecialchars($paciente['antecedente_quirurgico'] ?: 'Negados'); ?>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Section 4 & 5: Gineco & Tatuajes -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <div class="bg-slate-100 px-3 py-1 font-bold text-[11px] uppercase border border-slate-400 border-b-0">4. Antecedentes Ginecobstétricos</div>
                <table class="w-full text-left table-fixed border-collapse">
                    <tr class="bg-slate-50 text-[9px] font-semibold text-center">
                        <td class="p-1">Menarquia</td>
                        <td class="p-1">Sexarquia</td>
                        <td class="p-1">ACO</td>
                        <td class="p-1">Gestas</td>
                        <td class="p-1">Citología</td>
                    </tr>
                    <tr class="text-center font-medium text-[10px]">
                        <td class="p-1.5"><?php echo htmlspecialchars($paciente['gineco_menarquia'] ?: 'N/A'); ?></td>
                        <td class="p-1.5"><?php echo htmlspecialchars($paciente['gineco_sexarquia'] ?: 'N/A'); ?></td>
                        <td class="p-1.5"><?php echo htmlspecialchars($paciente['gineco_aco'] ?: 'N/A'); ?></td>
                        <td class="p-1.5"><?php echo htmlspecialchars($paciente['gineco_gestas'] ?: 'N/A'); ?></td>
                        <td class="p-1.5 text-ellipsis overflow-hidden whitespace-nowrap"><?php echo htmlspecialchars($paciente['gineco_citologia'] ?: 'N/A'); ?></td>
                    </tr>
                </table>
            </div>
            <div>
                <div class="bg-slate-100 px-3 py-1 font-bold text-[11px] uppercase border border-slate-400 border-b-0">5. Control de Tatuajes</div>
                <table class="w-full text-left border-collapse">
                    <tr>
                        <td class="p-1.5 flex flex-col justify-between" style="height: 43px;">
                            <div><span class="font-semibold text-slate-500">¿Tiene Tatuajes?:</span> <span class="font-bold"><?php echo htmlspecialchars($paciente['tiene_tatuajes'] ?: 'No'); ?></span></div>
                            <div class="text-[8px] text-slate-500 leading-tight">
                                <span class="font-semibold">Compromiso:</span> Yo me comprometo con la institución a no realizarme tatuajes durante el desarrollo de mi carrera.
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Section 6: Antecedentes Familiares -->
        <div class="mb-4">
            <div class="bg-slate-100 px-3 py-1 font-bold text-[11px] uppercase border border-slate-400 border-b-0">6. Antecedentes Familiares Patológicos</div>
            <table class="w-full text-left table-fixed border-collapse">
                <tr>
                    <td class="p-1.5 w-1/2"><span class="font-semibold text-slate-500">Padre:</span> <span class="text-slate-700 italic"><?php echo htmlspecialchars($paciente['antecedente_padre'] ?: 'Aparentemente Sano'); ?></span></td>
                    <td class="p-1.5 w-1/2"><span class="font-semibold text-slate-500">Madre:</span> <span class="text-slate-700 italic"><?php echo htmlspecialchars($paciente['antecedente_madre'] ?: 'Aparentemente Sana'); ?></span></td>
                </tr>
                <tr>
                    <td class="p-1.5"><span class="font-semibold text-slate-500">Hermanos:</span> <span class="text-slate-700 italic"><?php echo htmlspecialchars($paciente['antecedente_hermanos'] ?: 'Aparentemente Sanos'); ?></span></td>
                    <td class="p-1.5"><span class="font-semibold text-slate-500">Hijos:</span> <span class="text-slate-700 italic"><?php echo htmlspecialchars($paciente['antecedente_hijos'] ?: 'Aparentemente Sanos'); ?></span></td>
                </tr>
            </table>
        </div>

        <!-- Section 7: Datos Clínicos de Ingreso -->
        <div class="mb-4">
            <div class="bg-slate-100 px-3 py-1 font-bold text-[11px] uppercase border border-slate-400 border-b-0">7. Evaluación Clínica y Diagnóstico</div>
            <table class="w-full text-left border-collapse">
                <tr>
                    <td class="p-2">
                        <div class="mb-1"><span class="font-semibold text-slate-500 uppercase">Motivo de Consulta (MC):</span></div>
                        <div class="text-slate-700 italic pl-2 border-l-2 border-slate-200 mb-2"><?php echo htmlspecialchars($consulta['motivo_consulta'] ?? 'No registrado'); ?></div>
                        
                        <div class="mb-1"><span class="font-semibold text-slate-500 uppercase">Historia de la Enfermedad Actual (IEA):</span></div>
                        <div class="text-slate-700 italic pl-2 border-l-2 border-slate-200 mb-2"><?php echo htmlspecialchars($consulta['enfermedad_actual'] ?? ($consulta['resumen'] ?? 'No registrada')); ?></div>
                        
                        <div class="mb-1"><span class="font-semibold text-slate-500 uppercase">Diagnóstico Médico (DX):</span></div>
                        <div class="text-slate-900 font-bold pl-2 border-l-2 border-slate-300"><?php echo htmlspecialchars($consulta['diagnostico'] ?? 'No registrado'); ?></div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Section 8: Examen Físico y Signos Vitales -->
        <div class="mb-4">
            <div class="bg-slate-100 px-3 py-1 font-bold text-[11px] uppercase border border-slate-400 border-b-0">8. Examen Físico General</div>
            <table class="w-full text-left table-fixed border-collapse">
                <tr class="bg-slate-50 text-center font-bold text-[9px]">
                    <td class="p-1">TA (Presión)</td>
                    <td class="p-1">FC (Pulso)</td>
                    <td class="p-1">FR (Resp.)</td>
                    <td class="p-1">SpO2 (Sat.)</td>
                    <td class="p-1">Peso / Talla</td>
                </tr>
                <tr class="text-center font-semibold text-[10px]">
                    <td class="p-1.5"><?php echo htmlspecialchars($consulta['vital_ta'] ?? 'N/A'); ?></td>
                    <td class="p-1.5"><?php echo htmlspecialchars($consulta['vital_fc'] ?? 'N/A'); ?></td>
                    <td class="p-1.5"><?php echo htmlspecialchars($consulta['vital_fr'] ?? 'N/A'); ?></td>
                    <td class="p-1.5"><?php echo isset($consulta['vital_spo2']) && $consulta['vital_spo2'] !== '' ? htmlspecialchars($consulta['vital_spo2']) . '%' : 'N/A'; ?></td>
                    <td class="p-1.5"><?php echo htmlspecialchars($consulta['vital_peso_talla'] ?? 'N/A'); ?></td>
                </tr>
            </table>
            
            <table class="w-full text-left border-collapse border-t-0">
                <tr>
                    <td class="p-2 text-[10px]">
                        <div class="grid grid-cols-2 gap-x-6 gap-y-1.5">
                            <div><span class="font-semibold text-slate-700">Piel y mucosa:</span> <span class="text-slate-600"><?php echo htmlspecialchars($consulta['fisico_piel'] ?? 'Sin alteraciones'); ?></span></div>
                            <div><span class="font-semibold text-slate-700">Cabeza:</span> <span class="text-slate-600"><?php echo htmlspecialchars($consulta['fisico_cabeza'] ?? 'Sin alteraciones'); ?></span></div>
                            <div><span class="font-semibold text-slate-700">Cuello:</span> <span class="text-slate-600"><?php echo htmlspecialchars($consulta['fisico_cuello'] ?? 'Sin alteraciones'); ?></span></div>
                            <div><span class="font-semibold text-slate-700">Tórax:</span> <span class="text-slate-600"><?php echo htmlspecialchars($consulta['fisico_torax'] ?? 'Sin alteraciones'); ?></span></div>
                            <div><span class="font-semibold text-slate-700">Abdomen:</span> <span class="text-slate-600"><?php echo htmlspecialchars($consulta['fisico_abdomen'] ?? 'Sin alterations'); ?></span></div>
                            <div><span class="font-semibold text-slate-700">Extremidades:</span> <span class="text-slate-600"><?php echo htmlspecialchars($consulta['fisico_extremidades'] ?? 'Sin alteraciones'); ?></span></div>
                            <div class="col-span-2"><span class="font-semibold text-slate-700">Neurológico:</span> <span class="text-slate-600"><?php echo htmlspecialchars($consulta['fisico_neurologico'] ?? 'Sin alteraciones'); ?></span></div>
                        </div>
                        <div class="mt-3 border-t border-slate-200 pt-2 grid grid-cols-1 gap-1">
                            <div><span class="font-semibold text-slate-700">Laboratorios / Exámenes Solicitados:</span> <span class="text-slate-600"><?php echo htmlspecialchars($consulta['laboratorios'] ?? 'Ninguno'); ?></span></div>
                            <div><span class="font-semibold text-slate-700">Plan de Tratamiento:</span> <span class="text-slate-600"><?php echo htmlspecialchars($consulta['plan_tratamiento'] ?? 'Ninguno'); ?></span></div>
                            <div><span class="font-semibold text-slate-700">Pendiente:</span> <span class="text-slate-600"><?php echo htmlspecialchars($consulta['pendiente'] ?? 'Ninguno'); ?></span></div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Signature / Commitment Footer -->
        <div class="mt-8 pt-4">
            <div class="text-[9px] text-justify text-slate-500 leading-normal mb-8">
                <strong>COMPROMISO INSTITUCIONAL:</strong> Al firmar esta historia médica de ingreso, el estudiante asume el compromiso de cumplir con las normativas internas del consultorio y declarar de forma verídica todas sus condiciones de salud. Asimismo, se compromete formalmente con la institución a no realizarse tatuajes durante el desarrollo de su carrera en la Universidad Nacional Experimental Politécnica de la Fuerza Armada Nacional Bolivariana.
            </div>
            
            <div class="grid grid-cols-3 gap-6 text-center text-[9px] font-semibold text-slate-600 mt-4">
                <div class="flex flex-col items-center">
                    <div class="w-32 border-b border-slate-400 h-10 mb-1.5"></div>
                    <span>FIRMA DEL ESTUDIANTE</span>
                </div>
                <div class="flex flex-col items-center">
                    <div class="w-32 border-b border-slate-400 h-10 mb-1.5"></div>
                    <span>SELLO MÉDICO</span>
                </div>
                <div class="flex flex-col items-center">
                    <div class="w-32 border-b border-slate-400 h-10 mb-1.5"></div>
                    <span>MÉDICO EVALUADOR</span>
                </div>
            </div>
        </div>
        
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>
