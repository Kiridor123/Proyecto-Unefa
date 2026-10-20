<aside id="sidebar" class="app-sidebar -translate-x-full md:translate-x-0" aria-label="Navegación principal">
    <div class="sidebar-brand">
        <a href="#dashboard" onclick="switchTab('dashboard'); return false;" class="brand-lockup" aria-label="Salud UNEFA, ir al resumen">
            <svg class="brand-mark" width="34" height="34" viewBox="0 0 34 34" fill="none" aria-hidden="true">
                <path d="M12 4.5h10V12h7.5v10H22v7.5H12V22H4.5V12H12V4.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                <path d="M9 17h4l2-4 4 8 2-4h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span><strong>Salud UNEFA</strong><small>Gestión médica</small></span>
        </a>
        <button type="button" class="icon-button sidebar-close" onclick="toggleMobileSidebar()" aria-label="Cerrar menú"><i class="ph ph-x" aria-hidden="true"></i></button>
    </div>
    <nav class="app-nav" aria-label="Áreas de atención">
        <p class="nav-group-label">Espacio de atención</p>
        <a href="#dashboard" id="link-dashboard" onclick="switchTab('dashboard'); return false;" class="nav-link is-active" aria-current="page"><i class="ph ph-squares-four" aria-hidden="true"></i><span>Resumen</span></a>
        <a href="#pacientes" id="link-pacientes" onclick="switchTab('pacientes'); return false;" class="nav-link"><i class="ph ph-users" aria-hidden="true"></i><span>Pacientes</span></a>
        <a href="#citas" id="link-citas" onclick="switchTab('citas'); return false;" class="nav-link"><i class="ph ph-calendar-blank" aria-hidden="true"></i><span>Citas médicas</span></a>
        <a href="#reportes" id="link-reportes" onclick="switchTab('reportes'); return false;" class="nav-link"><i class="ph ph-file-text" aria-hidden="true"></i><span>Morbilidad</span></a>
    </nav>
    <div class="sidebar-note"><i class="ph ph-stethoscope" aria-hidden="true"></i><p>Atención universitaria<span>Expedientes, consultas y seguimiento.</span></p></div>
    <div class="sidebar-footer"><span>UNEFA</span><span>Salud universitaria</span></div>
</aside>
<div id="sidebarOverlay" class="sidebar-overlay hidden md:hidden" onclick="toggleMobileSidebar()" aria-hidden="true"></div>
