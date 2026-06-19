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
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3 pb-3">
                    <button id="btnConsultaAlVuelo" class="flex items-center justify-center gap-1.5 py-2.5 px-3 bg-brand-50 hover:bg-brand-100 text-brand-700 text-xs font-semibold rounded-xl border border-brand-100 transition shadow-sm min-h-[44px]">
                        <i class="ph ph-stethoscope text-base"></i> Nueva Consulta
                    </button>
                    <button id="btnCitaAlVuelo" class="flex items-center justify-center gap-1.5 py-2.5 px-3 bg-amber-50 hover:bg-amber-100 text-amber-700 text-xs font-semibold rounded-xl border border-amber-100 transition shadow-sm min-h-[44px]">
                        <i class="ph ph-calendar-plus text-base"></i> Programar Cita
                    </button>
                </div>
                <div class="pb-4 border-b border-slate-100">
                    <button id="btnPrintHistoria" class="w-full flex items-center justify-center gap-1.5 py-2.5 px-3 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition shadow-sm min-h-[44px]">
                        <i class="ph ph-printer text-base"></i> Imprimir Historia Clínica de Ingreso
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
