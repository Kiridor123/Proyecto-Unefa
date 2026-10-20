// Shared state. Lists are refreshed after every successful mutation.
let pacientesList = [];
let citasList = [];
let currentTab = 'dashboard';
let categoryChartInstance = null;
let historyChartInstance = null;
const activeRequests = new Map();
const surfaceOpeners = new Map();

class ApiError extends Error {
    constructor(message, status = 0, errors = {}) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.errors = errors || {};
    }
}

// Reads with the same key cancel the previous request to avoid replacing fresh data.
async function apiRequest(url, options = {}, requestKey = '') {
    const controller = new AbortController();
    if (requestKey) {
        activeRequests.get(requestKey)?.abort();
        activeRequests.set(requestKey, controller);
    }
    let timedOut = false;
    const timeout = setTimeout(() => { timedOut = true; controller.abort(); }, 20000);
    const headers = new Headers(options.headers || {});
    headers.set('Accept', 'application/json');
    headers.set('X-Requested-With', 'XMLHttpRequest');
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    if (token) headers.set('X-CSRF-Token', token);
    try {
        const response = await fetch(url, { ...options, headers, signal: controller.signal, credentials: 'same-origin', cache: 'no-store' });
        let payload;
        try { payload = await response.json(); }
        catch { throw new ApiError('El servidor no devolvió una respuesta válida. Intente de nuevo.', response.status); }
        if (!response.ok || !payload.success) {
            throw new ApiError(payload.message || 'No se pudo completar la solicitud. Intente de nuevo.', response.status, payload.errors);
        }
        return payload;
    } catch (error) {
        if (timedOut) throw new ApiError('El servidor tardó demasiado en responder. Intente de nuevo.');
        if (error.name === 'AbortError' || error instanceof ApiError) throw error;
        throw new ApiError('No se pudo conectar con el servidor. Revise su conexión e intente de nuevo.');
    } finally {
        clearTimeout(timeout);
        if (requestKey && activeRequests.get(requestKey) === controller) activeRequests.delete(requestKey);
    }
}

function escapeHTML(value) {
    return String(value ?? '').replace(/[&<>'"]/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[character]));
}
function normalizeSearch(value) {
    return String(value ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('es').trim();
}
function normalizeCedula(value) {
    const text = String(value ?? '').toUpperCase().replace(/\s/g, '');
    return /^\d/.test(text) ? `V-${text}` : text.replace(/^([VEJGCPN])(?!-)/, '$1-');
}
function localDate(date = new Date()) {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}
function localDateTime(date = new Date()) {
    return `${localDate(date)}T${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
}
function formatDate(value, includeTime = false) {
    if (!value) return 'Sin registro';
    const parts = String(value).match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2}))?/);
    if (!parts) return String(value);
    return `${parts[3]}/${parts[2]}/${parts[1]}${includeTime && parts[4] ? ` · ${parts[4]}:${parts[5]}` : ''}`;
}
function debounce(callback, delay = 180) {
    let timer;
    return (...args) => { clearTimeout(timer); timer = setTimeout(() => callback(...args), delay); };
}
function setVisible(element, visible, display = '') {
    if (!element) return;
    element.classList.toggle('hidden', !visible);
    element.style.display = visible ? display : 'none';
}
function textAction(label, icon, callback, className = 'btn btn-secondary') {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = className;
    if (icon) {
        const symbol = document.createElement('i');
        symbol.className = `ph ${icon}`;
        symbol.setAttribute('aria-hidden', 'true');
        button.append(symbol);
    }
    button.append(document.createTextNode(label));
    button.addEventListener('click', event => { event.stopPropagation(); callback(); });
    return button;
}
function showBlockError(id, anchor, message, retry) {
    let element = document.getElementById(id);
    if (!element) {
        element = document.createElement('div');
        element.id = id;
        element.className = 'state-panel';
        anchor?.parentNode.insertBefore(element, anchor);
    }
    element.replaceChildren();
    element.setAttribute('role', 'status');
    const copy = document.createElement('p');
    copy.textContent = message;
    element.append(copy, textAction('Reintentar', 'ph-arrow-clockwise', retry));
    setVisible(element, true);
}

function showNotification(type, message, duration = 4000) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: duration,
            timerProgressBar: true, icon: type, titleText: String(message || 'Operación completada'),
            didOpen: toast => { toast.addEventListener('mouseenter', Swal.stopTimer); toast.addEventListener('mouseleave', Swal.resumeTimer); }
        });
        return;
    }
    let region = document.getElementById('notificationRegion');
    if (!region) {
        region = document.createElement('div');
        region.id = 'notificationRegion';
        region.className = 'notification-region';
        region.setAttribute('role', 'status');
        document.body.append(region);
    }
    region.textContent = message;
    setTimeout(() => { region.textContent = ''; }, duration);
}

function showValidationErrors(errors) {
    const messages = Array.isArray(errors) ? errors : Object.values(errors || {});
    if (messages.length) showNotification('warning', messages.join(' '), 6000);
}
function clearInlineErrors(formId) {
    const form = document.getElementById(formId);
    if (!form) return;
    form.querySelectorAll('.inline-error-text').forEach(element => element.remove());
    form.querySelectorAll('[aria-invalid="true"]').forEach(input => {
        input.removeAttribute('aria-invalid');
        input.removeAttribute('aria-describedby');
        input.classList.remove('border-red-500', 'bg-red-50');
    });
}
function revealField(input) {
    const section = input?.closest('[id^="acc-"]');
    if (section) toggleAccordion(section.id);
    input?.focus();
}
function handleInlineErrors(errors, formId) {
    clearInlineErrors(formId);
    const form = document.getElementById(formId);
    if (!form || !errors || Array.isArray(errors)) return;
    let first;
    for (const [name, message] of Object.entries(errors)) {
        const input = Array.from(form.elements).find(field => field.name === name);
        if (!input || input.type === 'hidden') continue;
        const error = document.createElement('p');
        error.id = `${formId}-error-${name}`;
        error.className = 'inline-error-text';
        error.textContent = String(message);
        input.setAttribute('aria-invalid', 'true');
        input.setAttribute('aria-describedby', error.id);
        input.insertAdjacentElement('afterend', error);
        first ||= input;
    }
    if (first) revealField(first);
    return first;
}
function showFormError(error, formId) {
    handleInlineErrors(error.errors, formId);
    showNotification('error', error.message, 6000);
}
function validateForm(form, patientOnly = false) {
    clearInlineErrors(form.id);
    const patientFields = ['cedula', 'categoria', 'sexo', 'nombres', 'apellidos'];
    for (const field of form.elements) {
        if (patientOnly && !patientFields.includes(field.name)) continue;
        if (field.required && typeof field.value === 'string' && !field.value.trim()) field.value = '';
        if (field.willValidate && !field.checkValidity()) {
            revealField(field);
            field.reportValidity();
            return false;
        }
    }
    return true;
}
function setFormBusy(form, busy) {
    form.dataset.saving = busy ? 'true' : 'false';
    form.setAttribute('aria-busy', String(busy));
    const buttons = [...form.querySelectorAll('button'), ...document.querySelectorAll(`[form="${form.id}"]`)];
    if (form.id === 'registroForm') buttons.push(document.getElementById('btnGuardarSoloPaciente'));
    buttons.filter(Boolean).forEach(button => { button.disabled = busy; });
}

function syncScrollLock() {
    const open = ['patientModal', 'citaModal', 'patientDrawer'].some(id => {
        const element = document.getElementById(id);
        return element && !element.classList.contains('hidden');
    }) || (window.innerWidth < 1024 && document.getElementById('sidebar')?.classList.contains('translate-x-0'));
    document.body.style.overflow = open ? 'hidden' : '';
}
function openSurface(id, contentId, titleId) {
    const surface = document.getElementById(id);
    const content = document.getElementById(contentId);
    if (!surface || !content) return;
    // An old toast must not cover the title of the next task; newly raised messages remain visible.
    if (typeof Swal !== 'undefined' && document.querySelector('.swal2-toast')) Swal.close();
    surfaceOpeners.set(id, document.activeElement);
    surface.classList.remove('hidden');
    surface.removeAttribute('aria-hidden');
    content.setAttribute('role', 'dialog');
    content.setAttribute('aria-modal', 'true');
    content.setAttribute('aria-labelledby', titleId);
    content.tabIndex = -1;
    content.classList.remove('modal-enter', 'modal-leave-active', 'translate-x-full');
    content.classList.add('modal-enter-active');
    syncScrollLock();
    requestAnimationFrame(() => (content.querySelector('input:not([type="hidden"]), button') || content).focus());
}
function closeSurface(id, contentId) {
    const surface = document.getElementById(id);
    const content = document.getElementById(contentId);
    if (!surface || !content) return;
    surface.classList.add('hidden');
    surface.setAttribute('aria-hidden', 'true');
    content.classList.remove('modal-enter-active');
    content.classList.add(id === 'patientDrawer' ? 'translate-x-full' : 'modal-enter');
    syncScrollLock();
    const opener = surfaceOpeners.get(id);
    if (opener?.isConnected && opener.getClientRects().length) opener.focus();
    surfaceOpeners.delete(id);
}
function toggleMobileSidebar(force) {
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) return;
    const open = typeof force === 'boolean' ? force : !sidebar.classList.contains('translate-x-0');
    sidebar.classList.toggle('translate-x-0', open);
    sidebar.classList.toggle('-translate-x-full', !open);
    document.getElementById('sidebarToggle')?.setAttribute('aria-expanded', String(open));
    setVisible(document.getElementById('sidebarOverlay'), open);
    syncScrollLock();
    if (open) sidebar.querySelector('.nav-link')?.focus();
}
function switchTab(tabName, updateHistory = true) {
    const titles = { dashboard: 'Resumen de atención', pacientes: 'Pacientes', citas: 'Agenda de citas', reportes: 'Reportes de morbilidad' };
    if (!Object.hasOwn(titles, tabName)) tabName = 'dashboard';
    currentTab = tabName;
    if (window.innerWidth < 1024) toggleMobileSidebar(false);
    document.querySelectorAll('.tab-section').forEach(section => {
        const active = section.id === `section-${tabName}`;
        section.classList.toggle('hidden', !active);
        section.setAttribute('aria-hidden', String(!active));
    });
    document.querySelectorAll('.nav-link').forEach(link => {
        const active = link.id === `link-${tabName}`;
        link.classList.toggle('is-active', active);
        if (active) link.setAttribute('aria-current', 'page');
        else link.removeAttribute('aria-current');
    });
    const heading = document.getElementById('headerTitle');
    if (heading) heading.textContent = titles[tabName];
    document.title = `${titles[tabName]} · Salud UNEFA`;
    if (updateHistory && location.hash !== `#${tabName}`) history.pushState(null, '', `#${tabName}`);
    if (tabName === 'pacientes') loadPacientes();
    else if (tabName === 'citas') loadCitas();
    else if (tabName === 'dashboard') {
        // Charts may have refreshed while this section was hidden; measure the visible canvas now.
        categoryChartInstance?.resize();
        historyChartInstance?.resize();
        loadStats();
    }
}
function toggleAccordion(id) {
    document.querySelectorAll('[id^="acc-"]').forEach(section => {
        const open = section.id === id;
        section.classList.toggle('hidden', !open);
        document.getElementById(`icon-${section.id}`)?.classList.toggle('rotate-180', open);
        document.querySelector(`[aria-controls="${section.id}"]`)?.setAttribute('aria-expanded', String(open));
    });
}

let categoriasPromise;
async function loadCategorias() {
    if (categoriasPromise) return categoriasPromise;
    categoriasPromise = (async () => {
        try {
            const json = await apiRequest('api/get_categorias.php');
            for (const id of ['filterCategoria', 'formCategoria', 'citaCategoria']) {
                const select = document.getElementById(id);
                if (!select) continue;
                const value = select.value;
                select.replaceChildren(new Option(id === 'filterCategoria' ? 'Todas las categorías' : 'Seleccione una categoría', ''));
                json.data.forEach(category => select.add(new Option(category.nombre, String(category.id))));
                select.value = value;
            }
            setVisible(document.getElementById('categoriasError'), false);
        } catch (error) {
            showBlockError('categoriasError', document.getElementById('registroForm'), 'No se pudieron cargar las categorías.', loadCategorias);
            showNotification('error', error.message);
        } finally { categoriasPromise = null; }
    })();
    return categoriasPromise;
}
function openModal(cedula = '', categoriaId = '', nombres = '', apellidos = '') {
    const form = document.getElementById('registroForm');
    if (!form) return;
    form.reset();
    form.noValidate = true;
    delete form.dataset.patientCedula;
    resetPatientLookupState();
    clearInlineErrors(form.id);
    document.getElementById('formSoloPaciente').value = '0';
    document.getElementById('formFechaCircunstancia').value = localDate();
    document.getElementById('formCedula').value = cedula;
    document.getElementById('formCategoria').value = categoriaId || '';
    document.getElementById('formNombres').value = nombres;
    document.getElementById('formApellidos').value = apellidos;
    setVisible(document.getElementById('fileError'), false);
    setVisible(document.getElementById('fileName'), false);
    setVisible(document.getElementById('patientLookupStatus'), false);
    toggleAccordion('acc-paciente');
    openSurface('patientModal', 'modalContent', 'patientModalTitle');
    if (cedula) buscarPacientePorCedula(cedula);
}
function closeModal() {
    if (document.getElementById('registroForm')?.dataset.saving === 'true') return;
    activeRequests.get('patientLookup')?.abort();
    closeSurface('patientModal', 'modalContent');
}
function openCitaModal(pacienteId = 0, cedula = '', nombreCompleto = '') {
    const form = document.getElementById('citaForm');
    if (!form) return;
    form.reset();
    form.noValidate = true;
    delete form.dataset.lookupCedula;
    setVisible(document.getElementById('citaLookupStatus'), false);
    clearInlineErrors(form.id);
    document.getElementById('citaPacienteId').value = Number(pacienteId) || 0;
    document.getElementById('citaCedula').value = cedula;
    document.getElementById('citaPacienteNombreText').textContent = nombreCompleto;
    setVisible(document.getElementById('citaPacienteInfo'), Number(pacienteId) > 0);
    setVisible(document.getElementById('citaNuevoPacienteCampos'), false);
    ['citaNombres', 'citaApellidos', 'citaCategoria'].forEach(id => { document.getElementById(id).required = false; });
    const fecha = form.elements.namedItem('fecha_cita');
    if (fecha) fecha.min = localDateTime(new Date(Date.now() + 60000));
    openSurface('citaModal', 'citaModalContent', 'citaModalTitle');
}
function closeCitaModal() {
    if (document.getElementById('citaForm')?.dataset.saving === 'true') return;
    activeRequests.get('citaLookup')?.abort();
    closeSurface('citaModal', 'citaModalContent');
}
function closeDrawer() {
    activeRequests.get('patientDetails')?.abort();
    closeSurface('patientDrawer', 'drawerContent');
}
function validarArchivo(input) {
    const error = document.getElementById('fileError');
    const name = document.getElementById('fileName');
    setVisible(error, false);
    setVisible(name, false);
    input.setCustomValidity('');
    const file = input.files?.[0];
    if (!file) return true;
    let message = '';
    if (file.size > 10 * 1024 * 1024) message = 'El archivo supera el máximo de 10 MB.';
    else if (!['image/jpeg', 'image/png', 'application/pdf'].includes(file.type)) message = 'Seleccione un archivo JPG, PNG o PDF.';
    if (message) {
        error.textContent = message;
        setVisible(error, true);
        input.value = '';
        return false;
    }
    name.textContent = `Archivo seleccionado: ${file.name}`;
    setVisible(name, true);
    return true;
}
async function refreshClinicalViews() {
    const tasks = [loadStats(), loadPacientes()];
    if (currentTab === 'citas') tasks.push(loadCitas());
    await Promise.allSettled(tasks);
    if (typeof invalidateMorbilidad === 'function') invalidateMorbilidad();
}

document.addEventListener('input', event => {
    const input = event.target;
    if (!(input instanceof HTMLInputElement || input instanceof HTMLTextAreaElement)) return;
    if (input.name === 'cedula') input.value = normalizeCedula(input.value);
    if (input.name === 'vital_ta') {
        let value = input.value.replace(/[^\d/]/g, '');
        if (value.length === 5 && !value.includes('/')) value = `${value.slice(0, 3)}/${value.slice(3)}`;
        input.value = value;
    }
    if (input.getAttribute('aria-invalid') === 'true') {
        document.getElementById(input.getAttribute('aria-describedby'))?.remove();
        input.removeAttribute('aria-invalid');
        input.removeAttribute('aria-describedby');
    }
});
document.addEventListener('keydown', event => {
    if (document.querySelector('.swal2-container .swal2-popup:not(.swal2-toast)')) return;
    const open = ['citaModal', 'patientModal', 'patientDrawer'].find(id => {
        const element = document.getElementById(id);
        return element && !element.classList.contains('hidden');
    });
    if (open) {
        if (event.key === 'Escape') {
            event.preventDefault();
            ({ citaModal: closeCitaModal, patientModal: closeModal, patientDrawer: closeDrawer }[open])();
        }
        if (event.key === 'Tab') {
            const elements = [...document.getElementById(open).querySelectorAll('button:not(:disabled), a[href], input:not([type="hidden"]):not(:disabled), select:not(:disabled), textarea:not(:disabled), [tabindex="0"]')].filter(element => element.getClientRects().length);
            const first = elements[0], last = elements[elements.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
        }
    } else if (event.key === 'Escape') toggleMobileSidebar(false);
});
window.addEventListener('popstate', () => switchTab(location.hash.slice(1), false));
window.addEventListener('hashchange', () => { if (location.hash.slice(1) !== currentTab) switchTab(location.hash.slice(1), false); });
window.addEventListener('resize', () => { if (window.innerWidth >= 1024) toggleMobileSidebar(false); });
document.addEventListener('DOMContentLoaded', async () => {
    document.querySelectorAll('form').forEach(form => {
        form.querySelectorAll('input:not([type="hidden"]),select,textarea').forEach((input, index) => {
            if (!input.id) input.id = `${form.id}-field-${index}`;
            const label = input.parentElement.querySelector('label');
            if (label && !label.htmlFor) label.htmlFor = input.id;
        });
    });
    document.querySelectorAll('[onclick^="toggleAccordion"]').forEach(button => {
        const section = button.nextElementSibling;
        if (section) { button.setAttribute('aria-controls', section.id); button.setAttribute('aria-expanded', String(!section.classList.contains('hidden'))); }
    });
    await loadCategorias();
    switchTab(location.hash.slice(1) || 'dashboard', false);
});
