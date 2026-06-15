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
