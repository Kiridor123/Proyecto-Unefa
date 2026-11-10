<section id="section-pacientes" class="tab-section hidden" aria-label="Directorio de pacientes">
    <div class="section-intro">
        <p>Busque un paciente por cédula, nombre o apellido para consultar su historia y continuar la atención.</p>
        <div class="action-group">
            <button type="button" onclick="openModal()" class="btn btn-primary" data-permiso="clinica.editar"><i class="ph ph-plus" aria-hidden="true"></i>Registrar consulta</button>
            <button type="button" onclick="openModal('', '', '', '', { soloPaciente: true })" class="btn btn-primary" data-sin-permiso="clinica.editar" data-permiso="pacientes.editar"><i class="ph ph-user-plus" aria-hidden="true"></i>Registrar paciente</button>
        </div>
    </div>
    <section class="panel list-panel" aria-labelledby="patientsTitle">
        <div class="panel-heading">
            <div><h2 id="patientsTitle">Directorio de pacientes <span id="patientsCount" class="result-count" aria-live="polite"></span></h2><p>Identificación, categoría e historial de consultas</p></div>
            <button type="button" class="icon-button" onclick="loadPacientes()" aria-label="Actualizar pacientes"><i class="ph ph-arrow-clockwise" aria-hidden="true"></i></button>
        </div>
        <form class="toolbar" role="search" onsubmit="buscarPacientes(); return false;">
            <div class="field search-field"><label for="filterCedula">Buscar paciente</label><div class="input-icon"><i class="ph ph-magnifying-glass" aria-hidden="true"></i><input type="search" id="filterCedula" placeholder="Cédula, nombre o apellido" autocomplete="off" maxlength="100" enterkeyhint="search"></div></div>
            <div class="field filter-field"><label for="filterCategoria">Categoría institucional</label><select id="filterCategoria"><option value="">Todas las categorías</option></select></div>
        </form>
        <div id="tableLoader" class="state-panel loading-state" role="status"><span class="loading-bar"></span><p>Cargando pacientes…</p></div>
        <div id="pacientesTableWrap" class="table-frame hidden">
            <table id="patientsTable" class="data-table"><caption class="sr-only">Pacientes registrados y acceso a su historia clínica</caption><thead><tr><th scope="col">Cédula</th><th scope="col">Paciente</th><th scope="col">Categoría</th><th scope="col">Última consulta</th><th scope="col" class="text-right">Acciones</th></tr></thead><tbody id="tableBody"></tbody></table>
        </div>
        <div class="record-list hidden" id="tableBodyCards"></div>
        <div id="emptyState" class="state-panel hidden" role="status"><i class="ph ph-magnifying-glass" aria-hidden="true"></i><h3>No se encontraron pacientes</h3><p>Pruebe otra cédula o cambie la categoría seleccionada.</p></div>
        <nav id="pacientesPager" class="pager hidden" aria-label="Paginación de pacientes">
            <p id="pacientesPagerInfo" class="pager-info"></p>
            <div class="pager-actions">
                <button type="button" id="pacientesPrev" class="btn btn-secondary" onclick="cambiarPaginaPacientes(-1)"><i class="ph ph-caret-left" aria-hidden="true"></i>Anterior</button>
                <button type="button" id="pacientesNext" class="btn btn-secondary" onclick="cambiarPaginaPacientes(1)">Siguiente<i class="ph ph-caret-right" aria-hidden="true"></i></button>
            </div>
        </nav>
    </section>
</section>
