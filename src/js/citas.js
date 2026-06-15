// LÓGICA DE CITAS (TAB 3)
async function loadCitas() {
    const loader = document.getElementById('citasTableLoader');
    if (loader) loader.classList.remove('hidden');
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
        if (loader) loader.classList.add('hidden');
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

    citasList.forEach(c => {
        const pacienteNombre = `${c.nombres} ${c.apellidos}`.toLowerCase();
        const cedulaMatch = c.cedula.toLowerCase().includes(query);
        const nombreMatch = pacienteNombre.includes(query);

        if (cedulaMatch || nombreMatch) {
            visibleCount++;
            const row = document.createElement('tr');
            row.className = 'hover:bg-slate-50/60 transition';
            
            let statusBadge = '';
            let actionButtons = '';
            
            if (c.estado === 'Pendiente') {
                statusBadge = '<span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-600 border border-amber-200">Pendiente</span>';
                actionButtons = `
                    <button onclick="cambiarEstadoCita(${c.id}, 'Completada')" class="p-1 hover:bg-emerald-50 text-slate-400 hover:text-emerald-600 rounded-lg transition" title="Marcar como Completada">
                        <i class="ph ph-check-circle text-lg sm:text-xl"></i>
                    </button>
                    <button onclick="cambiarEstadoCita(${c.id}, 'Cancelada')" class="p-1 hover:bg-rose-50 text-slate-400 hover:text-rose-600 rounded-lg transition" title="Cancelar Cita">
                        <i class="ph ph-x-circle text-lg sm:text-xl"></i>
                    </button>
                `;
            } else if (c.estado === 'Completada') {
                statusBadge = '<span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-600 border border-emerald-200">Completada</span>';
            } else {
                statusBadge = '<span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-600 border border-rose-200">Cancelada</span>';
            }

            // Formateado compacto de fecha para pantallas pequeñas (soporta espacios o 'T' y recorta segundos)
            const parts = c.fecha_cita.split(/[\sT]/);
            const fechaParte = parts[0];
            const horaParte = parts[1] ? parts[1].substring(0, 5) : '';

            row.innerHTML = `
                <td class="px-4 sm:px-6 py-4 text-xs sm:text-sm font-medium text-slate-700">
                    <div class="font-semibold">${fechaParte}</div>
                    <div class="text-[10px] text-slate-400 sm:text-xs">${horaParte}</div>
                </td>
                <td class="px-4 sm:px-6 py-4 hidden sm:table-cell font-semibold text-slate-700 text-xs sm:text-sm">${escapeHTML(c.cedula)}</td>
                <td class="px-4 sm:px-6 py-4 font-medium text-slate-800 text-xs sm:text-sm">
                    <div class="truncate max-w-[100px] sm:max-w-none">${escapeHTML(c.nombres)} ${escapeHTML(c.apellidos)}</div>
                </td>
                <td class="px-4 sm:px-6 py-4 hidden lg:table-cell"><span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200/50">${escapeHTML(c.categoria)}</span></td>
                <td class="px-4 sm:px-6 py-4">${statusBadge}</td>
                <td class="px-4 sm:px-6 py-4 text-right">
                    <div class="flex justify-end gap-1.5 sm:gap-2">
                        ${actionButtons}
                    </div>
                </td>
            `;
            tbody.appendChild(row);
        }
    });

    if (emptyState) {
        if (visibleCount === 0) emptyState.classList.remove('hidden');
        else emptyState.classList.add('hidden');
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
                Swal.fire({
                    icon: 'success',
                    title: '¡Actualizado!',
                    text: json.message,
                    timer: 2000,
                    showConfirmButton: false
                });
                loadCitas();
            } else {
                Swal.fire('Error', json.message, 'error');
            }
        } catch(e) {
            Swal.fire('Error', 'No se pudo actualizar la cita.', 'error');
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
                Swal.fire({
                    icon: 'success',
                    title: '¡Cita Agendada!',
                    text: json.message,
                    confirmButtonColor: '#3b82f6'
                });
                closeCitaModal();
                // Recargar stats y tabla
                loadStats();
                if(currentTab === 'citas') loadCitas();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de reserva',
                    text: json.message,
                    confirmButtonColor: '#3b82f6'
                });
            }
        } catch (error) {
            Swal.fire('Error', 'Error al conectarse con el servidor para agendar.', 'error');
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
