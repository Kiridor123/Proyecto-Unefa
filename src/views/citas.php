<!-- SECTION 3: CITAS -->
<section id="section-citas" class="tab-section flex flex-col gap-6 sm:gap-8 hidden">
    <div class="glass-panel rounded-2xl flex flex-col border border-slate-200 overflow-hidden shadow-sm">
        <!-- Filtros y acciones -->
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-3 bg-white/50">
            <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                <div class="relative w-full sm:w-64">
                    <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" id="filterCitasPaciente" placeholder="Buscar por Cédula o Nombre..."
                        class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition shadow-sm">
                </div>
                <div class="relative w-full sm:min-w-[180px]">
                    <select id="filterCitasEstado" class="w-full pl-4 pr-10 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-brand-500 appearance-none shadow-sm transition">
                        <option value="">Todos los Estados</option>
                        <option value="Pendiente">Pendiente</option>
                        <option value="Completada">Completada</option>
                        <option value="Cancelada">Cancelada</option>
                    </select>
                    <i class="ph ph-caret-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                </div>
            </div>
            <button onclick="openCitaModal()" class="w-full sm:w-auto flex items-center justify-center gap-2 px-5 py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-sm font-medium rounded-xl transition shadow-sm shrink-0">
                <i class="ph ph-calendar-plus text-lg"></i> Agendar Cita
            </button>
        </div>

        <!-- Loader (visible por defecto, JS lo oculta) -->
        <div id="citasTableLoader" class="py-16 flex flex-col items-center justify-center text-amber-500">
            <i class="ph ph-spinner-gap animate-spin text-4xl mb-2"></i>
            <span class="text-sm font-medium">Cargando citas...</span>
        </div>

        <!-- Vista de TABLA para desktop (md+) -->
        <div id="citasTableWrap" class="hidden md:block overflow-x-auto">
            <table class="w-full text-left border-collapse" id="citasTable">
                <thead>
                    <tr class="bg-slate-50/80 text-xs uppercase text-slate-500 font-semibold border-b border-slate-200">
                        <th class="px-6 py-4">Fecha y Hora</th>
                        <th class="px-6 py-4">Cédula</th>
                        <th class="px-6 py-4">Paciente</th>
                        <th class="px-6 py-4 hidden lg:table-cell">Categoría</th>
                        <th class="px-6 py-4">Estado</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-slate-100 bg-white/30" id="citasTableBody">
                    <!-- Datos inyectados por JS -->
                </tbody>
            </table>
        </div>

        <!-- Vista de TARJETAS para móvil (< md) -->
        <div class="md:hidden grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 bg-slate-50/30" id="citasTableBodyCards">
            <!-- Tarjetas inyectadas por JS -->
        </div>

        <!-- Estado vacío -->
        <div id="citasEmptyState" class="hidden flex-col items-center justify-center py-12 text-slate-400">
            <i class="ph ph-calendar-blank text-4xl mb-3 text-slate-300"></i>
            <p class="text-sm">No se encontraron citas agendadas.</p>
        </div>
    </div>
</section>
