<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salud UNEFA - Sistema de Gestión Médica</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> 
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        brand: {
                            50: '#eff6ff', 
                            100: '#dbeafe', 
                            500: '#3b82f6', 
                            600: '#2563eb', 
                            700: '#1d4ed8', 
                            900: '#1e3a8a',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .glass-panel {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .modal-enter { opacity: 0; transform: scale(0.95); }
        .modal-enter-active { opacity: 1; transform: scale(1); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .modal-leave { opacity: 1; transform: scale(1); }
        .modal-leave-active { opacity: 0; transform: scale(0.95); transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="text-slate-800 antialiased min-h-screen flex">

    <!-- Sidebar Lateral con Transición Deslizable en Móvil -->
    <aside id="sidebar" class="w-64 bg-brand-900 text-white flex flex-col fixed h-full shadow-2xl z-30 transition-transform duration-300 ease-in-out transform -translate-x-full md:translate-x-0">
        <div class="p-6 flex items-center justify-between border-b border-brand-700/50">
            <div class="flex items-center gap-3">
                <div class="bg-white/10 p-2 rounded-lg shrink-0"><i class="ph ph-heartbeat text-2xl text-blue-300"></i></div>
                <div>
                    <h1 class="text-lg font-bold leading-tight">Salud UNEFA</h1>
                    <p class="text-xs text-brand-100/70">Gestión Médica</p>
                </div>
            </div>
            <button onclick="toggleMobileSidebar()" class="md:hidden text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition">
                <i class="ph ph-x text-2xl"></i>
            </button>
        </div>
        <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
            <a href="#" id="link-dashboard" onclick="switchTab('dashboard'); return false;" class="nav-link flex items-center gap-3 px-4 py-3 bg-brand-700/50 rounded-xl text-sm font-medium transition hover:bg-brand-700 text-white">
                <i class="ph ph-squares-four text-lg"></i> Dashboard
            </a>
            <a href="#" id="link-pacientes" onclick="switchTab('pacientes'); return false;" class="nav-link flex items-center gap-3 px-4 py-3 text-brand-100 hover:bg-white/5 rounded-xl text-sm font-medium transition">
                <i class="ph ph-users text-lg"></i> Pacientes
            </a>
            <a href="#" id="link-citas" onclick="switchTab('citas'); return false;" class="nav-link flex items-center gap-3 px-4 py-3 text-brand-100 hover:bg-white/5 rounded-xl text-sm font-medium transition">
                <i class="ph ph-calendar-check text-lg"></i> Citas Médicas
            </a>
            <a href="#" id="link-reportes" onclick="switchTab('reportes'); return false;" class="nav-link flex items-center gap-3 px-4 py-3 text-brand-100 hover:bg-white/5 rounded-xl text-sm font-medium transition">
                <i class="ph ph-file-text text-lg"></i> Reportes de Morbilidad
            </a>
        </nav>
        <div class="p-4 border-t border-brand-700/50 text-center text-xs text-brand-200/50">
            &copy; 2026 Salud UNEFA
        </div>
    </aside>

    <!-- Overlay para el Sidebar en móvil -->
    <div id="sidebarOverlay" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-20 hidden md:hidden" onclick="toggleMobileSidebar()"></div>

    <!-- Contenido Principal -->
    <main class="flex-1 ml-0 md:ml-64 flex flex-col min-h-screen">
        <!-- Encabezado -->
        <header class="h-16 glass-panel sticky top-0 z-20 flex justify-between items-center px-4 sm:px-8 border-b border-slate-200">
            <div class="flex items-center gap-3">
                <button onclick="toggleMobileSidebar()" class="md:hidden text-slate-600 hover:text-brand-600 p-2 rounded-lg hover:bg-slate-100 transition">
                    <i class="ph ph-list text-2xl"></i>
                </button>
                <h2 id="headerTitle" class="text-lg sm:text-xl font-semibold text-slate-800">Panel de Control</h2>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-[10px] sm:text-xs bg-brand-50 text-brand-600 border border-brand-100 px-2.5 py-1 sm:px-3 sm:py-1.5 rounded-full font-medium flex items-center gap-1 sm:gap-1.5">
                    <span class="w-1.5 h-1.5 sm:w-2 sm:h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Admin
                </span>
            </div>
        </header>

        <!-- Secciones de Contenido -->
        <div class="p-4 sm:p-8 flex-1 flex flex-col gap-6 sm:gap-8 max-w-7xl mx-auto w-full">
            
            <!-- SECTION 1: DASHBOARD -->
            <section id="section-dashboard" class="tab-section flex flex-col gap-6 sm:gap-8">
                <!-- Tarjetas Dinámicas -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                    <div class="glass-panel p-5 sm:p-6 rounded-2xl border-l-4 border-brand-500 flex items-center justify-between hover:-translate-y-1 hover:shadow-md transition-all duration-300">
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-slate-500 mb-1">Total Pacientes</p>
                            <h3 class="text-2xl sm:text-3xl font-bold text-slate-800" id="statPacientes">-</h3>
                        </div>
                        <div class="w-11 h-11 sm:w-12 sm:h-12 bg-brand-50 text-brand-600 rounded-xl flex items-center justify-center text-xl sm:text-2xl shrink-0"><i class="ph ph-users"></i></div>
                    </div>
                    
                    <div class="glass-panel p-5 sm:p-6 rounded-2xl border-l-4 border-amber-400 flex items-center justify-between hover:-translate-y-1 hover:shadow-md transition-all duration-300">
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-slate-500 mb-1">Citas Pendientes</p>
                            <h3 class="text-2xl sm:text-3xl font-bold text-slate-800" id="statCitas">-</h3>
                        </div>
                        <div class="w-11 h-11 sm:w-12 sm:h-12 bg-amber-50 text-amber-500 rounded-xl flex items-center justify-center text-xl sm:text-2xl shrink-0"><i class="ph ph-calendar-check"></i></div>
                    </div>

                    <div class="glass-panel p-5 sm:p-6 rounded-2xl border-l-4 border-emerald-500 flex items-center justify-between hover:-translate-y-1 hover:shadow-md transition-all duration-300">
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-slate-500 mb-1">Consultas del Mes</p>
                            <h3 class="text-2xl sm:text-3xl font-bold text-slate-800" id="statConsultas">-</h3>
                        </div>
                        <div class="w-11 h-11 sm:w-12 sm:h-12 bg-emerald-50 text-emerald-500 rounded-xl flex items-center justify-center text-xl sm:text-2xl shrink-0"><i class="ph ph-stethoscope"></i></div>
                    </div>

                    <div class="glass-panel p-5 sm:p-6 rounded-2xl border-l-4 border-rose-500 flex items-center justify-between hover:-translate-y-1 hover:shadow-md transition-all duration-300">
                        <div>
                            <p class="text-xs sm:text-sm font-medium text-slate-500 mb-1">Reposos Activos</p>
                            <h3 class="text-2xl sm:text-3xl font-bold text-slate-800" id="statReposos">-</h3>
                        </div>
                        <div class="w-11 h-11 sm:w-12 sm:h-12 bg-rose-50 text-rose-500 rounded-xl flex items-center justify-center text-xl sm:text-2xl shrink-0"><i class="ph ph-first-aid"></i></div>
                    </div>
                </div>

                <!-- Gráficos del Dashboard -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8">
                    <!-- Gráfico 1: Categorías -->
                    <div class="glass-panel p-4 sm:p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col min-h-[300px] sm:min-h-[350px]">
                        <h4 class="text-xs sm:text-sm font-semibold text-slate-700 uppercase tracking-wider mb-4 flex items-center gap-2">
                            <i class="ph ph-chart-pie text-brand-600 text-lg"></i> Distribución por Categoría
                        </h4>
                        <div class="flex-1 flex items-center justify-center relative w-full h-[220px] sm:h-[250px]">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>

                    <!-- Gráfico 2: Consultas Mensuales -->
                    <div class="glass-panel p-4 sm:p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col min-h-[300px] sm:min-h-[350px]">
                        <h4 class="text-xs sm:text-sm font-semibold text-slate-700 uppercase tracking-wider mb-4 flex items-center gap-2">
                            <i class="ph ph-chart-bar text-brand-600 text-lg"></i> Consultas Históricas
                        </h4>
                        <div class="flex-1 flex items-center justify-center relative w-full h-[220px] sm:h-[250px]">
                            <canvas id="historyChart"></canvas>
                        </div>
                    </div>
                </div>
            </section>

            <!-- SECTION 2: PACIENTES -->
            <section id="section-pacientes" class="tab-section flex flex-col gap-6 sm:gap-8 hidden">
                <div class="glass-panel rounded-2xl flex flex-col border border-slate-200 overflow-hidden shadow-sm">
                    <!-- Cabecera de filtros y acción -->
                    <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col lg:flex-row justify-between items-center gap-4 bg-white/50">
                        <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
                            <div class="relative w-full sm:w-64">
                                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input type="text" id="filterCedula" placeholder="Buscar por Cédula..." 
                                    class="w-full pl-10 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition shadow-sm">
                            </div>
                            <div class="relative w-full sm:min-w-[180px]">
                                <select id="filterCategoria" class="w-full pl-4 pr-10 py-2 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-brand-500 appearance-none shadow-sm transition">
                                    <option value="">Todas las Categorías</option>
                                    <!-- Cargado por JS -->
                                </select>
                                <i class="ph ph-caret-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                            </div>
                        </div>
                        <button onclick="openModal()" class="w-full lg:w-auto flex items-center justify-center gap-2 px-5 py-2.5 bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium rounded-xl transition shadow-sm">
                            <i class="ph ph-plus-circle text-lg"></i> Registrar Consulta
                        </button>
                    </div>

                    <!-- Tabla de Pacientes -->
                    <div class="overflow-x-auto relative">
                        <div id="tableLoader" class="absolute inset-0 bg-white/80 backdrop-blur-sm z-10 flex flex-col items-center justify-center text-brand-600">
                            <i class="ph ph-spinner-gap animate-spin text-4xl mb-2"></i>
                            <span class="text-sm font-medium">Cargando pacientes...</span>
                        </div>

                        <table class="w-full text-left border-collapse" id="patientsTable">
                            <thead>
                                <tr class="bg-slate-50/80 text-xs uppercase text-slate-500 font-semibold border-b border-slate-200">
                                    <th class="px-4 sm:px-6 py-4">Cédula</th>
                                    <th class="px-4 sm:px-6 py-4">Nombres y Apellidos</th>
                                    <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Categoría</th>
                                    <th class="px-4 sm:px-6 py-4 hidden sm:table-cell">Última Consulta</th>
                                    <th class="px-4 sm:px-6 py-4 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm divide-y divide-slate-100 bg-white/30" id="tableBody">
                                <!-- Datos inyectados -->
                            </tbody>
                        </table>
                        
                        <div id="emptyState" class="hidden flex flex-col items-center justify-center py-12 text-slate-400">
                            <i class="ph ph-magnifying-glass text-4xl mb-3 text-slate-300"></i>
                            <p class="text-sm">No se encontraron registros de pacientes.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- SECTION 3: CITAS -->
            <section id="section-citas" class="tab-section flex flex-col gap-6 sm:gap-8 hidden">
                <div class="glass-panel rounded-2xl flex flex-col border border-slate-200 overflow-hidden shadow-sm">
                    <!-- Filtros y acciones -->
                    <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col lg:flex-row justify-between items-center gap-4 bg-white/50">
                        <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
                            <div class="relative w-full sm:w-64">
                                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input type="text" id="filterCitasPaciente" placeholder="Buscar por Cédula o Nombre..." 
                                    class="w-full pl-10 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition shadow-sm">
                            </div>
                            <div class="relative w-full sm:min-w-[180px]">
                                <select id="filterCitasEstado" class="w-full pl-4 pr-10 py-2 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-brand-500 appearance-none shadow-sm transition">
                                    <option value="">Todos los Estados</option>
                                    <option value="Pendiente">Pendiente</option>
                                    <option value="Completada">Completada</option>
                                    <option value="Cancelada">Cancelada</option>
                                </select>
                                <i class="ph ph-caret-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                            </div>
                        </div>
                        <button onclick="openCitaModal()" class="w-full lg:w-auto flex items-center justify-center gap-2 px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium rounded-xl transition shadow-sm">
                            <i class="ph ph-calendar-plus text-lg"></i> Agendar Cita
                        </button>
                    </div>

                    <!-- Tabla de Citas -->
                    <div class="overflow-x-auto relative">
                        <div id="citasTableLoader" class="absolute inset-0 bg-white/80 backdrop-blur-sm z-10 flex flex-col items-center justify-center text-amber-500">
                            <i class="ph ph-spinner-gap animate-spin text-4xl mb-2"></i>
                            <span class="text-sm font-medium">Cargando citas...</span>
                        </div>

                        <table class="w-full text-left border-collapse" id="citasTable">
                            <thead>
                                <tr class="bg-slate-50/80 text-xs uppercase text-slate-500 font-semibold border-b border-slate-200">
                                    <th class="px-4 sm:px-6 py-4">Fecha y Hora</th>
                                    <th class="px-4 sm:px-6 py-4 hidden sm:table-cell">Cédula</th>
                                    <th class="px-4 sm:px-6 py-4">Paciente</th>
                                    <th class="px-4 sm:px-6 py-4 hidden lg:table-cell">Categoría</th>
                                    <th class="px-4 sm:px-6 py-4">Estado</th>
                                    <th class="px-4 sm:px-6 py-4 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm divide-y divide-slate-100 bg-white/30" id="citasTableBody">
                                <!-- Datos inyectados -->
                            </tbody>
                        </table>
                        
                        <div id="citasEmptyState" class="hidden flex flex-col items-center justify-center py-12 text-slate-400">
                            <i class="ph ph-calendar-blank text-4xl mb-3 text-slate-300"></i>
                            <p class="text-sm">No se encontraron citas agendadas.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- SECTION 4: REPORTES DE MORBILIDAD -->
            <section id="section-reportes" class="tab-section flex flex-col gap-6 sm:gap-8 hidden">
                <div class="glass-panel rounded-2xl flex flex-col border border-slate-200 overflow-hidden shadow-sm bg-white p-5 sm:p-6 space-y-6">
                    <div>
                        <h3 class="text-base sm:text-lg font-bold text-slate-800 flex items-center gap-2">
                            <i class="ph ph-file-text text-brand-600"></i> Reporte de Morbilidad de Jornada Médica
                        </h3>
                        <p class="text-xs sm:text-sm text-slate-500 mt-1">Consulte el historial de atenciones médicas y genere el formato de control de asistencia para la impresión de jornada.</p>
                    </div>

                    <form id="morbilidadForm" onsubmit="event.preventDefault(); buscarMorbilidad();" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end bg-slate-50 p-4 rounded-xl border border-slate-200">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Fecha de Inicio *</label>
                            <input type="date" id="morbilidadInicio" required class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Fecha de Fin *</label>
                            <input type="date" id="morbilidadFin" required class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 transition">
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="flex-1 py-2 px-4 bg-brand-600 hover:bg-brand-700 text-white font-medium rounded-lg text-sm transition shadow flex items-center justify-center gap-1.5">
                                <i class="ph ph-magnifying-glass"></i> Buscar
                            </button>
                            <button type="button" onclick="imprimirMorbilidadDirecta()" id="btnImprimirMorbilidad" disabled class="py-2 px-4 bg-amber-500 hover:bg-amber-600 disabled:opacity-50 disabled:cursor-not-allowed text-white font-medium rounded-lg text-sm transition shadow flex items-center justify-center gap-1.5">
                                <i class="ph ph-printer"></i> Imprimir
                            </button>
                        </div>
                    </form>

                    <!-- Tabla de Vista Previa -->
                    <div class="overflow-x-auto relative">
                        <table class="w-full text-left border-collapse" id="morbilidadTable">
                            <thead>
                                <tr class="bg-slate-50 text-xs uppercase text-slate-500 font-semibold border-b border-slate-200">
                                    <th class="px-4 py-3">Paciente</th>
                                    <th class="px-4 py-3">Cédula</th>
                                    <th class="px-4 py-3">Fecha Eval.</th>
                                    <th class="px-4 py-3">Categoría</th>
                                    <th class="px-4 py-3">Peso/Talla</th>
                                    <th class="px-4 py-3">TA</th>
                                    <th class="px-4 py-3">Diagnóstico</th>
                                </tr>
                            </thead>
                            <tbody class="text-xs divide-y divide-slate-100 bg-white" id="morbilidadTableBody">
                                <tr>
                                    <td colspan="7" class="text-center py-8 text-slate-400">Seleccione un rango de fechas y haga clic en Buscar.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

        </div>
    </main>

    <!-- Drawer de Detalle de Paciente (Historial Clínico) -->
    <div id="patientDrawer" class="fixed inset-0 z-40 hidden">
        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onclick="closeDrawer()"></div>
        <div id="drawerContent" class="fixed right-0 top-0 bottom-0 w-full max-w-lg bg-white shadow-2xl flex flex-col h-full transform translate-x-full transition-transform duration-300 ease-out z-50">
            <!-- Header -->
            <div class="px-5 py-4 sm:px-6 sm:py-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center font-bold text-sm shrink-0" id="detIniciales">
                        -
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm sm:text-base font-bold text-slate-800 truncate" id="detNombre">-</h3>
                        <p class="text-[10px] sm:text-xs text-slate-500 flex items-center gap-1">
                            <i class="ph ph-identification-card shrink-0"></i> <span id="detCedula" class="truncate">-</span> &bull; <span id="detCategoria" class="truncate">-</span>
                        </p>
                    </div>
                </div>
                <button onclick="closeDrawer()" class="text-slate-400 hover:text-slate-600 hover:bg-slate-200 p-1.5 rounded-lg transition shrink-0">
                    <i class="ph ph-x text-xl"></i>
                </button>
            </div>
            
            <!-- Contenido del Historial -->
            <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-6">
                <!-- Loader -->
                <div id="drawerLoader" class="flex flex-col items-center justify-center py-12 text-brand-600">
                    <i class="ph ph-spinner-gap animate-spin text-3xl mb-2"></i>
                    <span class="text-xs font-medium">Cargando historial médico...</span>
                </div>

                <!-- Contenedor de Información Real -->
                <div id="drawerData" class="space-y-6 hidden">
                    <!-- Accesos directos / Botones rápidos -->
                    <div class="grid grid-cols-2 gap-3 pb-3">
                        <button id="btnConsultaAlVuelo" class="flex items-center justify-center gap-1.5 py-2 px-3 bg-brand-50 hover:bg-brand-100 text-brand-700 text-xs font-semibold rounded-xl border border-brand-100 transition shadow-sm">
                            <i class="ph ph-stethoscope"></i> Nueva Consulta
                        </button>
                        <button id="btnCitaAlVuelo" class="flex items-center justify-center gap-1.5 py-2 px-3 bg-amber-50 hover:bg-amber-100 text-amber-700 text-xs font-semibold rounded-xl border border-amber-100 transition shadow-sm">
                            <i class="ph ph-calendar-plus"></i> Programar Cita
                        </button>
                    </div>
                    <div class="pb-4 border-b border-slate-100">
                        <button id="btnPrintHistoria" class="w-full flex items-center justify-center gap-1.5 py-2 px-3 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition shadow-sm">
                            <i class="ph ph-printer"></i> Imprimir Historia Clínica de Ingreso
                        </button>
                    </div>

                    <!-- Listado de Consultas -->
                    <div class="space-y-4">
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="ph ph-activity text-brand-600"></i> Historial Clínico
                        </h4>
                        <div id="detConsultas" class="space-y-3">
                            <!-- Inyectado vía JS -->
                        </div>
                    </div>

                    <!-- Listado de Citas -->
                    <div class="space-y-4 pt-4 border-t border-slate-100">
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="ph ph-calendar-check text-brand-600"></i> Control de Citas
                        </h4>
                        <div id="detCitas" class="divide-y divide-slate-50">
                            <!-- Inyectado vía JS -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Registro de Consulta (Responsive) -->
    <div id="patientModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onclick="closeModal()"></div>
        <div id="modalContent" class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl flex flex-col max-h-[90vh] modal-enter">
            <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50 rounded-t-2xl shrink-0">
                <h3 class="text-base sm:text-lg font-bold text-slate-800 flex items-center gap-2">
                    <i class="ph ph-user-plus text-brand-600"></i> Registrar Consulta e Historia Clínica
                </h3>
                <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600 hover:bg-slate-200 p-1.5 rounded-lg transition shrink-0">
                    <i class="ph ph-x text-lg"></i>
                </button>
            </div>
            
            <div class="p-4 sm:p-5 overflow-y-auto">
                <form id="registroForm" class="space-y-4">
                    <div class="space-y-3">
                        
                        <!-- Accordion Item 1: Datos Personales -->
                        <div class="border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                            <button type="button" onclick="toggleAccordion('acc-paciente')" class="w-full flex justify-between items-center px-4 py-3 bg-slate-50 hover:bg-slate-100 transition font-semibold text-xs sm:text-sm text-slate-700">
                                <span class="flex items-center gap-2"><i class="ph ph-user-circle text-brand-600 text-base sm:text-lg"></i> 1. Identificación del Paciente</span>
                                <i class="ph ph-caret-down text-slate-400 transition-transform duration-200" id="icon-acc-paciente"></i>
                            </button>
                            <div id="acc-paciente" class="p-4 sm:p-5 border-t border-slate-200 bg-white space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">Cédula de Identidad *</label>
                                        <input type="text" name="cedula" id="formCedula" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:border-brand-500 focus:outline-none transition" placeholder="Ej. V-12345678" onblur="buscarPacientePorCedula(this.value)">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">Categoría Institucional *</label>
                                        <select name="categoria" id="formCategoria" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:border-brand-500 focus:outline-none transition">
                                            <option value="">Seleccione...</option>
                                            <!-- Opciones cargadas por JS -->
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">Sexo *</label>
                                        <select name="sexo" id="formSexo" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:border-brand-500 focus:outline-none transition">
                                            <option value="">Seleccione...</option>
                                            <option value="Femenino">Femenino</option>
                                            <option value="Masculino">Masculino</option>
                                            <option value="Otro">Otro / No especificado</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">Nombres *</label>
                                        <input type="text" name="nombres" id="formNombres" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:border-brand-500 focus:outline-none transition">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">Apellidos *</label>
                                        <input type="text" name="apellidos" id="formApellidos" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:border-brand-500 focus:outline-none transition">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">Teléfono</label>
                                        <input type="text" name="telefono" id="formTelefono" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:border-brand-500 focus:outline-none transition" placeholder="Ej. 0412-1234567">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">Lugar de Nacimiento</label>
                                        <input type="text" name="lugar_nacimiento" id="formLugarNacimiento" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:border-brand-500 focus:outline-none transition" placeholder="Ej. Acarigua">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">Fecha de Nacimiento</label>
                                        <input type="date" name="fecha_nacimiento" id="formFechaNacimiento" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:border-brand-500 focus:outline-none transition">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">Nivel Educativo</label>
                                        <input type="text" name="nivel_educativo" id="formNivelEducativo" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:border-brand-500 focus:outline-none transition" placeholder="Ej. Bachiller / Universitario">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">Carrera (Solo Estudiantes)</label>
                                        <input type="text" name="carrera" id="formCarrera" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:border-brand-500 focus:outline-none transition" placeholder="Ej. Ing. de Sistemas">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">Semestre (Solo Estudiantes)</label>
                                        <input type="text" name="semestre" id="formSemestre" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:border-brand-500 focus:outline-none transition" placeholder="Ej. 1er Semestre">
                                    </div>
                                    <div class="sm:col-span-2 lg:col-span-3">
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">Dirección de Habitación</label>
                                        <textarea name="direccion" id="formDireccion" rows="2" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:border-brand-500 focus:outline-none transition resize-none" placeholder="Dirección completa del paciente..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Accordion Item 2: Antecedentes Médicos -->
                        <div class="border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                            <button type="button" onclick="toggleAccordion('acc-antecedentes')" class="w-full flex justify-between items-center px-4 py-3 bg-slate-50 hover:bg-slate-100 transition font-semibold text-xs sm:text-sm text-slate-700">
                                <span class="flex items-center gap-2"><i class="ph ph-heart text-brand-600 text-base sm:text-lg"></i> 2. Antecedentes Médicos, Ginecológicos y Tatuajes</span>
                                <i class="ph ph-caret-down text-slate-400 transition-transform duration-200" id="icon-acc-antecedentes"></i>
                            </button>
                            <div id="acc-antecedentes" class="p-4 sm:p-5 border-t border-slate-200 bg-white hidden space-y-5">
                                <!-- Antecedentes Personales Patológicos -->
                                <div>
                                    <h5 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2 border-b border-slate-100 pb-1 flex items-center gap-1"><i class="ph ph-clipboard-text"></i> Personales Patológicos (Indicar condición o 'Negado')</h5>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                        <div>
                                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">Cardiovascular</label>
                                            <input type="text" name="antecedente_cardiovascular" id="formAntecedenteCardiovascular" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Ej. Negado">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">Óseo / Articular</label>
                                            <input type="text" name="antecedente_oseo" id="formAntecedenteOseo" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Ej. Negado">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">Respiratorio</label>
                                            <input type="text" name="antecedente_respiratorio" id="formAntecedenteRespiratorio" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Ej. Asma leve">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">Digestivo</label>
                                            <input type="text" name="antecedente_digestivo" id="formAntecedenteDigestivo" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Ej. Negado">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">Endocrino / Metabólico</label>
                                            <input type="text" name="antecedente_endocrino" id="formAntecedenteEndocrino" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Ej. Resistencia insulina">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">Otros antecedentes</label>
                                            <input type="text" name="antecedente_otros" id="formAntecedenteOtros" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Alergias, etc.">
                                        </div>
                                    </div>
                                </div>

                                <!-- Quirurgicos -->
                                <div>
                                    <h5 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2 border-b border-slate-100 pb-1 flex items-center gap-1"><i class="ph ph-bandaids"></i> Quirúrgicos / Traumatológicos</h5>
                                    <input type="text" name="antecedente_quirurgico" id="formAntecedenteQuirurgico" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Cirugías previas, fracturas, prótesis...">
                                </div>

                                <!-- Antecedentes Ginecobstétricos -->
                                <div>
                                    <h5 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2 border-b border-slate-100 pb-1 flex items-center gap-1"><i class="ph ph-gender-female"></i> Ginecoobstétricos (Si aplica)</h5>
                                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                                        <div>
                                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">Menarquia</label>
                                            <input type="text" name="gineco_menarquia" id="formGinecoMenarquia" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Edad">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">Sexarquia</label>
                                            <input type="text" name="gineco_sexarquia" id="formGinecoSexarquia" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Edad">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">ACO</label>
                                            <input type="text" name="gineco_aco" id="formGinecoAco" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Ninguno / Marca">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">Gestas</label>
                                            <input type="text" name="gineco_gestas" id="formGinecoGestas" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="N° Embarazos">
                                        </div>
                                        <div class="col-span-2 sm:col-span-1">
                                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">Última Citología</label>
                                            <input type="text" name="gineco_citologia" id="formGinecoCitologia" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Fecha / Resultado">
                                        </div>
                                    </div>
                                </div>

                                <!-- Tatuajes y Compromiso -->
                                <div>
                                    <h5 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2 border-b border-slate-100 pb-1 flex items-center gap-1"><i class="ph ph-palette"></i> Control de Tatuajes</h5>
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">¿Tiene Tatuajes?</label>
                                            <select name="tiene_tatuajes" id="formTieneTatuajes" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition">
                                                <option value="No">No</option>
                                                <option value="Sí">Sí</option>
                                            </select>
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">Compromiso Institucional</label>
                                            <input type="text" name="compromiso_tatuajes" id="formCompromisoTatuajes" value="Yo me comprometo con la institución a no realizarme tatuajes durante el desarrollo de mi carrera." class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition">
                                        </div>
                                    </div>
                                </div>

                                <!-- Antecedentes Familiares -->
                                <div>
                                    <h5 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2 border-b border-slate-100 pb-1 flex items-center gap-1"><i class="ph ph-users-three"></i> Familiares Patológicos (Indicar condición o 'Sano')</h5>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                                        <div>
                                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">Padre</label>
                                            <input type="text" name="antecedente_padre" id="formAntecedentePadre" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Sano / Hipertensión">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">Madre</label>
                                            <input type="text" name="antecedente_madre" id="formAntecedenteMadre" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Sana / Diabetes">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">Hermanos</label>
                                            <input type="text" name="antecedente_hermanos" id="formAntecedenteHermanos" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Sanos">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">Hijos</label>
                                            <input type="text" name="antecedente_hijos" id="formAntecedenteHijos" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Sanos">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Accordion Item 3: Motivo y Signos Vitales -->
                        <div class="border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                            <button type="button" onclick="toggleAccordion('acc-vitales')" class="w-full flex justify-between items-center px-4 py-3 bg-slate-50 hover:bg-slate-100 transition font-semibold text-xs sm:text-sm text-slate-700">
                                <span class="flex items-center gap-2"><i class="ph ph-heartbeat text-brand-600 text-base sm:text-lg"></i> 3. Motivo Clínico y Signos Vitales</span>
                                <i class="ph ph-caret-down text-slate-400 transition-transform duration-200" id="icon-acc-vitales"></i>
                            </button>
                            <div id="acc-vitales" class="p-4 sm:p-5 border-t border-slate-200 bg-white hidden space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">Motivo de Consulta *</label>
                                        <textarea name="motivo_consulta" required rows="2" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:border-brand-500 focus:outline-none transition resize-none" placeholder="Ej. Dolor de cabeza, fiebre..."></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">Enfermedad Actual / Resumen *</label>
                                        <textarea name="enfermedad_actual" required rows="2" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:border-brand-500 focus:outline-none transition resize-none" placeholder="Ej. Paciente refiere cefalea de 3 días de evolución..."></textarea>
                                    </div>
                                </div>

                                <div class="border-t border-slate-100 pt-3">
                                    <h5 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2 flex items-center gap-1"><i class="ph ph-waveform"></i> Toma de Signos Vitales</h5>
                                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                                        <div>
                                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">Presión Art. (TA)</label>
                                            <input type="text" name="vital_ta" id="formVitalTA" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Ej. 120/80 mmHg">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">Frec. Cardíaca (FC)</label>
                                            <input type="text" name="vital_fc" id="formVitalFC" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Ej. 72 lpm">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">Frec. Resp. (FR)</label>
                                            <input type="text" name="vital_fr" id="formVitalFR" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Ej. 16 rpm">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">Saturación (SpO2)</label>
                                            <input type="text" name="vital_spo2" id="formVitalSpo2" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Ej. 98%">
                                        </div>
                                        <div class="col-span-2 sm:col-span-1">
                                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">Peso / Talla</label>
                                            <input type="text" name="vital_peso_talla" id="formVitalPesoTalla" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:border-brand-500 focus:outline-none transition" placeholder="Ej. 65 kg / 1.70 m">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Accordion Item 4: Examen Físico -->
                        <div class="border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                            <button type="button" onclick="toggleAccordion('acc-fisico')" class="w-full flex justify-between items-center px-4 py-3 bg-slate-50 hover:bg-slate-100 transition font-semibold text-xs sm:text-sm text-slate-700">
                                <span class="flex items-center gap-2"><i class="ph ph-stethoscope text-brand-600 text-base sm:text-lg"></i> 4. Examen Físico General</span>
                                <i class="ph ph-caret-down text-slate-400 transition-transform duration-200" id="icon-acc-fisico"></i>
                            </button>
                            <div id="acc-fisico" class="p-4 sm:p-5 border-t border-slate-200 bg-white hidden space-y-4">
                                <p class="text-[10px] text-slate-400 italic mb-2">Describa cualquier hallazgo patológico. Deje vacío o coloque 'Sin alteraciones' si está normal.</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">Piel y Mucosa</label>
                                        <input type="text" name="fisico_piel" id="formFisicoPiel" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:border-brand-500 focus:outline-none transition" placeholder="Sin alteraciones">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">Cabeza</label>
                                        <input type="text" name="fisico_cabeza" id="formFisicoCabeza" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:border-brand-500 focus:outline-none transition" placeholder="Sin alteraciones">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">Cuello</label>
                                        <input type="text" name="fisico_cuello" id="formFisicoCuello" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:border-brand-500 focus:outline-none transition" placeholder="Sin alteraciones">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">Tórax</label>
                                        <input type="text" name="fisico_torax" id="formFisicoTorax" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:border-brand-500 focus:outline-none transition" placeholder="Sin alteraciones">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">Abdomen</label>
                                        <input type="text" name="fisico_abdomen" id="formFisicoAbdomen" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:border-brand-500 focus:outline-none transition" placeholder="Sin alteraciones">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">Extremidades</label>
                                        <input type="text" name="fisico_extremidades" id="formFisicoExtremidades" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:border-brand-500 focus:outline-none transition" placeholder="Sin alteraciones">
                                    </div>
                                    <div class="sm:col-span-2 lg:col-span-3">
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">Neurológico</label>
                                        <input type="text" name="fisico_neurologico" id="formFisicoNeurologico" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:border-brand-500 focus:outline-none transition" placeholder="Activo, consciente y orientado">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Accordion Item 5: Diagnóstico, Tratamiento, Reposo y Adjunto -->
                        <div class="border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                            <button type="button" onclick="toggleAccordion('acc-diagnostico')" class="w-full flex justify-between items-center px-4 py-3 bg-slate-50 hover:bg-slate-100 transition font-semibold text-xs sm:text-sm text-slate-700">
                                <span class="flex items-center gap-2"><i class="ph ph-prescription text-brand-600 text-base sm:text-lg"></i> 5. Diagnóstico, Tratamiento, Reposo y Anexos</span>
                                <i class="ph ph-caret-down text-slate-400 transition-transform duration-200" id="icon-acc-diagnostico"></i>
                            </button>
                            <div id="acc-diagnostico" class="p-4 sm:p-5 border-t border-slate-200 bg-white hidden space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">Impresión Diagnóstica (DX) *</label>
                                        <textarea name="diagnostico" required rows="2" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:border-brand-500 focus:outline-none transition resize-none" placeholder="Ej. Faringitis aguda, etc."></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">Plan de Tratamiento / Indicaciones *</label>
                                        <textarea name="plan_tratamiento" required rows="2" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:border-brand-500 focus:outline-none transition resize-none" placeholder="Ej. Analgésicos cada 8 horas, abundante líquido..."></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">Laboratorios u Exámenes Complementarios</label>
                                        <textarea name="laboratorios" rows="1" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:border-brand-500 focus:outline-none transition resize-none" placeholder="Ej. Examen de orina, química sanguínea..."></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">Pendiente / Observaciones</label>
                                        <textarea name="pendiente" rows="1" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:border-brand-500 focus:outline-none transition resize-none" placeholder="Ej. Traer resultados en la próxima cita..."></textarea>
                                    </div>
                                </div>

                                <!-- Reposo Médico -->
                                <div class="border-t border-slate-100 pt-3">
                                    <h5 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2 flex items-center gap-1"><i class="ph ph-calendar"></i> Configurar Reposo Médico (Opcional)</h5>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-semibold text-slate-600 mb-1">Inicio de Reposo</label>
                                            <input type="date" name="inicio_reposo" id="formInicioReposo" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:border-brand-500 focus:outline-none transition">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-slate-600 mb-1">Fin de Reposo</label>
                                            <input type="date" name="fin_reposo" id="formFinReposo" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:border-brand-500 focus:outline-none transition">
                                        </div>
                                    </div>
                                </div>

                                <!-- Archivo Adjunto -->
                                <div class="border-t border-slate-100 pt-3">
                                    <h5 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2 flex items-center gap-1"><i class="ph ph-paperclip"></i> Anexar Informe Externo o Exámenes (Opcional)</h5>
                                    <div class="border-2 border-dashed border-slate-300 rounded-xl p-4 flex flex-col items-center justify-center bg-slate-50 hover:bg-slate-100 transition relative">
                                        <input type="file" name="archivo" id="archivoAdjunto" accept=".jpg,.jpeg,.png,.pdf" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="validarArchivo(this)">
                                        <i class="ph ph-cloud-arrow-up text-xl text-slate-400 mb-1"></i>
                                        <p class="text-xs font-medium text-slate-600 text-center">Haz clic o arrastra un archivo aquí</p>
                                        <p class="text-[10px] text-slate-400 mt-0.5 text-center">Formatos soportados: JPG, PNG, PDF (Máx. 10MB)</p>
                                        <div id="fileError" class="mt-2 text-[10px] text-red-500 font-medium hidden"></div>
                                        <div id="fileName" class="mt-2 text-[10px] text-brand-600 font-medium hidden"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
            
            <div class="px-5 py-4 border-t border-slate-100 bg-slate-50 rounded-b-2xl flex justify-end gap-3 shrink-0">
                <button onclick="closeModal()" class="px-4 py-2 text-xs sm:text-sm font-medium text-slate-600 hover:bg-slate-200 rounded-xl transition">Cancelar</button>
                <button type="submit" form="registroForm" id="btnGuardar" class="px-4 py-2 text-xs sm:text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 rounded-xl transition shadow-md flex items-center gap-1.5">
                    <i class="ph ph-floppy-disk"></i> Guardar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Agendamiento de Citas (Responsive) -->
    <div id="citaModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onclick="closeCitaModal()"></div>
        <div id="citaModalContent" class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md flex flex-col max-h-[90vh] modal-enter">
            <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50 rounded-t-2xl shrink-0">
                <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                    <i class="ph ph-calendar-plus text-amber-500"></i> Programar Cita
                </h3>
                <button onclick="closeCitaModal()" class="text-slate-400 hover:text-slate-600 hover:bg-slate-200 p-1.5 rounded-lg transition shrink-0">
                    <i class="ph ph-x text-lg"></i>
                </button>
            </div>
            
            <div class="p-5 overflow-y-auto space-y-4">
                <form id="citaForm" class="space-y-4">
                    <input type="hidden" name="paciente_id" id="citaPacienteId" value="0">
                    
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Cédula del Paciente *</label>
                        <input type="text" name="cedula" id="citaCedula" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:border-brand-500 transition" placeholder="Ej. V-12345678" onblur="buscarPacienteParaCita(this.value)">
                    </div>

                    <!-- Campos informativos si el paciente existe o se creará al vuelo -->
                    <div id="citaPacienteInfo" class="hidden p-3 bg-slate-50 border border-slate-100 rounded-xl space-y-1">
                        <p class="text-[10px] font-semibold text-brand-600">Paciente Encontrado:</p>
                        <p class="text-xs font-medium text-slate-700" id="citaPacienteNombreText">-</p>
                    </div>

                    <div id="citaNuevoPacienteCampos" class="hidden border border-amber-100 bg-amber-50/30 p-3.5 rounded-xl space-y-3">
                        <p class="text-[10px] font-semibold text-amber-600 flex items-center gap-1">
                            <i class="ph ph-warning-circle"></i> Paciente no registrado. Complete los datos:
                        </p>
                        <div>
                            <label class="block text-[9px] font-medium text-slate-500 mb-1">Nombres *</label>
                            <input type="text" name="nombres" id="citaNombres" class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs focus:border-brand-500 transition">
                        </div>
                        <div>
                            <label class="block text-[9px] font-medium text-slate-500 mb-1">Apellidos *</label>
                            <input type="text" name="apellidos" id="citaApellidos" class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs focus:border-brand-500 transition">
                        </div>
                        <div>
                            <label class="block text-[9px] font-medium text-slate-500 mb-1">Categoría Institucional *</label>
                            <select name="categoria" id="citaCategoria" class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs focus:border-brand-500 transition">
                                <option value="">Seleccione...</option>
                                <!-- Cargado por JS -->
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Fecha y Hora de la Cita *</label>
                        <input type="datetime-local" name="fecha_cita" required class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:border-brand-500 transition">
                    </div>
                </form>
            </div>
            
            <div class="px-5 py-4 border-t border-slate-100 bg-slate-50 rounded-b-2xl flex justify-end gap-3 shrink-0">
                <button onclick="closeCitaModal()" class="px-4 py-2 text-xs sm:text-sm font-medium text-slate-600 hover:bg-slate-200 rounded-xl transition">Cancelar</button>
                <button type="submit" form="citaForm" id="btnGuardarCita" class="px-4 py-2 text-xs sm:text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 rounded-xl transition shadow-md flex items-center gap-1.5">
                    <i class="ph ph-calendar-check"></i> Programar
                </button>
            </div>
        </div>
    </div>

    <!-- LÓGICA JAVASCRIPT -->
    <script>
        // Variables globales
        let pacientesList = [];
        let citasList = [];
        let currentTab = 'dashboard';
        let categoryChartInstance = null;
        let historyChartInstance = null;

        // Carga inicial
        document.addEventListener('DOMContentLoaded', () => {
            loadStats();
            loadCategorias();
            loadPacientes(); // Cargar pacientes en cache global
        });

        // Escapar HTML para evitar XSS
        function escapeHTML(str) {
            if (!str) return '';
            return str.replace(/[&<>'"]/g, 
                tag => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    "'": '&#39;',
                    '"': '&quot;'
                }[tag] || tag)
            );
        }

        // Toggles para Sidebar móvil con Transición Suave
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            const isOpen = sidebar.classList.contains('translate-x-0');
            
            if (isOpen) {
                sidebar.classList.remove('translate-x-0');
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            } else {
                sidebar.classList.add('translate-x-0');
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
            }
        }

        // Navegación de secciones (TABS)
        function switchTab(tabName) {
            currentTab = tabName;
            
            // Cerrar sidebar en móvil al cambiar de sección
            if(document.getElementById('sidebar').classList.contains('translate-x-0') && window.innerWidth < 768) {
                toggleMobileSidebar();
            }

            // Ocultar todas las secciones
            document.querySelectorAll('.tab-section').forEach(sec => sec.classList.add('hidden'));
            
            // Desactivar todos los links de navegación
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('bg-brand-700/50', 'text-white');
                link.classList.add('text-brand-100', 'hover:bg-white/5');
            });
            
            // Mostrar sección seleccionada
            document.getElementById('section-' + tabName).classList.remove('hidden');
            
            // Activar link seleccionado
            const activeLink = document.getElementById('link-' + tabName);
            if (activeLink) {
                activeLink.classList.add('bg-brand-700/50', 'text-white');
                activeLink.classList.remove('text-brand-100', 'hover:bg-white/5');
            }
            
            // Títulos de Header
            const titles = {
                'dashboard': 'Panel de Control',
                'pacientes': 'Gestión de Pacientes',
                'citas': 'Control de Citas Médicas',
                'reportes': 'Reportes de Morbilidad'
            };
            document.getElementById('headerTitle').textContent = titles[tabName] || 'Salud UNEFA';
            
            // Recargar sección correspondiente
            if (tabName === 'pacientes') {
                loadPacientes();
            } else if (tabName === 'citas') {
                loadCitas();
            } else if (tabName === 'dashboard') {
                loadStats();
            }
        }

        // Carga de Estadísticas
        async function loadStats() {
            try {
                let res = await fetch('api/get_stats.php');
                let json = await res.json();
                if(json.success) {
                    document.getElementById('statPacientes').textContent = json.data.total_pacientes;
                    document.getElementById('statCitas').textContent = json.data.citas_proximas;
                    document.getElementById('statConsultas').textContent = json.data.consultas_mes;
                    document.getElementById('statReposos').textContent = json.data.reposos_activos;
                    
                    // Renderizar gráficos si estamos en Dashboard
                    if (currentTab === 'dashboard') {
                        renderCharts(json.data);
                    }
                }
            } catch(e) { 
                console.error('Error stats:', e); 
            }
        }

        // Inicializador de Gráficos (Chart.js)
        function renderCharts(statsData) {
            // 1. Gráfico de Categorías (Doughnut)
            const catCtx = document.getElementById('categoryChart').getContext('2d');
            if (categoryChartInstance) categoryChartInstance.destroy();
            
            const catLabels = statsData.categorias.map(c => c.nombre);
            const catValues = statsData.categorias.map(c => parseInt(c.cantidad));
            
            categoryChartInstance = new Chart(catCtx, {
                type: 'doughnut',
                data: {
                    labels: catLabels,
                    datasets: [{
                        data: catValues,
                        backgroundColor: ['#2563eb', '#10b981', '#f59e0b', '#ef4444', '#6366f1'],
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { 
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                padding: 15,
                                font: { family: 'Inter', size: 10, weight: '500' }
                            }
                        }
                    },
                    cutout: '70%'
                }
            });
            
            // 2. Gráfico de Consultas Mensuales (Bar)
            const histCtx = document.getElementById('historyChart').getContext('2d');
            if (historyChartInstance) historyChartInstance.destroy();
            
            const histLabels = statsData.historial_mensual.map(h => h.mes_nombre);
            const histValues = statsData.historial_mensual.map(h => parseInt(h.cantidad));
            
            historyChartInstance = new Chart(histCtx, {
                type: 'bar',
                data: {
                    labels: histLabels,
                    datasets: [{
                        label: 'N° de Consultas',
                        data: histValues,
                        backgroundColor: 'rgba(37, 99, 235, 0.85)',
                        hoverBackgroundColor: '#2563eb',
                        borderRadius: 6,
                        barThickness: window.innerWidth < 640 ? 12 : 24
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { display: false } 
                    },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            grid: { color: '#f1f5f9' }, 
                            ticks: { 
                                stepSize: 1, 
                                font: { family: 'Inter', size: 9 } 
                            } 
                        },
                        x: { 
                            grid: { display: false },
                            ticks: { font: { family: 'Inter', size: 9 } }
                        }
                    }
                }
            });
        }

        // Carga de Categorías
        async function loadCategorias() {
            try {
                let res = await fetch('api/get_categorias.php');
                let json = await res.json();
                if(json.success) {
                    const filterSelect = document.getElementById('filterCategoria');
                    const formSelect = document.getElementById('formCategoria');
                    const formCitaSelect = document.getElementById('citaCategoria');
                    
                    let options = '';
                    json.data.forEach(cat => {
                        options += `<option value="${cat.id}">${escapeHTML(cat.nombre)}</option>`;
                    });

                    filterSelect.innerHTML = '<option value="">Todas las Categorías</option>' + options;
                    formSelect.innerHTML = '<option value="">Seleccione...</option>' + options;
                    formCitaSelect.innerHTML = '<option value="">Seleccione...</option>' + options;
                }
            } catch(e) { console.error('Error cats:', e); }
        }

        // Carga de Pacientes (API -> Cache global)
        async function loadPacientes() {
            document.getElementById('tableLoader').classList.remove('hidden');
            try {
                let res = await fetch('api/get_pacientes.php');
                let json = await res.json();
                if(json.success) {
                    pacientesList = json.data;
                    renderTable();
                }
            } catch(e) { 
                console.error('Error pacientes:', e); 
            } finally {
                document.getElementById('tableLoader').classList.add('hidden');
            }
        }

        // Renderizar Tabla de Pacientes (XSS Protegido e Inclusivo de break-points)
        function renderTable() {
            const tbody = document.getElementById('tableBody');
            const emptyState = document.getElementById('emptyState');
            
            const qCedula = document.getElementById('filterCedula').value.toLowerCase();
            const qCat = document.getElementById('filterCategoria').options[document.getElementById('filterCategoria').selectedIndex].text.toLowerCase();
            const isFilterCatActive = document.getElementById('filterCategoria').value !== "";

            tbody.innerHTML = '';
            let visibleCount = 0;

            pacientesList.forEach(p => {
                const cedulaMatch = p.cedula.toLowerCase().includes(qCedula);
                const catMatch = !isFilterCatActive || p.categoria.toLowerCase() === qCat;

                if (cedulaMatch && catMatch) {
                    visibleCount++;
                    const row = document.createElement('tr');
                    row.className = 'hover:bg-slate-50/60 cursor-pointer transition';
                    
                    // Al hacer clic en la fila, se abre el drawer con el historial clínico
                    row.onclick = () => viewPatientDetails(p.id);

                    row.innerHTML = `
                        <td class="px-4 sm:px-6 py-4 font-semibold text-slate-700 text-xs sm:text-sm">${escapeHTML(p.cedula)}</td>
                        <td class="px-4 sm:px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-brand-50 text-brand-600 flex items-center justify-center font-bold text-xs shrink-0">
                                    ${escapeHTML(p.nombres.charAt(0))}${escapeHTML(p.apellidos.charAt(0))}
                                </div>
                                <span class="font-medium text-slate-800 text-xs sm:text-sm truncate max-w-[120px] sm:max-w-none">${escapeHTML(p.nombres)} ${escapeHTML(p.apellidos)}</span>
                            </div>
                        </td>
                        <td class="px-4 sm:px-6 py-4 hidden md:table-cell">
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200/50">${escapeHTML(p.categoria)}</span>
                        </td>
                        <td class="px-4 sm:px-6 py-4 hidden sm:table-cell text-slate-500 text-xs sm:text-sm">${p.ultima_consulta ? p.ultima_consulta.split(' ')[0] : '<span class="text-slate-400 italic">Sin consultas</span>'}</td>
                        <td class="px-4 sm:px-6 py-4 text-right" onclick="event.stopPropagation()">
                            <div class="flex justify-end gap-1.5 sm:gap-2">
                                <button onclick="openModal('${escapeHTML(p.cedula)}', ${p.categoria_id}, '${escapeHTML(p.nombres)}', '${escapeHTML(p.apellidos)}')" class="p-1.5 hover:bg-brand-50 text-slate-400 hover:text-brand-600 rounded-lg transition" title="Registrar Consulta">
                                    <i class="ph ph-stethoscope text-base sm:text-lg"></i>
                                </button>
                                <button onclick="openCitaModal(${p.id}, '${escapeHTML(p.cedula)}', '${escapeHTML(p.nombres)} ${escapeHTML(p.apellidos)}')" class="p-1.5 hover:bg-amber-50 text-slate-400 hover:text-amber-500 rounded-lg transition" title="Programar Cita">
                                    <i class="ph ph-calendar-plus text-base sm:text-lg"></i>
                                </button>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(row);
                }
            });

            if (visibleCount === 0) emptyState.classList.remove('hidden');
            else emptyState.classList.add('hidden');
        }

        // Búsqueda Inteligente para la Consulta
        async function buscarPacientePorCedula(cedula) {
            if (!cedula) return;
            const cedulaLimpia = cedula.replace(/\s+/g, '').toUpperCase();
            
            // Buscar en nuestra lista cacheada local
            const paciente = pacientesList.find(p => p.cedula.replace(/\s+/g, '').toUpperCase() === cedulaLimpia);
            if (paciente) {
                try {
                    let res = await fetch(`api/get_paciente_detalle.php?id=${paciente.id}`);
                    let json = await res.json();
                    if (json.success) {
                        const p = json.data.paciente;
                        document.getElementById('formCategoria').value = p.categoria_id;
                        document.getElementById('formNombres').value = p.nombres;
                        document.getElementById('formApellidos').value = p.apellidos;
                        
                        // Rellenar campos adicionales de historia clínica
                        document.getElementById('formSexo').value = p.sexo || '';
                        document.getElementById('formTelefono').value = p.telefono || '';
                        document.getElementById('formLugarNacimiento').value = p.lugar_nacimiento || '';
                        document.getElementById('formFechaNacimiento').value = p.fecha_nacimiento || '';
                        document.getElementById('formNivelEducativo').value = p.nivel_educativo || '';
                        document.getElementById('formCarrera').value = p.carrera || '';
                        document.getElementById('formSemestre').value = p.semestre || '';
                        document.getElementById('formDireccion').value = p.direccion || '';
                        
                        document.getElementById('formAntecedenteCardiovascular').value = p.antecedente_cardiovascular || '';
                        document.getElementById('formAntecedenteOseo').value = p.antecedente_oseo || '';
                        document.getElementById('formAntecedenteRespiratorio').value = p.antecedente_respiratorio || '';
                        document.getElementById('formAntecedenteDigestivo').value = p.antecedente_digestivo || '';
                        document.getElementById('formAntecedenteEndocrino').value = p.antecedente_endocrino || '';
                        document.getElementById('formAntecedenteOtros').value = p.antecedente_otros || '';
                        document.getElementById('formAntecedenteQuirurgico').value = p.antecedente_quirurgico || '';
                        
                        document.getElementById('formGinecoMenarquia').value = p.gineco_menarquia || '';
                        document.getElementById('formGinecoSexarquia').value = p.gineco_sexarquia || '';
                        document.getElementById('formGinecoAco').value = p.gineco_aco || '';
                        document.getElementById('formGinecoGestas').value = p.gineco_gestas || '';
                        document.getElementById('formGinecoCitologia').value = p.gineco_citologia || '';
                        
                        document.getElementById('formTieneTatuajes').value = p.tiene_tatuajes || 'No';
                        document.getElementById('formCompromisoTatuajes').value = p.compromiso_tatuajes || 'Yo me comprometo con la institución a no realizarme tatuajes durante el desarrollo de mi carrera.';
                        
                        document.getElementById('formAntecedentePadre').value = p.antecedente_padre || '';
                        document.getElementById('formAntecedenteMadre').value = p.antecedente_madre || '';
                        document.getElementById('formAntecedenteHermanos').value = p.antecedente_hermanos || '';
                        document.getElementById('formAntecedenteHijos').value = p.antecedente_hijos || '';

                        // Efecto visual de autocompletado en el primer acordeón
                        const fields = ['formCategoria', 'formSexo', 'formNombres', 'formApellidos', 'formTelefono'];
                        fields.forEach(fid => {
                            const el = document.getElementById(fid);
                            if(el) {
                                el.classList.add('ring-2', 'ring-emerald-500/20', 'border-emerald-500');
                                setTimeout(() => {
                                    el.classList.remove('ring-2', 'ring-emerald-500/20', 'border-emerald-500');
                                }, 1500);
                            }
                        });
                    }
                } catch(e) {
                    console.error("Fallo al autocompletar paciente clínico", e);
                }
            }
        }

        // Control de secciones colapsables (Acordeón)
        function toggleAccordion(id) {
            document.querySelectorAll('[id^="acc-"]').forEach(item => {
                item.classList.add('hidden');
                const itemIcon = document.getElementById('icon-' + item.id);
                if (itemIcon) itemIcon.classList.remove('rotate-180');
            });
            const el = document.getElementById(id);
            const icon = document.getElementById('icon-' + id);
            if (el) {
                el.classList.remove('hidden');
                if (icon) icon.classList.add('rotate-180');
            }
        }

        // Búsqueda de Morbilidad por rango de fechas
        async function buscarMorbilidad() {
            const inicio = document.getElementById('morbilidadInicio').value;
            const fin = document.getElementById('morbilidadFin').value;
            if (!inicio || !fin) {
                Swal.fire('Campos requeridos', 'Por favor seleccione ambas fechas.', 'warning');
                return;
            }
            
            const tableBody = document.getElementById('morbilidadTableBody');
            const btnImprimir = document.getElementById('btnImprimirMorbilidad');
            
            tableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-8 text-brand-600">
                        <i class="ph ph-spinner-gap animate-spin text-2xl mb-2"></i>
                        <p>Buscando consultas...</p>
                    </td>
                </tr>
            `;
            btnImprimir.disabled = true;
            
            try {
                let res = await fetch(`api/get_morbilidad.php?fecha_inicio=${inicio}&fecha_fin=${fin}`);
                let json = await res.json();
                
                if (json.success) {
                    const consultas = json.data;
                    tableBody.innerHTML = '';
                    
                    if (consultas.length === 0) {
                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="7" class="text-center py-8 text-slate-400">No se encontraron consultas en este rango de fechas.</td>
                            </tr>
                        `;
                    } else {
                        consultas.forEach(c => {
                            let edad = 'N/A';
                            if (c.fecha_nacimiento) {
                                const nacimiento = new Date(c.fecha_nacimiento);
                                const circunstancia = new Date(c.fecha_circunstancia);
                                edad = circunstancia.getFullYear() - nacimiento.getFullYear();
                                const m = circunstancia.getMonth() - nacimiento.getMonth();
                                if (m < 0 || (m === 0 && circunstancia.getDate() < nacimiento.getDate())) {
                                    edad--;
                                }
                            }
                            
                            const row = document.createElement('tr');
                            row.className = 'border-b border-slate-100 hover:bg-slate-50 transition';
                            row.innerHTML = `
                                <td class="px-4 py-3 font-semibold text-slate-700">${escapeHTML(c.nombres + ' ' + c.apellidos)}</td>
                                <td class="px-4 py-3 text-slate-600">${escapeHTML(c.cedula)}</td>
                                <td class="px-4 py-3 text-slate-500">${escapeHTML(c.fecha_circunstancia)}</td>
                                <td class="px-4 py-3 text-slate-500">${escapeHTML(c.categoria)}</td>
                                <td class="px-4 py-3 text-slate-500">${escapeHTML(c.vital_peso_talla || 'N/A')}</td>
                                <td class="px-4 py-3 text-slate-500">${escapeHTML(c.vital_ta || 'N/A')}</td>
                                <td class="px-4 py-3 font-bold text-slate-800">${escapeHTML(c.diagnostico || c.resumen)}</td>
                            `;
                            tableBody.appendChild(row);
                        });
                        btnImprimir.disabled = false;
                    }
                } else {
                    Swal.fire('Error', json.message, 'error');
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="7" class="text-center py-8 text-rose-500">Error: ${escapeHTML(json.message)}</td>
                        </tr>
                    `;
                }
            } catch(e) {
                console.error(e);
                Swal.fire('Error', 'Hubo un error de conexión con el servidor.', 'error');
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-8 text-rose-500">Error al conectar con el servidor.</td>
                    </tr>
                `;
            }
        }
        
        function imprimirMorbilidadDirecta() {
            const inicio = document.getElementById('morbilidadInicio').value;
            const fin = document.getElementById('morbilidadFin').value;
            if (inicio && fin) {
                window.open(`api/print_morbilidad.php?fecha_inicio=${inicio}&fecha_fin=${fin}`, '_blank');
            }
        }

        // Historial Clínico del Paciente (Drawer)
        async function viewPatientDetails(id) {
            const drawer = document.getElementById('patientDrawer');
            drawer.classList.remove('hidden');
            void drawer.offsetWidth; // Reflow
            document.getElementById('drawerContent').classList.remove('translate-x-full');
            
            document.getElementById('drawerLoader').classList.remove('hidden');
            document.getElementById('drawerData').classList.add('hidden');
            
            try {
                let res = await fetch(`api/get_paciente_detalle.php?id=${id}`);
                let json = await res.json();
                
                if (json.success) {
                    const data = json.data;
                    document.getElementById('detNombre').textContent = `${data.paciente.nombres} ${data.paciente.apellidos}`;
                    document.getElementById('detCedula').textContent = data.paciente.cedula;
                    document.getElementById('detCategoria').textContent = data.paciente.categoria;
                    document.getElementById('detIniciales').textContent = `${data.paciente.nombres.charAt(0)}${data.paciente.apellidos.charAt(0)}`;
                    
                    // Configurar botones rápidos en el Drawer
                    document.getElementById('btnConsultaAlVuelo').onclick = () => {
                        closeDrawer();
                        openModal(data.paciente.cedula, data.paciente.categoria_id, data.paciente.nombres, data.paciente.apellidos);
                    };
                    document.getElementById('btnCitaAlVuelo').onclick = () => {
                        closeDrawer();
                        openCitaModal(data.paciente.id, data.paciente.cedula, `${data.paciente.nombres} ${data.paciente.apellidos}`);
                    };
                    document.getElementById('btnPrintHistoria').onclick = () => {
                        window.open(`api/print_historia.php?paciente_id=${data.paciente.id}`, '_blank');
                    };

                    // Renderizar Consultas
                    const detConsultas = document.getElementById('detConsultas');
                    detConsultas.innerHTML = '';
                    
                    if (data.consultas.length === 0) {
                        detConsultas.innerHTML = `
                            <div class="text-center py-8 text-slate-400 text-xs">
                                <i class="ph ph-stethoscope text-2xl mb-2 text-slate-300"></i>
                                <p>Este paciente no tiene consultas registradas.</p>
                            </div>
                        `;
                    } else {
                        data.consultas.forEach(c => {
                            let reposoHtml = '';
                            if (c.fecha_inicio_reposo && c.fecha_fin_reposo) {
                                const start = new Date(c.fecha_inicio_reposo);
                                const end = new Date(c.fecha_fin_reposo);
                                const days = Math.ceil(Math.abs(end - start) / (1000 * 60 * 60 * 24)) + 1;
                                reposoHtml = `
                                    <div class="mt-2 flex items-center gap-1.5 text-[10px] sm:text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-100 rounded-lg px-2.5 py-1.5 w-fit">
                                        <i class="ph ph-calendar-blank"></i>
                                        <span>Reposo: ${c.fecha_inicio_reposo} al ${c.fecha_fin_reposo} (${days} días)</span>
                                    </div>
                                `;
                            }

                            let archivosHtml = '';
                            if (c.archivos && c.archivos.length > 0) {
                                c.archivos.forEach(file => {
                                    const isPdf = file.tipo_archivo.includes('pdf');
                                    const iconClass = isPdf ? 'ph-file-pdf text-red-500' : 'ph-image text-blue-500';
                                    archivosHtml += `
                                        <a href="${file.ruta_archivo}" target="_blank" class="flex items-center gap-2 p-2 bg-slate-50 hover:bg-slate-100 border border-slate-200/60 rounded-xl text-[10px] font-medium text-slate-700 transition">
                                            <i class="ph ${iconClass} text-base shrink-0"></i>
                                            <span class="truncate max-w-[120px]">${file.ruta_archivo.split('/').pop()}</span>
                                            <i class="ph ph-download ml-auto text-slate-400"></i>
                                        </a>
                                    `;
                                });
                            }

                            const div = document.createElement('div');
                            div.className = 'p-4 sm:p-5 bg-white border border-slate-200/80 shadow-sm rounded-2xl space-y-3';
                            div.innerHTML = `
                                <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                                    <span class="text-[10px] font-semibold text-brand-600 bg-brand-50 border border-brand-100 px-2 py-0.5 rounded-full flex items-center gap-1">
                                        <i class="ph ph-calendar"></i> ${c.fecha_circunstancia}
                                    </span>
                                    <span class="text-[9px] font-medium text-slate-400">Reg: ${c.fecha_registro.split(' ')[0]}</span>
                                </div>
                                <p class="text-xs text-slate-600 leading-relaxed">${escapeHTML(c.resumen)}</p>
                                ${reposoHtml}
                                ${archivosHtml ? `<div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-2">${archivosHtml}</div>` : ''}
                                
                                <div class="flex flex-wrap gap-2 border-t border-slate-100 pt-2.5 mt-2">
                                    <button onclick="window.open('api/print_informe.php?consulta_id=${c.id}', '_blank')" class="flex items-center gap-1 px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-[10px] font-semibold text-slate-700 rounded-lg transition">
                                        <i class="ph ph-printer"></i> Imprimir Informe
                                    </button>
                                    ${c.fecha_inicio_reposo && c.fecha_fin_reposo ? `
                                        <button onclick="window.open('api/print_reposo.php?consulta_id=${c.id}', '_blank')" class="flex items-center gap-1 px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-[10px] font-semibold text-rose-700 rounded-lg border border-rose-100 transition">
                                            <i class="ph ph-prescription"></i> Imprimir Reposo
                                        </button>
                                    ` : ''}
                                </div>
                            `;
                            detConsultas.appendChild(div);
                        });
                    }

                    // Renderizar Citas
                    const detCitas = document.getElementById('detCitas');
                    detCitas.innerHTML = '';
                    
                    if (data.citas.length === 0) {
                        detCitas.innerHTML = `<p class="text-center py-4 text-slate-400 text-xs">Sin citas programadas.</p>`;
                    } else {
                        data.citas.forEach(cita => {
                            let badge = '';
                            if (cita.estado === 'Pendiente') badge = '<span class="text-[9px] font-bold bg-amber-50 text-amber-600 border border-amber-100 px-2 py-0.5 rounded-full">Pendiente</span>';
                            else if (cita.estado === 'Completada') badge = '<span class="text-[9px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-100 px-2 py-0.5 rounded-full">Completada</span>';
                            else badge = '<span class="text-[9px] font-bold bg-rose-50 text-rose-600 border border-rose-100 px-2 py-0.5 rounded-full">Cancelada</span>';
                            
                            // Formato fecha amigable
                            const fechaAmigable = cita.fecha_cita.replace('T', ' ');

                            const div = document.createElement('div');
                            div.className = 'flex justify-between items-center py-2';
                            div.innerHTML = `
                                <span class="text-xs font-medium text-slate-600 flex items-center gap-1">
                                    <i class="ph ph-clock"></i> ${fechaAmigable}
                                </span>
                                ${badge}
                            `;
                            detCitas.appendChild(div);
                        });
                    }

                    document.getElementById('drawerLoader').classList.add('hidden');
                    document.getElementById('drawerData').classList.remove('hidden');
                } else {
                    Swal.fire('Error', json.message, 'error');
                    closeDrawer();
                }
            } catch(e) {
                console.error(e);
                Swal.fire('Error', 'Hubo un fallo al recuperar el expediente.', 'error');
                closeDrawer();
            }
        }

        function closeDrawer() {
            document.getElementById('drawerContent').classList.add('translate-x-full');
            setTimeout(() => {
                document.getElementById('patientDrawer').classList.add('hidden');
            }, 300);
        }

        // LÓGICA DE CITAS (TAB 3)
        async function loadCitas() {
            document.getElementById('citasTableLoader').classList.remove('hidden');
            try {
                const estado = document.getElementById('filterCitasEstado').value;
                let res = await fetch(`api/get_citas.php?estado=${estado}`);
                let json = await res.json();
                if(json.success) {
                    citasList = json.data;
                    renderCitasTable();
                }
            } catch(e) {
                console.error('Error citas:', e);
            } finally {
                document.getElementById('citasTableLoader').classList.add('hidden');
            }
        }

        // Renderizado de Citas Adaptado a Pantallas Móviles
        function renderCitasTable() {
            const tbody = document.getElementById('citasTableBody');
            const emptyState = document.getElementById('citasEmptyState');
            const query = document.getElementById('filterCitasPaciente').value.toLowerCase();

            tbody.innerHTML = '';
            let visibleCount = 0;

            citasList.forEach(c => {
                const pacienteNombre = `${c.nombres} ${c.apellidos}`.toLowerCase();
                const cedulaMatch = c.cedula.toLowerCase().includes(query);
                const nombreMatch = pacienteNombre.includes(query);

                if (cedulaMatch || nombreMatch) {
                    visibleCount++;
                    const row = document.createElement('tr');
                    row.className = 'hover:bg-slate-50/60 transition';
                    
                    let statusBadge = '';
                    let actionButtons = '';
                    
                    if (c.estado === 'Pendiente') {
                        statusBadge = '<span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-600 border border-amber-200">Pendiente</span>';
                        actionButtons = `
                            <button onclick="cambiarEstadoCita(${c.id}, 'Completada')" class="p-1 hover:bg-emerald-50 text-slate-400 hover:text-emerald-600 rounded-lg transition" title="Marcar como Completada">
                                <i class="ph ph-check-circle text-lg sm:text-xl"></i>
                            </button>
                            <button onclick="cambiarEstadoCita(${c.id}, 'Cancelada')" class="p-1 hover:bg-rose-50 text-slate-400 hover:text-rose-600 rounded-lg transition" title="Cancelar Cita">
                                <i class="ph ph-x-circle text-lg sm:text-xl"></i>
                            </button>
                        `;
                    } else if (c.estado === 'Completada') {
                        statusBadge = '<span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-600 border border-emerald-200">Completada</span>';
                    } else {
                        statusBadge = '<span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-600 border border-rose-200">Cancelada</span>';
                    }

                    // Formateado compacto de fecha para pantallas pequeñas (soporta espacios o 'T' y recorta segundos)
                    const parts = c.fecha_cita.split(/[\sT]/);
                    const fechaParte = parts[0];
                    const horaParte = parts[1] ? parts[1].substring(0, 5) : '';

                    row.innerHTML = `
                        <td class="px-4 sm:px-6 py-4 text-xs sm:text-sm font-medium text-slate-700">
                            <div class="font-semibold">${fechaParte}</div>
                            <div class="text-[10px] text-slate-400 sm:text-xs">${horaParte}</div>
                        </td>
                        <td class="px-4 sm:px-6 py-4 hidden sm:table-cell font-semibold text-slate-700 text-xs sm:text-sm">${escapeHTML(c.cedula)}</td>
                        <td class="px-4 sm:px-6 py-4 font-medium text-slate-800 text-xs sm:text-sm">
                            <div class="truncate max-w-[100px] sm:max-w-none">${escapeHTML(c.nombres)} ${escapeHTML(c.apellidos)}</div>
                        </td>
                        <td class="px-4 sm:px-6 py-4 hidden lg:table-cell"><span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200/50">${escapeHTML(c.categoria)}</span></td>
                        <td class="px-4 sm:px-6 py-4">${statusBadge}</td>
                        <td class="px-4 sm:px-6 py-4 text-right">
                            <div class="flex justify-end gap-1.5 sm:gap-2">
                                ${actionButtons}
                            </div>
                        </td>
                    `;
                    tbody.appendChild(row);
                }
            });

            if (visibleCount === 0) emptyState.classList.remove('hidden');
            else emptyState.classList.add('hidden');
        }

        // Filtros Citas
        document.getElementById('filterCitasPaciente').addEventListener('input', renderCitasTable);
        document.getElementById('filterCitasEstado').addEventListener('change', loadCitas);

        // Cambiar Estado Cita
        async function cambiarEstadoCita(citaId, nuevoEstado) {
            const result = await Swal.fire({
                title: '¿Está seguro?',
                text: `Se cambiará el estado de la cita a "${nuevoEstado}".`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3b82f6',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Sí, cambiar',
                cancelButtonText: 'Cancelar'
            });

            if (result.isConfirmed) {
                try {
                    const formData = new FormData();
                    formData.append('cita_id', citaId);
                    formData.append('estado', nuevoEstado);
                    
                    let res = await fetch('api/update_cita_estado.php', {
                        method: 'POST',
                        body: formData
                    });
                    let json = await res.json();
                    
                    if (json.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Actualizado!',
                            text: json.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        loadCitas();
                    } else {
                        Swal.fire('Error', json.message, 'error');
                    }
                } catch(e) {
                    Swal.fire('Error', 'No se pudo actualizar la cita.', 'error');
                }
            }
        }

        // Búsqueda inteligente para el formulario de agendar citas
        function buscarPacienteParaCita(cedula) {
            if (!cedula) return;
            const cedulaLimpia = cedula.replace(/\s+/g, '').toUpperCase();
            const paciente = pacientesList.find(p => p.cedula.replace(/\s+/g, '').toUpperCase() === cedulaLimpia);
            
            const infoBox = document.getElementById('citaPacienteInfo');
            const nuevoBox = document.getElementById('citaNuevoPacienteCampos');
            
            if (paciente) {
                document.getElementById('citaPacienteId').value = paciente.id;
                document.getElementById('citaPacienteNombreText').textContent = `${paciente.nombres} ${paciente.apellidos} (${paciente.categoria})`;
                infoBox.classList.remove('hidden');
                nuevoBox.classList.add('hidden');
                
                // Desactivar requeridos del vuelo
                document.getElementById('citaNombres').required = false;
                document.getElementById('citaApellidos').required = false;
                document.getElementById('citaCategoria').required = false;
            } else {
                document.getElementById('citaPacienteId').value = 0;
                infoBox.classList.add('hidden');
                nuevoBox.classList.remove('hidden');
                
                // Activar requeridos del vuelo
                document.getElementById('citaNombres').required = true;
                document.getElementById('citaApellidos').required = true;
                document.getElementById('citaCategoria').required = true;
            }
        }

        // LÓGICA DE MODALES DE REGISTRO
        // Filtros de eventos para Pacientes
        document.getElementById('filterCedula').addEventListener('input', renderTable);
        document.getElementById('filterCategoria').addEventListener('change', renderTable);

        // Envío de Formulario Registro Consulta
        document.getElementById('registroForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnGuardar');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="ph ph-spinner animate-spin"></i> Guardando...';
            btn.disabled = true;

            const formData = new FormData(this);

            try {
                let res = await fetch('api/save_registro.php', {
                    method: 'POST',
                    body: formData
                });
                let json = await res.json();
                
                if (json.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Registrado!',
                        text: json.message,
                        confirmButtonColor: '#3b82f6'
                    });
                    closeModal();
                    // Recargar tablas y stats
                    loadStats();
                    loadPacientes();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de validación',
                        text: json.message,
                        confirmButtonColor: '#3b82f6'
                    });
                }
            } catch (error) {
                Swal.fire('Error', 'Hubo un error de conexión con el servidor', 'error');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });

        // Envío de Formulario Programar Cita
        document.getElementById('citaForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnGuardarCita');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="ph ph-spinner animate-spin"></i> Guardando...';
            btn.disabled = true;

            const formData = new FormData(this);

            try {
                let res = await fetch('api/save_cita.php', {
                    method: 'POST',
                    body: formData
                });
                let json = await res.json();
                
                if (json.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Cita Agendada!',
                        text: json.message,
                        confirmButtonColor: '#3b82f6'
                    });
                    closeCitaModal();
                    // Recargar stats y tabla
                    loadStats();
                    if(currentTab === 'citas') loadCitas();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de reserva',
                        text: json.message,
                        confirmButtonColor: '#3b82f6'
                    });
                }
            } catch (error) {
                Swal.fire('Error', 'Error al conectarse con el servidor para agendar.', 'error');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });

        // Modal Lógica - Consulta
        const modal = document.getElementById('patientModal');
        const modalContent = document.getElementById('modalContent');

        function openModal(cedula = '', categoriaId = '', nombres = '', apellidos = '') {
            modal.classList.remove('hidden');
            void modalContent.offsetWidth; // reflow
            modalContent.classList.remove('modal-enter');
            modalContent.classList.add('modal-enter-active');
            
            // Si viene pre-rellenado (desde el drawer o botón de fila)
            if (cedula) {
                document.getElementById('formCedula').value = cedula;
                document.getElementById('formCategoria').value = categoriaId;
                document.getElementById('formNombres').value = nombres;
                document.getElementById('formApellidos').value = apellidos;
                
                // Cargar el historial completo del paciente para rellenar campos clínicos
                buscarPacientePorCedula(cedula);
            }
            
            // Colocar por defecto la fecha de hoy en fecha de circunstancia
            const today = new Date().toISOString().split('T')[0];
            modal.querySelector('input[name="fecha_circunstancia"]').value = today;

            // Por defecto, abrir la primera pestaña (identificación) del acordeón
            toggleAccordion('acc-paciente');
            document.getElementById('formCompromisoTatuajes').value = 'Yo me comprometo con la institución a no realizarme tatuajes durante el desarrollo de mi carrera.';
        }

        function closeModal() {
            modalContent.classList.remove('modal-enter-active');
            modalContent.classList.add('modal-leave-active');
            setTimeout(() => {
                modal.classList.add('hidden');
                modalContent.classList.remove('modal-leave-active');
                modalContent.classList.add('modal-enter');
                document.getElementById('registroForm').reset();
                document.getElementById('fileError').classList.add('hidden');
                document.getElementById('fileName').classList.add('hidden');
            }, 200);
        }

        // Modal Lógica - Cita
        const citaModal = document.getElementById('citaModal');
        const citaModalContent = document.getElementById('citaModalContent');

        function openCitaModal(pacienteId = 0, cedula = '', nombreCompleto = '') {
            citaModal.classList.remove('hidden');
            void citaModalContent.offsetWidth; // reflow
            citaModalContent.classList.remove('modal-enter');
            citaModalContent.classList.add('modal-enter-active');
            
            if (pacienteId > 0) {
                document.getElementById('citaPacienteId').value = pacienteId;
                document.getElementById('citaCedula').value = cedula;
                document.getElementById('citaPacienteNombreText').textContent = nombreCompleto;
                document.getElementById('citaPacienteInfo').classList.remove('hidden');
                
                // Desactivar requeridos del vuelo
                document.getElementById('citaNombres').required = false;
                document.getElementById('citaApellidos').required = false;
                document.getElementById('citaCategoria').required = false;
            }
        }

        function closeCitaModal() {
            citaModalContent.classList.remove('modal-enter-active');
            citaModalContent.classList.add('modal-leave-active');
            setTimeout(() => {
                citaModal.classList.add('hidden');
                citaModalContent.classList.remove('modal-leave-active');
                citaModalContent.classList.add('modal-enter');
                document.getElementById('citaForm').reset();
                document.getElementById('citaPacienteInfo').classList.add('hidden');
                document.getElementById('citaNuevoPacienteCampos').classList.add('hidden');
                document.getElementById('citaPacienteId').value = 0;
            }, 200);
        }

        // Validación de Archivos (MIME/size)
        function validarArchivo(input) {
            const fileError = document.getElementById('fileError');
            const fileName = document.getElementById('fileName');
            const maxSize = 10 * 1024 * 1024;
            const validTypes = ['image/jpeg', 'image/png', 'application/pdf'];

            fileError.classList.add('hidden');
            fileName.classList.add('hidden');

            if (input.files && input.files[0]) {
                const file = input.files[0];
                if (file.size > maxSize) {
                    fileError.textContent = "El archivo excede el tamaño máximo permitido de 10MB.";
                    fileError.classList.remove('hidden');
                    input.value = "";
                    return;
                }
                if (!validTypes.includes(file.type)) {
                    fileError.textContent = "Formato no válido. Solo se permiten archivos JPG, PNG o PDF.";
                    fileError.classList.remove('hidden');
                    input.value = "";
                    return;
                }
                fileName.textContent = "Archivo seleccionado: " + file.name;
                fileName.classList.remove('hidden');
            }
        }
    </script>
</body>
</html>
