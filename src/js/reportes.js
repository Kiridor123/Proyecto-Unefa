// Búsqueda de Morbilidad por rango de fechas
async function buscarMorbilidad() {
    const inicio = document.getElementById('morbilidadInicio').value;
    const fin = document.getElementById('morbilidadFin').value;
    if (!inicio || !fin) {
        showNotification('warning', 'Por favor seleccione ambas fechas.');
        return;
    }
    
    const tableBody = document.getElementById('morbilidadTableBody');
    const tableWrap = document.getElementById('morbilidadTableWrap');
    const cardsContainer = document.getElementById('morbilidadCards');
    const loader = document.getElementById('morbilidadLoader');
    const emptyState = document.getElementById('morbilidadEmptyState');
    const emptyText = document.getElementById('morbilidadEmptyText');
    const btnImprimir = document.getElementById('btnImprimirMorbilidad');
    
    if (!tableBody || !btnImprimir) return;
    
    // Ocultar todo y mostrar spinner
    if (tableWrap) tableWrap.style.display = 'none';
    if (cardsContainer) cardsContainer.style.display = 'none';
    if (emptyState) emptyState.classList.add('hidden');
    if (loader) loader.classList.remove('hidden');
    
    btnImprimir.disabled = true;
    tableBody.innerHTML = '';
    if (cardsContainer) cardsContainer.innerHTML = '';
    
    try {
        let res = await fetch(`api/get_morbilidad.php?fecha_inicio=${inicio}&fecha_fin=${fin}`);
        let json = await res.json();
        
        if (json.success) {
            const consultas = json.data;
            
            if (consultas.length === 0) {
                if (emptyText) emptyText.textContent = 'No se encontraron consultas en este rango de fechas.';
                if (emptyState) emptyState.classList.remove('hidden');
            } else {
                consultas.forEach(c => {
                    const initials = `${escapeHTML(c.nombres.charAt(0))}${escapeHTML(c.apellidos.charAt(0))}`;
                    const fullName = `${escapeHTML(c.nombres)} ${escapeHTML(c.apellidos)}`;
                    
                    // ── FILA DE TABLA (visible solo en md+) ───────────────────
                    const row = document.createElement('tr');
                    row.className = 'border-b border-slate-100 hover:bg-slate-50 transition';
                    row.innerHTML = `
                        <td class="px-4 py-3 font-semibold text-slate-700">${fullName}</td>
                        <td class="px-4 py-3 text-slate-600">${escapeHTML(c.cedula)}</td>
                        <td class="px-4 py-3 text-slate-500">${escapeHTML(c.fecha_circunstancia)}</td>
                        <td class="px-4 py-3 text-slate-500">${escapeHTML(c.categoria)}</td>
                        <td class="px-4 py-3 text-slate-500">${escapeHTML(c.vital_peso_talla || 'N/A')}</td>
                        <td class="px-4 py-3 text-slate-500">${escapeHTML(c.vital_ta || 'N/A')}</td>
                        <td class="px-4 py-3 font-bold text-slate-800">${escapeHTML(c.diagnostico || c.resumen)}</td>
                    `;
                    tableBody.appendChild(row);
                    
                    // ── TARJETA MÓVIL (visible solo en < md) ─────────────────
                    if (cardsContainer) {
                        const card = document.createElement('div');
                        card.className = 'glass-panel p-5 rounded-2xl border border-slate-100 bg-white/85 hover:bg-white transition-all duration-300 flex flex-col justify-between shadow-sm hover:shadow-md gap-4 relative text-left';
                        card.innerHTML = `
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-11 h-11 rounded-full bg-brand-50 text-brand-600 flex items-center justify-center font-bold text-sm shrink-0 border border-brand-100 shadow-sm">${initials}</div>
                                    <div class="min-w-0">
                                        <h4 class="font-bold text-slate-800 text-sm sm:text-base truncate leading-tight">${fullName}</h4>
                                        <p class="text-xs text-slate-500 font-semibold mt-1 flex items-center gap-1">
                                            <i class="ph ph-identification-card text-brand-500 text-sm"></i> ${escapeHTML(c.cedula)}
                                        </p>
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200/50 shrink-0">${escapeHTML(c.categoria)}</span>
                            </div>
                            
                            <div class="border-t border-slate-100/70 pt-3 grid grid-cols-2 gap-3 text-[11px] text-slate-500">
                                <div class="flex flex-col">
                                    <span class="text-slate-400">Fecha Eval:</span>
                                    <span class="font-semibold text-slate-700">${escapeHTML(c.fecha_circunstancia)}</span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-slate-400">T.A. / Medidas:</span>
                                    <span class="font-semibold text-slate-700">${escapeHTML(c.vital_ta || 'N/A')} &bull; ${escapeHTML(c.vital_peso_talla || 'N/A')}</span>
                                </div>
                            </div>
                            
                            <div class="border-t border-slate-100/70 pt-3">
                                <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Diagnóstico / Resumen:</span>
                                <p class="text-xs font-bold text-slate-800 leading-relaxed bg-brand-50/50 p-2.5 border border-brand-100/30 rounded-xl">${escapeHTML(c.diagnostico || c.resumen)}</p>
                            </div>
                        `;
                        cardsContainer.appendChild(card);
                    }
                });
                
                if (tableWrap) tableWrap.style.display = '';
                if (cardsContainer) cardsContainer.style.display = '';
                btnImprimir.disabled = false;
            }
        } else {
            showNotification('error', json.message);
            if (emptyText) emptyText.textContent = `Error: ${json.message}`;
            if (emptyState) emptyState.classList.remove('hidden');
        }
    } catch(e) {
        console.error(e);
        showNotification('error', 'Hubo un error de conexión con el servidor.');
        if (emptyText) emptyText.textContent = 'Error al conectar con el servidor.';
        if (emptyState) emptyState.classList.remove('hidden');
    } finally {
        if (loader) loader.classList.add('hidden');
    }
}

function imprimirMorbilidadDirecta() {
    const inicio = document.getElementById('morbilidadInicio').value;
    const fin = document.getElementById('morbilidadFin').value;
    if (inicio && fin) {
        window.open(`api/print_morbilidad.php?fecha_inicio=${inicio}&fecha_fin=${fin}`, '_blank');
    }
}
