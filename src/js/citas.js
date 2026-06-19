// LÓGICA DE CITAS (TAB 3)
async function loadCitas() {
    const loader = document.getElementById('citasTableLoader');
    const tableWrap = document.getElementById('citasTableWrap');
    const cardsWrap = document.getElementById('citasTableBodyCards');
    if (loader) loader.style.display = 'flex';
    if (tableWrap) tableWrap.style.display = 'none';
    if (cardsWrap) cardsWrap.style.display = 'none';
    try {
        const estadoEl = document.getElementById('filterCitasEstado');
        const estado = estadoEl ? estadoEl.value : 'Pendiente';
        let res = await fetch(`api/get_citas.php?estado=${estado}`);
        let json = await res.json();
        if(json.success) {
            citasList = json.data;
            renderCitasTable();
        }
    } catch(e) {
        console.error('Error citas:', e);
    } finally {
        if (loader) loader.style.display = 'none';
        if (tableWrap) tableWrap.style.display = '';
        if (cardsWrap) cardsWrap.style.display = '';
    }
}

// Renderizado de Citas Adaptado a Pantallas Móviles
function renderCitasTable() {
    const tbody = document.getElementById('citasTableBody');
    const emptyState = document.getElementById('citasEmptyState');
    if (!tbody) return;

    const filterCitasPacienteEl = document.getElementById('filterCitasPaciente');
    const query = filterCitasPacienteEl ? filterCitasPacienteEl.value.toLowerCase() : '';

    tbody.innerHTML = '';
    let visibleCount = 0;

    const cardsContainer = document.getElementById('citasTableBodyCards');
    if (cardsContainer) cardsContainer.innerHTML = '';

    citasList.forEach(c => {
        const pacienteNombre = `${c.nombres} ${c.apellidos}`.toLowerCase();
        const cedulaMatch = c.cedula.toLowerCase().includes(query);
        const nombreMatch = pacienteNombre.includes(query);

        if (cedulaMatch || nombreMatch) {
            visibleCount++;

            let statusBadge = '';
            let actionButtons = '';
            let statusColor = '';

            if (c.estado === 'Pendiente') {
                statusBadge = '<span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-600 border border-amber-200">Pendiente</span>';
                statusColor = 'text-amber-600';
                actionButtons = `
                    <button onclick="cambiarEstadoCita(${c.id}, 'Completada')" class="p-1.5 hover:bg-emerald-50 text-slate-400 hover:text-emerald-600 rounded-lg transition" title="Marcar como Completada">
                        <i class="ph ph-check-circle text-xl"></i>
                    </button>
                    <button onclick="cambiarEstadoCita(${c.id}, 'Cancelada')" class="p-1.5 hover:bg-rose-50 text-slate-400 hover:text-rose-600 rounded-lg transition" title="Cancelar Cita">
                        <i class="ph ph-x-circle text-xl"></i>
                    </button>
                `;
            } else if (c.estado === 'Completada') {
                statusBadge = '<span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-600 border border-emerald-200">Completada</span>';
                statusColor = 'text-emerald-600';
            } else {
                statusBadge = '<span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-600 border border-rose-200">Cancelada</span>';
                statusColor = 'text-rose-500';
            }

            const parts = c.fecha_cita.split(/[\sT]/);
            const fechaParte = parts[0];
            const horaParte = parts[1] ? parts[1].substring(0, 5) : '';
            const fullName = `${escapeHTML(c.nombres)} ${escapeHTML(c.apellidos)}`;

            // ── FILA DE TABLA (visible solo en md+) ───────────────────
            const row = document.createElement('tr');
            row.className = 'hover:bg-slate-50/60 transition';
            row.innerHTML = `
                <td class="px-6 py-4 text-sm font-medium text-slate-700">
                    <div class="font-semibold">${fechaParte}</div>
                    <div class="text-xs text-slate-400">${horaParte}</div>
                </td>
                <td class="px-6 py-4 font-semibold text-slate-700 text-sm">${escapeHTML(c.cedula)}</td>
                <td class="px-6 py-4 font-medium text-slate-800 text-sm">${fullName}</td>
                <td class="px-6 py-4 hidden lg:table-cell">
                    <span class="px-2 py-1 rounded-md text-xs font-semibold bg-slate-100 text-slate-600">${escapeHTML(c.categoria)}</span>
                </td>
                <td class="px-6 py-4">${statusBadge}</td>
                <td class="px-6 py-4 text-right">
                    <div class="flex justify-end gap-1.5">
                        ${actionButtons}
                    </div>
                </td>
            `;
            tbody.appendChild(row);

            // ── TARJETA MÓVIL (visible solo en < md) ─────────────────
            if (cardsContainer) {
                const card = document.createElement('div');
                card.className = 'glass-panel p-5 rounded-2xl border border-slate-100 bg-white/85 hover:bg-white transition-all duration-300 flex flex-col justify-between shadow-sm hover:shadow-md gap-4 relative';
                
                // Formato amigable de fecha para el badge
                const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                const mesNum = parseInt(fechaParte.split('-')[1]) - 1;
                const mesTexto = meses[mesNum] || '';
                const diaTexto = fechaParte.split('-')[2] || '';

                card.innerHTML = `
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex flex-col items-center justify-center shrink-0 border border-amber-100 shadow-sm leading-none">
                                <span class="text-xs font-bold">${diaTexto}</span>
                                <span class="text-[9px] font-semibold uppercase text-amber-500 mt-0.5">${mesTexto}</span>
                            </div>
                            <div class="min-w-0">
                                <h4 class="font-bold text-slate-800 text-sm sm:text-base truncate leading-tight">${fullName}</h4>
                                <p class="text-xs text-slate-500 font-semibold mt-1 flex items-center gap-1">
                                    <i class="ph ph-identification-card text-slate-400 text-sm"></i> ${escapeHTML(c.cedula)}
                                </p>
                            </div>
                        </div>
                        <div class="shrink-0">${statusBadge}</div>
                    </div>
                    
                    <div class="border-t border-slate-100/70 pt-3 flex flex-wrap gap-4 text-[11px] text-slate-500">
                        <div class="flex items-center gap-1.5">
                            <i class="ph ph-clock text-xs text-slate-400"></i>
                            <span>Hora de cita:</span>
                            <span class="font-semibold text-slate-700">${horaParte}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <i class="ph ph-tag text-xs text-slate-400"></i>
                            <span>Categoría:</span>
                            <span class="font-semibold text-slate-700">${escapeHTML(c.categoria)}</span>
                        </div>
                    </div>
                    
                    ${actionButtons ? `
                    <div class="grid grid-cols-2 gap-2 mt-1 pt-1" onclick="event.stopPropagation()">
                        <button onclick="cambiarEstadoCita(${c.id}, 'Completada')" class="flex items-center justify-center gap-1.5 py-2.5 px-3 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 text-xs font-bold rounded-xl border border-emerald-100 transition shadow-sm min-h-[44px]">
                            <i class="ph ph-check-circle text-base"></i> Completar
                        </button>
                        <button onclick="cambiarEstadoCita(${c.id}, 'Cancelada')" class="flex items-center justify-center gap-1.5 py-2.5 px-3 bg-rose-50 hover:bg-rose-100 text-rose-600 text-xs font-bold rounded-xl border border-rose-100 transition shadow-sm min-h-[44px]">
                            <i class="ph ph-x-circle text-base"></i> Cancelar
                        </button>
                    </div>
                    ` : ''}
                `;
                cardsContainer.appendChild(card);
            }
        }
    });


    if (emptyState) {
        if (visibleCount === 0) {
            emptyState.classList.remove('hidden');
            emptyState.style.display = 'flex';
        } else {
            emptyState.classList.add('hidden');
            emptyState.style.display = 'none';
        }
    }
}

// Cambiar Estado Cita
async function cambiarEstadoCita(citaId, nuevoEstado) {
    const result = await Swal.fire({
        title: '¿Está seguro?',
        text: `Se cambiará el estado de la cita a "${nuevoEstado}".`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3b82f6',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: 'Sí, cambiar',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        try {
            const formData = new FormData();
            formData.append('cita_id', citaId);
            formData.append('estado', nuevoEstado);
            
            let res = await fetch('api/update_cita_estado.php', {
                method: 'POST',
                body: formData
            });
            let json = await res.json();
            
            if (json.success) {
                showNotification('success', json.message);
                loadCitas();
            } else {
                showNotification('error', json.message);
            }
        } catch(e) {
            showNotification('error', 'No se pudo actualizar la cita.');
        }
    }
}

// Búsqueda inteligente para el formulario de agendar citas
function buscarPacienteParaCita(cedula) {
    if (!cedula) return;
    const cedulaLimpia = cedula.replace(/\s+/g, '').toUpperCase();
    const paciente = pacientesList.find(p => p.cedula.replace(/\s+/g, '').toUpperCase() === cedulaLimpia);
    
    const infoBox = document.getElementById('citaPacienteInfo');
    const nuevoBox = document.getElementById('citaNuevoPacienteCampos');
    if (!infoBox || !nuevoBox) return;
    
    if (paciente) {
        document.getElementById('citaPacienteId').value = paciente.id;
        document.getElementById('citaPacienteNombreText').textContent = `${paciente.nombres} ${paciente.apellidos} (${paciente.categoria})`;
        infoBox.classList.remove('hidden');
        nuevoBox.classList.add('hidden');
        
        // Desactivar requeridos del vuelo
        document.getElementById('citaNombres').required = false;
        document.getElementById('citaApellidos').required = false;
        document.getElementById('citaCategoria').required = false;
    } else {
        document.getElementById('citaPacienteId').value = 0;
        infoBox.classList.add('hidden');
        nuevoBox.classList.remove('hidden');
        
        // Activar requeridos del vuelo
        document.getElementById('citaNombres').required = true;
        document.getElementById('citaApellidos').required = true;
        document.getElementById('citaCategoria').required = true;
    }
}

// Envío de Formulario Programar Cita
const citaForm = document.getElementById('citaForm');
if (citaForm) {
    citaForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = document.getElementById('btnGuardarCita');
        if (!btn) return;
        
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="ph ph-spinner animate-spin"></i> Guardando...';
        btn.disabled = true;

        const formData = new FormData(this);

        try {
            let res = await fetch('api/save_cita.php', {
                method: 'POST',
                body: formData
            });
            let json = await res.json();
            
            if (json.success) {
                showNotification('success', json.message);
                closeCitaModal();
                // Recargar stats y tabla
                loadStats();
                if(currentTab === 'citas') loadCitas();
            } else {
                if (json.errors && Object.keys(json.errors).length > 0) {
                    if (typeof handleInlineErrors === 'function') {
                        handleInlineErrors(json.errors, 'citaForm');
                    }
                    if (typeof showValidationErrors === 'function') {
                        showValidationErrors(json.errors);
                    } else {
                        showNotification('error', json.message);
                    }
                } else {
                    showNotification('error', json.message);
                }
            }
        } catch (error) {
            console.error(error);
            showNotification('error', 'Error al conectarse con el servidor para agendar.');
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    });
}

// Filtros Citas
const filterCitasPaciente = document.getElementById('filterCitasPaciente');
if (filterCitasPaciente) {
    filterCitasPaciente.addEventListener('input', renderCitasTable);
}

const filterCitasEstado = document.getElementById('filterCitasEstado');
if (filterCitasEstado) {
    filterCitasEstado.addEventListener('change', loadCitas);
}
