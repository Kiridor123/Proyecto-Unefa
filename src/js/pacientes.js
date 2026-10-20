let pacientesRequestVersion = 0;
let patientLookupVersion = 0;
let patientDetailsVersion = 0;
let pendingPatientLookup = null;
const editedPatientFields = new Set();
const patientFieldNames = ['categoria', 'sexo', 'nombres', 'apellidos', 'telefono', 'lugar_nacimiento', 'fecha_nacimiento', 'nivel_educativo', 'carrera', 'semestre', 'direccion', 'antecedente_cardiovascular', 'antecedente_oseo', 'antecedente_respiratorio', 'antecedente_digestivo', 'antecedente_endocrino', 'antecedente_otros', 'antecedente_quirurgico', 'gineco_menarquia', 'gineco_sexarquia', 'gineco_aco', 'gineco_gestas', 'gineco_citologia', 'tiene_tatuajes', 'compromiso_tatuajes', 'antecedente_padre', 'antecedente_madre', 'antecedente_hermanos', 'antecedente_hijos'];

async function loadPacientes() {
    const version = ++pacientesRequestVersion;
    const loader = document.getElementById('tableLoader');
    const table = document.getElementById('pacientesTableWrap');
    const cards = document.getElementById('tableBodyCards');
    setVisible(document.getElementById('pacientesError'), false);
    setVisible(document.getElementById('emptyState'), false);
    setVisible(loader, true, 'flex');
    setVisible(table, false);
    setVisible(cards, false);
    document.getElementById('section-pacientes')?.setAttribute('aria-busy', 'true');
    try {
        const json = await apiRequest('api/get_pacientes.php', {}, 'pacientes');
        if (version !== pacientesRequestVersion) return;
        pacientesList = Array.isArray(json.data) ? json.data : [];
        renderTable();
        setVisible(table, true);
        setVisible(cards, true);
    } catch (error) {
        if (error.name === 'AbortError') return;
        showBlockError('pacientesError', loader, error.message, loadPacientes);
    } finally {
        if (version === pacientesRequestVersion) {
            setVisible(loader, false);
            document.getElementById('section-pacientes')?.setAttribute('aria-busy', 'false');
        }
    }
}

function patientActions(patient, includeDetails = false) {
    const actions = document.createElement('div');
    actions.className = 'record-actions';
    if (includeDetails) actions.append(textAction('Ver historia', 'ph-folder-open', () => viewPatientDetails(patient.id)));
    actions.append(
        textAction('Consulta', 'ph-stethoscope', () => openModal(patient.cedula, patient.categoria_id, patient.nombres, patient.apellidos)),
        textAction('Agendar', 'ph-calendar-plus', () => openCitaModal(patient.id, patient.cedula, `${patient.nombres} ${patient.apellidos}`))
    );
    return actions;
}
function renderTable() {
    const body = document.getElementById('tableBody');
    const cards = document.getElementById('tableBodyCards');
    if (!body) return;
    const query = normalizeSearch(document.getElementById('filterCedula')?.value);
    const category = document.getElementById('filterCategoria');
    const categoryName = normalizeSearch(category?.selectedOptions[0]?.text);
    const patients = pacientesList.filter(patient =>
        normalizeSearch(`${patient.cedula} ${patient.nombres} ${patient.apellidos}`).includes(query) &&
        (!category?.value || (patient.categoria_id ? String(patient.categoria_id) === category.value : normalizeSearch(patient.categoria) === categoryName))
    );
    body.replaceChildren();
    cards?.replaceChildren();
    const tableFragment = document.createDocumentFragment();
    const cardsFragment = document.createDocumentFragment();
    patients.forEach(patient => {
        const fullName = `${patient.nombres ?? ''} ${patient.apellidos ?? ''}`.trim();
        const row = document.createElement('tr');
        row.innerHTML = `<td>${escapeHTML(patient.cedula)}</td><td class="patient-name-cell"></td><td>${escapeHTML(patient.categoria)}</td><td>${escapeHTML(patient.ultima_consulta ? formatDate(patient.ultima_consulta) : 'Sin consultas')}</td><td></td>`;
        row.children[1].append(textAction(fullName, '', () => viewPatientDetails(patient.id), 'patient-name-button'));
        row.lastElementChild.append(patientActions(patient));
        tableFragment.append(row);

        const card = document.createElement('article');
        card.className = 'record-card';
        card.innerHTML = `<div class="record-identity"><div><h3></h3><p>${escapeHTML(patient.cedula)}</p></div><span class="status-badge">${escapeHTML(patient.categoria)}</span></div><p class="record-meta">Última consulta: ${escapeHTML(patient.ultima_consulta ? formatDate(patient.ultima_consulta) : 'Sin consultas')}</p>`;
        card.querySelector('h3').append(textAction(fullName, '', () => viewPatientDetails(patient.id), 'patient-name-button'));
        card.append(patientActions(patient));
        cardsFragment.append(card);
    });
    body.append(tableFragment);
    cards?.append(cardsFragment);
    const count = document.getElementById('patientsCount');
    if (count) count.textContent = `${patients.length} de ${pacientesList.length} pacientes`;
    const empty = document.getElementById('emptyState');
    setVisible(empty, !patients.length, 'flex');
    if (empty) {
        const title = empty.querySelector('h3, h4');
        const copy = empty.querySelector('p');
        if (title) title.textContent = query || category?.value ? 'Sin coincidencias' : 'Aún no hay pacientes';
        if (copy) copy.textContent = query || category?.value ? 'Pruebe otro nombre, cédula o categoría.' : 'Registre la primera historia clínica para comenzar.';
    }
}

function setPatientLookupStatus(message) {
    let status = document.getElementById('patientLookupStatus');
    if (!status) {
        status = document.createElement('p');
        status.id = 'patientLookupStatus';
        status.className = 'field-help';
        status.setAttribute('role', 'status');
        document.getElementById('formCedula')?.insertAdjacentElement('afterend', status);
    }
    status.textContent = message;
    setVisible(status, Boolean(message));
}
function resetAutofilledPatient() {
    const form = document.getElementById('registroForm');
    if (!form?.dataset.patientCedula) return;
    patientFieldNames.forEach(name => {
        const field = form.elements.namedItem(name);
        if (!field) return;
        field.value = name === 'tiene_tatuajes' ? 'No' : name === 'compromiso_tatuajes' ? field.defaultValue : '';
    });
    delete form.dataset.patientCedula;
    editedPatientFields.clear();
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
    if (!form) return Promise.resolve(false);
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
            const patient = json.data;
            patientFieldNames.forEach(name => {
                const field = form.elements.namedItem(name);
                // Preserve intentional edits, including clearing a field while the request was pending.
                if (field && !editedPatientFields.has(name)) field.value = (name === 'categoria' ? patient.categoria_id : patient[name]) ?? (name === 'tiene_tatuajes' ? 'No' : '');
            });
            form.dataset.patientCedula = normalized;
            form.dataset.lookupResolvedCedula = normalized;
            setPatientLookupStatus('Historia encontrada. Los datos personales y antecedentes están disponibles para revisar.');
            return true;
        } catch (error) {
            if (error.name === 'AbortError' || !currentIdentity()) return false;
            if (error.status === 404) {
                form.dataset.lookupResolvedCedula = normalized;
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

function openClinicalDocument(endpoint, parameter, id) {
    const value = Number(id);
    if (!Number.isSafeInteger(value) || value <= 0) return;
    window.open(`api/${endpoint}?${parameter}=${value}`, '_blank', 'noopener,noreferrer');
}
function renderClinicalHistory(consultas) {
    const container = document.getElementById('detConsultas');
    container.replaceChildren();
    if (!consultas.length) { container.innerHTML = '<p class="record-meta">Este paciente aún no tiene consultas registradas.</p>'; return; }
    consultas.forEach(consulta => {
        const card = document.createElement('article');
        card.className = 'record-card clinical-entry';
        card.innerHTML = `<div class="record-identity"><h4>Consulta del ${escapeHTML(formatDate(consulta.fecha_circunstancia))}</h4></div><p class="record-meta">Registrada: ${escapeHTML(formatDate(consulta.fecha_registro))}</p><p class="clinical-summary">${escapeHTML(consulta.resumen || consulta.enfermedad_actual || '')}</p>${consulta.diagnostico ? `<p><strong>Diagnóstico:</strong> ${escapeHTML(consulta.diagnostico)}</p>` : ''}${consulta.plan_tratamiento ? `<p><strong>Indicaciones:</strong> ${escapeHTML(consulta.plan_tratamiento)}</p>` : ''}`;
        if (consulta.fecha_inicio_reposo && consulta.fecha_fin_reposo) {
            const rest = document.createElement('p');
            rest.className = 'record-meta';
            rest.textContent = `Reposo: ${formatDate(consulta.fecha_inicio_reposo)} al ${formatDate(consulta.fecha_fin_reposo)}`;
            card.append(rest);
        }
        if (Array.isArray(consulta.archivos)) consulta.archivos.forEach(file => {
            if (!Number.isSafeInteger(Number(file.id)) || Number(file.id) <= 0) return;
            const link = document.createElement('a');
            link.className = 'attachment-link';
            link.href = `api/download_archivo.php?id=${Number(file.id)}`;
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            link.textContent = `Ver adjunto: ${file.nombre_original || String(file.ruta_archivo || '').split('/').pop() || 'Archivo clínico'}`;
            card.append(link);
        });
        const actions = document.createElement('div');
        actions.className = 'record-actions';
        actions.append(textAction('Imprimir informe', 'ph-printer', () => openClinicalDocument('print_informe.php', 'consulta_id', consulta.id)));
        if (consulta.fecha_inicio_reposo && consulta.fecha_fin_reposo) actions.append(textAction('Imprimir reposo', 'ph-prescription', () => openClinicalDocument('print_reposo.php', 'consulta_id', consulta.id)));
        card.append(actions);
        container.append(card);
    });
}
async function viewPatientDetails(id) {
    const version = ++patientDetailsVersion;
    openSurface('patientDrawer', 'drawerContent', 'detNombre');
    const loader = document.getElementById('drawerLoader');
    setVisible(loader, true, 'flex');
    setVisible(document.getElementById('drawerError'), false);
    setVisible(document.getElementById('drawerData'), false);
    document.getElementById('detNombre').textContent = 'Historia clínica';
    document.getElementById('detCedula').textContent = 'Cargando…';
    document.getElementById('detCategoria').textContent = '';
    document.getElementById('detIniciales').textContent = '';
    try {
        const json = await apiRequest(`api/get_paciente_detalle.php?id=${encodeURIComponent(id)}`, {}, 'patientDetails');
        if (version !== patientDetailsVersion) return;
        const { paciente: patient, consultas = [], citas = [] } = json.data;
        document.getElementById('detNombre').textContent = `${patient.nombres} ${patient.apellidos}`;
        document.getElementById('detCedula').textContent = patient.cedula;
        document.getElementById('detCategoria').textContent = patient.categoria;
        document.getElementById('detIniciales').textContent = `${String(patient.nombres || '').charAt(0)}${String(patient.apellidos || '').charAt(0)}`;
        document.getElementById('btnConsultaAlVuelo').onclick = () => { closeDrawer(); openModal(patient.cedula, patient.categoria_id, patient.nombres, patient.apellidos); };
        document.getElementById('btnCitaAlVuelo').onclick = () => { closeDrawer(); openCitaModal(patient.id, patient.cedula, `${patient.nombres} ${patient.apellidos}`); };
        document.getElementById('btnPrintHistoria').onclick = () => openClinicalDocument('print_historia.php', 'paciente_id', patient.id);
        renderClinicalHistory(consultas);
        const appointments = document.getElementById('detCitas');
        appointments.replaceChildren();
        if (!citas.length) appointments.innerHTML = '<p class="record-meta">Sin citas registradas.</p>';
        citas.forEach(cita => {
            const row = document.createElement('div');
            row.className = 'record-identity appointment-history-row';
            row.innerHTML = `<span>${escapeHTML(formatDate(cita.fecha_cita, true))}</span>${appointmentStatus(cita.estado)}`;
            appointments.append(row);
        });
        setVisible(document.getElementById('drawerData'), true);
    } catch (error) {
        if (error.name !== 'AbortError') showBlockError('drawerError', loader, error.message, () => viewPatientDetails(id));
    } finally { if (version === patientDetailsVersion) setVisible(loader, false); }
}

function guardarSoloPaciente() {
    const form = document.getElementById('registroForm');
    if (!form || form.dataset.saving === 'true') return;
    document.getElementById('formSoloPaciente').value = '1';
    form.requestSubmit();
}
const registroForm = document.getElementById('registroForm');
registroForm?.addEventListener('submit', async function (event) {
    event.preventDefault();
    if (this.dataset.saving === 'true') return;
    const flag = document.getElementById('formSoloPaciente');
    const patientOnly = flag.value === '1';
    const cedula = this.elements.namedItem('cedula');
    if (!cedula.checkValidity()) { revealField(cedula); cedula.reportValidity(); flag.value = '0'; return; }
    const submittedCedula = normalizeCedula(cedula.value);
    const button = document.getElementById(patientOnly ? 'btnGuardarSoloPaciente' : 'btnGuardar');
    const original = button.innerHTML;
    button.textContent = 'Verificando historia…';
    setFormBusy(this, true);
    try {
        const resolved = await buscarPacientePorCedula(submittedCedula);
        if (!resolved || normalizeCedula(cedula.value) !== submittedCedula || this.dataset.lookupResolvedCedula !== submittedCedula) {
            showNotification('warning', this.dataset.patientLookupError || 'La cédula cambió durante la búsqueda. Revise los datos y vuelva a guardar.');
            return;
        }
        if (!validateForm(this, patientOnly)) return;
        const start = this.elements.namedItem('inicio_reposo').value;
        const end = this.elements.namedItem('fin_reposo').value;
        if (!patientOnly && ((Boolean(start) !== Boolean(end)) || (start && end < start))) {
            handleInlineErrors({ fin_reposo: 'Indique ambas fechas de reposo y un final igual o posterior al inicio.' }, this.id);
            return;
        }
        button.textContent = 'Guardando…';
        const json = await apiRequest('api/save_registro.php', { method: 'POST', body: new FormData(this) });
        setFormBusy(this, false);
        closeModal();
        showNotification('success', json.message);
        await refreshClinicalViews();
    } catch (error) { showFormError(error, this.id); }
    finally { setFormBusy(this, false); button.innerHTML = original; flag.value = '0'; }
});
document.getElementById('filterCedula')?.addEventListener('input', debounce(renderTable));
document.getElementById('filterCategoria')?.addEventListener('change', renderTable);
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
