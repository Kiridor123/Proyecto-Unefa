<section id="section-citas" class="tab-section hidden" aria-label="Agenda de citas médicas">
    <div class="section-intro"><p>Organice las próximas atenciones y dé seguimiento a cada cita.</p><button type="button" onclick="openCitaModal()" class="btn btn-primary"><i class="ph ph-calendar-plus" aria-hidden="true"></i>Agendar cita</button></div>
    <section class="panel list-panel" aria-labelledby="citasTitle">
        <div class="panel-heading"><div><h2 id="citasTitle">Agenda de atención <span id="citasCount" class="result-count"></span></h2><p>Fecha, paciente y estado de cada cita</p></div><button type="button" class="icon-button" onclick="loadCitas()" aria-label="Actualizar citas"><i class="ph ph-arrow-clockwise" aria-hidden="true"></i></button></div>
        <div class="toolbar">
            <div class="field search-field"><label for="filterCitasPaciente">Buscar paciente</label><div class="input-icon"><i class="ph ph-magnifying-glass" aria-hidden="true"></i><input type="search" id="filterCitasPaciente" placeholder="Cédula o nombre del paciente" autocomplete="off"></div></div>
            <div class="field filter-field"><label for="filterCitasEstado">Estado de la cita</label><select id="filterCitasEstado"><option value="">Todos los estados</option><option value="Pendiente">Pendiente</option><option value="Completada">Completada</option><option value="Cancelada">Cancelada</option></select></div>
        </div>
        <div id="citasTableLoader" class="state-panel loading-state" role="status"><span class="loading-bar"></span><p>Cargando citas…</p></div>
        <div id="citasTableWrap" class="hidden md:block table-frame">
            <table id="citasTable" class="data-table"><caption class="sr-only">Citas médicas registradas y sus estados</caption><thead><tr><th scope="col">Fecha y hora</th><th scope="col">Cédula</th><th scope="col">Paciente</th><th scope="col">Categoría</th><th scope="col">Estado</th><th scope="col" class="text-right">Acciones</th></tr></thead><tbody id="citasTableBody"></tbody></table>
        </div>
        <div class="record-list md:hidden" id="citasTableBodyCards"></div>
        <div id="citasEmptyState" class="state-panel hidden" role="status"><i class="ph ph-calendar-blank" aria-hidden="true"></i><h3>No hay citas para mostrar</h3><p>Cambie los filtros o agende una nueva atención.</p></div>
    </section>
</section>
