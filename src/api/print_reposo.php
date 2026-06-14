<?php
require_once '../config/Database.php';

$consulta_id = isset($_GET['consulta_id']) ? (int)$_GET['consulta_id'] : 0;

if ($consulta_id <= 0) {
    die("ID de consulta inválido.");
}

try {
    $db = Config\Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("
        SELECT c.*, p.cedula, p.nombres, p.apellidos, p.fecha_nacimiento, cat.nombre AS categoria
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
    
    // Calcular días de reposo
    $dias_reposo = 0;
    if ($consulta['fecha_inicio_reposo'] && $consulta['fecha_fin_reposo']) {
        $start = new DateTime($consulta['fecha_inicio_reposo']);
        $end = new DateTime($consulta['fecha_fin_reposo']);
        $interval = $start->diff($end);
        $dias_reposo = $interval->days + 1; // Inclusive
    } else {
        die("Esta consulta no tiene un reposo registrado.");
    }
    
    // Formatear fechas en español
    $meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
        7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    
    $hoy = new DateTime();
    $dia_hoy = $hoy->format('d');
    $mes_hoy = $meses[(int)$hoy->format('m')];
    $anio_hoy = $hoy->format('Y');

    $fecha_inicio_f = date('d/m/Y', strtotime($consulta['fecha_inicio_reposo']));
    $fecha_fin_f = date('d/m/Y', strtotime($consulta['fecha_fin_reposo']));

} catch (Exception $e) {
    die("Error de base de datos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reposo Médico - <?php echo htmlspecialchars($consulta['cedula']); ?></title>
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
        <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2.5 rounded-xl shadow-md transition flex items-center gap-2 text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Imprimir Reposo
        </button>
        <button onclick="window.close()" class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold px-5 py-2.5 rounded-xl transition text-sm">
            Cerrar
        </button>
    </div>

    <!-- Paper Container -->
    <div class="print-area bg-white w-full max-w-[21cm] min-h-[29.7cm] p-12 border border-slate-300 shadow-xl flex flex-col justify-between">
        
        <!-- Header -->
        <div>
            <div class="flex justify-between items-center border-b border-slate-900 pb-4 mb-8">
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
            <div class="text-center my-10">
                <h1 class="text-xl sm:text-2xl font-bold tracking-widest text-slate-900 underline decoration-double decoration-1 underline-offset-8">REPOSO MÉDICO</h1>
            </div>

            <!-- Content -->
            <div class="space-y-6 text-sm sm:text-base text-slate-800 leading-relaxed text-justify mt-8">
                <p>
                    Quien suscribe, Médico de la Universidad Nacional Experimental Politécnica de la Fuerza Armada Bolivariana (UNEFA) - Núcleo Portuguesa, Extensión Acarigua, hace constar que el/la ciudadano/a:
                </p>

                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-2 font-medium">
                    <div><span class="text-slate-500 font-semibold">Nombres y Apellidos:</span> <?php echo htmlspecialchars($consulta['nombres'] . ' ' . $consulta['apellidos']); ?></div>
                    <div><span class="text-slate-500 font-semibold">Cédula de Identidad:</span> <?php echo htmlspecialchars($consulta['cedula']); ?></div>
                    <div><span class="text-slate-500 font-semibold">Categoría:</span> <?php echo htmlspecialchars($consulta['categoria']); ?></div>
                </div>

                <p>
                    Presenta una condición clínica evaluada en consulta que amerita reposo médico por un lapso de 
                    <span class="font-bold text-slate-900"><?php echo $dias_reposo; ?> días</span> 
                    consecutivos, a partir del <span class="font-bold text-slate-900"><?php echo $fecha_inicio_f; ?></span> 
                    hasta el <span class="font-bold text-slate-900"><?php echo $fecha_fin_f; ?></span> (inclusive).
                </p>

                <div class="mt-4">
                    <span class="font-bold text-slate-900 block mb-1">Diagnóstico / Resumen Clínico:</span>
                    <div class="border border-slate-200 rounded-xl p-4 min-h-[100px] bg-slate-50 italic text-slate-700">
                        <?php echo nl2br(htmlspecialchars($consulta['diagnostico'] ?: $consulta['resumen'])); ?>
                    </div>
                </div>

                <p class="pt-4 text-right">
                    Se expide la presente constancia a petición de la parte interesada en Acarigua, a los <?php echo $dia_hoy; ?> días del mes de <?php echo $mes_hoy; ?> del año <?php echo $anio_hoy; ?>.
                </p>
            </div>
        </div>

        <!-- Footer / Signatures -->
        <div class="mt-20 border-t border-slate-200 pt-10">
            <div class="grid grid-cols-2 gap-12 text-center text-xs font-semibold text-slate-700">
                <div class="flex flex-col items-center">
                    <div class="w-48 border-b border-slate-400 h-16 mb-2"></div>
                    <span>SELLO MÉDICO</span>
                </div>
                <div class="flex flex-col items-center">
                    <div class="w-48 border-b border-slate-400 h-16 mb-2"></div>
                    <span>FIRMA DE DIRECTOR/A</span>
                </div>
            </div>
        </div>
        
    </div>

    <script>
        // Disparar la impresión automáticamente al cargar la página
        window.onload = function() {
            // Un pequeño retardo para asegurar que los estilos de Tailwind y logos carguen completamente
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>
