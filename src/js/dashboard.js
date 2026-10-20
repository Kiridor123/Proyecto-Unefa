let statsRequestVersion = 0;
let statsLastUpdated = '';

async function loadStats() {
    const version = ++statsRequestVersion;
    const section = document.getElementById('section-dashboard');
    section?.setAttribute('aria-busy', 'true');
    setVisible(document.getElementById('dashboardError'), false);
    const updated = document.getElementById('dashboardUpdated');
    if (updated) updated.textContent = statsLastUpdated ? `Actualizando · última lectura ${statsLastUpdated}` : 'Cargando datos de atención…';
    if (!statsLastUpdated) ['statPacientes', 'statCitas', 'statConsultas', 'statReposos'].forEach(id => {
        const element = document.getElementById(id);
        if (element) element.textContent = '—';
    });
    try {
        const json = await apiRequest('api/get_stats.php', {}, 'stats');
        if (version !== statsRequestVersion) return;
        const stats = json.data;
        const fields = { statPacientes: 'total_pacientes', statCitas: 'citas_proximas', statConsultas: 'consultas_mes', statReposos: 'reposos_activos' };
        for (const [id, key] of Object.entries(fields)) {
            const element = document.getElementById(id);
            if (element) element.textContent = Number(stats[key] || 0).toLocaleString('es-VE');
        }
        renderCharts(stats);
        statsLastUpdated = new Date().toLocaleTimeString('es-VE', { hour: '2-digit', minute: '2-digit' });
        if (updated) updated.textContent = `Actualizado a las ${statsLastUpdated}`;
    } catch (error) {
        if (error.name === 'AbortError') return;
        showBlockError('dashboardError', document.getElementById('dashboardUpdated'), error.message, loadStats);
        if (updated) updated.textContent = statsLastUpdated ? `Última lectura correcta: ${statsLastUpdated}` : 'Sin conexión con los datos';
    } finally { if (version === statsRequestVersion) section?.setAttribute('aria-busy', 'false'); }
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
function renderCharts(stats) {
    const categories = Array.isArray(stats.categorias) ? stats.categorias : [];
    const history = Array.isArray(stats.historial_mensual) ? stats.historial_mensual : [];
    const family = getComputedStyle(document.body).fontFamily;
    const chartAvailable = typeof Chart !== 'undefined';
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
            data: { labels: categories.map(category => category.nombre), datasets: [{ label: 'Pacientes', data: categories.map(category => Number(category.cantidad || 0)), backgroundColor: '#256e78', borderRadius: 3, maxBarThickness: 22 }] },
            options: {
                indexAxis: 'y', responsive: true, maintainAspectRatio: false, animation: false,
                plugins: { legend: { display: false }, tooltip: { displayColors: false } },
                scales: {
                    x: { beginAtZero: true, suggestedMax: total <= 1 ? 1 : undefined, border: { display: false }, grid: { color: '#edf0f0' }, ticks: { precision: 0, maxTicksLimit: 5, color: '#657078', font: { family, size: 12 } } },
                    y: { border: { display: false }, grid: { display: false }, ticks: { color: '#38464d', font: { family, size: 12 } } }
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
            data: { labels: history.map(month => month.mes_nombre), datasets: [{ label: 'Consultas', data: values, backgroundColor: '#256e78', borderRadius: 3, maxBarThickness: 28 }] },
            options: {
                responsive: true, maintainAspectRatio: false, animation: false,
                plugins: { legend: { display: false }, tooltip: { displayColors: false } },
                scales: {
                    y: { beginAtZero: true, suggestedMax: total === 0 ? 1 : undefined, border: { display: false }, grid: { color: '#edf0f0' }, ticks: { precision: 0, maxTicksLimit: 5, color: '#657078', font: { family, size: 12 } } },
                    x: { border: { display: false }, grid: { display: false }, ticks: { color: '#657078', font: { family, size: 12 } } }
                }
            }
        });
    }
}
