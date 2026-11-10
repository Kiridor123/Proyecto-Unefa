<section id="section-citas" class="tab-section hidden" aria-label="Agenda de citas médicas">
    <div class="section-intro"><p>Organice las próximas atenciones, atienda a cada paciente desde su cita y dé seguimiento a las pendientes.</p><button type="button" onclick="openCitaModal()" class="btn btn-primary"><i class="ph ph-calendar-plus" aria-hidden="true"></i>Agendar cita</button></div>
    <section class="panel list-panel" aria-labelledby="citasTitle">
        <div class="panel-heading"><div><h2 id="citasTitle">Agenda de atención <span id="citasCount" class="result-count"></span></h2><p id="citasScopeText">Fecha, paciente, motivo y estado de cada cita</p></div><button type="button" class="icon-button" onclick="loadCitas()" aria-label="Actualizar citas"><i class="ph ph-arrow-clockwise" aria-hidden="true"></i></button></div>
        <div class="citas-views" role="group" aria-label="Periodo de la agenda">
            <button type="button" class="view-chip" data-citas-vista="hoy" aria-pressed="false">Hoy <span class="view-chip-count" id="citasResumenHoy"></span></button>
            <button type="button" class="view-chip" data-citas-vista="proximas" aria-pressed="true">Próximas <span class="view-chip-count" id="citasResumenProximas"></span></button>
            <button type="button" class="view-chip" data-citas-vista="vencidas" aria-pressed="false">Vencidas <span class="view-chip-count view-chip-alert" id="citasResumenVencidas"></span></button>
            <button type="button" class="view-chip" data-citas-vista="todas" aria-pressed="false">Todas</button>
        </div>
        <div class="toolbar citas-toolbar">
            <div class="field search-field"><label for="filterCitasPaciente">Buscar paciente</label><div class="input-icon"><i class="ph ph-magnifying-glass" aria-hidden="true"></i><input type="search" id="filterCitasPaciente" placeholder="Cédula, nombre o apellido" autocomplete="off" maxlength="100"></div></div>
            <div class="field date-filter-field"><label for="filterCitasFecha">Día específico</label><input type="date" id="filterCitasFecha"></div>
            <div class="field filter-field"><label for="filterCitasEstado">Estado de la cita</label><select id="filterCitasEstado"><option value="">Todos los estados</option><option value="Pendiente">Pendiente</option><option value="Completada">Completada</option><option value="Cancelada">Cancelada</option></select></div>
        </div>
        <div id="citasTableLoader" class="state-panel loading-state" role="status"><span class="loading-bar"></span><p>Cargando citas…</p></div>
        <div id="citasTableWrap" class="hidden md:block table-frame">
            <table id="citasTable" class="data-table citas-table"><caption class="sr-only">Citas médicas de la selección actual y sus estados</caption><thead><tr><th scope="col">Hora</th><th scope="col">Cédula</th><th scope="col">Paciente y motivo</th><th scope="col">Categoría</th><th scope="col">Estado</th><th scope="col" class="text-right">Acciones</th></tr></thead><tbody id="citasTableBody"></tbody></table>
        </div>
        <div class="record-list md:hidden" id="citasTableBodyCards"></div>
        <div id="citasEmptyState" class="state-panel hidden" role="status"><i class="ph ph-calendar-blank" aria-hidden="true"></i><h3>No hay citas para mostrar</h3><p>Cambie los filtros o agende una nueva atención.</p></div>
        <nav id="citasPager" class="list-pager hidden" aria-label="Páginas de la agenda"></nav>
    </section>
</section>
