let statsRequestVersion = 0;
let statsLastUpdated = '';

async function loadStats() {
    const version = ++statsRequestVersion;
    const section = document.getElementById('section-dashboard');
    section?.setAttribute('aria-busy', 'true');
    setVisible(document.getElementById('dashboardError'), false);
    const updated = document.getElementById('dashboardUpdated');
    if (updated) updated.textContent = statsLastUpdated ? `Actualizando · última lectura ${statsLastUpdated}` : 'Cargando datos de atención…';
    if (!statsLastUpdated) {
        ['statPacientes', 'statCitasHoy', 'statConsultas', 'statReposos'].forEach(id => {
            const element = document.getElementById(id);
            if (element) element.textContent = '—';
        });
        setTodayPanelState('agendaHoy', 'loading');
        setTodayPanelState('reposos', 'loading');
    }
    try {
        const json = await apiRequest('api/get_stats.php', {}, 'stats');
        if (version !== statsRequestVersion) return;
        const stats = json.data || {};
        const fields = { statPacientes: 'total_pacientes', statCitasHoy: 'citas_hoy', statConsultas: 'consultas_mes', statReposos: 'reposos_activos' };
        for (const [id, key] of Object.entries(fields)) {
            const element = document.getElementById(id);
            if (element) element.textContent = Number(stats[key] || 0).toLocaleString('es-VE');
        }
        const detail = document.getElementById('statCitasDetalle');
        if (detail) {
            const upcoming = Number(stats.citas_proximas || 0);
            detail.textContent = `${upcoming.toLocaleString('es-VE')} ${upcoming === 1 ? 'pendiente' : 'pendientes'} desde ahora`;
        }
        renderAgendaHoy(Array.isArray(stats.agenda_hoy) ? stats.agenda_hoy : [], Number(stats.citas_hoy || 0));
        renderRepososPorVencer(Array.isArray(stats.reposos_por_vencer) ? stats.reposos_por_vencer : []);
        renderCharts(stats);
        statsLastUpdated = new Date().toLocaleTimeString('es-VE', { hour: '2-digit', minute: '2-digit' });
        if (updated) updated.textContent = `Actualizado a las ${statsLastUpdated}`;
    } catch (error) {
        if (error.name === 'AbortError' || version !== statsRequestVersion) return;
        showBlockError('dashboardError', document.getElementById('dashboardUpdated'), error.message, loadStats);
        if (updated) updated.textContent = statsLastUpdated ? `Última lectura correcta: ${statsLastUpdated}` : 'Sin conexión con los datos';
        if (!statsLastUpdated) {
            setTodayPanelState('agendaHoy', 'error');
            setTodayPanelState('reposos', 'error');
        }
    } finally { if (version === statsRequestVersion) section?.setAttribute('aria-busy', 'false'); }
}

// Estados de los paneles de la jornada: cargando, lista, vacío o error.
function setTodayPanelState(prefix, state) {
    setVisible(document.getElementById(`${prefix}Loader`), state === 'loading', 'flex');
    setVisible(document.getElementById(`${prefix}List`), state === 'list');
    const empty = document.getElementById(`${prefix}Empty`);
    setVisible(empty, state === 'empty' || state === 'error', 'flex');
    if (!empty) return;
    empty.classList.toggle('is-error', state === 'error');
    const title = empty.querySelector('h3');
    const copy = empty.querySelector('p');
    if (state === 'error') {
        if (empty.dataset.title === undefined) { empty.dataset.title = title?.textContent || ''; empty.dataset.copy = copy?.textContent || ''; }
        if (title) title.textContent = 'No se pudo cargar esta información';
        if (copy) copy.textContent = 'Use «Actualizar» para intentarlo de nuevo.';
    } else if (empty.dataset.title !== undefined) {
        if (title) title.textContent = empty.dataset.title;
        if (copy) copy.textContent = empty.dataset.copy;
        delete empty.dataset.title;
        delete empty.dataset.copy;
    }
}
function dashboardPatientName(item) {
    const fullName = `${item.nombres ?? ''} ${item.apellidos ?? ''}`.trim() || 'Paciente sin nombre';
    if (vistaPuede('clinica.ver') && item.paciente_id && typeof viewPatientDetails === 'function') {
        const button = textAction(fullName, '', () => viewPatientDetails(item.paciente_id), 'patient-name-button');
        button.setAttribute('aria-label', `Ver la historia clínica de ${fullName}`);
        return button;
    }
    const strong = document.createElement('strong');
    strong.textContent = fullName;
    return strong;
}
function renderAgendaHoy(items, total) {
    const list = document.getElementById('agendaHoyList');
    if (!list) return;
    list.replaceChildren();
    const dateLabel = document.getElementById('agendaHoyFecha');
    if (dateLabel) dateLabel.textContent = `Pendientes del ${new Date().toLocaleDateString('es-VE', { weekday: 'long', day: 'numeric', month: 'long' })}`;
    const count = document.getElementById('agendaHoyCount');
    if (count) count.textContent = total ? `${total.toLocaleString('es-VE')} ${total === 1 ? 'pendiente' : 'pendientes'}` : '';
    items.forEach(item => {
        const appointment = { ...item, estado: item.estado || 'Pendiente' };
        const fullName = `${item.nombres ?? ''} ${item.apellidos ?? ''}`.trim();
        const overdue = isOverdue(appointment);
        const row = document.createElement('li');
        row.className = `today-row${overdue ? ' is-overdue' : ''}`;
        const time = document.createElement('time');
        time.className = 'today-time';
        time.dateTime = String(item.fecha_cita || '').replace(' ', 'T');
        time.textContent = formatTime(item.fecha_cita);
        const body = document.createElement('div');
        body.className = 'today-body';
        body.append(dashboardPatientName(item));
        const meta = document.createElement('span');
        meta.className = 'table-secondary';
        meta.textContent = [item.cedula, item.motivo].filter(Boolean).join(' · ');
        body.append(meta);
        if (overdue) body.insertAdjacentHTML('beforeend', '<span class="status-badge status-overdue">Vencida</span>');
        row.append(time, body);
        if (vistaPuede('clinica.editar')) {
            const attend = textAction('Atender', 'ph-stethoscope', () => atenderCita(appointment), 'btn btn-secondary');
            attend.setAttribute('aria-label', `Atender a ${fullName} y registrar la consulta`);
            row.append(attend);
        }
        list.append(row);
    });
    const more = document.getElementById('agendaHoyMore');
    const remaining = Math.max(0, total - items.length);
    if (more) {
        more.replaceChildren();
        if (remaining) more.append(textAction(`Ver ${remaining} ${remaining === 1 ? 'cita más' : 'citas más'} en la agenda`, '', () => mostrarCitasDe('hoy'), 'btn btn-text'));
        setVisible(more, remaining > 0);
    }
    setTodayPanelState('agendaHoy', items.length ? 'list' : 'empty');
}
function renderRepososPorVencer(items) {
    const panel = document.getElementById('repososPanel');
    const allowed = vistaPuede('clinica.ver');
    if (panel) panel.hidden = !allowed;
    document.getElementById('dashboardTodayGrid')?.classList.toggle('is-single', !allowed);
    const list = document.getElementById('repososList');
    if (!list || !allowed) return;
    list.replaceChildren();
    const today = parseLocalDateTime(localDate());
    const count = document.getElementById('repososCount');
    if (count) count.textContent = items.length ? String(items.length) : '';
    items.forEach(item => {
        const end = parseLocalDateTime(item.fecha_fin_reposo);
        const days = end && today ? Math.round((end - today) / 86400000) : null;
        const row = document.createElement('li');
        row.className = 'today-row';
        const badge = document.createElement('span');
        badge.className = `today-days${days !== null && days <= 0 ? ' is-today' : ''}`;
        badge.textContent = days === null ? '—' : days <= 0 ? 'Hoy' : days === 1 ? 'Mañana' : `En ${days} días`;
        const body = document.createElement('div');
        body.className = 'today-body';
        body.append(dashboardPatientName(item));
        const meta = document.createElement('span');
        meta.className = 'table-secondary';
        meta.textContent = `${item.cedula ?? ''} · Reposo del ${formatDate(item.fecha_inicio_reposo)} al ${formatDate(item.fecha_fin_reposo)}`;
        body.append(meta);
        row.append(badge, body);
        list.append(row);
    });
    setTodayPanelState('reposos', items.length ? 'list' : 'empty');
}

function chartEmptyState(canvas, id, message, visible) {
    let empty = document.getElementById(id);
    if (!empty) {
        empty = document.createElement('p');
        empty.id = id;
        empty.className = 'chart-empty';
        empty.setAttribute('role', 'status');
        canvas.parentElement.append(empty);
    }
    empty.textContent = message;
    setVisible(empty, visible);
}
function chartPalette() {
    const styles = getComputedStyle(document.documentElement);
    const token = (name, fallback) => styles.getPropertyValue(name).trim() || fallback;
    return {
        series: token('--accent', '#1a4b8c'),
        seriesHover: token('--primary', '#0f2a56'),
        tooltipBg: token('--unefa-navy', '#0c234b'),
        grid: token('--rule', '#eaeff6'),
        ticks: token('--muted', '#4e5e76'),
        labels: token('--text', '#101d33')
    };
}
function renderCharts(stats) {
    const categories = Array.isArray(stats.categorias) ? stats.categorias : [];
    const history = Array.isArray(stats.historial_mensual) ? stats.historial_mensual : [];
    const family = getComputedStyle(document.body).fontFamily;
    const palette = chartPalette();
    const chartAvailable = typeof Chart !== 'undefined';
    const tooltipOptions = {
        displayColors: false,
        backgroundColor: palette.tooltipBg,
        titleColor: '#ffffff',
        bodyColor: '#e8effa',
        padding: 10,
        cornerRadius: 6
    };
    const categoryCanvas = document.getElementById('categoryChart');
    if (categoryCanvas) {
        categoryChartInstance?.destroy();
        categoryChartInstance = null;
        const total = categories.reduce((sum, category) => sum + Number(category.cantidad || 0), 0);
        const summary = categories.map(category => `${category.nombre}: ${Number(category.cantidad || 0)}`).join('. ');
        categoryCanvas.setAttribute('role', 'img');
        categoryCanvas.setAttribute('aria-label', `Pacientes por categoría. ${summary || 'Sin pacientes registrados.'}`);
        chartEmptyState(categoryCanvas, 'categoryChartEmpty', chartAvailable ? 'Aún no hay pacientes registrados por categoría.' : summary || 'Sin pacientes registrados.', !total || !chartAvailable);
        categoryCanvas.style.visibility = !total || !chartAvailable ? 'hidden' : 'visible';
        if (chartAvailable && total) categoryChartInstance = new Chart(categoryCanvas.getContext('2d'), {
            type: 'bar',
            data: { labels: categories.map(category => category.nombre), datasets: [{ label: 'Pacientes', data: categories.map(category => Number(category.cantidad || 0)), backgroundColor: palette.series, hoverBackgroundColor: palette.seriesHover, borderRadius: 3, maxBarThickness: 22 }] },
            options: {
                indexAxis: 'y', responsive: true, maintainAspectRatio: false, animation: false,
                plugins: { legend: { display: false }, tooltip: tooltipOptions },
                scales: {
                    x: { beginAtZero: true, suggestedMax: total <= 1 ? 1 : undefined, border: { display: false }, grid: { color: palette.grid }, ticks: { precision: 0, maxTicksLimit: 5, color: palette.ticks, font: { family, size: 12 } } },
                    y: { border: { display: false }, grid: { display: false }, ticks: { color: palette.labels, font: { family, size: 12 } } }
                }
            }
        });
    }
    const historyCanvas = document.getElementById('historyChart');
    if (historyCanvas) {
        historyChartInstance?.destroy();
        historyChartInstance = null;
        const values = history.map(month => Number(month.cantidad || 0));
        const total = values.reduce((sum, count) => sum + count, 0);
        const summary = history.map(month => `${month.mes_nombre}: ${Number(month.cantidad || 0)}`).join('. ');
        historyCanvas.setAttribute('role', 'img');
        historyCanvas.setAttribute('aria-label', `Consultas por mes. ${summary || 'Sin consultas registradas.'}`);
        chartEmptyState(historyCanvas, 'historyChartEmpty', chartAvailable ? 'Aún no hay consultas en este período.' : summary || 'Sin consultas registradas.', !total || !chartAvailable);
        historyCanvas.style.visibility = chartAvailable ? 'visible' : 'hidden';
        if (chartAvailable) historyChartInstance = new Chart(historyCanvas.getContext('2d'), {
            type: 'bar',
            data: { labels: history.map(month => month.mes_nombre), datasets: [{ label: 'Consultas', data: values, backgroundColor: palette.series, hoverBackgroundColor: palette.seriesHover, borderRadius: 3, maxBarThickness: 28 }] },
            options: {
                responsive: true, maintainAspectRatio: false, animation: false,
                plugins: { legend: { display: false }, tooltip: tooltipOptions },
                scales: {
                    y: { beginAtZero: true, suggestedMax: total === 0 ? 1 : undefined, border: { display: false }, grid: { color: palette.grid }, ticks: { precision: 0, maxTicksLimit: 5, color: palette.ticks, font: { family, size: 12 } } },
                    x: { border: { display: false }, grid: { display: false }, ticks: { color: palette.ticks, font: { family, size: 12 } } }
                }
            }
        });
    }
}
