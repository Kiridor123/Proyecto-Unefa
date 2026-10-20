let morbilidadRequestVersion = 0;
let morbilidadResultRange = null;

function reportRange() {
    return { inicio: document.getElementById('morbilidadInicio').value, fin: document.getElementById('morbilidadFin').value };
}
function invalidateMorbilidad() {
    morbilidadResultRange = null;
    activeRequests.get('morbilidad')?.abort();
    morbilidadRequestVersion++;
    document.getElementById('section-reportes')?.setAttribute('aria-busy', 'false');
    document.getElementById('btnImprimirMorbilidad').disabled = true;
    setVisible(document.getElementById('morbilidadLoader'), false);
    setVisible(document.getElementById('morbilidadTableWrap'), false);
    setVisible(document.getElementById('morbilidadCards'), false);
    setVisible(document.getElementById('morbilidadError'), false);
    setVisible(document.getElementById('morbilidadEmptyState'), true);
    const copy = document.getElementById('morbilidadEmptyText');
    if (copy) copy.textContent = 'Seleccione el período y consulte el reporte para ver los resultados actualizados.';
    const count = document.getElementById('morbilidadCount');
    if (count) count.textContent = 'Sin consulta de período';
}
async function buscarMorbilidad() {
    const range = reportRange();
    if (!range.inicio || !range.fin) {
        showNotification('warning', 'Seleccione la fecha inicial y la fecha final.');
        document.getElementById(!range.inicio ? 'morbilidadInicio' : 'morbilidadFin').focus();
        return;
    }
    if (range.fin < range.inicio) {
        showNotification('warning', 'La fecha final debe ser igual o posterior a la fecha inicial.');
        document.getElementById('morbilidadFin').focus();
        return;
    }
    const version = ++morbilidadRequestVersion;
    const body = document.getElementById('morbilidadTableBody');
    const table = document.getElementById('morbilidadTableWrap');
    const cards = document.getElementById('morbilidadCards');
    const loader = document.getElementById('morbilidadLoader');
    const empty = document.getElementById('morbilidadEmptyState');
    const print = document.getElementById('btnImprimirMorbilidad');
    if (!body || !print) return;
    morbilidadResultRange = null;
    print.disabled = true;
    setVisible(table, false);
    setVisible(cards, false);
    setVisible(empty, false);
    setVisible(document.getElementById('morbilidadError'), false);
    setVisible(loader, true, 'flex');
    document.getElementById('section-reportes')?.setAttribute('aria-busy', 'true');
    try {
        const parameters = new URLSearchParams({ fecha_inicio: range.inicio, fecha_fin: range.fin });
        const json = await apiRequest(`api/get_morbilidad.php?${parameters}`, {}, 'morbilidad');
        if (version !== morbilidadRequestVersion) return;
        const consultas = Array.isArray(json.data) ? json.data : [];
        body.replaceChildren();
        cards?.replaceChildren();
        consultas.forEach(consulta => {
            const fullName = `${consulta.nombres ?? ''} ${consulta.apellidos ?? ''}`.trim();
            const diagnosis = consulta.diagnostico || consulta.resumen || 'Sin diagnóstico registrado';
            const row = document.createElement('tr');
            row.innerHTML = `<td>${escapeHTML(fullName)}</td><td>${escapeHTML(consulta.cedula)}</td><td>${escapeHTML(formatDate(consulta.fecha_circunstancia))}</td><td>${escapeHTML(consulta.categoria)}</td><td>${escapeHTML(consulta.vital_peso_talla || '—')}</td><td>${escapeHTML(consulta.vital_ta || '—')}</td><td class="report-diagnosis">${escapeHTML(diagnosis)}</td>`;
            body.append(row);
            const card = document.createElement('article');
            card.className = 'record-card';
            card.innerHTML = `<div class="record-identity"><div><h3>${escapeHTML(fullName)}</h3><p>${escapeHTML(consulta.cedula)}</p></div><span class="status-badge">${escapeHTML(consulta.categoria)}</span></div><dl class="record-meta report-record-details"><div><dt>Evaluación</dt><dd>${escapeHTML(formatDate(consulta.fecha_circunstancia))}</dd></div><div><dt>Tensión arterial</dt><dd>${escapeHTML(consulta.vital_ta || 'Sin registro')}</dd></div><div><dt>Peso y talla</dt><dd>${escapeHTML(consulta.vital_peso_talla || 'Sin registro')}</dd></div></dl><p class="report-diagnosis"><strong>Diagnóstico</strong><br>${escapeHTML(diagnosis)}</p>`;
            cards?.append(card);
        });
        morbilidadResultRange = range;
        print.disabled = !consultas.length;
        setVisible(table, Boolean(consultas.length));
        setVisible(cards, Boolean(consultas.length));
        setVisible(empty, !consultas.length);
        const emptyText = document.getElementById('morbilidadEmptyText');
        if (emptyText) emptyText.textContent = 'No hay consultas registradas en este período. Pruebe otro rango de fechas.';
        const count = document.getElementById('morbilidadCount');
        if (count) count.textContent = `${consultas.length} ${consultas.length === 1 ? 'consulta' : 'consultas'} · ${formatDate(range.inicio)} al ${formatDate(range.fin)}`;
    } catch (error) {
        if (error.name !== 'AbortError') showBlockError('morbilidadError', loader, error.message, buscarMorbilidad);
    } finally {
        if (version === morbilidadRequestVersion) {
            setVisible(loader, false);
            document.getElementById('section-reportes')?.setAttribute('aria-busy', 'false');
        }
    }
}
function imprimirMorbilidadDirecta() {
    if (!morbilidadResultRange || document.getElementById('btnImprimirMorbilidad').disabled) return;
    const current = reportRange();
    if (current.inicio !== morbilidadResultRange.inicio || current.fin !== morbilidadResultRange.fin) { invalidateMorbilidad(); return; }
    const parameters = new URLSearchParams({ fecha_inicio: current.inicio, fecha_fin: current.fin });
    window.open(`api/print_morbilidad.php?${parameters}`, '_blank', 'noopener,noreferrer');
}
for (const id of ['morbilidadInicio', 'morbilidadFin']) document.getElementById(id)?.addEventListener('change', invalidateMorbilidad);
