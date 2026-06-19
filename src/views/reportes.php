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

        <!-- Loader (compartido) -->
        <div id="morbilidadLoader" class="hidden py-16 flex flex-col items-center justify-center text-brand-600">
            <i class="ph ph-spinner-gap animate-spin text-4xl mb-2"></i>
            <span class="text-sm font-medium">Buscando consultas...</span>
        </div>

        <!-- Estado vacío / Inicial (compartido) -->
        <div id="morbilidadEmptyState" class="py-12 flex flex-col items-center justify-center text-slate-400">
            <i class="ph ph-file-text text-4xl mb-3 text-slate-300"></i>
            <p class="text-sm" id="morbilidadEmptyText">Seleccione un rango de fechas y haga clic en Buscar.</p>
        </div>

        <!-- Vista de TABLA para desktop (md+) -->
        <div id="morbilidadTableWrap" class="hidden md:block overflow-x-auto relative">
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
                    <!-- Datos inyectados por JS -->
                </tbody>
            </table>
        </div>

        <!-- Vista de TARJETAS para móvil (< md) -->
        <div class="md:hidden grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 bg-slate-50/30 rounded-xl" id="morbilidadCards" style="display: none;">
            <!-- Tarjetas inyectadas por JS -->
        </div>
    </div>
</section>
