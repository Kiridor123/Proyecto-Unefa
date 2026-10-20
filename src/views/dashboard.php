<section id="section-dashboard" class="tab-section" aria-label="Resumen de la actividad médica">
    <div class="section-intro">
        <p>Una vista de la actividad del servicio y el acceso a cada atención.</p>
        <div class="action-group">
            <button type="button" onclick="openCitaModal()" class="btn btn-secondary"><i class="ph ph-calendar-plus" aria-hidden="true"></i>Agendar cita</button>
            <button type="button" onclick="openModal()" class="btn btn-primary"><i class="ph ph-plus" aria-hidden="true"></i>Registrar consulta</button>
        </div>
    </div>
    <div id="dashboardError" class="state-panel state-error hidden" role="status"></div>
    <div class="metric-strip" aria-label="Indicadores del servicio">
        <div class="metric"><p>Pacientes registrados</p><strong id="statPacientes" class="num">—</strong><span>Expedientes en el sistema</span></div>
        <div class="metric"><p>Citas pendientes</p><strong id="statCitas" class="num">—</strong><span>Por atender</span></div>
        <div class="metric"><p>Consultas del mes</p><strong id="statConsultas" class="num">—</strong><span>Atenciones registradas</span></div>
        <div class="metric"><p>Reposos activos</p><strong id="statReposos" class="num">—</strong><span>Con vigencia actual</span></div>
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
            <a href="#citas" onclick="switchTab('citas'); return false;"><span><strong>Revisar la agenda</strong><small>Citas y estados de atención</small></span><i class="ph ph-arrow-up-right" aria-hidden="true"></i></a>
            <a href="#reportes" onclick="switchTab('reportes'); return false;"><span><strong>Preparar morbilidad</strong><small>Atenciones por periodo</small></span><i class="ph ph-arrow-up-right" aria-hidden="true"></i></a>
        </div>
    </section>
</section>
