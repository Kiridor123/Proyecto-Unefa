<?php
require_once '../config/Database.php';

$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';

if (empty($fecha_inicio) || empty($fecha_fin)) {
    die("Rango de fechas inválido o incompleto.");
}

try {
    $db = Config\Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("
        SELECT 
            c.*, 
            p.cedula, p.nombres, p.apellidos, p.sexo, p.fecha_nacimiento, p.carrera, p.semestre, p.telefono,
            cat.nombre AS categoria
        FROM consultas c
        JOIN pacientes p ON c.paciente_id = p.id
        JOIN categorias_institucionales cat ON p.categoria_id = cat.id
        WHERE c.fecha_circunstancia BETWEEN ? AND ?
        ORDER BY c.fecha_circunstancia ASC, c.id ASC
    ");
    
    $stmt->execute([$fecha_inicio, $fecha_fin]);
    $consultas = $stmt->fetchAll();
    
    $fecha_inicio_f = date('d/m/Y', strtotime($fecha_inicio));
    $fecha_fin_f = date('d/m/Y', strtotime($fecha_fin));

} catch (Exception $e) {
    die("Error de base de datos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Morbilidad (<?php echo $fecha_inicio_f; ?> al <?php echo $fecha_fin_f; ?>)</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            @page {
                size: landscape;
                margin: 1cm;
            }
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
                padding: 0;
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
        <button onclick="window.print()" class="bg-amber-600 hover:bg-amber-700 text-white font-semibold px-5 py-2.5 rounded-xl shadow-md transition flex items-center gap-2 text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Imprimir Reporte
        </button>
        <button onclick="window.close()" class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold px-5 py-2.5 rounded-xl transition text-sm">
            Cerrar
        </button>
    </div>

    <!-- Paper Container (Landscape A4-ish) -->
    <div class="print-area bg-white w-full max-w-[29.7cm] p-10 border border-slate-300 shadow-xl text-slate-800 text-[10px] leading-tight flex flex-col justify-between">
        
        <!-- Header -->
        <div>
            <div class="flex justify-between items-center border-b border-slate-900 pb-3 mb-4">
                <img src="../uploads/logo_unefa_1.png" class="h-12 w-auto" alt="Logo MPPD">
                <div class="text-center font-bold text-[8px] sm:text-[9px] uppercase tracking-tight leading-tight flex-1 px-4 text-slate-800">
                    República Bolivariana de Venezuela<br>
                    Ministerio del Poder Popular para la Defensa<br>
                    Universidad Nacional Experimental de las Fuerzas Armadas<br>
                    Vicerrectorado Académico - Dirección UNEFA Acarigua
                </div>
                <img src="../uploads/logo_morbilidad.png" class="h-12 w-auto" alt="Logo UNEFA">
            </div>

            <!-- Title -->
            <div class="my-4">
                <h1 class="text-xs sm:text-sm font-bold text-slate-900 uppercase">Control de asistencia médica</h1>
                <p class="text-[10px] font-semibold text-slate-600 mt-0.5">Jornada Médica Estudiantil / Control Semanal: <span class="text-slate-900 font-bold decoration-underline"><?php echo $fecha_inicio_f; ?> al <?php echo $fecha_fin_f; ?></span></p>
            </div>

            <!-- Table -->
            <table class="w-full border-collapse mt-4">
                <thead>
                    <tr class="bg-slate-100 text-slate-800 font-bold text-[9px] text-center uppercase">
                        <th class="p-1.5 border border-slate-400 w-[18%]">Nombre y Apellido</th>
                        <th class="p-1.5 border border-slate-400 w-[10%]">Cédula</th>
                        <th class="p-1.5 border border-slate-400 w-[5%]">Edad</th>
                        <th class="p-1.5 border border-slate-400 w-[5%]">Sexo</th>
                        <th class="p-1.5 border border-slate-400 w-[6%]">Semestre</th>
                        <th class="p-1.5 border border-slate-400 w-[12%]">Carrera</th>
                        <th class="p-1.5 border border-slate-400 w-[9%]">Teléfono</th>
                        <th class="p-1.5 border border-slate-400 w-[8%]">Peso/Talla</th>
                        <th class="p-1.5 border border-slate-400 w-[7%]">TA</th>
                        <th class="p-1.5 border border-slate-400 w-[5%]">FC</th>
                        <th class="p-1.5 border border-slate-400 w-[15%]">Diagnóstico</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($consultas) === 0): ?>
                        <tr>
                            <td colspan="11" class="text-center p-6 text-slate-400 text-xs italic">
                                No se encontraron registros de morbilidad en el rango de fechas seleccionado.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        foreach ($consultas as $c): 
                            // Calcular edad si tiene fecha de nacimiento
                            $edad = 'N/A';
                            if ($c['fecha_nacimiento']) {
                                $fecha_nac = new DateTime($c['fecha_nacimiento']);
                                $hoy = new DateTime($c['fecha_circunstancia']); // Edad al momento de la consulta
                                $edad = $fecha_nac->diff($hoy)->y;
                            }
                        ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="p-1.5 border border-slate-300 font-medium"><?php echo htmlspecialchars($c['nombres'] . ' ' . $c['apellidos']); ?></td>
                                <td class="p-1.5 border border-slate-300 text-center"><?php echo htmlspecialchars($c['cedula']); ?></td>
                                <td class="p-1.5 border border-slate-300 text-center"><?php echo $edad; ?></td>
                                <td class="p-1.5 border border-slate-300 text-center"><?php echo htmlspecialchars($c['sexo'] ?: 'N/A'); ?></td>
                                <td class="p-1.5 border border-slate-300 text-center"><?php echo htmlspecialchars($c['semestre'] ?: 'N/A'); ?></td>
                                <td class="p-1.5 border border-slate-300"><?php echo htmlspecialchars($c['carrera'] ?: 'N/A'); ?></td>
                                <td class="p-1.5 border border-slate-300 text-center"><?php echo htmlspecialchars($c['telefono'] ?: 'N/A'); ?></td>
                                <td class="p-1.5 border border-slate-300 text-center"><?php echo htmlspecialchars($c['vital_peso_talla'] ?: 'N/A'); ?></td>
                                <td class="p-1.5 border border-slate-300 text-center"><?php echo htmlspecialchars($c['vital_ta'] ?: 'N/A'); ?></td>
                                <td class="p-1.5 border border-slate-300 text-center"><?php echo htmlspecialchars($c['vital_fc'] ?: 'N/A'); ?></td>
                                <td class="p-1.5 border border-slate-300 text-justify font-semibold text-slate-900"><?php echo htmlspecialchars($c['diagnostico'] ?: $c['resumen']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Footer / Signature -->
        <div class="mt-12 flex justify-between items-center text-[9px] font-semibold text-slate-500 no-print-section">
            <div>
                Total de Pacientes Atendidos: <span class="font-bold text-slate-800 text-xs"><?php echo count($consultas); ?></span>
            </div>
            <div class="flex flex-col items-center">
                <div class="w-48 border-b border-slate-400 h-12 mb-1.5"></div>
                <span>FIRMA DEL MÉDICO / COORDINADOR DE LA JORNADA</span>
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
