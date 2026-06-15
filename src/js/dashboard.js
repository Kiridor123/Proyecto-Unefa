// Carga de Estadísticas
async function loadStats() {
    try {
        let res = await fetch('api/get_stats.php');
        let json = await res.json();
        if(json.success) {
            const statPacientes = document.getElementById('statPacientes');
            const statCitas = document.getElementById('statCitas');
            const statConsultas = document.getElementById('statConsultas');
            const statReposos = document.getElementById('statReposos');

            if (statPacientes) statPacientes.textContent = json.data.total_pacientes;
            if (statCitas) statCitas.textContent = json.data.citas_proximas;
            if (statConsultas) statConsultas.textContent = json.data.consultas_mes;
            if (statReposos) statReposos.textContent = json.data.reposos_activos;
            
            // Renderizar gráficos si estamos en Dashboard
            if (currentTab === 'dashboard') {
                renderCharts(json.data);
            }
        }
    } catch(e) { 
        console.error('Error stats:', e); 
    }
}

// Inicializador de Gráficos (Chart.js)
function renderCharts(statsData) {
    const categoryChartEl = document.getElementById('categoryChart');
    if (categoryChartEl) {
        const catCtx = categoryChartEl.getContext('2d');
        if (categoryChartInstance) categoryChartInstance.destroy();
        
        const catLabels = statsData.categorias.map(c => c.nombre);
        const catValues = statsData.categorias.map(c => parseInt(c.cantidad));
        
        categoryChartInstance = new Chart(catCtx, {
            type: 'doughnut',
            data: {
                labels: catLabels,
                datasets: [{
                    data: catValues,
                    backgroundColor: ['#2563eb', '#10b981', '#f59e0b', '#ef4444', '#6366f1'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            padding: 15,
                            font: { family: 'Inter', size: 10, weight: '500' }
                        }
                    }
                },
                cutout: '70%'
            }
        });
    }
    
    const historyChartEl = document.getElementById('historyChart');
    if (historyChartEl) {
        const histCtx = historyChartEl.getContext('2d');
        if (historyChartInstance) historyChartInstance.destroy();
        
        const histLabels = statsData.historial_mensual.map(h => h.mes_nombre);
        const histValues = statsData.historial_mensual.map(h => parseInt(h.cantidad));
        
        historyChartInstance = new Chart(histCtx, {
            type: 'bar',
            data: {
                labels: histLabels,
                datasets: [{
                    label: 'N° de Consultas',
                    data: histValues,
                    backgroundColor: 'rgba(37, 99, 235, 0.85)',
                    hoverBackgroundColor: '#2563eb',
                    borderRadius: 6,
                    barThickness: window.innerWidth < 640 ? 12 : 24
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { display: false } 
                },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        grid: { color: '#f1f5f9' }, 
                        ticks: { 
                            stepSize: 1, 
                            font: { family: 'Inter', size: 9 } 
                        } 
                    },
                    x: { 
                        grid: { display: false },
                        ticks: { font: { family: 'Inter', size: 9 } }
                    }
                }
            }
        });
    }
}
