<?php
require_once '../config/Database.php';

$consulta_id = isset($_GET['consulta_id']) ? (int)$_GET['consulta_id'] : 0;

if ($consulta_id <= 0) {
    die("ID de consulta inválido.");
}

try {
    $db = Config\Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("
        SELECT c.*, p.cedula, p.nombres, p.apellidos, p.fecha_nacimiento, p.sexo, p.telefono, p.direccion,
               p.carrera, p.semestre, p.nivel_educativo, cat.nombre AS categoria
        FROM consultas c
        JOIN pacientes p ON c.paciente_id = p.id
        JOIN categorias_institucionales cat ON p.categoria_id = cat.id
        WHERE c.id = ?
    ");
    $stmt->execute([$consulta_id]);
    $consulta = $stmt->fetch();
    
    if (!$consulta) {
        die("Consulta no encontrada.");
    }
    
    // Calcular edad del paciente si tiene fecha de nacimiento
    $edad = 'No especificada';
    if ($consulta['fecha_nacimiento']) {
        $fecha_nac = new DateTime($consulta['fecha_nacimiento']);
        $hoy = new DateTime();
        $edad = $fecha_nac->diff($hoy)->y . ' años';
    }
    
    // Formatear fecha de la circunstancia
    $fecha_consulta_f = date('d/m/Y', strtotime($consulta['fecha_circunstancia']));

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
    <title>Informe Médico - <?php echo htmlspecialchars($consulta['cedula']); ?></title>
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
                padding: 1.5cm;
                width: 100%;
                min-height: auto;
            }
        }
        body {
            font-family: 'Arial', sans-serif;
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen flex flex-col items-center p-4 sm:p-8">
    <!-- Floating Print Button -->
    <div class="no-print mb-6 flex gap-3">
        <button onclick="window.print()" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-5 py-2.5 rounded-xl shadow-md transition flex items-center gap-2 text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Imprimir Informe
        </button>
        <button onclick="window.close()" class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold px-5 py-2.5 rounded-xl transition text-sm">
            Cerrar
        </button>
    </div>

    <!-- Paper Container -->
    <div class="print-area bg-white w-full max-w-[21cm] min-h-[29.7cm] p-12 border border-slate-300 shadow-xl flex flex-col justify-between">
        
        <!-- Header -->
        <div>
            <div class="flex justify-between items-center border-b border-slate-900 pb-4 mb-6">
                <img src="../uploads/logo_unefa_1.png" class="h-16 w-auto" alt="Logo MPPD">
                <div class="text-center font-bold text-[10px] sm:text-xs uppercase tracking-tight leading-tight flex-1 px-4 text-slate-800">
                    República Bolivariana de Venezuela<br>
                    Ministerio del Poder Popular para la Defensa<br>
                    Universidad Nacional Experimental Politécnica<br>
                    de la Fuerza Armada Bolivariana<br>
                    Vicerrectorado Región Los Llanos<br>
                    Núcleo Portuguesa Extensión Acarigua
                </div>
                <img src="../uploads/logo_unefa_2.png" class="h-16 w-auto" alt="Logo UNEFA">
            </div>

            <!-- Title -->
            <div class="text-center my-6">
                <h1 class="text-lg sm:text-xl font-bold tracking-widest text-slate-900 underline decoration-double decoration-1 underline-offset-8">INFORME MÉDICO</h1>
            </div>

            <!-- Content -->
            <div class="space-y-4 text-xs sm:text-sm text-slate-800 mt-6">
                
                <!-- Datos Personales -->
                <div>
                    <h3 class="font-bold border-b border-slate-300 pb-1 text-slate-900 uppercase">1. Identificación del Paciente</h3>
                    <div class="grid grid-cols-2 gap-x-6 gap-y-2 mt-2">
                        <div><span class="font-semibold text-slate-500">Nombres y Apellidos:</span> <?php echo htmlspecialchars($consulta['nombres'] . ' ' . $consulta['apellidos']); ?></div>
                        <div><span class="font-semibold text-slate-500">Cédula:</span> <?php echo htmlspecialchars($consulta['cedula']); ?></div>
                        <div><span class="font-semibold text-slate-500">Edad:</span> <?php echo $edad; ?></div>
                        <div><span class="font-semibold text-slate-500">Sexo:</span> <?php echo htmlspecialchars($consulta['sexo'] ?: 'No especificado'); ?></div>
                        <div><span class="font-semibold text-slate-500">Categoría:</span> <?php echo htmlspecialchars($consulta['categoria']); ?></div>
                        <div><span class="font-semibold text-slate-500">Teléfono:</span> <?php echo htmlspecialchars($consulta['telefono'] ?: 'No registrado'); ?></div>
                        <?php if ($consulta['carrera']): ?>
                            <div><span class="font-semibold text-slate-500">Carrera / Semestre:</span> <?php echo htmlspecialchars($consulta['carrera'] . ' (' . $consulta['semestre'] . ')'); ?></div>
                        <?php endif; ?>
                        <div class="col-span-2"><span class="font-semibold text-slate-500">Dirección:</span> <?php echo htmlspecialchars($consulta['direccion'] ?: 'No registrada'); ?></div>
                    </div>
                </div>

                <!-- Detalle de la Consulta -->
                <div class="pt-2">
                    <h3 class="font-bold border-b border-slate-300 pb-1 text-slate-900 uppercase">2. Motivo de Consulta y Enfermedad Actual</h3>
                    <div class="space-y-2 mt-2">
                        <div>
                            <span class="font-semibold text-slate-700">Motivo de Consulta:</span>
                            <p class="text-justify text-slate-600 pl-2 mt-0.5"><?php echo nl2br(htmlspecialchars($consulta['motivo_consulta'] ?: 'No especificado')); ?></p>
                        </div>
                        <div>
                            <span class="font-semibold text-slate-700">Historia de la Enfermedad Actual (HEA):</span>
                            <p class="text-justify text-slate-600 pl-2 mt-0.5"><?php echo nl2br(htmlspecialchars($consulta['enfermedad_actual'] ?: $consulta['resumen'])); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Examen Físico y Signos Vitales -->
                <div class="pt-2">
                    <h3 class="font-bold border-b border-slate-300 pb-1 text-slate-900 uppercase">3. Examen Físico y Signos Vitales</h3>
                    
                    <!-- Signos Vitales Table -->
                    <div class="grid grid-cols-5 gap-2 text-center mt-2 border border-slate-200 rounded-lg p-2 bg-slate-50">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-500">Tensión Art. (TA)</span>
                            <p class="font-semibold text-slate-800 text-xs mt-0.5"><?php echo htmlspecialchars($consulta['vital_ta'] ?: 'N/A'); ?></p>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-500">Frec. Cardíaca (FC)</span>
                            <p class="font-semibold text-slate-800 text-xs mt-0.5"><?php echo htmlspecialchars($consulta['vital_fc'] ?: 'N/A'); ?></p>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-500">Frec. Resp. (FR)</span>
                            <p class="font-semibold text-slate-800 text-xs mt-0.5"><?php echo htmlspecialchars($consulta['vital_fr'] ?: 'N/A'); ?></p>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-500">Saturación (SpO2)</span>
                            <p class="font-semibold text-slate-800 text-xs mt-0.5"><?php echo htmlspecialchars($consulta['vital_spo2'] ? $consulta['vital_spo2'] . '%' : 'N/A'); ?></p>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-500">Peso / Talla</span>
                            <p class="font-semibold text-slate-800 text-xs mt-0.5"><?php echo htmlspecialchars($consulta['vital_peso_talla'] ?: 'N/A'); ?></p>
                        </div>
                    </div>

                    <!-- Physical Exam Elements -->
                    <div class="grid grid-cols-2 gap-x-6 gap-y-1.5 mt-3 text-xs leading-relaxed">
                        <?php
                        $fisicos = [
                            'Piel y Mucosa' => $consulta['fisico_piel'],
                            'Cabeza' => $consulta['fisico_cabeza'],
                            'Cuello' => $consulta['fisico_cuello'],
                            'Tórax' => $consulta['fisico_torax'],
                            'Abdomen' => $consulta['fisico_abdomen'],
                            'Extremidades' => $consulta['fisico_extremidades'],
                            'Neurológico' => $consulta['fisico_neurologico']
                        ];
                        foreach ($fisicos as $name => $value):
                            if (!empty($value)):
                        ?>
                            <div><span class="font-semibold text-slate-700"><?php echo $name; ?>:</span> <span class="text-slate-600"><?php echo htmlspecialchars($value); ?></span></div>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                </div>

                <!-- Diagnóstico y Tratamiento -->
                <div class="pt-2">
                    <h3 class="font-bold border-b border-slate-300 pb-1 text-slate-900 uppercase">4. Diagnóstico Clínico e Indicaciones</h3>
                    <div class="space-y-2 mt-2">
                        <div>
                            <span class="font-semibold text-slate-700">Impresión Diagnóstica (DX):</span>
                            <p class="text-justify text-slate-800 pl-2 font-bold"><?php echo nl2br(htmlspecialchars($consulta['diagnostico'] ?: 'No registrado')); ?></p>
                        </div>
                        <div>
                            <span class="font-semibold text-slate-700">Plan de Tratamiento / Indicaciones Médicas:</span>
                            <p class="text-justify text-slate-600 pl-2 mt-0.5"><?php echo nl2br(htmlspecialchars($consulta['plan_tratamiento'] ?: 'No registrado')); ?></p>
                        </div>
                        <?php if (!empty($consulta['laboratorios'])): ?>
                        <div>
                            <span class="font-semibold text-slate-700">Laboratorios / Exámenes Solicitados:</span>
                            <p class="text-justify text-slate-600 pl-2 mt-0.5"><?php echo nl2br(htmlspecialchars($consulta['laboratorios'])); ?></p>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($consulta['pendiente'])): ?>
                        <div>
                            <span class="font-semibold text-slate-700">Pendiente / Próximos Pasos:</span>
                            <p class="text-justify text-slate-600 pl-2 mt-0.5"><?php echo nl2br(htmlspecialchars($consulta['pendiente'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <p class="pt-4 text-right text-xs text-slate-500">
                    Documento emitido en el Consultorio Médico UNEFA - Extensión Acarigua, el <?php echo $dia_hoy; ?> de <?php echo $mes_hoy; ?> de <?php echo $anio_hoy; ?>.
                </p>
            </div>
        </div>

        <!-- Footer / Signatures -->
        <div class="mt-12 border-t border-slate-200 pt-8">
            <div class="grid grid-cols-2 gap-12 text-center text-xs font-semibold text-slate-700">
                <div class="flex flex-col items-center">
                    <div class="w-48 border-b border-slate-400 h-16 mb-2"></div>
                    <span>SELLO MÉDICO</span>
                </div>
                <div class="flex flex-col items-center">
                    <div class="w-48 border-b border-slate-400 h-16 mb-2"></div>
                    <span>FIRMA DEL MÉDICO EVALUADOR</span>
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
