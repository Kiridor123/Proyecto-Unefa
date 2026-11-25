<section id="section-reportes" class="tab-section hidden" aria-label="Reportes de morbilidad">
    <div class="section-intro"><p>Consulte las atenciones de un periodo, filtre por paciente, categoría o diagnóstico, prepare el formato institucional de morbilidad o exporte los datos a una hoja de cálculo.</p></div>
    <section class="panel report-filter-panel" aria-labelledby="reportFilterTitle">
        <div class="panel-heading"><div><h2 id="reportFilterTitle">Filtros y periodo de atención</h2><p>Ambas fechas se incluyen en la consulta (rango máximo de 5 años). Los filtros aplicados también se reflejan al imprimir o exportar CSV.</p></div><i class="ph ph-calendar-dots panel-symbol" aria-hidden="true"></i></div>
        <form id="morbilidadForm" novalidate>
            <div class="report-filter-form">
                <div class="field"><label for="morbilidadInicio">Fecha de inicio <span aria-hidden="true">*</span></label><input type="date" id="morbilidadInicio" name="fecha_inicio" required min="1900-01-01"></div>
                <div class="field"><label for="morbilidadFin">Fecha de fin <span aria-hidden="true">*</span></label><input type="date" id="morbilidadFin" name="fecha_fin" required min="1900-01-01"></div>
                <div class="action-group report-actions">
                    <button type="submit" class="btn btn-primary"><i class="ph ph-magnifying-glass" aria-hidden="true"></i>Consultar atenciones</button>
                    <button type="button" id="btnImprimirMorbilidad" disabled class="btn btn-secondary"><i class="ph ph-printer" aria-hidden="true"></i>Imprimir</button>
                    <button type="button" id="btnExportarMorbilidad" disabled class="btn btn-secondary"><i class="ph ph-file-csv" aria-hidden="true"></i>Exportar CSV</button>
                </div>
            </div>
            <div class="report-shortcuts" role="group" aria-label="Periodos frecuentes">
                <span>Periodos frecuentes:</span>
                <button type="button" class="view-chip" data-periodo="hoy">Hoy</button>
                <button type="button" class="view-chip" data-periodo="semana">Últimos 7 días</button>
                <button type="button" class="view-chip" data-periodo="mes">Mes en curso</button>
                <button type="button" class="view-chip" data-periodo="mes-anterior">Mes anterior</button>
            </div>
            <div class="toolbar citas-toolbar report-search-toolbar" role="search" aria-label="Búsqueda y filtros de morbilidad">
                <div class="field search-field">
                    <label for="morbilidadBuscar">Buscar en el periodo</label>
                    <div class="input-icon">
                        <i class="ph ph-magnifying-glass" aria-hidden="true"></i>
                        <input type="search" id="morbilidadBuscar" name="q" placeholder="Paciente, cédula, diagnóstico, motivo, tratamiento o carrera" autocomplete="off" maxlength="100" enterkeyhint="search">
                    </div>
                </div>
                <div class="field filter-field">
                    <label for="morbilidadCategoria">Categoría institucional</label>
                    <select id="morbilidadCategoria" name="categoria_id">
                        <option value="">Todas las categorías</option>
                    </select>
                </div>
                <div class="field report-select-sm">
                    <label for="morbilidadSexo">Sexo</label>
                    <select id="morbilidadSexo" name="sexo">
                        <option value="">Todos</option>
                        <option value="Femenino">Femenino</option>
                        <option value="Masculino">Masculino</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
                <div class="field report-select-md">
                    <label for="morbilidadReposo">Reposo médico</label>
                    <select id="morbilidadReposo" name="con_reposo">
                        <option value="">Todas las consultas</option>
                        <option value="1">Con reposo</option>
                        <option value="0">Sin reposo</option>
                    </select>
                </div>
                <div class="action-group">
                    <button type="button" id="btnLimpiarMorbilidad" class="btn btn-text" disabled><i class="ph ph-x-circle" aria-hidden="true"></i>Limpiar filtros</button>
                </div>
            </div>
        </form>
    </section>
    <section class="panel list-panel" aria-labelledby="morbilidadTitle">
        <div class="panel-heading"><div><h2 id="morbilidadTitle">Atenciones del periodo <span id="morbilidadCount" class="result-count" aria-live="polite"></span></h2><p>Datos clínicos registrados en las consultas</p></div></div>
        <div id="morbilidadActiveFilters" class="report-shortcuts report-active-filters hidden" role="status" aria-live="polite"></div>
        <div id="morbilidadStale" class="stale-notice hidden" role="status"><i class="ph ph-warning-circle" aria-hidden="true"></i><p id="morbilidadStaleText">Los filtros o fechas cambiaron y aún no se aplican. Los resultados corresponden a la consulta anterior.</p><button type="submit" form="morbilidadForm" class="btn btn-secondary">Aplicar filtros</button></div>
        <div id="morbilidadLoader" class="state-panel loading-state hidden" role="status"><span class="loading-bar"></span><p>Consultando atenciones…</p></div>
        <div id="morbilidadEmptyState" class="state-panel" role="status"><i class="ph ph-file-text" aria-hidden="true"></i><h3 id="morbilidadEmptyTitle">Prepare el reporte del periodo</h3><p id="morbilidadEmptyText">Seleccione las fechas y pulse Consultar atenciones.</p></div>
        <div id="morbilidadResults">
            <div id="morbilidadTableWrap" class="hidden md:block table-frame">
                <table id="morbilidadTable" class="data-table report-table"><caption class="sr-only">Consultas médicas del periodo seleccionado</caption><thead><tr><th scope="col">Paciente</th><th scope="col">Cédula</th><th scope="col">Fecha</th><th scope="col">Categoría</th><th scope="col" class="num">Peso / talla</th><th scope="col" class="num">TA</th><th scope="col">Diagnóstico</th></tr></thead><tbody id="morbilidadTableBody"></tbody></table>
            </div>
            <div class="record-list md:hidden" id="morbilidadCards" style="display: none;"></div>
        </div>
    </section>
</section>
