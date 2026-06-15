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
