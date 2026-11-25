// Directorio de pacientes, historia clínica (panel lateral) y guardado del formulario de consulta.
let pacientesRequestVersion = 0;
let patientLookupVersion = 0;
let patientDetailsVersion = 0;
let pendingPatientLookup = null;
let pacientesPage = 1;
let pacientesMeta = { total: 0, pagina: 1, por_pagina: 25 };
let pacientesLoadedOnce = false;
const PACIENTES_POR_PAGINA = 25;
const pacientesById = new Map();
const consultaDetailsCache = new Map();
const editedPatientFields = new Set();
const patientFieldNames = ['categoria', 'sexo', 'nombres', 'apellidos', 'telefono', 'lugar_nacimiento', 'fecha_nacimiento', 'nivel_educativo', 'carrera', 'semestre', 'direccion', 'antecedente_cardiovascular', 'antecedente_oseo', 'antecedente_respiratorio', 'antecedente_digestivo', 'antecedente_endocrino', 'antecedente_otros', 'antecedente_quirurgico', 'gineco_menarquia', 'gineco_sexarquia', 'gineco_aco', 'gineco_gestas', 'gineco_citologia', 'tiene_tatuajes', 'compromiso_tatuajes', 'antecedente_padre', 'antecedente_madre', 'antecedente_hermanos', 'antecedente_hijos'];
const CONSULTA_EDIT_FIELDS = ['motivo_consulta', 'enfermedad_actual', 'diagnostico', 'plan_tratamiento', 'vital_ta', 'vital_fc', 'vital_fr', 'vital_spo2', 'vital_peso_talla', 'fisico_piel', 'fisico_cabeza', 'fisico_cuello', 'fisico_torax', 'fisico_abdomen', 'fisico_extremidades', 'fisico_neurologico', 'laboratorios', 'pendiente'];
const PATIENT_DATA_LABELS = [['sexo', 'Sexo'], ['telefono', 'Teléfono'], ['lugar_nacimiento', 'Lugar de nacimiento'], ['nivel_educativo', 'Nivel educativo'], ['carrera', 'Carrera'], ['semestre', 'Semestre'], ['direccion', 'Dirección', true]];
const BACKGROUND_LABELS = [['antecedente_cardiovascular', 'Cardiovascular'], ['antecedente_oseo', 'Óseo / articular'], ['antecedente_respiratorio', 'Respiratorio'], ['antecedente_digestivo', 'Digestivo'], ['antecedente_endocrino', 'Endocrino / metabólico'], ['antecedente_otros', 'Otros'], ['antecedente_quirurgico', 'Quirúrgicos / traumatológicos', true], ['gineco_menarquia', 'Menarquia'], ['gineco_sexarquia', 'Sexarquia'], ['gineco_aco', 'ACO'], ['gineco_gestas', 'Gestas'], ['gineco_citologia', 'Última citología'], ['tiene_tatuajes', 'Tatuajes'], ['antecedente_padre', 'Padre'], ['antecedente_madre', 'Madre'], ['antecedente_hermanos', 'Hermanos'], ['antecedente_hijos', 'Hijos']];
const CONSULTA_DETAIL_LABELS = [['vital_ta', 'T.A. (mmHg)'], ['vital_fc', 'F.C. (lpm)'], ['vital_fr', 'F.R. (rpm)'], ['vital_spo2', 'SpO₂ (%)'], ['vital_peso_talla', 'Peso / talla'], ['motivo_consulta', 'Motivo de consulta', true], ['enfermedad_actual', 'Enfermedad actual', true], ['fisico_piel', 'Piel y mucosas'], ['fisico_cabeza', 'Cabeza'], ['fisico_cuello', 'Cuello'], ['fisico_torax', 'Tórax'], ['fisico_abdomen', 'Abdomen'], ['fisico_extremidades', 'Extremidades'], ['fisico_neurologico', 'Neurológico'], ['laboratorios', 'Laboratorios', true], ['pendiente', 'Pendiente / observaciones', true]];

function hasValue(value) {
    return value !== null && value !== undefined && String(value).trim() !== '';
}
function patientFullName(patient) {
    return `${patient?.nombres ?? ''} ${patient?.apellidos ?? ''}`.trim();
}
function ageFrom(birthDate) {
    const parts = String(birthDate || '').match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (!parts) return null;
    const today = new Date();
    let age = today.getFullYear() - Number(parts[1]);
    if (today.getMonth() + 1 < Number(parts[2]) || (today.getMonth() + 1 === Number(parts[2]) && today.getDate() < Number(parts[3]))) age--;
    return age >= 0 && age < 130 ? age : null;
}
function statusBadge(estado) {
    if (typeof appointmentStatus === 'function') return appointmentStatus(estado);
    return `<span class="status-badge">${escapeHTML(estado || 'Sin estado')}</span>`;
}

let pacientesSearchTimer = null;
const PACIENTES_SEARCH_MAX = 100;

function sanitizePacientesSearch(value) {
    return String(value ?? '')
        .replace(/[\t\r\n]+/g, ' ')
        .replace(/[\x00-\x1F\x7F]+/g, '')
        .replace(/\s+/g, ' ')
        .trim()
        .slice(0, PACIENTES_SEARCH_MAX);
}

/* ---------- Directorio con búsqueda y paginación en el servidor (FE-4) ---------- */
function pacientesQuery() {
    const params = new URLSearchParams();
    const query = sanitizePacientesSearch(document.getElementById('filterCedula')?.value);
    const category = document.getElementById('filterCategoria')?.value || '';
    if (query) params.set('q', query);
    if (/^[1-9]\d{0,9}$/.test(category)) params.set('categoria_id', category);
    params.set('pagina', String(pacientesPage));
    params.set('por_pagina', String(PACIENTES_POR_PAGINA));
    return params;
}
async function loadPacientes() {
    if (!can('pacientes.ver')) return;
    clearTimeout(pacientesSearchTimer);
    pacientesSearchTimer = null;
    const version = ++pacientesRequestVersion;
    const loader = document.getElementById('tableLoader');
    const table = document.getElementById('pacientesTableWrap');
    const cards = document.getElementById('tableBodyCards');
    const section = document.getElementById('section-pacientes');
    setVisible(document.getElementById('pacientesError'), false);
    // Solo la primera carga oculta la lista; las siguientes conservan lo visible mientras llega la respuesta.
    if (!pacientesLoadedOnce) {
        setVisible(document.getElementById('emptyState'), false);
        setVisible(loader, true, 'flex');
        setVisible(table, false);
        setVisible(cards, false);
    }
    section?.setAttribute('aria-busy', 'true');
    try {
        const json = await apiRequest(`api/get_pacientes.php?${pacientesQuery()}`, {}, 'pacientes');
        if (version !== pacientesRequestVersion) return;
        let rows = Array.isArray(json.data) ? json.data : [];
        if (json.meta && typeof json.meta === 'object') {
            pacientesMeta = {
                total: Number(json.meta.total) || 0,
                pagina: Number(json.meta.pagina) || pacientesPage,
                por_pagina: Number(json.meta.por_pagina) || PACIENTES_POR_PAGINA
            };
            // Si la página pedida ya no existe (p. ej. tras filtrar), se vuelve a la última disponible.
            const lastPage = Math.max(1, Math.ceil(pacientesMeta.total / pacientesMeta.por_pagina));
            if (!rows.length && pacientesPage > lastPage) { pacientesPage = lastPage; return loadPacientes(); }
        } else {
            // Respuesta sin paginar (API anterior): se filtra en el navegador.
            rows = filterPacientesLocally(rows);
            pacientesMeta = { total: rows.length, pagina: 1, por_pagina: rows.length || PACIENTES_POR_PAGINA };
        }
        pacientesList = rows;
        pacientesLoadedOnce = true;
        renderTable();
        setVisible(table, true);
        setVisible(cards, true);
    } catch (error) {
        if (error.name === 'AbortError' || version !== pacientesRequestVersion) return;
        if (error.status === 422) {
            showNotification('warning', error.message || 'Revise los criterios de búsqueda.');
        } else {
            showBlockError('pacientesError', loader, error.message, loadPacientes);
        }
    } finally {
        if (version === pacientesRequestVersion) {
            setVisible(loader, false);
            section?.setAttribute('aria-busy', 'false');
        }
    }
}
function filterPacientesLocally(rows) {
    const query = normalizeSearch(sanitizePacientesSearch(document.getElementById('filterCedula')?.value));
    const tokens = query ? query.split(/\s+/).filter(Boolean).slice(0, 6) : [];
    const category = document.getElementById('filterCategoria')?.value || '';
    return rows.filter(patient => {
        if (category && String(patient.categoria_id) !== category) return false;
        if (!tokens.length) return true;
        const haystack = normalizeSearch(`${patient.nombres ?? ''} ${patient.apellidos ?? ''}`);
        const cedulaNorm = String(patient.cedula ?? '').toUpperCase().replace(/[\s.-]+/g, '');
        return tokens.every(token => {
            const cedulaToken = token.toUpperCase().replace(/[\s.-]+/g, '');
            return haystack.includes(token) || (Boolean(cedulaToken) && /^[A-Z0-9]+$/.test(cedulaToken) && cedulaNorm.includes(cedulaToken));
        });
    });
}
function buscarPacientes() {
    clearTimeout(pacientesSearchTimer);
    pacientesSearchTimer = null;
    pacientesPage = 1;
    return loadPacientes();
}
function cambiarPaginaPacientes(delta) {
    const lastPage = Math.max(1, Math.ceil(pacientesMeta.total / pacientesMeta.por_pagina));
    const next = Math.min(lastPage, Math.max(1, pacientesPage + delta));
    if (next === pacientesPage) return;
    pacientesPage = next;
    loadPacientes().then(() => document.getElementById('patientsTitle')?.scrollIntoView({ block: 'start' }));
}
function createPatientActionButton(action, id, label, icon, primary = false) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = `btn ${primary ? 'btn-primary' : 'btn-secondary'}`;
    button.dataset.action = action;
    button.dataset.id = String(Number(id));
    if (icon) {
        const symbol = document.createElement('i');
        symbol.className = `ph ${icon}`;
        symbol.setAttribute('aria-hidden', 'true');
        button.append(symbol);
    }
    button.append(document.createTextNode(label));
    return button;
}
function createPatientActionsNode(patient) {
    const id = patient.id;
    const actions = document.createElement('div');
    actions.className = 'record-actions';
    if (can('clinica.editar')) actions.append(createPatientActionButton('consulta', id, 'Consulta', 'ph-stethoscope'));
    else if (can('pacientes.editar')) actions.append(createPatientActionButton('editar', id, 'Editar datos', 'ph-user-gear'));
    if (can('citas.gestionar')) actions.append(createPatientActionButton('cita', id, 'Agendar', 'ph-calendar-plus'));
    return actions;
}
function createPatientNameButton(id, fullName) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'patient-name-button';
    button.dataset.action = 'ver';
    button.dataset.id = String(id);
    button.textContent = fullName;
    return button;
}
function renderTable() {
    const body = document.getElementById('tableBody');
    const cards = document.getElementById('tableBodyCards');
    if (!body) return;
    pacientesById.clear();
    body.replaceChildren();
    cards?.replaceChildren();

    pacientesList.forEach(patient => {
        const id = Number(patient.id);
        if (!Number.isSafeInteger(id) || id <= 0) return;
        pacientesById.set(id, patient);
        const name = patientFullName(patient) || 'Sin nombre';
        const last = patient.ultima_consulta ? formatDate(patient.ultima_consulta) : 'Sin consultas';

        const row = document.createElement('tr');
        const cedulaCell = document.createElement('td');
        cedulaCell.textContent = String(patient.cedula || '');
        const nameCell = document.createElement('td');
        nameCell.className = 'patient-name-cell';
        nameCell.append(createPatientNameButton(id, name));
        const catCell = document.createElement('td');
        catCell.textContent = String(patient.categoria || '');
        const lastCell = document.createElement('td');
        lastCell.textContent = last;
        const actionsCell = document.createElement('td');
        actionsCell.append(createPatientActionsNode(patient));
        row.append(cedulaCell, nameCell, catCell, lastCell, actionsCell);
        body.append(row);

        if (cards) {
            const card = document.createElement('article');
            card.className = 'record-card';
            const identity = document.createElement('div');
            identity.className = 'record-identity';
            const who = document.createElement('div');
            const heading = document.createElement('h3');
            heading.append(createPatientNameButton(id, name));
            const cedulaP = document.createElement('p');
            cedulaP.textContent = String(patient.cedula || '');
            who.append(heading, cedulaP);
            const badge = document.createElement('span');
            badge.className = 'status-badge';
            badge.textContent = String(patient.categoria || '');
            identity.append(who, badge);
            const meta = document.createElement('p');
            meta.className = 'record-meta';
            meta.textContent = `Última consulta: ${last}`;
            card.append(identity, meta, createPatientActionsNode(patient));
            cards.append(card);
        }
    });

    const filtered = Boolean(sanitizePacientesSearch(document.getElementById('filterCedula')?.value) || document.getElementById('filterCategoria')?.value);
    const { total, pagina, por_pagina: perPage } = pacientesMeta;
    const from = total ? (pagina - 1) * perPage + 1 : 0;
    const to = Math.min(total, from + pacientesList.length - 1);
    const count = document.getElementById('patientsCount');
    if (count) count.textContent = total ? `${total} ${total === 1 ? 'paciente' : 'pacientes'}${filtered ? ' encontrados' : ''}` : '';
    const empty = document.getElementById('emptyState');
    setVisible(empty, !pacientesList.length, 'flex');
    if (empty) {
        empty.querySelector('h3').textContent = filtered ? 'Sin coincidencias' : 'Aún no hay pacientes';
        empty.querySelector('p').textContent = filtered ? 'Pruebe otro nombre, cédula o categoría.' : 'Registre la primera historia clínica para comenzar.';
    }
    const pager = document.getElementById('pacientesPager');
    const lastPage = Math.max(1, Math.ceil(total / perPage));
    setVisible(pager, total > perPage, 'flex');
    const info = document.getElementById('pacientesPagerInfo');
    if (info) info.textContent = total ? `${from}–${to} de ${total} · Página ${pagina} de ${lastPage}` : '';
    const prev = document.getElementById('pacientesPrev');
    const next = document.getElementById('pacientesNext');
    if (prev) prev.disabled = pagina <= 1;
    if (next) next.disabled = pagina >= lastPage;
}
// Un solo manejador por contenedor en lugar de uno por botón.
function handlePatientAction(event) {
    const button = event.target.closest('[data-action]');
    if (!button) return;
    const patient = pacientesById.get(Number(button.dataset.id));
    if (!patient) return;
    const action = button.dataset.action;
    if (action === 'ver') viewPatientDetails(patient.id);
    else if (action === 'consulta') openModal(patient.cedula, patient.categoria_id, patient.nombres, patient.apellidos);
    else if (action === 'editar') openModal(patient.cedula, patient.categoria_id, patient.nombres, patient.apellidos, { soloPaciente: true });
    else if (action === 'cita') openCitaModal(patient.id, patient.cedula, patientFullName(patient));
}

/* ---------- Búsqueda por cédula en el formulario ---------- */
function setPatientLookupStatus(message) {
    let status = document.getElementById('patientLookupStatus');
    if (!status) {
        status = document.createElement('p');
        status.id = 'patientLookupStatus';
        status.className = 'field-help lookup-status';
        status.setAttribute('role', 'status');
        const field = document.getElementById('formCedula')?.closest('.field');
        if (field) field.append(status);
    }
    status.textContent = message;
    setVisible(status, Boolean(message));
}
function setPatientField(form, name, value) {
    if (name === 'compromiso_tatuajes') { setCompromisoTatuajes(value); return; }
    const field = form.elements.namedItem(name);
    if (!field || field instanceof RadioNodeList) return;
    if (name === 'tiene_tatuajes') field.value = hasValue(value) ? String(value) : 'No';
    else if (name === 'fecha_nacimiento') field.value = String(value ?? '').slice(0, 10);
    else field.value = value ?? '';
}
function resetAutofilledPatient() {
    const form = document.getElementById('registroForm');
    if (!form?.dataset.patientCedula) return;
    patientFieldNames.forEach(name => setPatientField(form, name, ''));
    delete form.dataset.patientCedula;
    delete form.dataset.pacienteId;
    editedPatientFields.clear();
    setPatientSummary('');
    updateDemographicVisibility();
    updateSectionStatus();
}
function resetPatientLookupState() {
    const form = document.getElementById('registroForm');
    patientLookupVersion++;
    activeRequests.get('patientLookup')?.abort();
    pendingPatientLookup = null;
    editedPatientFields.clear();
    if (form) {
        delete form.dataset.lookupResolvedCedula;
        delete form.dataset.patientLookupError;
    }
}
function buscarPacientePorCedula(cedula) {
    const form = document.getElementById('registroForm');
    if (!form || registroMode === 'edicion') return Promise.resolve(false);
    const normalized = normalizeCedula(cedula);
    if (form.dataset.lookupResolvedCedula === normalized) return Promise.resolve(true);
    if (pendingPatientLookup?.cedula === normalized) return pendingPatientLookup.promise;
    const version = ++patientLookupVersion;
    activeRequests.get('patientLookup')?.abort();
    if (!/^[VEJGCPN]-\d{1,18}$/.test(normalized)) { setPatientLookupStatus(''); return Promise.resolve(false); }
    delete form.dataset.patientLookupError;
    setPatientLookupStatus('Buscando historia clínica…');
    const lookup = { cedula: normalized, promise: null };
    pendingPatientLookup = lookup;
    const currentIdentity = () => version === patientLookupVersion && normalizeCedula(document.getElementById('formCedula').value) === normalized;
    lookup.promise = (async () => {
        try {
            const json = await apiRequest(`api/get_paciente.php?cedula=${encodeURIComponent(normalized)}`, {}, 'patientLookup');
            if (!currentIdentity()) return false;
            const patient = json.data || {};
            // Si el usuario aún no había escrito nada, cargar los datos no cuenta como cambio sin guardar.
            const untouched = !isRegistroDirty();
            patientFieldNames.forEach(name => {
                // Se respetan los cambios hechos mientras llegaba la respuesta, incluso si vació un campo.
                if (editedPatientFields.has(name)) return;
                setPatientField(form, name, name === 'categoria' ? patient.categoria_id : patient[name]);
            });
            form.dataset.patientCedula = normalized;
            form.dataset.lookupResolvedCedula = normalized;
            if (patient.id) form.dataset.pacienteId = String(patient.id);
            updateDemographicVisibility();
            setPatientSummary(`${patientFullName(patient)} · ${normalized}${patient.categoria ? ` · ${patient.categoria}` : ''}`);
            setPatientLookupStatus('Historia encontrada. Revise los datos personales antes de guardar.');
            // UX-2: con el paciente identificado, el trabajo sigue en la atención clínica.
            if (registroMode === 'consulta') {
                const identityFocused = document.getElementById('acc-paciente')?.contains(document.activeElement) && document.activeElement?.id !== 'formCedula';
                if (!identityFocused && !form.querySelector('#acc-paciente [aria-invalid="true"]')) closeAccordion('acc-paciente');
                openAccordion('acc-vitales');
                openAccordion('acc-diagnostico');
            }
            updateSectionStatus();
            if (untouched) takeRegistroSnapshot();
            return true;
        } catch (error) {
            if (error.name === 'AbortError' || !currentIdentity()) return false;
            if (error.status === 404) {
                form.dataset.lookupResolvedCedula = normalized;
                delete form.dataset.pacienteId;
                setPatientSummary('');
                openAccordion('acc-paciente');
                setPatientLookupStatus('Paciente nuevo. Complete sus datos personales.');
                return true;
            }
            form.dataset.patientLookupError = error.message;
            setPatientLookupStatus(error.message);
            return false;
        } finally { if (pendingPatientLookup === lookup) pendingPatientLookup = null; }
    })();
    return lookup.promise;
}

/* ---------- Historia clínica (panel lateral, UX-9) ---------- */
function openClinicalDocument(endpoint, parameter, id) {
    const value = Number(id);
    if (!Number.isSafeInteger(value) || value <= 0) return;
    window.open(`api/${endpoint}?${parameter}=${value}`, '_blank', 'noopener,noreferrer');
}
function appendDetail(list, label, value, full = false) {
    if (!hasValue(value)) return false;
    const wrapper = document.createElement('div');
    if (full) wrapper.className = 'full-width';
    const term = document.createElement('dt');
    term.textContent = label;
    const description = document.createElement('dd');
    description.textContent = String(value);
    wrapper.append(term, description);
    list.append(wrapper);
    return true;
}
function emptyNote(container, text) {
    const note = document.createElement('p');
    note.className = 'record-meta';
    note.textContent = text;
    container.append(note);
}
function renderPatientData(patient) {
    const data = document.getElementById('detDatos');
    if (data) {
        data.replaceChildren();
        const age = ageFrom(patient.fecha_nacimiento);
        appendDetail(data, 'Fecha de nacimiento', patient.fecha_nacimiento ? `${formatDate(patient.fecha_nacimiento)}${age !== null ? ` · ${age} años` : ''}` : '');
        PATIENT_DATA_LABELS.forEach(([key, label, full]) => appendDetail(data, label, patient[key], full));
        if (!data.children.length) emptyNote(data, 'Sin datos personales adicionales registrados.');
    }
    const background = document.getElementById('detAntecedentes');
    if (background && can('clinica.ver')) {
        background.replaceChildren();
        BACKGROUND_LABELS.forEach(([key, label, full]) => appendDetail(background, label, patient[key], full));
        if (!background.children.length) emptyNote(background, 'Sin antecedentes registrados.');
    }
}
function consultaDetailsList(consulta) {
    const list = document.createElement('dl');
    list.className = 'record-details';
    CONSULTA_DETAIL_LABELS.forEach(([key, label, full]) => appendDetail(list, label, consulta[key], full));
    return list;
}
// Médico tratante e historial de correcciones: se piden a get_consulta.php al desplegar la consulta.
async function loadConsultaExtras(consultaId, container) {
    container.replaceChildren();
    container.setAttribute('aria-busy', 'true');
    emptyNote(container, 'Cargando registro de la consulta…');
    try {
        let data = consultaDetailsCache.get(consultaId);
        if (!data) {
            const json = await apiRequest(`api/get_consulta.php?id=${encodeURIComponent(consultaId)}`, {}, `consultaExtras-${consultaId}`);
            data = json.data && typeof json.data === 'object' ? json.data : json;
            consultaDetailsCache.set(consultaId, data);
        }
        container.replaceChildren();
        const consulta = data.consulta || {};
        const list = document.createElement('dl');
        list.className = 'record-details';
        const doctor = hasValue(consulta.medico_nombre) ? `${consulta.medico_nombre}${hasValue(consulta.medico_registro) ? ` — Reg. ${consulta.medico_registro}` : ''}` : 'No registrado';
        appendDetail(list, 'Médico tratante', doctor, true);
        if (consulta.actualizado_en) appendDetail(list, 'Última corrección', formatDate(consulta.actualizado_en, true));
        container.append(list);
        const versions = Array.isArray(data.versiones) ? data.versiones : [];
        if (versions.length) {
            const heading = document.createElement('p');
            heading.className = 'version-heading';
            heading.textContent = `Correcciones (${versions.length})`;
            const items = document.createElement('ol');
            items.className = 'version-list';
            versions.forEach(version => {
                const item = document.createElement('li');
                const meta = document.createElement('span');
                meta.className = 'version-meta';
                meta.textContent = `Versión ${version.version} · ${formatDate(version.editado_en, true)} · ${version.editado_por_nombre || 'Usuario no disponible'}`;
                const reason = document.createElement('span');
                reason.textContent = String(version.motivo || '');
                item.append(meta, reason);
                items.append(item);
            });
            container.append(heading, items);
        }
    } catch (error) {
        if (error.name === 'AbortError') return;
        container.replaceChildren();
        const retry = textAction('Reintentar', 'ph-arrow-clockwise', () => loadConsultaExtras(consultaId, container), 'btn btn-text');
        emptyNote(container, error.status === 404 ? 'El registro de correcciones no está disponible.' : `No se pudo cargar el registro de la consulta. ${error.message}`);
        if (error.status !== 404) container.append(retry);
    } finally { container.setAttribute('aria-busy', 'false'); }
}
function renderClinicalHistory(consultas, patient) {
    const container = document.getElementById('detConsultas');
    if (!container) return;
    container.replaceChildren();
    if (!consultas.length) { emptyNote(container, 'Este paciente aún no tiene consultas registradas.'); return; }
    consultas.forEach(consulta => {
        const id = Number(consulta.id);
        const card = document.createElement('article');
        card.className = 'record-card clinical-entry';
        const heading = document.createElement('div');
        heading.className = 'record-identity';
        const headingTitle = document.createElement('h4');
        headingTitle.textContent = `Consulta del ${formatDate(consulta.fecha_circunstancia)}`;
        heading.append(headingTitle);
        if (consulta.actualizado_en) {
            const correctedBadge = document.createElement('span');
            correctedBadge.className = 'status-badge';
            correctedBadge.textContent = 'Corregida';
            heading.append(correctedBadge);
        }
        const meta = document.createElement('p');
        meta.className = 'record-meta';
        meta.textContent = `Registrada: ${formatDate(consulta.fecha_registro, true)}`;
        card.append(heading, meta);
        const summary = consulta.resumen || consulta.enfermedad_actual;
        if (hasValue(summary)) {
            const text = document.createElement('p');
            text.className = 'clinical-summary';
            text.textContent = summary;
            card.append(text);
        }
        [['diagnostico', 'Diagnóstico'], ['plan_tratamiento', 'Indicaciones']].forEach(([key, label]) => {
            if (!hasValue(consulta[key])) return;
            const line = document.createElement('p');
            const strong = document.createElement('strong');
            strong.textContent = `${label}: `;
            line.append(strong, document.createTextNode(consulta[key]));
            card.append(line);
        });
        if (consulta.fecha_inicio_reposo && consulta.fecha_fin_reposo) {
            const rest = document.createElement('p');
            rest.className = 'record-meta';
            rest.textContent = `Reposo: ${formatDate(consulta.fecha_inicio_reposo)} al ${formatDate(consulta.fecha_fin_reposo)}`;
            card.append(rest);
        }
        const details = document.createElement('details');
        details.className = 'consulta-details';
        const toggle = document.createElement('summary');
        toggle.textContent = 'Ver consulta completa';
        const extras = document.createElement('div');
        extras.className = 'consulta-extras';
        const fullList = consultaDetailsList(consulta);
        details.append(toggle, fullList.children.length ? fullList : Object.assign(document.createElement('p'), { className: 'record-meta', textContent: 'Sin signos vitales ni examen físico registrados.' }), extras);
        details.addEventListener('toggle', () => { if (details.open && !extras.dataset.loaded && id > 0) { extras.dataset.loaded = '1'; loadConsultaExtras(id, extras); } });
        card.append(details);
        if (Array.isArray(consulta.archivos)) consulta.archivos.forEach(file => {
            const fileId = Number(file.id);
            if (!Number.isSafeInteger(fileId) || fileId <= 0) return;
            const link = document.createElement('a');
            link.className = 'attachment-link';
            link.href = `api/download_archivo.php?id=${fileId}`;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            const type = { 'application/pdf': 'PDF', 'image/jpeg': 'imagen JPG', 'image/png': 'imagen PNG' }[file.tipo_archivo] || 'archivo';
            link.textContent = `Ver adjunto (${type}${file.fecha_subida ? `, ${formatDate(file.fecha_subida)}` : ''})`;
            card.append(link);
        });
        const actions = document.createElement('div');
        actions.className = 'record-actions';
        actions.append(textAction('Imprimir informe', 'ph-printer', () => openClinicalDocument('print_informe.php', 'consulta_id', id)));
        actions.append(textAction('Imprimir récipe', 'ph-prescription', () => openClinicalDocument('print_recipe.php', 'consulta_id', id)));
        if (consulta.fecha_inicio_reposo && consulta.fecha_fin_reposo) actions.append(textAction('Imprimir reposo', 'ph-file-text', () => openClinicalDocument('print_reposo.php', 'consulta_id', id)));
        if (can('clinica.editar') && id > 0) actions.append(textAction('Corregir consulta', 'ph-pencil-simple', async () => {
            if (await closeDrawer() === false) return;
            openModal(patient.cedula, patient.categoria_id, patient.nombres, patient.apellidos, { consultaId: id });
        }, 'btn btn-text'));
        card.append(actions);
        container.append(card);
    });
}
function renderAppointments(citas) {
    const appointments = document.getElementById('detCitas');
    if (!appointments) return;
    appointments.replaceChildren();
    if (!citas.length) { emptyNote(appointments, 'Sin citas registradas.'); return; }
    citas.forEach(cita => {
        const row = document.createElement('div');
        row.className = 'record-identity appointment-history-row';
        const info = document.createElement('span');
        info.append(document.createTextNode(formatDate(cita.fecha_cita, true)));
        if (cita.motivo) {
            const reason = document.createElement('small');
            reason.className = 'table-secondary';
            reason.textContent = String(cita.motivo);
            info.append(reason);
        }
        const badge = document.createElement('span');
        const statusModifier = { Pendiente: 'pending', Completada: 'completed', Cancelada: 'cancelled' }[cita.estado] || 'neutral';
        badge.className = `status-badge status-${statusModifier}`;
        badge.textContent = String(cita.estado || 'Sin estado');
        row.append(info, badge);
        appointments.append(row);
    });
}
async function viewPatientDetails(id) {
    const patientId = Number(id);
    if (!Number.isSafeInteger(patientId) || patientId <= 0) return;
    const version = ++patientDetailsVersion;
    const clinical = can('clinica.ver');
    openSurface('patientDrawer', 'drawerContent', 'detNombre');
    const loader = document.getElementById('drawerLoader');
    setVisible(loader, true, 'flex');
    setVisible(document.getElementById('drawerError'), false);
    setVisible(document.getElementById('drawerData'), false);
    document.getElementById('detNombre').textContent = clinical ? 'Historia clínica' : 'Datos del paciente';
    document.getElementById('detCedula').textContent = 'Cargando…';
    document.getElementById('detCategoria').textContent = '';
    document.getElementById('detIniciales').textContent = '';
    try {
        // Sin acceso clínico solo se consultan los datos de identificación y contacto.
        const json = clinical
            ? await apiRequest(`api/get_paciente_detalle.php?id=${patientId}`, {}, 'patientDetails')
            : await apiRequest(`api/get_paciente.php?id=${patientId}`, {}, 'patientDetails');
        if (version !== patientDetailsVersion) return;
        const patient = clinical ? json.data.paciente : json.data;
        const consultas = clinical && Array.isArray(json.data.consultas) ? json.data.consultas : [];
        const citas = clinical && Array.isArray(json.data.citas) ? json.data.citas : [];
        const fullName = patientFullName(patient);
        document.getElementById('detNombre').textContent = fullName || 'Paciente';
        document.getElementById('detCedula').textContent = patient.cedula || '';
        document.getElementById('detCategoria').textContent = patient.categoria || '';
        document.getElementById('detIniciales').textContent = `${String(patient.nombres || '').charAt(0)}${String(patient.apellidos || '').charAt(0)}`.toUpperCase();
        document.getElementById('btnConsultaAlVuelo').onclick = async () => { if (await closeDrawer() !== false) openModal(patient.cedula, patient.categoria_id, patient.nombres, patient.apellidos); };
        document.getElementById('btnCitaAlVuelo').onclick = async () => { if (await closeDrawer() !== false) openCitaModal(patient.id, patient.cedula, fullName); };
        document.getElementById('btnEditarPaciente').onclick = async () => { if (await closeDrawer() !== false) openModal(patient.cedula, patient.categoria_id, patient.nombres, patient.apellidos, { soloPaciente: true }); };
        document.getElementById('btnPrintHistoria').onclick = () => openClinicalDocument('print_historia.php', 'paciente_id', patient.id);
        renderPatientData(patient);
        if (clinical) {
            consultaDetailsCache.clear();
            renderClinicalHistory(consultas, patient);
            renderAppointments(citas);
        }
        setVisible(document.getElementById('drawerData'), true);
    } catch (error) {
        if (error.name !== 'AbortError' && version === patientDetailsVersion) showBlockError('drawerError', loader, error.message, () => viewPatientDetails(patientId));
    } finally { if (version === patientDetailsVersion) setVisible(loader, false); }
}

/* ---------- Corrección de consultas ---------- */
async function loadConsultaForEdit(consultaId) {
    const form = document.getElementById('registroForm');
    const save = document.getElementById('btnGuardar');
    if (!form) return;
    delete form.dataset.editTimedOut;
    form.dataset.loading = 'true';
    form.setAttribute('aria-busy', 'true');
    if (save) save.disabled = true;
    document.getElementById('editConsultaPaciente').textContent = 'Cargando consulta…';
    document.getElementById('editConsultaMeta').textContent = '';
    try {
        const json = await apiRequest(`api/get_consulta.php?id=${encodeURIComponent(consultaId)}`, {}, 'consultaEdit');
        if (registroMode !== 'edicion' || document.getElementById('formConsultaId').value !== String(consultaId)) return;
        const data = json.data && typeof json.data === 'object' ? json.data : json;
        const consulta = data.consulta || {};
        const patient = data.paciente || {};
        const versions = Array.isArray(data.versiones) ? data.versiones : [];
        CONSULTA_EDIT_FIELDS.forEach(name => {
            const field = form.elements.namedItem(name);
            if (field && !(field instanceof RadioNodeList)) field.value = consulta[name] ?? '';
        });
        form.elements.namedItem('inicio_reposo').value = String(consulta.fecha_inicio_reposo || '').slice(0, 10);
        form.elements.namedItem('fin_reposo').value = String(consulta.fecha_fin_reposo || '').slice(0, 10);
        document.getElementById('formFechaCircunstancia').value = String(consulta.fecha_circunstancia || '').slice(0, 10);
        document.getElementById('formVersionEsperada').value = String(versions.length);
        const resumen = document.getElementById('formResumen');
        if (resumen) resumen.value = hasValue(consulta.resumen) && consulta.resumen !== consulta.enfermedad_actual ? consulta.resumen : '';
        if (patient.id) form.dataset.pacienteId = String(patient.id);
        else if (consulta.paciente_id) form.dataset.pacienteId = String(consulta.paciente_id);
        document.getElementById('editConsultaPaciente').textContent = `${patientFullName(patient)}${patient.cedula ? ` · ${patient.cedula}` : ''}`;
        const metaParts = [`Consulta del ${formatDate(consulta.fecha_circunstancia)}`];
        if (hasValue(consulta.medico_nombre)) metaParts.push(`atendida por ${consulta.medico_nombre}`);
        metaParts.push(versions.length ? `${versions.length} ${versions.length === 1 ? 'corrección previa' : 'correcciones previas'}` : 'sin correcciones previas');
        document.getElementById('editConsultaMeta').textContent = metaParts.join(' · ');
        updateSectionStatus();
        takeRegistroSnapshot();
    } catch (error) {
        if (error.name === 'AbortError') return;
        showNotification('error', `No se pudo cargar la consulta. ${error.message}`, 6000);
        closeModal(true);
    } finally {
        delete form.dataset.loading;
        form.setAttribute('aria-busy', 'false');
        if (save && form.dataset.saving !== 'true') save.disabled = false;
    }
}

/* ---------- Guardado ---------- */
function guardarSoloPaciente() {
    const form = document.getElementById('registroForm');
    if (!form || form.dataset.saving === 'true') return;
    document.getElementById('formSoloPaciente').value = '1';
    form.requestSubmit();
}
function restoreSoloPacienteFlag() {
    const flag = document.getElementById('formSoloPaciente');
    if (flag) flag.value = registroMode === 'paciente' ? '1' : '0';
}
function validateReposo(form) {
    const start = form.elements.namedItem('inicio_reposo')?.value;
    const end = form.elements.namedItem('fin_reposo')?.value;
    if (Boolean(start) !== Boolean(end)) {
        handleInlineErrors({ [start ? 'fin_reposo' : 'inicio_reposo']: 'Indique ambas fechas del reposo.' }, form.id);
        return false;
    }
    if (start && end && end < start) {
        handleInlineErrors({ fin_reposo: 'El final del reposo debe ser igual o posterior al inicio.' }, form.id);
        return false;
    }
    return true;
}
// 409 identity_mismatch: la cédula existe con otro nombre o sexo. Devuelve 'confirmar', 'usar' o 'cancelar'.
async function resolveIdentityMismatch(form, actual) {
    const typed = {
        nombre: `${form.elements.namedItem('nombres').value} ${form.elements.namedItem('apellidos').value}`.trim(),
        sexo: form.elements.namedItem('sexo').value,
        categoria: form.elements.namedItem('categoria').selectedOptions[0]?.text || ''
    };
    const registered = { nombre: patientFullName(actual), sexo: actual?.sexo || '', categoria: actual?.categoria || '' };
    if (typeof Swal === 'undefined') return window.confirm(`La cédula ya está registrada a nombre de ${registered.nombre}. ¿Guardar con los datos escritos?`) ? 'confirmar' : 'cancelar';
    const row = (label, a, b) => `<tr${normalizeSearch(a) !== normalizeSearch(b) ? ' class="is-different"' : ''}><th scope="row">${label}</th><td>${escapeHTML(a || '—')}</td><td>${escapeHTML(b || '—')}</td></tr>`;
    const result = await Swal.fire({
        icon: 'warning',
        titleText: 'La cédula pertenece a otra identidad',
        html: `<p class="swal-copy">Esta cédula ya está registrada con datos distintos. Compruebe el documento del paciente antes de continuar.</p>
            <table class="identity-compare"><thead><tr><th scope="col"><span class="sr-only">Dato</span></th><th scope="col">Registrado</th><th scope="col">Escrito ahora</th></tr></thead><tbody>
            ${row('Nombre', registered.nombre, typed.nombre)}${row('Sexo', registered.sexo, typed.sexo)}${row('Categoría', registered.categoria, typed.categoria)}
            </tbody></table>`,
        showDenyButton: true, showCancelButton: true, focusCancel: true,
        confirmButtonText: 'Actualizar con lo escrito', denyButtonText: 'Usar datos registrados', cancelButtonText: 'Revisar'
    });
    if (result.isConfirmed) return 'confirmar';
    if (result.isDenied) return 'usar';
    return 'cancelar';
}
function applyRegisteredIdentity(form, actual) {
    if (!actual) return;
    ['nombres', 'apellidos', 'sexo'].forEach(name => { if (actual[name] !== undefined) setPatientField(form, name, actual[name]); });
    if (actual.categoria_id) setPatientField(form, 'categoria', actual.categoria_id);
    ['nombres', 'apellidos', 'sexo', 'categoria'].forEach(name => editedPatientFields.delete(name));
    updateDemographicVisibility();
    updateSectionStatus();
    setPatientSummary(`${patientFullName(actual)} · ${normalizeCedula(form.elements.namedItem('cedula').value)}${actual.categoria ? ` · ${actual.categoria}` : ''}`);
}
// mode 'edicion': update_consulta no deduplica reenvíos, así que no se promete que reintentar sea seguro.
async function showSaveTimeout(mode = 'registro') {
    const message = mode === 'edicion'
        ? 'No se recibió confirmación del servidor. Es posible que la corrección se haya guardado. Antes de volver a pulsar Guardar, recargue la consulta y revise su historial de correcciones.'
        : 'No se recibió confirmación del servidor. Es posible que el registro se haya guardado. Puede volver a pulsar Guardar: el sistema reconoce este mismo envío y no lo duplica.';
    if (typeof Swal !== 'undefined') await Swal.fire({ icon: 'warning', titleText: 'Sin confirmación del servidor', text: message, confirmButtonText: 'Entendido' });
    else showNotification('warning', message, 9000);
    refreshClinicalViews();
}
async function submitConsultaEdit(form, button) {
    if (!validateForm(form) || !validateReposo(form)) return;
    button.textContent = 'Guardando cambios…';
    try {
        const json = await apiRequest('api/update_consulta.php', { method: 'POST', body: new FormData(form), timeoutMs: 45000 });
        delete form.dataset.editTimedOut;
        const pacienteId = Number(form.dataset.pacienteId) || 0;
        setFormBusy(form, false);
        await closeModal(true);
        showNotification('success', json.message || 'La consulta se corrigió correctamente.');
        await refreshClinicalViews();
        if (pacienteId > 0) viewPatientDetails(pacienteId);
    } catch (error) {
        if (error.code === 'version_conflict') {
            setFormBusy(form, false);
            // Tras un envío sin confirmación, lo más probable es que la corrección anterior sí se guardara.
            const afterTimeout = form.dataset.editTimedOut === 'true';
            const reload = await confirmDialog(afterTimeout ? {
                title: 'Es probable que su corrección ya se guardara',
                text: 'El envío anterior no recibió confirmación, pero la consulta ya tiene una versión más reciente. Recárguela y revise el historial de correcciones antes de volver a guardar.',
                confirmText: 'Recargar consulta', cancelText: 'Seguir editando'
            } : {
                title: 'La consulta cambió mientras la editaba',
                text: 'Otra persona guardó una corrección de esta consulta. Recárguela para ver la versión actual; sus cambios no guardados se perderán.',
                confirmText: 'Recargar consulta', cancelText: 'Seguir editando'
            });
            if (reload) loadConsultaForEdit(Number(document.getElementById('formConsultaId').value));
            return;
        }
        if (error.code === 'timeout') {
            form.dataset.editTimedOut = 'true';
            await showSaveTimeout('edicion');
            return;
        }
        showFormError(error, form.id);
    }
}
// 409 cita_no_pendiente: la cita que se estaba atendiendo se canceló o completó en otra sesión.
// Devuelve true si el usuario acepta guardar la consulta sin vincularla.
async function resolveCitaNoPendiente() {
    refreshClinicalViews();
    return confirmDialog({
        title: 'La cita ya no está pendiente',
        text: 'Otra persona cambió el estado de esta cita mientras escribía la consulta. ¿Guardar la consulta sin vincularla a la cita? Lo escrito se conserva en cualquier caso.',
        confirmText: 'Guardar sin vincular', cancelText: 'Seguir editando'
    });
}
async function submitRegistro(form, button, patientOnly) {
    const cedula = form.elements.namedItem('cedula');
    const submittedCedula = normalizeCedula(cedula.value);
    button.textContent = 'Verificando historia…';
    const resolved = await buscarPacientePorCedula(submittedCedula);
    if (!resolved || normalizeCedula(cedula.value) !== submittedCedula || form.dataset.lookupResolvedCedula !== submittedCedula) {
        showNotification('warning', form.dataset.patientLookupError || 'La cédula cambió durante la búsqueda. Revise los datos y vuelva a guardar.');
        return;
    }
    if (!validateForm(form, patientOnly)) return;
    if (!patientOnly && !validateReposo(form)) return;
    const confirmIdentity = document.getElementById('formConfirmarIdentidad');
    const citaField = document.getElementById('formCitaId');
    let identityConfirmed = false;
    let citaUnlinked = false;
    for (let attempt = 0; attempt < 3; attempt++) {
        button.textContent = 'Guardando…';
        confirmIdentity.disabled = !identityConfirmed;
        const body = new FormData(form);
        if (patientOnly) body.set('solo_paciente', '1');
        const hasFile = !patientOnly && Boolean(form.elements.namedItem('archivo')?.files?.length);
        try {
            // FE-3: con adjunto la subida puede tardar; el límite supera el tiempo máximo del servidor.
            const json = await apiRequest('api/save_registro.php', { method: 'POST', body, timeoutMs: hasFile ? 120000 : 45000 });
            confirmIdentity.disabled = true;
            setFormBusy(form, false);
            await closeModal(true);
            showNotification('success', json.duplicado ? 'Este registro ya se había guardado; no se creó un duplicado.' : (json.message || 'Registro guardado correctamente.'));
            await refreshClinicalViews();
            return;
        } catch (error) {
            confirmIdentity.disabled = true;
            if (error.code === 'identity_mismatch' && !identityConfirmed) {
                const actual = error.payload?.actual || null;
                setFormBusy(form, false);
                button.innerHTML = button.dataset.originalHtml || button.innerHTML;
                const choice = await resolveIdentityMismatch(form, actual);
                if (choice === 'confirmar') {
                    if (!isSurfaceOpen('patientModal')) return;
                    setFormBusy(form, true);
                    identityConfirmed = true;
                    continue;
                }
                if (choice === 'usar') {
                    applyRegisteredIdentity(form, actual);
                    showNotification('info', 'Se cargaron los datos registrados. Revíselos y vuelva a guardar.', 6000);
                } else revealField(form.elements.namedItem('nombres'));
                return;
            }
            if (error.code === 'cita_no_pendiente' && !citaUnlinked && citaField && !citaField.disabled) {
                setFormBusy(form, false);
                button.innerHTML = button.dataset.originalHtml || button.innerHTML;
                if (!await resolveCitaNoPendiente() || !isSurfaceOpen('patientModal')) return;
                // La transacción anterior se revirtió: el mismo solicitud_id sigue sin usarse.
                citaField.value = '';
                citaField.disabled = true;
                citaUnlinked = true;
                setFormBusy(form, true);
                continue;
            }
            if (error.code === 'timeout') { await showSaveTimeout(); return; }
            showFormError(error, form.id);
            return;
        }
    }
}
const registroForm = document.getElementById('registroForm');
registroForm?.addEventListener('submit', async function (event) {
    event.preventDefault();
    if (this.dataset.saving === 'true' || this.dataset.loading === 'true') return;
    const patientOnly = document.getElementById('formSoloPaciente').value === '1';
    const editing = registroMode === 'edicion';
    if (!editing) {
        const cedula = this.elements.namedItem('cedula');
        cedula.value = normalizeCedula(cedula.value);
        if (!cedula.checkValidity()) {
            handleInlineErrors({ cedula: validationText(cedula) }, this.id);
            restoreSoloPacienteFlag();
            return;
        }
    }
    const button = document.getElementById(patientOnly && registroMode === 'consulta' ? 'btnGuardarSoloPaciente' : 'btnGuardar');
    const original = button.innerHTML;
    button.dataset.originalHtml = original;
    setFormBusy(this, true);
    try {
        if (editing) await submitConsultaEdit(this, button);
        else await submitRegistro(this, button, patientOnly);
    } catch (error) {
        showFormError(error, this.id);
    } finally {
        setFormBusy(this, false);
        button.innerHTML = original;
        delete button.dataset.originalHtml;
        restoreSoloPacienteFlag();
    }
});

/* ---------- Eventos ---------- */
const filterCedulaInput = document.getElementById('filterCedula');
filterCedulaInput?.addEventListener('input', () => {
    clearTimeout(pacientesSearchTimer);
    pacientesSearchTimer = setTimeout(() => { buscarPacientes(); }, 300);
});
filterCedulaInput?.addEventListener('keydown', event => {
    if (event.key === 'Enter') {
        event.preventDefault();
        buscarPacientes();
    } else if (event.key === 'Escape' && filterCedulaInput.value) {
        event.preventDefault();
        filterCedulaInput.value = '';
        buscarPacientes();
    }
});
document.getElementById('filterCategoria')?.addEventListener('change', buscarPacientes);
document.getElementById('btnLimpiarPacientes')?.addEventListener('click', () => {
    if (filterCedulaInput) filterCedulaInput.value = '';
    const cat = document.getElementById('filterCategoria');
    if (cat) cat.value = '';
    buscarPacientes();
});
document.getElementById('tableBody')?.addEventListener('click', handlePatientAction);
document.getElementById('tableBodyCards')?.addEventListener('click', handlePatientAction);
document.getElementById('formCedula')?.addEventListener('input', function () {
    patientLookupVersion++;
    activeRequests.get('patientLookup')?.abort();
    pendingPatientLookup = null;
    delete registroForm.dataset.lookupResolvedCedula;
    delete registroForm.dataset.patientLookupError;
    if (registroForm.dataset.patientCedula && registroForm.dataset.patientCedula !== normalizeCedula(this.value)) resetAutofilledPatient();
    setPatientLookupStatus('');
});
for (const eventName of ['input', 'change']) registroForm?.addEventListener(eventName, event => {
    if (patientFieldNames.includes(event.target.name)) editedPatientFields.add(event.target.name);
});
