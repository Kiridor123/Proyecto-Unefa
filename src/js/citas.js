let citasRequestVersion = 0;
let citaLookupVersion = 0;
let citasSearchTimer = null;
const updatingAppointments = new Set();
const CITAS_POR_PAGINA = 25;
const CITAS_SEARCH_MAX = 100;
const CITAS_ESTADOS_VALIDOS = new Set(['', 'Pendiente', 'Completada', 'Cancelada']);
// Estado de la agenda: vista rápida (hoy, próximas, vencidas, todas) o un día concreto.
const citasFiltro = { vista: 'proximas', pagina: 1 };

function sanitizeCitasSearch(value) {
    return String(value ?? '')
        .replace(/[\t\r\n]+/g, ' ')
        .replace(/[\x00-\x1F\x7F]+/g, '')
        .replace(/\s+/g, ' ')
        .trim()
        .slice(0, CITAS_SEARCH_MAX);
}
function createStatusBadgeNode(label, modifier = 'neutral') {
    const badge = document.createElement('span');
    badge.className = `status-badge status-${modifier}`;
    badge.textContent = label || 'Sin estado';
    return badge;
}
function appendAppointmentStatusNodes(container, appointment) {
    const modifier = { Pendiente: 'pending', Completada: 'completed', Cancelada: 'cancelled' }[appointment.estado] || 'neutral';
    container.append(createStatusBadgeNode(appointment.estado || 'Sin estado', modifier));
    if (isOverdue(appointment)) {
        container.append(document.createTextNode(' '), createStatusBadgeNode('Vencida', 'overdue'));
    }
}

// Comprueba un permiso del contrato de sesión; sin can() (p. ej. durante la integración) no bloquea.
function vistaPuede(permiso) {
    return typeof can === 'function' ? can(permiso) : true;
}
function appointmentStatus(value) {
    const status = { Pendiente: 'pending', Completada: 'completed', Cancelada: 'cancelled' }[value] || 'neutral';
    return `<span class="status-badge status-${status}">${escapeHTML(value || 'Sin estado')}</span>`;
}
function addDays(date, days) {
    const copy = new Date(date);
    copy.setDate(copy.getDate() + days);
    return copy;
}
function parseLocalDateTime(value) {
    const parts = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2}))?/);
    if (!parts) return null;
    return new Date(Number(parts[1]), Number(parts[2]) - 1, Number(parts[3]), Number(parts[4] || 0), Number(parts[5] || 0));
}
function formatDayHeading(value) {
    const date = parseLocalDateTime(value);
    if (!date) return 'Fecha sin registro';
    const day = localDate(date);
    const today = localDate();
    const prefix = day === today ? 'Hoy · ' : day === localDate(addDays(new Date(), 1)) ? 'Mañana · ' : day === localDate(addDays(new Date(), -1)) ? 'Ayer · ' : '';
    const label = date.toLocaleDateString('es-VE', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    return prefix + label.charAt(0).toUpperCase() + label.slice(1);
}
function formatTime(value) {
    const parts = String(value || '').match(/[ T](\d{2}):(\d{2})/);
    return parts ? `${parts[1]}:${parts[2]}` : '—';
}
function isOverdue(appointment) {
    const date = parseLocalDateTime(appointment.fecha_cita);
    return appointment.estado === 'Pendiente' && date !== null && date < new Date();
}

// Paginación compartida por las vistas de W3b (agenda y auditoría).
function vistasRenderPager(container, meta, onPage, unidad = ['registro', 'registros']) {
    if (!container) return;
    container.replaceChildren();
    const total = Number(meta?.total || 0);
    const porPagina = Math.max(1, Number(meta?.por_pagina || 1));
    const pagina = Math.max(1, Number(meta?.pagina || 1));
    const paginas = Math.max(1, Math.ceil(total / porPagina));
    if (total <= porPagina && pagina === 1) { setVisible(container, false); return; }
    const desde = total ? (pagina - 1) * porPagina + 1 : 0;
    const hasta = Math.min(total, pagina * porPagina);
    const resumen = document.createElement('p');
    resumen.className = 'list-pager-summary';
    resumen.textContent = `${desde.toLocaleString('es-VE')}–${hasta.toLocaleString('es-VE')} de ${total.toLocaleString('es-VE')} ${total === 1 ? unidad[0] : unidad[1]} · Página ${pagina} de ${paginas}`;
    const botones = document.createElement('div');
    botones.className = 'record-actions';
    const anterior = textAction('Anterior', 'ph-caret-left', () => onPage(pagina - 1));
    anterior.disabled = pagina <= 1;
    anterior.setAttribute('aria-label', 'Página anterior');
    const siguiente = textAction('Siguiente', 'ph-caret-right', () => onPage(pagina + 1));
    siguiente.disabled = pagina >= paginas;
    siguiente.setAttribute('aria-label', 'Página siguiente');
    botones.append(anterior, siguiente);
    container.append(resumen, botones);
    setVisible(container, true, 'flex');
}

function citasQueryParameters() {
    const estadoSelect = document.getElementById('filterCitasEstado');
    const rawFecha = document.getElementById('filterCitasFecha')?.value || '';
    const fecha = /^\d{4}-\d{2}-\d{2}$/.test(rawFecha) ? rawFecha : '';
    const q = sanitizeCitasSearch(document.getElementById('filterCitasPaciente')?.value);
    const parameters = new URLSearchParams({ pagina: String(citasFiltro.pagina), por_pagina: String(CITAS_POR_PAGINA) });
    const rawEstado = estadoSelect?.value || '';
    let estado = CITAS_ESTADOS_VALIDOS.has(rawEstado) ? rawEstado : '';
    const hoy = localDate();
    if (fecha) {
        parameters.set('fecha', fecha);
        parameters.set('orden', 'asc');
    } else if (citasFiltro.vista === 'hoy') {
        parameters.set('fecha', hoy);
        parameters.set('orden', 'asc');
    } else if (citasFiltro.vista === 'proximas') {
        parameters.set('desde', hoy);
        parameters.set('orden', 'asc');
    } else if (citasFiltro.vista === 'vencidas') {
        // Pendientes de días anteriores; las atrasadas de hoy se señalan en la vista «Hoy».
        parameters.set('hasta', localDate(addDays(new Date(), -1)));
        parameters.set('orden', 'desc');
        estado = 'Pendiente';
    } else {
        parameters.set('orden', 'desc');
    }
    if (estado) parameters.set('estado', estado);
    if (q) parameters.set('q', q);
    return parameters;
}
function citasScopeDescription() {
    const fecha = document.getElementById('filterCitasFecha')?.value || '';
    if (fecha) return `Citas del ${formatDate(fecha)}`;
    return {
        hoy: 'Citas de hoy, en orden de llegada',
        proximas: 'Citas desde hoy en adelante',
        vencidas: 'Citas pendientes de días anteriores: complételas, reprográmelas o cancélelas',
        todas: 'Todas las citas, de la más reciente a la más antigua'
    }[citasFiltro.vista] || 'Fecha, paciente, motivo y estado de cada cita';
}
function syncCitasFilterControls() {
    const fecha = document.getElementById('filterCitasFecha')?.value || '';
    document.querySelectorAll('[data-citas-vista]').forEach(button => {
        button.setAttribute('aria-pressed', String(!fecha && button.dataset.citasVista === citasFiltro.vista));
    });
    const estado = document.getElementById('filterCitasEstado');
    if (estado) {
        const forced = !fecha && citasFiltro.vista === 'vencidas';
        if (forced) { estado.dataset.previous ??= estado.value; estado.value = 'Pendiente'; }
        else if (estado.dataset.previous !== undefined) { estado.value = estado.dataset.previous; delete estado.dataset.previous; }
        estado.disabled = forced;
        estado.title = forced ? 'Las citas vencidas siempre están pendientes.' : '';
    }
    const scope = document.getElementById('citasScopeText');
    if (scope) scope.textContent = citasScopeDescription();
}
function renderCitasResumen(resumen) {
    const values = { citasResumenHoy: resumen?.hoy_pendientes, citasResumenProximas: resumen?.proximas_pendientes, citasResumenVencidas: resumen?.vencidas_pendientes };
    for (const [id, value] of Object.entries(values)) {
        const element = document.getElementById(id);
        if (!element) continue;
        const count = Number(value || 0);
        element.textContent = value === undefined || value === null ? '' : count.toLocaleString('es-VE');
        element.classList.toggle('hidden', value === undefined || value === null || (id === 'citasResumenVencidas' && !count));
        // El contador solo cuenta pendientes; la lista de «Hoy» y «Próximas» muestra todos los estados.
        element.setAttribute('aria-label', `${count} ${count === 1 ? 'pendiente' : 'pendientes'}`);
        element.title = `${count.toLocaleString('es-VE')} ${count === 1 ? 'cita pendiente' : 'citas pendientes'}`;
    }
}
// Permite a otras vistas (p. ej. el panel) abrir la agenda con una vista concreta.
function mostrarCitasDe(vista = 'hoy') {
    citasFiltro.vista = ['hoy', 'proximas', 'vencidas', 'todas'].includes(vista) ? vista : 'proximas';
    citasFiltro.pagina = 1;
    const fecha = document.getElementById('filterCitasFecha');
    if (fecha) fecha.value = '';
    const search = document.getElementById('filterCitasPaciente');
    if (search) search.value = '';
    syncCitasFilterControls();
    if (typeof currentTab !== 'undefined' && currentTab === 'citas') loadCitas();
    else switchTab('citas');
}

async function loadCitas() {
    clearTimeout(citasSearchTimer);
    citasSearchTimer = null;
    const version = ++citasRequestVersion;
    const loader = document.getElementById('citasTableLoader');
    const table = document.getElementById('citasTableWrap');
    const cards = document.getElementById('citasTableBodyCards');
    const pager = document.getElementById('citasPager');
    if (!loader) return;
    syncCitasFilterControls();
    setVisible(document.getElementById('citasError'), false);
    setVisible(document.getElementById('citasEmptyState'), false);
    setVisible(loader, true, 'flex');
    setVisible(table, false);
    setVisible(cards, false);
    setVisible(pager, false);
    document.getElementById('section-citas')?.setAttribute('aria-busy', 'true');
    try {
        const json = await apiRequest(`api/get_citas.php?${citasQueryParameters()}`, {}, 'citas');
        if (version !== citasRequestVersion) return;
        citasList = Array.isArray(json.data) ? json.data : [];
        const meta = json.meta || { total: citasList.length, pagina: 1, por_pagina: Math.max(citasList.length, 1) };
        const paginas = Math.max(1, Math.ceil(Number(meta.total || 0) / Math.max(1, Number(meta.por_pagina || 1))));
        // Si una acción vació la última página, se retrocede a la anterior.
        if (!citasList.length && citasFiltro.pagina > 1 && citasFiltro.pagina > paginas) {
            citasFiltro.pagina = paginas;
            loadCitas();
            return;
        }
        renderCitasResumen(json.resumen);
        renderCitasTable(meta);
        setVisible(table, citasList.length > 0);
        setVisible(cards, citasList.length > 0);
        vistasRenderPager(pager, meta, pagina => { citasFiltro.pagina = pagina; loadCitas(); document.getElementById('citasTitle')?.scrollIntoView({ block: 'start' }); }, ['cita', 'citas']);
    } catch (error) {
        if (error.name === 'AbortError' || version !== citasRequestVersion) return;
        if (error.status === 422) {
            showNotification('warning', error.message || 'Revise los filtros de la agenda.');
        } else {
            showBlockError('citasError', loader, error.message, loadCitas);
        }
    } finally {
        if (version === citasRequestVersion) {
            setVisible(loader, false);
            document.getElementById('section-citas')?.setAttribute('aria-busy', 'false');
        }
    }
}
function appointmentButton(label, icon, appointment, callback, className = 'btn btn-secondary') {
    const button = textAction(label, icon, callback, className);
    button.dataset.appointmentId = String(appointment.id);
    button.disabled = updatingAppointments.has(Number(appointment.id));
    return button;
}
function atenderCita(appointment) {
    if (!vistaPuede('clinica.editar')) return;
    openModal(appointment.cedula || '', '', '', '', { citaId: Number(appointment.id) });
}
function appointmentActions(appointment) {
    const actions = document.createElement('div');
    actions.className = 'record-actions';
    if (appointment.estado !== 'Pendiente') return actions;
    const name = `${appointment.nombres ?? ''} ${appointment.apellidos ?? ''}`.trim();
    if (vistaPuede('clinica.editar')) {
        const attend = appointmentButton('Atender', 'ph-stethoscope', appointment, () => atenderCita(appointment), 'btn btn-primary');
        attend.setAttribute('aria-label', `Atender a ${name} y registrar la consulta`);
        actions.append(attend);
    }
    const reschedule = appointmentButton('Reprogramar', 'ph-calendar-dots', appointment, () => reprogramarCita(appointment));
    reschedule.setAttribute('aria-label', `Reprogramar la cita de ${name}`);
    actions.append(reschedule);
    for (const [label, icon, status] of [['Completar', 'ph-check', 'Completada'], ['Cancelar', 'ph-x', 'Cancelada']]) {
        const button = appointmentButton(label, icon, appointment, () => cambiarEstadoCita(appointment.id, status), 'btn btn-text');
        button.setAttribute('aria-label', `${label} la cita de ${name}`);
        actions.append(button);
    }
    return actions;
}
function appointmentPatientName(appointment) {
    const fullName = `${appointment.nombres ?? ''} ${appointment.apellidos ?? ''}`.trim() || 'Paciente sin nombre';
    if (vistaPuede('clinica.ver') && appointment.paciente_id && typeof viewPatientDetails === 'function') {
        const button = textAction(fullName, '', () => viewPatientDetails(appointment.paciente_id), 'patient-name-button');
        button.setAttribute('aria-label', `Ver la historia clínica de ${fullName}`);
        return button;
    }
    const strong = document.createElement('strong');
    strong.textContent = fullName;
    return strong;
}
function appointmentDetail(appointment) {
    const wrapper = document.createElement('div');
    wrapper.className = 'appointment-patient';
    wrapper.append(appointmentPatientName(appointment));
    if (appointment.motivo) {
        const motivo = document.createElement('span');
        motivo.className = 'table-secondary appointment-reason';
        motivo.textContent = appointment.motivo;
        wrapper.append(motivo);
    }
    return wrapper;
}
function appointmentStatusHTML(appointment) {
    return appointmentStatus(appointment.estado) + (isOverdue(appointment) ? ' <span class="status-badge status-overdue">Vencida</span>' : '');
}
function renderCitasTable(meta = null) {
    const body = document.getElementById('citasTableBody');
    const cards = document.getElementById('citasTableBodyCards');
    if (!body) return;
    body.replaceChildren();
    cards?.replaceChildren();
    let currentDay = '';
    citasList.forEach(appointment => {
        const day = String(appointment.fecha_cita || '').slice(0, 10);
        if (day !== currentDay) {
            currentDay = day;
            const heading = formatDayHeading(appointment.fecha_cita);
            const dayRow = document.createElement('tr');
            dayRow.className = 'day-row';
            const cell = document.createElement('th');
            cell.scope = 'colgroup';
            cell.colSpan = 6;
            cell.textContent = heading;
            dayRow.append(cell);
            body.append(dayRow);
            const dayCard = document.createElement('h3');
            dayCard.className = 'record-day-heading';
            dayCard.textContent = heading;
            cards?.append(dayCard);
        }
        const time = formatTime(appointment.fecha_cita);
        const row = document.createElement('tr');
        if (isOverdue(appointment)) row.className = 'is-overdue';
        const timeCell = document.createElement('td');
        timeCell.className = 'tabular-nums';
        const timeEl = document.createElement('time');
        timeEl.dateTime = String(appointment.fecha_cita || '').replace(' ', 'T');
        timeEl.textContent = time;
        timeCell.append(timeEl);
        const cedulaCell = document.createElement('td');
        cedulaCell.textContent = String(appointment.cedula || '');
        const patientCell = document.createElement('td');
        patientCell.className = 'patient-name-cell';
        patientCell.append(appointmentDetail(appointment));
        const catCell = document.createElement('td');
        catCell.textContent = String(appointment.categoria || '');
        const statusCell = document.createElement('td');
        appendAppointmentStatusNodes(statusCell, appointment);
        const actionsCell = document.createElement('td');
        actionsCell.append(appointmentActions(appointment));
        row.append(timeCell, cedulaCell, patientCell, catCell, statusCell, actionsCell);
        body.append(row);

        const card = document.createElement('article');
        card.className = 'record-card';
        const identity = document.createElement('div');
        identity.className = 'record-identity';
        const who = document.createElement('div');
        who.append(appointmentDetail(appointment));
        const cedula = document.createElement('p');
        cedula.textContent = `${appointment.cedula ?? ''} · ${appointment.categoria ?? ''}`;
        who.append(cedula);
        const statusWrap = document.createElement('span');
        statusWrap.className = 'record-status';
        appendAppointmentStatusNodes(statusWrap, appointment);
        identity.append(who, statusWrap);
        const date = document.createElement('p');
        date.className = 'appointment-date';
        date.textContent = `${time} · ${formatDate(appointment.fecha_cita)}`;
        card.append(identity, date);
        if (appointment.estado === 'Pendiente') card.append(appointmentActions(appointment));
        cards?.append(card);
    });
    const total = Number(meta?.total ?? citasList.length);
    const count = document.getElementById('citasCount');
    if (count) count.textContent = `${total.toLocaleString('es-VE')} ${total === 1 ? 'cita' : 'citas'}`;
    const empty = document.getElementById('citasEmptyState');
    setVisible(empty, !citasList.length, 'flex');
    if (empty && !citasList.length) {
        const query = (document.getElementById('filterCitasPaciente')?.value || '').trim();
        const title = empty.querySelector('h3, h4');
        const copy = empty.querySelector('p');
        const messages = query
            ? ['Sin coincidencias', 'Pruebe otro nombre o cédula, o cambie el periodo de la agenda.']
            : document.getElementById('filterCitasFecha')?.value
                ? ['No hay citas ese día', 'Elija otra fecha o agende una nueva atención.']
                : {
                    hoy: ['No hay citas para hoy', 'Las citas programadas para hoy aparecerán aquí.'],
                    proximas: ['No hay próximas citas', 'Agende una cita para comenzar la planificación.'],
                    vencidas: ['No hay citas vencidas', 'Todas las citas de días anteriores fueron cerradas.'],
                    todas: ['Aún no hay citas', 'Agende la primera atención del servicio.']
                }[citasFiltro.vista] || ['No hay citas en esta selección', 'Cambie los filtros o agende una cita.'];
        if (title) title.textContent = messages[0];
        if (copy) copy.textContent = messages[1];
    }
}

async function handleAppointmentConflict(error) {
    // FE-6: si otra persona ya cerró, movió o eliminó la cita, se sincroniza la vista.
    if (error.status === 404 || error.status === 409) {
        showNotification('warning', error.message, 6000);
        const tasks = [loadCitas()];
        if (typeof loadStats === 'function') tasks.push(loadStats());
        await Promise.allSettled(tasks);
        return true;
    }
    return false;
}
function setAppointmentBusy(id, busy) {
    if (busy) updatingAppointments.add(id); else updatingAppointments.delete(id);
    document.querySelectorAll('[data-appointment-id]').forEach(button => {
        if (button.dataset.appointmentId === String(id)) button.disabled = busy;
    });
}
async function cambiarEstadoCita(citaId, nuevoEstado) {
    const id = Number(citaId);
    if (updatingAppointments.has(id) || !['Completada', 'Cancelada'].includes(nuevoEstado)) return;
    setAppointmentBusy(id, true);
    try {
        const completing = nuevoEstado === 'Completada';
        const text = completing
            ? 'La cita quedará como completada sin registrar ninguna consulta. Si va a atender al paciente, use «Atender».'
            : 'La cita quedará registrada como cancelada.';
        const confirmed = typeof Swal !== 'undefined' ? (await Swal.fire({
            titleText: completing ? 'Completar sin consulta' : 'Cancelar esta cita',
            text, icon: completing ? 'warning' : 'question', showCancelButton: true, focusCancel: true,
            confirmButtonColor: '#202a30', cancelButtonColor: '#667078',
            confirmButtonText: completing ? 'Completar cita' : 'Cancelar cita', cancelButtonText: 'Volver'
        })).isConfirmed : window.confirm(text);
        if (!confirmed) return;
        const data = new FormData();
        data.append('cita_id', String(id));
        data.append('estado', nuevoEstado);
        const json = await apiRequest('api/update_cita_estado.php', { method: 'POST', body: data });
        showNotification('success', json.message);
        await Promise.allSettled([loadCitas(), typeof loadStats === 'function' ? loadStats() : null]);
    } catch (error) {
        if (!(await handleAppointmentConflict(error))) showNotification('error', error.message);
    } finally { setAppointmentBusy(id, false); }
}
async function reprogramarCita(appointment) {
    const id = Number(appointment.id);
    if (updatingAppointments.has(id) || typeof Swal === 'undefined') return;
    setAppointmentBusy(id, true);
    try {
        const name = `${appointment.nombres ?? ''} ${appointment.apellidos ?? ''}`.trim();
        const minimum = localDateTime(new Date(Date.now() + 60000));
        const current = String(appointment.fecha_cita || '').replace(' ', 'T').slice(0, 16);
        const maximum = localDateTime(addDays(new Date(), 730));
        const result = await Swal.fire({
            titleText: 'Reprogramar cita',
            text: `${name} · actualmente ${formatDate(appointment.fecha_cita, true)}`,
            input: 'datetime-local',
            inputLabel: 'Nueva fecha y hora',
            inputValue: current > minimum ? current : minimum,
            inputAttributes: { min: minimum, max: maximum, required: 'required', 'aria-label': 'Nueva fecha y hora de la cita' },
            showCancelButton: true, confirmButtonColor: '#202a30', cancelButtonColor: '#667078',
            confirmButtonText: 'Reprogramar', cancelButtonText: 'Volver',
            showLoaderOnConfirm: true,
            allowOutsideClick: () => !Swal.isLoading(),
            inputValidator: value => {
                if (!value) return 'Indique la nueva fecha y hora.';
                if (value <= localDateTime()) return 'Seleccione una fecha y hora posteriores a la actual.';
                if (value > maximum) return 'La cita no puede programarse a más de dos años.';
                if (value === current) return 'Seleccione una fecha u hora distinta a la actual.';
                return undefined;
            },
            preConfirm: async value => {
                const data = new FormData();
                data.append('cita_id', String(id));
                data.append('fecha_cita', value);
                try {
                    return await apiRequest('api/reprogramar_cita.php', { method: 'POST', body: data });
                } catch (error) {
                    if (error.status === 404 || (error.status === 409 && !error.errors?.fecha_cita)) return { conflicto: error };
                    Swal.showValidationMessage(error.errors?.fecha_cita || error.message);
                    return false;
                }
            }
        });
        if (!result.isConfirmed || !result.value) return;
        if (result.value.conflicto) { await handleAppointmentConflict(result.value.conflicto); return; }
        showNotification('success', result.value.message || 'La cita se reprogramó correctamente.');
        await Promise.allSettled([loadCitas(), typeof loadStats === 'function' ? loadStats() : null]);
    } catch (error) {
        if (!(await handleAppointmentConflict(error))) showNotification('error', error.message);
    } finally { setAppointmentBusy(id, false); }
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
        if (fecha.value > localDateTime(addDays(new Date(), 730))) {
            handleInlineErrors({ fecha_cita: 'La cita no puede programarse a más de dos años.' }, this.id);
            return;
        }
        button.textContent = 'Programando…';
        const data = new FormData(this);
        if (Number(data.get('paciente_id')) <= 0) data.delete('paciente_id');
        const motivo = String(data.get('motivo') || '').trim();
        if (motivo) data.set('motivo', motivo); else data.delete('motivo');
        const json = await apiRequest('api/save_cita.php', { method: 'POST', body: data });
        setFormBusy(this, false);
        closeCitaModal();
        showNotification('success', json.message);
        // Una cita no cambia la morbilidad: se refrescan solo el panel, los pacientes y la agenda.
        const tasks = [typeof loadStats === 'function' ? loadStats() : null, typeof loadPacientes === 'function' ? loadPacientes() : null];
        if (currentTab === 'citas') tasks.push(loadCitas());
        await Promise.allSettled(tasks);
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
const filterCitasPacienteInput = document.getElementById('filterCitasPaciente');
filterCitasPacienteInput?.addEventListener('input', () => {
    clearTimeout(citasSearchTimer);
    citasSearchTimer = setTimeout(() => { citasFiltro.pagina = 1; loadCitas(); }, 300);
});
filterCitasPacienteInput?.addEventListener('keydown', event => {
    if (event.key === 'Enter') {
        event.preventDefault();
        citasFiltro.pagina = 1;
        loadCitas();
    } else if (event.key === 'Escape' && filterCitasPacienteInput.value) {
        event.preventDefault();
        filterCitasPacienteInput.value = '';
        citasFiltro.pagina = 1;
        loadCitas();
    }
});
document.getElementById('filterCitasEstado')?.addEventListener('change', () => { citasFiltro.pagina = 1; loadCitas(); });
document.getElementById('filterCitasFecha')?.addEventListener('change', () => { citasFiltro.pagina = 1; loadCitas(); });
document.getElementById('btnLimpiarCitas')?.addEventListener('click', () => {
    if (filterCitasPacienteInput) filterCitasPacienteInput.value = '';
    const fecha = document.getElementById('filterCitasFecha');
    if (fecha) fecha.value = '';
    const estado = document.getElementById('filterCitasEstado');
    if (estado && !estado.disabled) estado.value = '';
    citasFiltro.pagina = 1;
    loadCitas();
});
document.querySelectorAll('[data-citas-vista]').forEach(button => button.addEventListener('click', () => {
    const fecha = document.getElementById('filterCitasFecha');
    if (citasFiltro.vista === button.dataset.citasVista && !fecha?.value) return;
    citasFiltro.vista = button.dataset.citasVista;
    citasFiltro.pagina = 1;
    if (fecha) fecha.value = '';
    loadCitas();
}));
syncCitasFilterControls();
