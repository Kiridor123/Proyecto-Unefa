let morbilidadRequestVersion = 0;
// Rango cuyos resultados están en pantalla (null si no hay resultados).
let morbilidadResultRange = null;
let morbilidadResultCount = 0;
let morbilidadInFlightRange = null;
// Se incrementa cuando cambian los datos del servidor (p. ej. al guardar una consulta).
let morbilidadDataVersion = 0;
let morbilidadResultDataVersion = 0;
let morbilidadExporting = false;

function reportRange() {
    return { inicio: document.getElementById('morbilidadInicio')?.value || '', fin: document.getElementById('morbilidadFin')?.value || '' };
}
function sameRange(a, b) {
    return Boolean(a && b) && a.inicio === b.inicio && a.fin === b.fin;
}
function currentMonthRange(offset = 0) {
    const today = new Date();
    const start = new Date(today.getFullYear(), today.getMonth() + offset, 1);
    const end = offset === 0 ? today : new Date(today.getFullYear(), today.getMonth() + offset + 1, 0);
    return { inicio: localDate(start), fin: localDate(end) };
}
function setReportRange(range) {
    const inicio = document.getElementById('morbilidadInicio');
    const fin = document.getElementById('morbilidadFin');
    if (!inicio || !fin) return;
    inicio.value = range.inicio;
    fin.value = range.fin;
    fin.min = range.inicio || '1900-01-01';
    clearInlineErrors('morbilidadForm');
}
function morbilidadVisible() {
    const section = document.getElementById('section-reportes');
    return Boolean(section) && !section.classList.contains('hidden');
}
// Devuelve los errores del rango, o un objeto vacío si es válido.
function validateReportRange(range) {
    const errors = {};
    if (!range.inicio) errors.fecha_inicio = 'Seleccione la fecha inicial.';
    if (!range.fin) errors.fecha_fin = 'Seleccione la fecha final.';
    if (range.inicio && range.fin && range.fin < range.inicio) errors.fecha_fin = 'La fecha final debe ser igual o posterior a la inicial.';
    if (range.inicio && range.fin && !errors.fecha_fin) {
        const start = new Date(`${range.inicio}T00:00:00`);
        start.setFullYear(start.getFullYear() + 5);
        if (new Date(`${range.fin}T00:00:00`) > start) errors.fecha_fin = 'El rango no puede superar 5 años.';
    }
    return errors;
}

// Activa o desactiva Imprimir/Exportar y el aviso de fechas sin aplicar.
function updateReportControls() {
    const applied = morbilidadResultRange !== null && sameRange(reportRange(), morbilidadResultRange);
    const busy = morbilidadInFlightRange !== null;
    const disabled = !applied || busy || !morbilidadResultCount;
    const print = document.getElementById('btnImprimirMorbilidad');
    if (print) print.disabled = disabled;
    const exportButton = document.getElementById('btnExportarMorbilidad');
    if (exportButton) exportButton.disabled = disabled || morbilidadExporting;
    const unapplied = morbilidadResultRange !== null && !applied;
    setVisible(document.getElementById('morbilidadStale'), unapplied && !busy, 'flex');
    const results = document.getElementById('morbilidadResults');
    if (results) {
        results.classList.toggle('is-stale', unapplied);
        if (unapplied) results.setAttribute('aria-describedby', 'morbilidadStaleText');
        else results.removeAttribute('aria-describedby');
    }
}
// Las fechas cambiaron: se conservan los resultados y se marcan como «sin aplicar».
function marcarRangoSinAplicar() {
    const range = reportRange();
    const fin = document.getElementById('morbilidadFin');
    if (fin) fin.min = range.inicio || '1900-01-01';
    const errors = validateReportRange(range);
    if (range.inicio && range.fin && errors.fecha_fin) handleInlineErrors({ fecha_fin: errors.fecha_fin }, 'morbilidadForm');
    else clearInlineErrors('morbilidadForm');
    updateReportControls();
}
// FE-11: llamada por refreshClinicalViews al cambiar los datos. No vacía el reporte:
// lo marca como desactualizado y lo vuelve a consultar en silencio si está a la vista.
function invalidateMorbilidad() {
    morbilidadDataVersion++;
    if (morbilidadResultRange && morbilidadVisible()) buscarMorbilidad({ silencioso: true });
}

function renderMorbilidadRows(consultas) {
    const body = document.getElementById('morbilidadTableBody');
    const cards = document.getElementById('morbilidadCards');
    body.replaceChildren();
    cards?.replaceChildren();
    consultas.forEach(consulta => {
        const fullName = `${consulta.nombres ?? ''} ${consulta.apellidos ?? ''}`.trim();
        const diagnosis = consulta.diagnostico || consulta.resumen || 'Sin diagnóstico registrado';
        const row = document.createElement('tr');
        row.innerHTML = `<td>${escapeHTML(fullName)}</td><td>${escapeHTML(consulta.cedula)}</td><td>${escapeHTML(formatDate(consulta.fecha_circunstancia))}</td><td>${escapeHTML(consulta.categoria)}</td><td class="num">${escapeHTML(consulta.vital_peso_talla || '—')}</td><td class="num">${escapeHTML(consulta.vital_ta || '—')}</td><td class="report-diagnosis">${escapeHTML(diagnosis)}</td>`;
        body.append(row);
        const card = document.createElement('article');
        card.className = 'record-card';
        card.innerHTML = `<div class="record-identity"><div><h3>${escapeHTML(fullName)}</h3><p>${escapeHTML(consulta.cedula)}</p></div><span class="status-badge">${escapeHTML(consulta.categoria)}</span></div><dl class="record-meta report-record-details"><div><dt>Evaluación</dt><dd>${escapeHTML(formatDate(consulta.fecha_circunstancia))}</dd></div><div><dt>Tensión arterial</dt><dd>${escapeHTML(consulta.vital_ta || 'Sin registro')}</dd></div><div><dt>Peso y talla</dt><dd>${escapeHTML(consulta.vital_peso_talla || 'Sin registro')}</dd></div></dl><p class="report-diagnosis"><strong>Diagnóstico</strong><br>${escapeHTML(diagnosis)}</p>`;
        cards?.append(card);
    });
}
function setMorbilidadEmpty(title, text) {
    const heading = document.getElementById('morbilidadEmptyTitle');
    if (heading) heading.textContent = title;
    const copy = document.getElementById('morbilidadEmptyText');
    if (copy) copy.textContent = text;
}

/**
 * Consulta la morbilidad del rango de los campos.
 * - forzar: el usuario pulsó Consultar; valida y siempre consulta.
 * - silencioso: refresco tras cambios de datos; conserva la tabla mientras carga.
 * Sin opciones (al entrar en la pestaña) solo consulta si no hay resultados vigentes
 * y nunca aplica fechas que el usuario dejó sin aplicar.
 */
async function buscarMorbilidad(opciones = {}) {
    const { forzar = false, silencioso = false } = opciones || {};
    if (!vistaPuede('reportes.ver')) return;
    const body = document.getElementById('morbilidadTableBody');
    if (!body) return;
    const range = reportRange();
    const errors = validateReportRange(range);
    if (Object.keys(errors).length) {
        if (forzar) {
            handleInlineErrors(errors, 'morbilidadForm');
            showNotification('warning', Object.values(errors)[0]);
        }
        return;
    }
    if (!forzar) {
        if (morbilidadInFlightRange && sameRange(morbilidadInFlightRange, range)) return;
        if (morbilidadResultRange && !sameRange(morbilidadResultRange, range)) return;
        if (morbilidadResultRange && morbilidadResultDataVersion === morbilidadDataVersion) return;
    }
    clearInlineErrors('morbilidadForm');
    const version = ++morbilidadRequestVersion;
    const dataVersion = morbilidadDataVersion;
    const table = document.getElementById('morbilidadTableWrap');
    const cards = document.getElementById('morbilidadCards');
    const loader = document.getElementById('morbilidadLoader');
    const empty = document.getElementById('morbilidadEmptyState');
    const keepVisible = silencioso && sameRange(morbilidadResultRange, range) && morbilidadResultCount > 0;
    morbilidadInFlightRange = range;
    updateReportControls();
    setVisible(document.getElementById('morbilidadError'), false);
    if (!keepVisible) {
        setVisible(table, false);
        setVisible(cards, false);
        setVisible(empty, false);
        setVisible(loader, true, 'flex');
    }
    document.getElementById('section-reportes')?.setAttribute('aria-busy', 'true');
    try {
        const parameters = new URLSearchParams({ fecha_inicio: range.inicio, fecha_fin: range.fin });
        const json = await apiRequest(`api/get_morbilidad.php?${parameters}`, {}, 'morbilidad');
        if (version !== morbilidadRequestVersion) return;
        const consultas = Array.isArray(json.data) ? json.data : [];
        renderMorbilidadRows(consultas);
        morbilidadResultRange = range;
        morbilidadResultCount = consultas.length;
        morbilidadResultDataVersion = dataVersion;
        setVisible(table, consultas.length > 0);
        setVisible(cards, consultas.length > 0);
        setVisible(empty, !consultas.length, 'flex');
        if (!consultas.length) setMorbilidadEmpty('Sin atenciones en el periodo', 'No hay consultas registradas entre esas fechas. Pruebe otro rango.');
        const count = document.getElementById('morbilidadCount');
        if (count) count.textContent = `${consultas.length.toLocaleString('es-VE')} ${consultas.length === 1 ? 'consulta' : 'consultas'} · ${formatDate(range.inicio)} al ${formatDate(range.fin)}`;
    } catch (error) {
        if (error.name === 'AbortError' || version !== morbilidadRequestVersion) return;
        if (error.status === 422 && error.errors && Object.keys(error.errors).length) {
            handleInlineErrors(error.errors, 'morbilidadForm');
            showNotification('warning', error.message);
            if (!keepVisible) {
                setMorbilidadEmpty('Revise el periodo', error.message);
                setVisible(empty, true, 'flex');
                morbilidadResultRange = null;
                morbilidadResultCount = 0;
            }
        } else if (keepVisible) {
            showNotification('error', `No se pudo actualizar el reporte: ${error.message}`);
        } else {
            morbilidadResultRange = null;
            morbilidadResultCount = 0;
            showBlockError('morbilidadError', loader, error.message, () => buscarMorbilidad({ forzar: true }));
        }
    } finally {
        if (version === morbilidadRequestVersion) {
            morbilidadInFlightRange = null;
            setVisible(loader, false);
            document.getElementById('section-reportes')?.setAttribute('aria-busy', 'false');
            updateReportControls();
            // Si los datos cambiaron durante la consulta, se vuelve a pedir en silencio.
            if (morbilidadResultRange && morbilidadResultDataVersion !== morbilidadDataVersion && morbilidadVisible()) buscarMorbilidad({ silencioso: true });
        }
    }
}
function appliedReportRange() {
    const current = reportRange();
    if (!morbilidadResultRange || !sameRange(current, morbilidadResultRange)) { updateReportControls(); return null; }
    return current;
}
function imprimirMorbilidadDirecta() {
    if (document.getElementById('btnImprimirMorbilidad')?.disabled) return;
    const range = appliedReportRange();
    if (!range) return;
    const parameters = new URLSearchParams({ fecha_inicio: range.inicio, fecha_fin: range.fin });
    window.open(`api/print_morbilidad.php?${parameters}`, '_blank', 'noopener,noreferrer');
}
function fileNameFromDisposition(header, fallback) {
    const match = /filename\*?=(?:UTF-8'')?"?([^";]+)"?/i.exec(header || '');
    if (!match) return fallback;
    try { return decodeURIComponent(match[1]); } catch { return match[1]; }
}
// FN-7: descarga el CSV del rango aplicado con un enlace temporal.
async function exportarMorbilidadCSV() {
    const button = document.getElementById('btnExportarMorbilidad');
    if (!button || button.disabled || morbilidadExporting) return;
    const range = appliedReportRange();
    if (!range) return;
    morbilidadExporting = true;
    const original = button.innerHTML;
    button.disabled = true;
    button.textContent = 'Exportando…';
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 60000);
    try {
        const parameters = new URLSearchParams({ fecha_inicio: range.inicio, fecha_fin: range.fin });
        const response = await fetch(`api/export_morbilidad.php?${parameters}`, {
            credentials: 'same-origin', cache: 'no-store', signal: controller.signal,
            headers: { Accept: 'text/csv, application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (response.status === 401) { window.location.href = 'login.php?expirada=1'; return; }
        const type = response.headers.get('Content-Type') || '';
        if (!response.ok || type.includes('application/json')) {
            let message = 'No se pudo exportar el reporte. Intente de nuevo.';
            try { message = (await response.json()).message || message; } catch { /* respuesta sin JSON */ }
            throw new Error(message);
        }
        const blob = await response.blob();
        const name = fileNameFromDisposition(response.headers.get('Content-Disposition'), `morbilidad_${range.inicio}_${range.fin}.csv`);
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = name;
        link.hidden = true;
        document.body.append(link);
        link.click();
        link.remove();
        setTimeout(() => URL.revokeObjectURL(url), 10000);
        showNotification('success', `Se descargó ${name}.`);
    } catch (error) {
        const message = error.name === 'AbortError' ? 'La exportación tardó demasiado. Intente con un periodo más corto.'
            : error instanceof TypeError ? 'No se pudo conectar con el servidor. Revise su conexión e intente de nuevo.'
                : error.message;
        showNotification('error', message, 6000);
    } finally {
        clearTimeout(timeout);
        morbilidadExporting = false;
        button.innerHTML = original;
        updateReportControls();
    }
}

(function initReportes() {
    const inicio = document.getElementById('morbilidadInicio');
    const fin = document.getElementById('morbilidadFin');
    if (!inicio || !fin) return;
    // UX-11: el periodo por defecto es el mes en curso.
    if (!inicio.value && !fin.value) setReportRange(currentMonthRange());
    inicio.max = localDate();
    for (const input of [inicio, fin]) input.addEventListener('change', marcarRangoSinAplicar);
    const shortcuts = { hoy: () => ({ inicio: localDate(), fin: localDate() }), mes: () => currentMonthRange(), 'mes-anterior': () => currentMonthRange(-1) };
    document.querySelectorAll('[data-periodo]').forEach(button => button.addEventListener('click', () => {
        const build = shortcuts[button.dataset.periodo];
        if (!build) return;
        setReportRange(build());
        buscarMorbilidad({ forzar: true });
    }));
    // Al mostrarse la pestaña se consulta si no hay resultados vigentes (o si quedaron desactualizados).
    const section = document.getElementById('section-reportes');
    if (section && typeof MutationObserver === 'function') {
        new MutationObserver(() => { if (morbilidadVisible()) buscarMorbilidad(); }).observe(section, { attributes: true, attributeFilter: ['class'] });
    }
    updateReportControls();
})();
