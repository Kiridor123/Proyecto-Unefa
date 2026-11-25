let morbilidadRequestVersion = 0;
// Filtros cuyos resultados están en pantalla (null si no hay resultados).
let morbilidadResultFilters = null;
let morbilidadResultCount = 0;
let morbilidadInFlightFilters = null;
// Se incrementa cuando cambian los datos del servidor (p. ej. al guardar una consulta).
let morbilidadDataVersion = 0;
let morbilidadResultDataVersion = 0;
let morbilidadExporting = false;
let morbilidadSearchTimer = null;
let morbilidadCategoriasLoaded = false;

const MORBILIDAD_SEARCH_MAX = 100;
const MORBILIDAD_SEXOS_VALIDOS = new Set(['', 'Femenino', 'Masculino', 'Otro']);
const MORBILIDAD_REPOSO_VALIDOS = new Set(['', '1', '0']);

function sanitizeMorbilidadSearch(value) {
    return String(value ?? '')
        .replace(/[\t\r\n]+/g, ' ')
        .replace(/[\x00-\x1F\x7F]+/g, '')
        .replace(/\s+/g, ' ')
        .trim()
        .slice(0, MORBILIDAD_SEARCH_MAX);
}
function reportFilters() {
    const sexo = document.getElementById('morbilidadSexo')?.value || '';
    const conReposo = document.getElementById('morbilidadReposo')?.value || '';
    const categoriaId = document.getElementById('morbilidadCategoria')?.value || '';
    return {
        inicio: document.getElementById('morbilidadInicio')?.value || '',
        fin: document.getElementById('morbilidadFin')?.value || '',
        q: sanitizeMorbilidadSearch(document.getElementById('morbilidadBuscar')?.value),
        categoria_id: /^[1-9]\d{0,9}$/.test(categoriaId) ? categoriaId : '',
        sexo: MORBILIDAD_SEXOS_VALIDOS.has(sexo) ? sexo : '',
        con_reposo: MORBILIDAD_REPOSO_VALIDOS.has(conReposo) ? conReposo : ''
    };
}
function sameFilters(a, b) {
    return Boolean(a && b)
        && a.inicio === b.inicio
        && a.fin === b.fin
        && a.q === b.q
        && a.categoria_id === b.categoria_id
        && a.sexo === b.sexo
        && a.con_reposo === b.con_reposo;
}
function hasRefinementFilters(filters) {
    return Boolean(filters && (filters.q || filters.categoria_id || filters.sexo || filters.con_reposo));
}
function buildMorbilidadParams(filters) {
    const parameters = new URLSearchParams({ fecha_inicio: filters.inicio, fecha_fin: filters.fin });
    if (filters.q) parameters.set('q', filters.q);
    if (filters.categoria_id) parameters.set('categoria_id', filters.categoria_id);
    if (filters.sexo) parameters.set('sexo', filters.sexo);
    if (filters.con_reposo) parameters.set('con_reposo', filters.con_reposo);
    return parameters;
}
function currentMonthRange(offset = 0) {
    const today = new Date();
    const start = new Date(today.getFullYear(), today.getMonth() + offset, 1);
    const end = offset === 0 ? today : new Date(today.getFullYear(), today.getMonth() + offset + 1, 0);
    return { inicio: localDate(start), fin: localDate(end) };
}
function lastDaysRange(days) {
    const end = new Date();
    const start = new Date(end.getFullYear(), end.getMonth(), end.getDate() - Math.max(0, days - 1));
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
// Devuelve los errores de rango/filtros, o un objeto vacío si todo es válido.
function validateReportFilters(filters) {
    const errors = {};
    if (!filters.inicio) errors.fecha_inicio = 'Seleccione la fecha inicial.';
    if (!filters.fin) errors.fecha_fin = 'Seleccione la fecha final.';
    if (filters.inicio && !/^\d{4}-\d{2}-\d{2}$/.test(filters.inicio)) errors.fecha_inicio = 'Indique una fecha de inicio válida (AAAA-MM-DD).';
    if (filters.fin && !/^\d{4}-\d{2}-\d{2}$/.test(filters.fin)) errors.fecha_fin = 'Indique una fecha de fin válida (AAAA-MM-DD).';
    if (filters.inicio && filters.fin && !errors.fecha_inicio && !errors.fecha_fin) {
        if (filters.fin < filters.inicio) {
            errors.fecha_fin = 'La fecha final debe ser igual o posterior a la inicial.';
        } else {
            const start = new Date(`${filters.inicio}T00:00:00`);
            start.setFullYear(start.getFullYear() + 5);
            if (new Date(`${filters.fin}T00:00:00`) > start) errors.fecha_fin = 'El rango no puede superar 5 años.';
        }
    }
    const rawSearch = String(document.getElementById('morbilidadBuscar')?.value || '').trim();
    if (rawSearch.length > MORBILIDAD_SEARCH_MAX) {
        errors.q = `La búsqueda admite como máximo ${MORBILIDAD_SEARCH_MAX} caracteres.`;
    }
    return errors;
}

function selectedCategoriaLabel(categoriaId) {
    if (!categoriaId) return '';
    const select = document.getElementById('morbilidadCategoria');
    const option = select ? Array.from(select.options).find(opt => opt.value === String(categoriaId)) : null;
    return option ? option.textContent : `Categoría #${categoriaId}`;
}

function renderActiveFilters(filters) {
    const container = document.getElementById('morbilidadActiveFilters');
    if (!container) return;
    container.replaceChildren();
    if (!filters || !hasRefinementFilters(filters)) {
        setVisible(container, false);
        return;
    }
    const label = document.createElement('span');
    label.textContent = 'Filtros activos:';
    container.append(label);

    const addChip = (text, ariaLabel, onRemove) => {
        const chip = document.createElement('button');
        chip.type = 'button';
        chip.className = 'view-chip';
        chip.setAttribute('aria-pressed', 'true');
        chip.setAttribute('aria-label', ariaLabel);
        const span = document.createElement('span');
        span.textContent = text;
        const icon = document.createElement('i');
        icon.className = 'ph ph-x';
        icon.setAttribute('aria-hidden', 'true');
        chip.append(span, icon);
        chip.addEventListener('click', onRemove);
        container.append(chip);
    };

    if (filters.q) {
        addChip(`Búsqueda: «${filters.q}»`, 'Quitar filtro de búsqueda', () => {
            const input = document.getElementById('morbilidadBuscar');
            if (input) input.value = '';
            buscarMorbilidad({ forzar: true, silencioso: true });
        });
    }
    if (filters.categoria_id) {
        const catName = selectedCategoriaLabel(filters.categoria_id);
        addChip(`Categoría: ${catName}`, 'Quitar filtro de categoría', () => {
            const select = document.getElementById('morbilidadCategoria');
            if (select) select.value = '';
            buscarMorbilidad({ forzar: true, silencioso: true });
        });
    }
    if (filters.sexo) {
        addChip(`Sexo: ${filters.sexo}`, 'Quitar filtro de sexo', () => {
            const select = document.getElementById('morbilidadSexo');
            if (select) select.value = '';
            buscarMorbilidad({ forzar: true, silencioso: true });
        });
    }
    if (filters.con_reposo) {
        const reposoLabel = filters.con_reposo === '1' ? 'Con reposo' : 'Sin reposo';
        addChip(`Reposo: ${reposoLabel}`, 'Quitar filtro de reposo', () => {
            const select = document.getElementById('morbilidadReposo');
            if (select) select.value = '';
            buscarMorbilidad({ forzar: true, silencioso: true });
        });
    }
    setVisible(container, true, 'flex');
}

// Activa o desactiva Imprimir/Exportar, Limpiar filtros y el aviso de cambios sin aplicar.
function updateReportControls() {
    const current = reportFilters();
    const applied = morbilidadResultFilters !== null && sameFilters(current, morbilidadResultFilters);
    const busy = morbilidadInFlightFilters !== null;
    const disabled = !applied || busy || !morbilidadResultCount;
    const print = document.getElementById('btnImprimirMorbilidad');
    if (print) print.disabled = disabled;
    const exportButton = document.getElementById('btnExportarMorbilidad');
    if (exportButton) exportButton.disabled = disabled || morbilidadExporting;
    const clearButton = document.getElementById('btnLimpiarMorbilidad');
    if (clearButton) clearButton.disabled = !hasRefinementFilters(current);

    const unapplied = morbilidadResultFilters !== null && !applied;
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
    const filters = reportFilters();
    const fin = document.getElementById('morbilidadFin');
    if (fin) fin.min = filters.inicio || '1900-01-01';
    const errors = validateReportFilters(filters);
    if (filters.inicio && filters.fin && errors.fecha_fin) handleInlineErrors({ fecha_fin: errors.fecha_fin }, 'morbilidadForm');
    else clearInlineErrors('morbilidadForm');
    updateReportControls();
}
// FE-11: llamada por refreshClinicalViews al cambiar los datos. No vacía el reporte:
// lo marca como desactualizado y lo vuelve a consultar en silencio si está a la vista.
function invalidateMorbilidad() {
    morbilidadDataVersion++;
    if (morbilidadResultFilters && morbilidadVisible()) buscarMorbilidad({ silencioso: true });
}

function createTextCell(text, className = '') {
    const td = document.createElement('td');
    if (className) td.className = className;
    td.textContent = text;
    return td;
}

function renderMorbilidadRows(consultas) {
    const body = document.getElementById('morbilidadTableBody');
    const cards = document.getElementById('morbilidadCards');
    body.replaceChildren();
    cards?.replaceChildren();
    consultas.forEach(consulta => {
        const fullName = `${consulta.nombres ?? ''} ${consulta.apellidos ?? ''}`.trim() || 'Paciente';
        const diagnosis = consulta.diagnostico || consulta.resumen || 'Sin diagnóstico registrado';
        const hasReposo = Boolean(consulta.fecha_inicio_reposo && consulta.fecha_fin_reposo);

        const row = document.createElement('tr');
        const diagCell = document.createElement('td');
        diagCell.className = 'report-diagnosis';
        diagCell.textContent = diagnosis;
        if (hasReposo) {
            const meta = document.createElement('span');
            meta.className = 'table-secondary';
            meta.textContent = `Reposo: ${formatDate(consulta.fecha_inicio_reposo)} al ${formatDate(consulta.fecha_fin_reposo)}`;
            diagCell.append(meta);
        }
        row.append(
            createTextCell(fullName),
            createTextCell(String(consulta.cedula || '—')),
            createTextCell(formatDate(consulta.fecha_circunstancia)),
            createTextCell(String(consulta.categoria || '—')),
            createTextCell(String(consulta.vital_peso_talla || '—'), 'num'),
            createTextCell(String(consulta.vital_ta || '—'), 'num'),
            diagCell
        );
        body.append(row);

        if (cards) {
            const card = document.createElement('article');
            card.className = 'record-card';

            const identity = document.createElement('div');
            identity.className = 'record-identity';
            const who = document.createElement('div');
            const heading = document.createElement('h3');
            heading.textContent = fullName;
            const cedulaP = document.createElement('p');
            cedulaP.textContent = String(consulta.cedula || '');
            who.append(heading, cedulaP);
            const badge = document.createElement('span');
            badge.className = 'status-badge';
            badge.textContent = String(consulta.categoria || 'Sin categoría');
            identity.append(who, badge);

            const dl = document.createElement('dl');
            dl.className = 'record-meta report-record-details';
            const pairs = [
                ['Evaluación', formatDate(consulta.fecha_circunstancia)],
                ['Tensión arterial', String(consulta.vital_ta || 'Sin registro')],
                ['Peso y talla', String(consulta.vital_peso_talla || 'Sin registro')]
            ];
            if (hasReposo) {
                pairs.push(['Reposo', `${formatDate(consulta.fecha_inicio_reposo)} al ${formatDate(consulta.fecha_fin_reposo)}`]);
            }
            pairs.forEach(([dtText, ddText]) => {
                const wrap = document.createElement('div');
                const dt = document.createElement('dt');
                dt.textContent = dtText;
                const dd = document.createElement('dd');
                dd.textContent = ddText;
                wrap.append(dt, dd);
                dl.append(wrap);
            });

            const diagP = document.createElement('p');
            diagP.className = 'report-diagnosis';
            const strong = document.createElement('strong');
            strong.textContent = 'Diagnóstico';
            diagP.append(strong, document.createElement('br'), document.createTextNode(diagnosis));

            card.append(identity, dl, diagP);
            cards.append(card);
        }
    });
}
function setMorbilidadEmpty(title, text) {
    const heading = document.getElementById('morbilidadEmptyTitle');
    if (heading) heading.textContent = title;
    const copy = document.getElementById('morbilidadEmptyText');
    if (copy) copy.textContent = text;
}

async function loadMorbilidadCategorias() {
    const select = document.getElementById('morbilidadCategoria');
    if (!select) return;
    // Reutiliza opciones si ya fueron cargadas en el filtro de pacientes.
    const source = document.getElementById('filterCategoria');
    if (source && source.options.length > 1) {
        const current = select.value;
        select.replaceChildren(new Option('Todas las categorías', ''));
        Array.from(source.options).slice(1).forEach(opt => select.add(new Option(opt.textContent, opt.value)));
        select.value = current;
        morbilidadCategoriasLoaded = true;
        return;
    }
    if (morbilidadCategoriasLoaded) return;
    try {
        const json = await apiRequest('api/get_categorias.php', {}, 'morbilidadCategorias');
        const current = select.value;
        select.replaceChildren(new Option('Todas las categorías', ''));
        (Array.isArray(json.data) ? json.data : []).forEach(cat => select.add(new Option(cat.nombre, String(cat.id))));
        select.value = current;
        morbilidadCategoriasLoaded = true;
    } catch {
        // Silencioso: la consulta sigue operando sin filtro de categoría.
    }
}

function limpiarFiltrosMorbilidad() {
    clearTimeout(morbilidadSearchTimer);
    morbilidadSearchTimer = null;
    const search = document.getElementById('morbilidadBuscar');
    const categoria = document.getElementById('morbilidadCategoria');
    const sexo = document.getElementById('morbilidadSexo');
    const reposo = document.getElementById('morbilidadReposo');
    if (search) search.value = '';
    if (categoria) categoria.value = '';
    if (sexo) sexo.value = '';
    if (reposo) reposo.value = '';
    const current = reportFilters();
    if (Object.keys(validateReportFilters(current)).length) {
        setReportRange(currentMonthRange());
    }
    clearInlineErrors('morbilidadForm');
    updateReportControls();
    buscarMorbilidad({ forzar: true, silencioso: true });
}

/**
 * Consulta la morbilidad con las fechas y filtros activos.
 * - forzar: el usuario aplicó un filtro o pulsó Consultar; valida y siempre consulta.
 * - silencioso: refresco tras cambios de datos o escritura; conserva la tabla mientras carga.
 */
async function buscarMorbilidad(opciones = {}) {
    const { forzar = false, silencioso = false } = opciones || {};
    if (!vistaPuede('reportes.ver')) return;
    const body = document.getElementById('morbilidadTableBody');
    if (!body) return;
    clearTimeout(morbilidadSearchTimer);
    morbilidadSearchTimer = null;

    const filters = reportFilters();
    const errors = validateReportFilters(filters);
    if (Object.keys(errors).length) {
        if (forzar && !silencioso) {
            handleInlineErrors(errors, 'morbilidadForm');
            showNotification('warning', Object.values(errors)[0]);
        } else if (errors.q) {
            handleInlineErrors({ q: errors.q }, 'morbilidadForm');
        }
        return;
    }
    if (!forzar) {
        if (morbilidadInFlightFilters && sameFilters(morbilidadInFlightFilters, filters)) return;
        if (morbilidadResultFilters && !sameFilters(morbilidadResultFilters, filters)) return;
        if (morbilidadResultFilters && morbilidadResultDataVersion === morbilidadDataVersion) return;
    }
    clearInlineErrors('morbilidadForm');
    const version = ++morbilidadRequestVersion;
    const dataVersion = morbilidadDataVersion;
    const table = document.getElementById('morbilidadTableWrap');
    const cards = document.getElementById('morbilidadCards');
    const loader = document.getElementById('morbilidadLoader');
    const empty = document.getElementById('morbilidadEmptyState');
    const keepVisible = silencioso && morbilidadResultFilters !== null && morbilidadResultCount > 0;
    morbilidadInFlightFilters = filters;
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
        const parameters = buildMorbilidadParams(filters);
        const json = await apiRequest(`api/get_morbilidad.php?${parameters}`, {}, 'morbilidad');
        if (version !== morbilidadRequestVersion) return;
        const consultas = Array.isArray(json.data) ? json.data : [];
        renderMorbilidadRows(consultas);
        morbilidadResultFilters = filters;
        morbilidadResultCount = consultas.length;
        morbilidadResultDataVersion = dataVersion;
        renderActiveFilters(filters);
        setVisible(table, consultas.length > 0);
        setVisible(cards, consultas.length > 0);
        setVisible(empty, !consultas.length, 'flex');
        if (!consultas.length) {
            if (hasRefinementFilters(filters)) {
                setMorbilidadEmpty('Sin coincidencias en el periodo', 'No hay consultas que coincidan con la búsqueda o los filtros seleccionados. Pruebe otros términos o limpie los filtros.');
            } else {
                setMorbilidadEmpty('Sin atenciones en el periodo', 'No hay consultas registradas entre esas fechas. Pruebe otro rango.');
            }
        }
        const count = document.getElementById('morbilidadCount');
        if (count) {
            const suffix = hasRefinementFilters(filters) ? ' filtradas' : '';
            count.textContent = `${consultas.length.toLocaleString('es-VE')} ${consultas.length === 1 ? 'consulta' : 'consultas'}${suffix} · ${formatDate(filters.inicio)} al ${formatDate(filters.fin)}`;
        }
    } catch (error) {
        if (error.name === 'AbortError' || version !== morbilidadRequestVersion) return;
        if (error.status === 422 && error.errors && Object.keys(error.errors).length) {
            handleInlineErrors(error.errors, 'morbilidadForm');
            showNotification('warning', error.message);
            if (!keepVisible) {
                setMorbilidadEmpty('Revise los filtros', error.message);
                setVisible(empty, true, 'flex');
                morbilidadResultFilters = null;
                morbilidadResultCount = 0;
            }
        } else if (keepVisible) {
            showNotification('error', `No se pudo actualizar el reporte: ${error.message}`);
        } else {
            morbilidadResultFilters = null;
            morbilidadResultCount = 0;
            showBlockError('morbilidadError', loader, error.message, () => buscarMorbilidad({ forzar: true }));
        }
    } finally {
        if (version === morbilidadRequestVersion) {
            morbilidadInFlightFilters = null;
            setVisible(loader, false);
            document.getElementById('section-reportes')?.setAttribute('aria-busy', 'false');
            updateReportControls();
            // Si los datos cambiaron durante la consulta, se vuelve a pedir en silencio.
            if (morbilidadResultFilters && morbilidadResultDataVersion !== morbilidadDataVersion && morbilidadVisible()) buscarMorbilidad({ silencioso: true });
        }
    }
}
function appliedReportFilters() {
    const current = reportFilters();
    if (!morbilidadResultFilters || !sameFilters(current, morbilidadResultFilters)) { updateReportControls(); return null; }
    return current;
}
function imprimirMorbilidadDirecta() {
    if (document.getElementById('btnImprimirMorbilidad')?.disabled) return;
    const filters = appliedReportFilters();
    if (!filters) return;
    const parameters = buildMorbilidadParams(filters);
    window.open(`api/print_morbilidad.php?${parameters}`, '_blank', 'noopener,noreferrer');
}
function fileNameFromDisposition(header, fallback) {
    const match = /filename\*?=(?:UTF-8'')?"?([^";]+)"?/i.exec(header || '');
    if (!match) return fallback;
    try { return decodeURIComponent(match[1]); } catch { return match[1]; }
}
// FN-7: descarga el CSV con exactamente los mismos filtros aplicados.
async function exportarMorbilidadCSV() {
    const button = document.getElementById('btnExportarMorbilidad');
    if (!button || button.disabled || morbilidadExporting) return;
    const filters = appliedReportFilters();
    if (!filters) return;
    morbilidadExporting = true;
    const originalNodes = Array.from(button.childNodes);
    button.disabled = true;
    button.textContent = 'Exportando…';
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 60000);
    try {
        const parameters = buildMorbilidadParams(filters);
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
        const name = fileNameFromDisposition(response.headers.get('Content-Disposition'), `morbilidad_${filters.inicio}_${filters.fin}.csv`);
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
        button.replaceChildren(...originalNodes);
        updateReportControls();
    }
}

(function initReportes() {
    const form = document.getElementById('morbilidadForm');
    const inicio = document.getElementById('morbilidadInicio');
    const fin = document.getElementById('morbilidadFin');
    if (!form || !inicio || !fin) return;

    // UX-11: el periodo por defecto es el mes en curso.
    if (!inicio.value && !fin.value) setReportRange(currentMonthRange());
    inicio.max = localDate();

    form.addEventListener('submit', event => {
        event.preventDefault();
        buscarMorbilidad({ forzar: true });
    });
    document.getElementById('btnImprimirMorbilidad')?.addEventListener('click', imprimirMorbilidadDirecta);
    document.getElementById('btnExportarMorbilidad')?.addEventListener('click', exportarMorbilidadCSV);
    document.getElementById('btnLimpiarMorbilidad')?.addEventListener('click', limpiarFiltrosMorbilidad);

    for (const input of [inicio, fin]) input.addEventListener('change', marcarRangoSinAplicar);

    const searchInput = document.getElementById('morbilidadBuscar');
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            updateReportControls();
            clearTimeout(morbilidadSearchTimer);
            morbilidadSearchTimer = setTimeout(() => {
                buscarMorbilidad({ forzar: true, silencioso: true });
            }, 300);
        });
        searchInput.addEventListener('keydown', event => {
            if (event.key === 'Enter') {
                event.preventDefault();
                clearTimeout(morbilidadSearchTimer);
                buscarMorbilidad({ forzar: true });
            } else if (event.key === 'Escape' && searchInput.value) {
                event.preventDefault();
                searchInput.value = '';
                clearTimeout(morbilidadSearchTimer);
                buscarMorbilidad({ forzar: true, silencioso: true });
            }
        });
    }

    for (const id of ['morbilidadCategoria', 'morbilidadSexo', 'morbilidadReposo']) {
        document.getElementById(id)?.addEventListener('change', () => {
            updateReportControls();
            buscarMorbilidad({ forzar: true, silencioso: true });
        });
    }

    const shortcuts = {
        hoy: () => ({ inicio: localDate(), fin: localDate() }),
        semana: () => lastDaysRange(7),
        mes: () => currentMonthRange(),
        'mes-anterior': () => currentMonthRange(-1)
    };
    document.querySelectorAll('[data-periodo]').forEach(button => button.addEventListener('click', () => {
        const build = shortcuts[button.dataset.periodo];
        if (!build) return;
        setReportRange(build());
        buscarMorbilidad({ forzar: true });
    }));

    // Al mostrarse la pestaña se cargan categorías y se consulta si no hay resultados vigentes.
    const section = document.getElementById('section-reportes');
    if (section && typeof MutationObserver === 'function') {
        new MutationObserver(() => {
            if (morbilidadVisible()) {
                loadMorbilidadCategorias();
                buscarMorbilidad();
            }
        }).observe(section, { attributes: true, attributeFilter: ['class'] });
    }
    if (vistaPuede('reportes.ver')) loadMorbilidadCategorias();
    updateReportControls();
})();
