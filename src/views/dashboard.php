<section id="section-dashboard" class="tab-section" aria-label="Resumen de la actividad médica">
    <div class="section-intro">
        <p>Una vista de la actividad del servicio y el acceso a cada atención.</p>
        <div class="action-group">
            <button type="button" onclick="openCitaModal()" class="btn btn-secondary" data-permiso="citas.gestionar"><i class="ph ph-calendar-plus" aria-hidden="true"></i>Agendar cita</button>
            <button type="button" onclick="openModal()" class="btn btn-primary" data-permiso="clinica.editar"><i class="ph ph-plus" aria-hidden="true"></i>Registrar consulta</button>
            <button type="button" onclick="openModal('', '', '', '', { soloPaciente: true })" class="btn btn-primary" data-sin-permiso="clinica.editar" data-permiso="pacientes.editar"><i class="ph ph-user-plus" aria-hidden="true"></i>Registrar paciente</button>
        </div>
    </div>
    <div id="dashboardError" class="state-panel state-error hidden" role="status"></div>
    <div class="metric-strip" aria-label="Indicadores del servicio">
        <div class="metric"><p>Pacientes registrados</p><strong id="statPacientes" class="num">—</strong><span>Expedientes en el sistema</span></div>
        <div class="metric"><p>Citas de hoy</p><strong id="statCitasHoy" class="num">—</strong><span id="statCitasDetalle">Pendientes por atender</span></div>
        <div class="metric"><p>Consultas del mes</p><strong id="statConsultas" class="num">—</strong><span>Atenciones registradas</span></div>
        <div class="metric"><p>Reposos activos</p><strong id="statReposos" class="num">—</strong><span>Con vigencia actual</span></div>
    </div>
    <div class="today-grid" id="dashboardTodayGrid">
        <section class="panel today-panel" aria-labelledby="agendaHoyTitle">
            <div class="panel-heading"><div><h2 id="agendaHoyTitle">Agenda de hoy <span id="agendaHoyCount" class="result-count"></span></h2><p id="agendaHoyFecha">Citas pendientes de la jornada</p></div><button type="button" class="btn btn-text" onclick="mostrarCitasDe('hoy')">Ver agenda<i class="ph ph-arrow-right" aria-hidden="true"></i></button></div>
            <div id="agendaHoyLoader" class="state-panel state-compact loading-state" role="status"><span class="loading-bar"></span><p>Cargando la agenda de hoy…</p></div>
            <ol id="agendaHoyList" class="today-list hidden" aria-labelledby="agendaHoyTitle"></ol>
            <div id="agendaHoyEmpty" class="state-panel state-compact hidden" role="status"><i class="ph ph-calendar-check" aria-hidden="true"></i><h3>Sin citas pendientes para hoy</h3><p>Las citas de la jornada aparecerán aquí con la opción de atender.</p></div>
            <p id="agendaHoyMore" class="today-more hidden"></p>
        </section>
        <section class="panel today-panel" aria-labelledby="repososTitle" data-permiso="clinica.ver" id="repososPanel">
            <div class="panel-heading"><div><h2 id="repososTitle">Reposos por vencer <span id="repososCount" class="result-count"></span></h2><p>Terminan hoy o en los próximos 3 días</p></div></div>
            <div id="repososLoader" class="state-panel state-compact loading-state" role="status"><span class="loading-bar"></span><p>Cargando reposos…</p></div>
            <ul id="repososList" class="today-list hidden" aria-labelledby="repososTitle"></ul>
            <div id="repososEmpty" class="state-panel state-compact hidden" role="status"><i class="ph ph-bed" aria-hidden="true"></i><h3>Ningún reposo vence pronto</h3><p>Los reposos que terminen en los próximos 3 días aparecerán aquí.</p></div>
        </section>
    </div>
    <div class="section-heading"><div><h2>Actividad asistencial</h2><p id="dashboardUpdated">Consultando la información del servicio…</p></div><button type="button" class="btn btn-text" onclick="loadStats()"><i class="ph ph-arrow-clockwise" aria-hidden="true"></i>Actualizar</button></div>
    <div class="analytics-grid">
        <section class="panel chart-panel" aria-labelledby="historyChartTitle">
            <div class="panel-heading"><div><h3 id="historyChartTitle">Consultas por mes</h3><p>Historial de atenciones registradas</p></div><span class="chart-unit">Consultas</span></div>
            <div class="chart-frame"><canvas id="historyChart" role="img" aria-label="Número de consultas por mes"></canvas></div>
        </section>
        <section class="panel chart-panel" aria-labelledby="categoryChartTitle">
            <div class="panel-heading"><div><h3 id="categoryChartTitle">Pacientes por categoría</h3><p>Comunidad atendida por el servicio</p></div></div>
            <div class="chart-frame"><canvas id="categoryChart" role="img" aria-label="Número de pacientes por categoría institucional"></canvas></div>
        </section>
    </div>
    <section class="workflow-section" aria-labelledby="workflowTitle">
        <div class="section-heading"><div><h2 id="workflowTitle">Continúe la atención</h2><p>Acceda al expediente, a la agenda o al reporte de la jornada.</p></div></div>
        <div class="workflow-links">
            <a href="#pacientes" onclick="switchTab('pacientes'); return false;"><span><strong>Consultar expedientes</strong><small>Pacientes e historia clínica</small></span><i class="ph ph-arrow-up-right" aria-hidden="true"></i></a>
            <a href="#citas" onclick="mostrarCitasDe('proximas'); return false;"><span><strong>Revisar la agenda</strong><small>Citas y estados de atención</small></span><i class="ph ph-arrow-up-right" aria-hidden="true"></i></a>
            <a href="#reportes" onclick="switchTab('reportes'); return false;" data-permiso="reportes.ver"><span><strong>Preparar morbilidad</strong><small>Atenciones por periodo</small></span><i class="ph ph-arrow-up-right" aria-hidden="true"></i></a>
        </div>
    </section>
</section>
