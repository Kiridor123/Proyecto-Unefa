let citasRequestVersion = 0;
let citaLookupVersion = 0;
const updatingAppointments = new Set();

function appointmentStatus(value) {
    const status = { Pendiente: 'pending', Completada: 'completed', Cancelada: 'cancelled' }[value] || 'neutral';
    return `<span class="status-badge status-${status}">${escapeHTML(value || 'Sin estado')}</span>`;
}
async function loadCitas() {
    const version = ++citasRequestVersion;
    const loader = document.getElementById('citasTableLoader');
    const table = document.getElementById('citasTableWrap');
    const cards = document.getElementById('citasTableBodyCards');
    setVisible(document.getElementById('citasError'), false);
    setVisible(document.getElementById('citasEmptyState'), false);
    setVisible(loader, true, 'flex');
    setVisible(table, false);
    setVisible(cards, false);
    document.getElementById('section-citas')?.setAttribute('aria-busy', 'true');
    try {
        const state = document.getElementById('filterCitasEstado')?.value || '';
        const json = await apiRequest(`api/get_citas.php?estado=${encodeURIComponent(state)}`, {}, 'citas');
        if (version !== citasRequestVersion) return;
        citasList = Array.isArray(json.data) ? json.data : [];
        renderCitasTable();
        setVisible(table, true);
        setVisible(cards, true);
    } catch (error) {
        if (error.name !== 'AbortError') showBlockError('citasError', loader, error.message, loadCitas);
    } finally {
        if (version === citasRequestVersion) {
            setVisible(loader, false);
            document.getElementById('section-citas')?.setAttribute('aria-busy', 'false');
        }
    }
}
function appointmentActions(appointment) {
    const actions = document.createElement('div');
    actions.className = 'record-actions';
    if (appointment.estado === 'Pendiente') {
        for (const [label, icon, status] of [['Completar', 'ph-check', 'Completada'], ['Cancelar', 'ph-x', 'Cancelada']]) {
            const button = textAction(label, icon, () => cambiarEstadoCita(appointment.id, status));
            button.dataset.appointmentId = String(appointment.id);
            button.disabled = updatingAppointments.has(Number(appointment.id));
            actions.append(button);
        }
    }
    return actions;
}
function renderCitasTable() {
    const body = document.getElementById('citasTableBody');
    const cards = document.getElementById('citasTableBodyCards');
    if (!body) return;
    const query = normalizeSearch(document.getElementById('filterCitasPaciente')?.value);
    const appointments = citasList.filter(appointment => normalizeSearch(`${appointment.cedula} ${appointment.nombres} ${appointment.apellidos}`).includes(query));
    body.replaceChildren();
    cards?.replaceChildren();
    appointments.forEach(appointment => {
        const fullName = `${appointment.nombres ?? ''} ${appointment.apellidos ?? ''}`.trim();
        const date = formatDate(appointment.fecha_cita, true);
        const row = document.createElement('tr');
        row.innerHTML = `<td class="tabular-nums">${escapeHTML(date)}</td><td>${escapeHTML(appointment.cedula)}</td><td>${escapeHTML(fullName)}</td><td>${escapeHTML(appointment.categoria)}</td><td>${appointmentStatus(appointment.estado)}</td><td></td>`;
        row.lastElementChild.append(appointmentActions(appointment));
        body.append(row);
        const card = document.createElement('article');
        card.className = 'record-card';
        card.innerHTML = `<div class="record-identity"><div><h3>${escapeHTML(fullName)}</h3><p>${escapeHTML(appointment.cedula)}</p></div>${appointmentStatus(appointment.estado)}</div><p class="appointment-date">${escapeHTML(date)}</p><p class="record-meta">${escapeHTML(appointment.categoria)}</p>`;
        if (appointment.estado === 'Pendiente') card.append(appointmentActions(appointment));
        cards?.append(card);
    });
    const count = document.getElementById('citasCount');
    if (count) count.textContent = `${appointments.length} ${appointments.length === 1 ? 'cita' : 'citas'}`;
    const empty = document.getElementById('citasEmptyState');
    setVisible(empty, !appointments.length, 'flex');
    if (empty) {
        const title = empty.querySelector('h3, h4');
        const copy = empty.querySelector('p');
        if (title) title.textContent = query ? 'Sin coincidencias' : 'No hay citas en esta selección';
        if (copy) copy.textContent = query ? 'Pruebe otro nombre o cédula.' : 'Cambie el estado del filtro o programe una cita.';
    }
}

async function cambiarEstadoCita(citaId, nuevoEstado) {
    const id = Number(citaId);
    if (updatingAppointments.has(id) || !['Completada', 'Cancelada'].includes(nuevoEstado)) return;
    updatingAppointments.add(id);
    const buttons = [...document.querySelectorAll('[data-appointment-id]')].filter(button => button.dataset.appointmentId === String(id));
    buttons.forEach(button => { button.disabled = true; });
    try {
        const confirmed = typeof Swal !== 'undefined' ? (await Swal.fire({
            titleText: nuevoEstado === 'Completada' ? 'Completar esta cita' : 'Cancelar esta cita',
            text: nuevoEstado === 'Completada' ? 'La cita quedará registrada como completada.' : 'La cita quedará registrada como cancelada.',
            icon: 'question', showCancelButton: true, confirmButtonColor: '#202a30', cancelButtonColor: '#667078',
            confirmButtonText: nuevoEstado === 'Completada' ? 'Completar cita' : 'Cancelar cita', cancelButtonText: 'Volver'
        })).isConfirmed : window.confirm(`¿Cambiar la cita a ${nuevoEstado.toLowerCase()}?`);
        if (!confirmed) return;
        const data = new FormData();
        data.append('cita_id', id);
        data.append('estado', nuevoEstado);
        const json = await apiRequest('api/update_cita_estado.php', { method: 'POST', body: data });
        showNotification('success', json.message);
        await Promise.allSettled([loadCitas(), loadStats()]);
    } catch (error) { showNotification('error', error.message); }
    finally {
        updatingAppointments.delete(id);
        document.querySelectorAll('[data-appointment-id]').forEach(button => {
            if (button.dataset.appointmentId === String(id)) button.disabled = false;
        });
    }
}

function setCitaLookupStatus(message) {
    let status = document.getElementById('citaLookupStatus');
    if (!status) {
        status = document.createElement('p');
        status.id = 'citaLookupStatus';
        status.className = 'field-help';
        status.setAttribute('role', 'status');
        document.getElementById('citaCedula')?.insertAdjacentElement('afterend', status);
    }
    status.textContent = message;
    setVisible(status, Boolean(message));
}
function setCitaPatient(patient) {
    document.getElementById('citaPacienteId').value = patient?.id || 0;
    document.getElementById('citaPacienteNombreText').textContent = patient ? `${patient.nombres} ${patient.apellidos} · ${patient.categoria}` : '';
    setVisible(document.getElementById('citaPacienteInfo'), Boolean(patient));
    setVisible(document.getElementById('citaNuevoPacienteCampos'), !patient);
    ['citaNombres', 'citaApellidos', 'citaCategoria'].forEach(id => { document.getElementById(id).required = !patient; });
}
async function buscarPacienteParaCita(cedula) {
    const form = document.getElementById('citaForm');
    const normalized = normalizeCedula(cedula);
    if (form.dataset.lookupCedula === normalized) return true;
    const version = ++citaLookupVersion;
    activeRequests.get('citaLookup')?.abort();
    document.getElementById('citaPacienteId').value = '0';
    if (!/^[VEJGCPN]-\d{1,18}$/.test(normalized)) { setCitaLookupStatus('Ingrese una cédula válida, por ejemplo V-12345678.'); return false; }
    setCitaLookupStatus('Buscando paciente…');
    try {
        const json = await apiRequest(`api/get_paciente.php?cedula=${encodeURIComponent(normalized)}`, {}, 'citaLookup');
        if (version !== citaLookupVersion || normalizeCedula(document.getElementById('citaCedula').value) !== normalized) return false;
        setCitaPatient(json.data);
        form.dataset.lookupCedula = normalized;
        setCitaLookupStatus('');
        return true;
    } catch (error) {
        if (error.name === 'AbortError' || version !== citaLookupVersion) return false;
        if (error.status === 404) {
            setCitaPatient(null);
            form.dataset.lookupCedula = normalized;
            setCitaLookupStatus('El paciente se registrará junto con la cita.');
            return true;
        }
        setCitaLookupStatus(error.message);
        return false;
    }
}
const citaForm = document.getElementById('citaForm');
citaForm?.addEventListener('submit', async function (event) {
    event.preventDefault();
    if (this.dataset.saving === 'true') return;
    clearInlineErrors(this.id);
    const cedula = document.getElementById('citaCedula');
    if (!cedula.checkValidity()) { cedula.reportValidity(); return; }
    const button = document.getElementById('btnGuardarCita');
    const original = button.innerHTML;
    setFormBusy(this, true);
    button.textContent = 'Verificando…';
    try {
        if (!(await buscarPacienteParaCita(cedula.value)) || !validateForm(this)) return;
        const fecha = this.elements.namedItem('fecha_cita');
        if (fecha.value <= localDateTime()) {
            handleInlineErrors({ fecha_cita: 'Seleccione una fecha y hora posteriores a la actual.' }, this.id);
            return;
        }
        button.textContent = 'Programando…';
        const data = new FormData(this);
        if (Number(data.get('paciente_id')) <= 0) data.delete('paciente_id');
        const json = await apiRequest('api/save_cita.php', { method: 'POST', body: data });
        setFormBusy(this, false);
        closeCitaModal();
        showNotification('success', json.message);
        await refreshClinicalViews();
    } catch (error) { showFormError(error, this.id); }
    finally { setFormBusy(this, false); button.innerHTML = original; }
});
document.getElementById('citaCedula')?.addEventListener('input', () => {
    citaLookupVersion++;
    activeRequests.get('citaLookup')?.abort();
    delete citaForm.dataset.lookupCedula;
    document.getElementById('citaPacienteId').value = '0';
    setVisible(document.getElementById('citaPacienteInfo'), false);
    setVisible(document.getElementById('citaNuevoPacienteCampos'), false);
    ['citaNombres', 'citaApellidos', 'citaCategoria'].forEach(id => { document.getElementById(id).value = ''; document.getElementById(id).required = false; });
    setCitaLookupStatus('');
});
document.getElementById('filterCitasPaciente')?.addEventListener('input', debounce(renderCitasTable));
document.getElementById('filterCitasEstado')?.addEventListener('change', loadCitas);
