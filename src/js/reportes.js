// Búsqueda de Morbilidad por rango de fechas
async function buscarMorbilidad() {
    const inicio = document.getElementById('morbilidadInicio').value;
    const fin = document.getElementById('morbilidadFin').value;
    if (!inicio || !fin) {
        showNotification('warning', 'Por favor seleccione ambas fechas.');
        return;
    }
    
    const tableBody = document.getElementById('morbilidadTableBody');
    const btnImprimir = document.getElementById('btnImprimirMorbilidad');
    if (!tableBody || !btnImprimir) return;
    
    tableBody.innerHTML = `
        <tr>
            <td colspan="7" class="text-center py-8 text-brand-600">
                <i class="ph ph-spinner-gap animate-spin text-2xl mb-2"></i>
                <p>Buscando consultas...</p>
            </td>
        </tr>
    `;
    btnImprimir.disabled = true;
    
    try {
        let res = await fetch(`api/get_morbilidad.php?fecha_inicio=${inicio}&fecha_fin=${fin}`);
        let json = await res.json();
        
        if (json.success) {
            const consultas = json.data;
            tableBody.innerHTML = '';
            
            if (consultas.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-8 text-slate-400">No se encontraron consultas en este rango de fechas.</td>
                    </tr>
                `;
            } else {
                consultas.forEach(c => {
                    let edad = 'N/A';
                    if (c.fecha_nacimiento) {
                        const nacimiento = new Date(c.fecha_nacimiento);
                        const circunstancia = new Date(c.fecha_circunstancia);
                        edad = circunstancia.getFullYear() - nacimiento.getFullYear();
                        const m = circunstancia.getMonth() - nacimiento.getMonth();
                        if (m < 0 || (m === 0 && circunstancia.getDate() < nacimiento.getDate())) {
                            edad--;
                        }
                    }
                    
                    const row = document.createElement('tr');
                    row.className = 'border-b border-slate-100 hover:bg-slate-50 transition';
                    row.innerHTML = `
                        <td class="px-4 py-3 font-semibold text-slate-700">${escapeHTML(c.nombres + ' ' + c.apellidos)}</td>
                        <td class="px-4 py-3 text-slate-600">${escapeHTML(c.cedula)}</td>
                        <td class="px-4 py-3 text-slate-500">${escapeHTML(c.fecha_circunstancia)}</td>
                        <td class="px-4 py-3 text-slate-500">${escapeHTML(c.categoria)}</td>
                        <td class="px-4 py-3 text-slate-500">${escapeHTML(c.vital_peso_talla || 'N/A')}</td>
                        <td class="px-4 py-3 text-slate-500">${escapeHTML(c.vital_ta || 'N/A')}</td>
                        <td class="px-4 py-3 font-bold text-slate-800">${escapeHTML(c.diagnostico || c.resumen)}</td>
                    `;
                    tableBody.appendChild(row);
                });
                btnImprimir.disabled = false;
            }
        } else {
            showNotification('error', json.message);
            tableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-8 text-rose-500">Error: ${escapeHTML(json.message)}</td>
                </tr>
            `;
        }
    } catch(e) {
        console.error(e);
        showNotification('error', 'Hubo un error de conexión con el servidor.');
        tableBody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-8 text-rose-500">Error al conectar con el servidor.</td>
            </tr>
        `;
    }
}

function imprimirMorbilidadDirecta() {
    const inicio = document.getElementById('morbilidadInicio').value;
    const fin = document.getElementById('morbilidadFin').value;
    if (inicio && fin) {
        window.open(`api/print_morbilidad.php?fecha_inicio=${inicio}&fecha_fin=${fin}`, '_blank');
    }
}
