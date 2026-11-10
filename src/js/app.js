// Estado compartido. Las listas se recargan después de cada cambio confirmado.
let pacientesList = [];
let citasList = [];
let currentTab = 'dashboard';
let categoryChartInstance = null;
let historyChartInstance = null;
const activeRequests = new Map();
const surfaceOpeners = new Map();

// Un único punto de corte para el menú lateral plegable (UX-12).
const MOBILE_LAYOUT = window.matchMedia('(max-width: 1023px)');
const SURFACE_IDS = ['citaModal', 'patientModal', 'patientDrawer', 'usuarioModal'];
const PATIENT_FIELDS = ['cedula', 'categoria', 'sexo', 'nombres', 'apellidos'];
const ROLE_LABELS = { administrador: 'Administrador', medico: 'Médico', asistente: 'Asistente' };
const TAB_TITLES = { dashboard: 'Resumen de atención', pacientes: 'Pacientes', citas: 'Agenda de citas', reportes: 'Reportes de morbilidad', admin: 'Administración' };
const TAB_PERMISSIONS = { pacientes: 'pacientes.ver', citas: 'citas.gestionar', reportes: 'reportes.ver', admin: 'usuarios.gestionar' };
const SESSION_KEEPALIVE_MS = 5 * 60 * 1000;

class ApiError extends Error {
    constructor(message, status = 0, errors = {}, code = '', payload = null) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.errors = errors || {};
        this.code = code || '';
        this.payload = payload;
    }
}

/* ---------- Sesión y permisos ---------- */
function saludSession() {
    const session = window.SALUD_SESSION;
    return session && typeof session === 'object' ? session : { usuario: null, permisos: [], csrf_token: '' };
}
function currentUser() {
    return saludSession().usuario || null;
}
function can(permiso) {
    const permisos = saludSession().permisos;
    return Array.isArray(permisos) && permisos.includes(permiso);
}
function csrfToken() {
    return saludSession().csrf_token || document.querySelector('meta[name="csrf-token"]')?.content || '';
}
function updateSession(payload) {
    if (!payload || typeof payload !== 'object') return;
    const session = { ...saludSession() };
    if (payload.usuario && typeof payload.usuario === 'object') session.usuario = payload.usuario;
    if (Array.isArray(payload.permisos)) session.permisos = payload.permisos;
    if (typeof payload.csrf_token === 'string' && payload.csrf_token) session.csrf_token = payload.csrf_token;
    window.SALUD_SESSION = session;
}
// Oculta (atributo hidden) lo que el rol no puede usar. data-sin-permiso hace lo contrario:
// muestra el elemento solo a quien NO tiene ese permiso (p. ej. "Registrar paciente" para asistentes).
function applyPermissions(root = document) {
    root.querySelectorAll('[data-permiso]').forEach(element => { if (!can(element.dataset.permiso)) element.hidden = true; });
    root.querySelectorAll('[data-sin-permiso]').forEach(element => { if (can(element.dataset.sinPermiso)) element.hidden = true; });
}
function roleLabel(role) {
    return ROLE_LABELS[role] || 'Usuario';
}

let sessionLost = false;
let sessionRedirecting = false;
let sessionDialogOpen = false;
function redirectToLogin(reason = 'expirada') {
    if (sessionRedirecting) return;
    sessionRedirecting = true;
    window.removeEventListener('beforeunload', warnBeforeUnload);
    location.replace(`login.php?${reason}=1`);
}
function hasUnsavedWork() {
    return isSurfaceOpen('patientModal') || isSurfaceOpen('citaModal');
}
// Si la sesión vence con un formulario abierto no se redirige: se ofrece iniciar sesión en otra pestaña
// para no perder lo escrito. Al volver a esta pestaña se renueva el token CSRF.
function handleSessionExpired() {
    sessionLost = true;
    if (!hasUnsavedWork() || typeof Swal === 'undefined') { redirectToLogin(); return; }
    if (sessionDialogOpen) return;
    sessionDialogOpen = true;
    Swal.fire({
        icon: 'warning', titleText: 'Su sesión expiró',
        text: 'Lo que escribió sigue en el formulario. Inicie sesión en una pestaña nueva y vuelva a esta para guardar de nuevo.',
        showDenyButton: true, showCancelButton: true,
        confirmButtonText: 'Abrir inicio de sesión', denyButtonText: 'Descartar e ir al inicio de sesión', cancelButtonText: 'Seguir aquí'
    }).then(result => {
        sessionDialogOpen = false;
        if (result.isConfirmed) window.open('login.php', '_blank', 'noopener');
        else if (result.isDenied) redirectToLogin();
    });
}
async function refreshSession() {
    try {
        const json = await apiRequest('api/session.php', { skipAuthRedirect: true, timeoutMs: 10000 }, 'session');
        updateSession(json.data && typeof json.data === 'object' ? json.data : json);
        sessionLost = false;
        return true;
    } catch {
        return false;
    }
}

/* ---------- Peticiones ---------- */
// Las lecturas con la misma clave cancelan la anterior para no sustituir datos recientes.
// options.timeoutMs: tiempo máximo (20 s por defecto; 0 lo desactiva). options.skipAuthRedirect: no redirigir ante 401.
async function apiRequest(url, options = {}, requestKey = '') {
    const { timeoutMs = 20000, skipAuthRedirect = false, ...fetchOptions } = options || {};
    const method = String(fetchOptions.method || 'GET').toUpperCase();
    const controller = new AbortController();
    if (requestKey) {
        activeRequests.get(requestKey)?.abort();
        activeRequests.set(requestKey, controller);
    }
    let timedOut = false;
    const timeout = timeoutMs > 0 ? setTimeout(() => { timedOut = true; controller.abort(); }, timeoutMs) : null;
    const headers = new Headers(fetchOptions.headers || {});
    headers.set('Accept', 'application/json');
    headers.set('X-Requested-With', 'XMLHttpRequest');
    if (method !== 'GET' && method !== 'HEAD') {
        const token = csrfToken();
        if (token) headers.set('X-CSRF-Token', token);
    }
    try {
        const response = await fetch(url, { ...fetchOptions, method, headers, signal: controller.signal, credentials: 'same-origin', cache: 'no-store' });
        let payload;
        try { payload = await response.json(); }
        catch {
            if (response.status === 401 && !skipAuthRedirect) handleSessionExpired();
            throw new ApiError(response.status === 401 ? 'Su sesión expiró. Inicie sesión de nuevo.' : 'El servidor no devolvió una respuesta válida. Intente de nuevo.', response.status);
        }
        if (!response.ok || !payload || !payload.success) {
            const error = new ApiError(payload?.message || 'No se pudo completar la solicitud. Intente de nuevo.', response.status, payload?.errors, payload?.code, payload);
            if (response.status === 401 && !skipAuthRedirect) handleSessionExpired();
            if (response.status === 403) showNotification('error', error.message || 'No tiene permiso para esta acción.');
            throw error;
        }
        if (typeof payload.csrf_token === 'string' && payload.csrf_token) updateSession({ csrf_token: payload.csrf_token });
        return payload;
    } catch (error) {
        if (timedOut) {
            throw new ApiError(method === 'GET'
                ? 'El servidor tardó demasiado en responder. Intente de nuevo.'
                : 'No se recibió confirmación del servidor. Es posible que los datos se hayan guardado: revise el historial antes de reintentar.', 0, {}, 'timeout');
        }
        if (error.name === 'AbortError' || error instanceof ApiError) throw error;
        throw new ApiError('No se pudo conectar con el servidor. Revise su conexión e intente de nuevo.', 0, {}, 'network');
    } finally {
        if (timeout) clearTimeout(timeout);
        if (requestKey && activeRequests.get(requestKey) === controller) activeRequests.delete(requestKey);
    }
}
// UUID v4 para la idempotencia de los registros. crypto.randomUUID solo existe en contextos seguros
// (HTTPS o localhost); en la red local por HTTP se usa getRandomValues.
function newRequestId() {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        try { return crypto.randomUUID(); } catch { /* contexto no seguro */ }
    }
    const bytes = new Uint8Array(16);
    crypto.getRandomValues(bytes);
    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;
    const hex = Array.from(bytes, byte => byte.toString(16).padStart(2, '0')).join('');
    return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
}

/* ---------- Utilidades ---------- */
function escapeHTML(value) {
    return String(value ?? '').replace(/[&<>'"]/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[character]));
}
function normalizeSearch(value) {
    return String(value ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLocaleLowerCase('es').trim();
}
// Tolerante a lo que se teclea (FE-2): quita espacios y puntos, acepta guiones repetidos
// y solo añade el guion cuando hay dígitos detrás de la letra.
function normalizeCedula(value) {
    let text = String(value ?? '').toUpperCase().replace(/[\s.]/g, '');
    if (/^\d/.test(text)) text = `V-${text}`;
    return text.replace(/^([VEJGCPN])-*(?=\d)/, '$1-').replace(/-{2,}/g, '-');
}
function formatTensionArterial(value) {
    let text = String(value ?? '').replace(/[^\d/]/g, '');
    if (text.length === 5 && !text.includes('/')) text = `${text.slice(0, 3)}/${text.slice(3)}`;
    return text;
}
// Reescribe el valor solo si cambia y conserva la posición del cursor.
function reformatPreservingCaret(input, formatter) {
    const value = input.value;
    const formatted = formatter(value);
    if (formatted === value) return;
    const caret = input.selectionStart ?? value.length;
    const nextCaret = Math.min(formatter(value.slice(0, caret)).length, formatted.length);
    input.value = formatted;
    try { input.setSelectionRange(nextCaret, nextCaret); } catch { /* el tipo de campo no admite selección */ }
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
function addDays(isoDate, days) {
    const [year, month, day] = String(isoDate).split('-').map(Number);
    const date = new Date(year, month - 1, day);
    date.setDate(date.getDate() + days);
    return localDate(date);
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
function confirmDialog({ title, text, confirmText = 'Aceptar', cancelText = 'Cancelar', icon = 'warning' }) {
    if (typeof Swal === 'undefined') return Promise.resolve(window.confirm(`${title}\n\n${text}`));
    return Swal.fire({ icon, titleText: title, text, showCancelButton: true, confirmButtonText: confirmText, cancelButtonText: cancelText, focusCancel: true })
        .then(result => result.isConfirmed);
}

/* ---------- Notificaciones ---------- */
function modalDialogVisible() {
    return Boolean(document.querySelector('.swal2-container .swal2-popup:not(.swal2-toast):not(.swal2-hide)'));
}
function showNotification(type, message, duration = 4000) {
    // Un aviso breve nunca debe sustituir a un diálogo abierto (SweetAlert solo muestra uno a la vez).
    if (typeof Swal !== 'undefined' && !modalDialogVisible()) {
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
    region.hidden = false;
    clearTimeout(region.hideTimer);
    region.hideTimer = setTimeout(() => { region.textContent = ''; region.hidden = true; }, duration);
}
function showValidationErrors(errors) {
    const messages = Array.isArray(errors) ? errors : Object.values(errors || {});
    if (messages.length) showNotification('warning', messages.join(' '), 6000);
}

/* ---------- Errores en línea (FE-1, FE-12) ---------- */
function clearFieldError(input) {
    if (!input) return;
    if (input.form && input.name) document.getElementById(`${input.form.id}-error-${input.name}`)?.remove();
    if ('describedbyOriginal' in input.dataset) {
        const original = input.dataset.describedbyOriginal;
        if (original) input.setAttribute('aria-describedby', original);
        else input.removeAttribute('aria-describedby');
        delete input.dataset.describedbyOriginal;
    }
    input.removeAttribute('aria-invalid');
}
// Solo borra los mensajes creados por handleInlineErrors; los textos fijos del formulario se conservan.
function clearInlineErrors(formId) {
    const form = document.getElementById(formId);
    if (!form) return;
    form.querySelectorAll('[data-inline-error]').forEach(element => element.remove());
    form.querySelectorAll('[aria-invalid="true"]').forEach(clearFieldError);
}
function revealField(input) {
    if (!input) return;
    const section = input.closest('.clinical-section-body');
    if (section) openAccordion(section.id);
    input.focus();
}
function findFormField(form, name) {
    return Array.from(form.elements).find(field => field.name === name && field.type !== 'hidden' && !field.disabled);
}
function handleInlineErrors(errors, formId) {
    clearInlineErrors(formId);
    const form = document.getElementById(formId);
    if (!form || !errors || Array.isArray(errors)) return;
    let first;
    for (const [name, message] of Object.entries(errors)) {
        const input = findFormField(form, name);
        if (!input) continue;
        const error = document.createElement('p');
        error.id = `${formId}-error-${name}`;
        error.className = 'inline-error-text';
        error.dataset.inlineError = '';
        error.textContent = String(message);
        if (!('describedbyOriginal' in input.dataset)) input.dataset.describedbyOriginal = input.getAttribute('aria-describedby') || '';
        input.setAttribute('aria-describedby', [input.dataset.describedbyOriginal, error.id].filter(Boolean).join(' '));
        input.setAttribute('aria-invalid', 'true');
        const field = input.closest('.field');
        if (field && field.contains(input)) field.append(error);
        else input.insertAdjacentElement('afterend', error);
        const section = input.closest('.clinical-section-body');
        if (section) openAccordion(section.id);
        first ||= input;
    }
    if (first) revealField(first);
    return first;
}
function showFormError(error, formId) {
    handleInlineErrors(error.errors, formId);
    const form = document.getElementById(formId);
    // Los errores sin campo visible se añaden al aviso para que no se pierdan.
    const orphan = form && error.errors && !Array.isArray(error.errors)
        ? Object.entries(error.errors).filter(([name]) => !findFormField(form, name)).map(([, message]) => String(message))
        : [];
    showNotification('error', [error.message, ...orphan].filter(Boolean).join(' '), 6000);
}
function validationText(field) {
    const validity = field.validity;
    if (validity.valueMissing) return field.type === 'checkbox' ? 'Marque esta casilla para continuar.' : 'Este campo es obligatorio.';
    if (validity.patternMismatch) return field.dataset.patternMessage || field.title || 'Revise el formato del dato.';
    if (validity.tooShort) return `Escriba al menos ${field.minLength} caracteres.`;
    if (validity.tooLong) return `Escriba como máximo ${field.maxLength} caracteres.`;
    if (validity.rangeUnderflow) return field.type === 'date' ? `La fecha no puede ser anterior al ${formatDate(field.min)}.` : `El valor mínimo es ${field.min}.`;
    if (validity.rangeOverflow) return field.type === 'date' ? `La fecha no puede ser posterior al ${formatDate(field.max)}.` : `El valor máximo es ${field.max}.`;
    if (validity.badInput || validity.typeMismatch || validity.stepMismatch) {
        if (field.type === 'number') return 'Indique un número válido.';
        if (field.type === 'date' || field.type === 'datetime-local') return 'Indique una fecha válida.';
        return 'Revise el valor indicado.';
    }
    return field.validationMessage || 'Revise este campo.';
}
// Señala todos los campos inválidos a la vez, abre sus secciones y enfoca el primero (UX-2).
function validateForm(form, patientOnly = false) {
    clearInlineErrors(form.id);
    const errors = {};
    for (const field of form.elements) {
        if (!field.name || errors[field.name]) continue;
        if (patientOnly && !PATIENT_FIELDS.includes(field.name)) continue;
        if (field.required && typeof field.value === 'string' && field.type !== 'file' && !field.value.trim()) field.value = '';
        if (field.willValidate && !field.checkValidity()) errors[field.name] = validationText(field);
    }
    const count = Object.keys(errors).length;
    if (!count) return true;
    handleInlineErrors(errors, form.id);
    showNotification('warning', count === 1 ? 'Revise el campo señalado.' : `Revise los ${count} campos señalados.`);
    return false;
}
function setFormBusy(form, busy) {
    form.dataset.saving = busy ? 'true' : 'false';
    form.setAttribute('aria-busy', String(busy));
    const buttons = [...form.querySelectorAll('button'), ...document.querySelectorAll(`[form="${form.id}"]`)];
    if (form.id === 'registroForm') buttons.push(document.getElementById('btnGuardarSoloPaciente'));
    buttons.filter(Boolean).forEach(button => { button.disabled = busy; });
}

/* ---------- Superficies (modales y panel lateral) e historial (UX-8) ---------- */
function isSurfaceOpen(id) {
    const element = document.getElementById(id);
    return Boolean(element && !element.classList.contains('hidden'));
}
function openSurfaceId() {
    return SURFACE_IDS.find(isSurfaceOpen);
}
function syncScrollLock() {
    const sidebarOpen = MOBILE_LAYOUT.matches && document.getElementById('sidebar')?.classList.contains('is-open');
    document.body.style.overflow = openSurfaceId() || sidebarOpen ? 'hidden' : '';
}
// Cada superficie abierta añade una entrada al historial: Atrás la cierra en lugar de cambiar de sección.
let historySkipPop = false;
let historyPendingSurface = null;
function pushSurfaceState(id) {
    if (historySkipPop) { historyPendingSurface = id; return; }
    if (history.state?.surface === id) return;
    history.pushState({ surface: id }, '', location.href);
}
function popSurfaceState(id) {
    if (historyPendingSurface === id) { historyPendingSurface = null; return; }
    if (!historySkipPop && history.state?.surface === id) {
        historySkipPop = true;
        history.back();
    }
}
function resolveFocusTarget(content, preferred) {
    const usable = element => element && !element.disabled && !element.readOnly && element.getClientRects().length > 0;
    if (usable(preferred)) return preferred;
    const auto = content.querySelector('[data-autofocus]');
    if (usable(auto) && !auto.value) return auto;
    const field = [...content.querySelectorAll('.modal-body input:not([type="hidden"]), .modal-body select, .modal-body textarea')].find(usable);
    return field || content.querySelector('button:not(:disabled)') || content;
}
function openSurface(id, contentId, titleId, focusTarget = null) {
    const surface = document.getElementById(id);
    const content = document.getElementById(contentId);
    if (!surface || !content) return;
    // Un aviso antiguo no debe tapar el título de la siguiente tarea; los nuevos sí se muestran.
    if (typeof Swal !== 'undefined' && document.querySelector('.swal2-toast')) Swal.close();
    if (!isSurfaceOpen(id)) surfaceOpeners.set(id, document.activeElement);
    surface.classList.remove('hidden');
    surface.removeAttribute('aria-hidden');
    content.setAttribute('role', 'dialog');
    content.setAttribute('aria-modal', 'true');
    content.setAttribute('aria-labelledby', titleId);
    content.tabIndex = -1;
    content.classList.remove('modal-enter', 'modal-leave-active', 'translate-x-full');
    content.classList.add('modal-enter-active');
    syncScrollLock();
    pushSurfaceState(id);
    // FE-9 / UX-5: el foco va al primer campo útil, nunca al botón Cerrar si hay campos.
    requestAnimationFrame(() => resolveFocusTarget(content, focusTarget).focus());
}
function closeSurface(id, contentId) {
    const surface = document.getElementById(id);
    const content = document.getElementById(contentId);
    if (!surface || !content) return;
    const wasOpen = isSurfaceOpen(id);
    surface.classList.add('hidden');
    surface.setAttribute('aria-hidden', 'true');
    content.classList.remove('modal-enter-active');
    content.classList.add(id === 'patientDrawer' ? 'translate-x-full' : 'modal-enter');
    syncScrollLock();
    if (wasOpen) popSurfaceState(id);
    const opener = surfaceOpeners.get(id);
    if (opener?.isConnected && opener.getClientRects().length) opener.focus();
    surfaceOpeners.delete(id);
}
function closeSurfaceById(id) {
    const closers = { citaModal: closeCitaModal, patientModal: closeModal, patientDrawer: closeDrawer };
    if (typeof closeUsuarioModal === 'function') closers.usuarioModal = closeUsuarioModal;
    return (closers[id] || (() => true))();
}

function toggleMobileSidebar(force) {
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) return;
    const open = MOBILE_LAYOUT.matches && (typeof force === 'boolean' ? force : !sidebar.classList.contains('is-open'));
    sidebar.classList.toggle('is-open', open);
    document.getElementById('sidebarToggle')?.setAttribute('aria-expanded', String(open));
    document.getElementById('sidebarOverlay')?.classList.toggle('hidden', !open);
    syncScrollLock();
    if (open) sidebar.querySelector('.nav-link:not([hidden])')?.focus();
}
function canAccessTab(tabName) {
    const permission = TAB_PERMISSIONS[tabName];
    return !permission || can(permission);
}
function switchTab(tabName, updateHistory = true) {
    if (!Object.hasOwn(TAB_TITLES, tabName) || !canAccessTab(tabName) || !document.getElementById(`section-${tabName}`)) tabName = 'dashboard';
    currentTab = tabName;
    toggleMobileSidebar(false);
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
    if (heading) heading.textContent = TAB_TITLES[tabName];
    document.title = `${TAB_TITLES[tabName]} · Salud UNEFA`;
    if (updateHistory && location.hash !== `#${tabName}`) history.pushState(null, '', `#${tabName}`);
    if (tabName === 'pacientes') { if (typeof loadPacientes === 'function') loadPacientes(); }
    else if (tabName === 'citas') { if (typeof loadCitas === 'function') loadCitas(); }
    else if (tabName === 'reportes') loadReportesOnEnter();
    else if (tabName === 'admin') { if (typeof loadAdmin === 'function') loadAdmin(); }
    else {
        // Las gráficas pueden haberse actualizado ocultas; se miden ahora sobre el lienzo visible.
        categoryChartInstance?.resize();
        historyChartInstance?.resize();
        if (typeof loadStats === 'function') loadStats();
    }
}
// Solo consulta automáticamente si ya hay un periodo elegido y todavía no hay resultados en pantalla.
function loadReportesOnEnter() {
    if (typeof buscarMorbilidad !== 'function') return;
    const start = document.getElementById('morbilidadInicio')?.value;
    const end = document.getElementById('morbilidadFin')?.value;
    const body = document.getElementById('morbilidadTableBody');
    if (start && end && body && !body.children.length) buscarMorbilidad();
}

/* ---------- Acordeón no exclusivo (UX-2) ---------- */
function setAccordion(id, open) {
    const section = document.getElementById(id);
    if (!section) return;
    section.classList.toggle('hidden', !open);
    const toggle = document.querySelector(`[aria-controls="${id}"]`);
    toggle?.setAttribute('aria-expanded', String(open));
    toggle?.closest('.clinical-section')?.classList.toggle('is-open', open);
}
function toggleAccordion(id) {
    const section = document.getElementById(id);
    if (section) setAccordion(id, section.classList.contains('hidden'));
}
function openAccordion(id) { setAccordion(id, true); }
function closeAccordion(id) { setAccordion(id, false); }
function setPatientSummary(text) {
    const summary = document.getElementById('summary-acc-paciente');
    if (summary) summary.textContent = text ? ` · ${text}` : '';
}
function updateSectionStatus() {
    document.querySelectorAll('#registroForm .clinical-section').forEach(section => {
        const body = section.querySelector('.clinical-section-body');
        const badge = body && document.getElementById(`status-${body.id}`);
        if (!badge) return;
        const required = section.hidden ? [] : [...body.querySelectorAll('[required]')].filter(field => !field.disabled);
        const pending = required.filter(field => !String(field.value).trim()).length;
        badge.textContent = !required.length ? '' : pending ? `${pending} ${pending === 1 ? 'obligatorio pendiente' : 'obligatorios pendientes'}` : 'Completo';
        badge.className = `section-status${required.length ? (pending ? ' is-pending' : ' is-complete') : ''}`;
    });
}

/* ---------- Categorías ---------- */
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
            updateDemographicVisibility();
        } catch (error) {
            if (error.name === 'AbortError') return;
            showBlockError('categoriasError', document.getElementById('registroForm'), 'No se pudieron cargar las categorías.', loadCategorias);
            showNotification('error', error.message);
        } finally { categoriasPromise = null; }
    })();
    return categoriasPromise;
}

/* ---------- Formulario de consulta ---------- */
let registroMode = 'consulta';
let registroCloseConfirming = false;
const REGISTRO_TITLES = {
    consulta: ['Consulta e historia clínica', 'Revise la identificación y complete la atención. Los campos con * son obligatorios.', 'ph-floppy-disk', 'Guardar consulta'],
    paciente: ['Datos del paciente', 'Identificación y datos demográficos. Los campos con * son obligatorios.', 'ph-floppy-disk', 'Guardar paciente'],
    edicion: ['Corregir consulta', 'Modifique los datos clínicos e indique el motivo de la corrección.', 'ph-floppy-disk', 'Guardar cambios']
};
function applyRegistroMode(mode) {
    const form = document.getElementById('registroForm');
    if (!form) return;
    registroMode = mode;
    const clinical = can('clinica.editar');
    form.querySelectorAll('.clinical-section').forEach(section => {
        const name = section.dataset.section;
        let visible;
        if (mode === 'edicion') visible = 'consulta' in section.dataset;
        else if (mode === 'paciente') visible = name === 'paciente' || (name === 'antecedentes' && clinical);
        else visible = name === 'paciente' || clinical;
        // Lo oculto también se deshabilita: no se valida ni se envía.
        section.hidden = !visible;
        section.disabled = !visible;
    });
    const edit = document.getElementById('editConsultaBlock');
    if (edit) { edit.hidden = mode !== 'edicion'; edit.disabled = mode !== 'edicion'; }
    ['formConsultaId', 'formVersionEsperada', 'formResumen'].forEach(id => { const field = document.getElementById(id); if (field) field.disabled = mode !== 'edicion'; });
    const requestId = document.getElementById('formSolicitudId');
    if (requestId) requestId.disabled = mode === 'edicion';
    const attachment = document.getElementById('attachmentField');
    if (attachment) attachment.hidden = mode === 'edicion';
    const file = document.getElementById('archivoAdjunto');
    if (file) file.disabled = mode === 'edicion';
    const [title, subtitle, icon, label] = REGISTRO_TITLES[mode];
    document.getElementById('patientModalTitle').textContent = title;
    document.getElementById('patientModalSubtitle').textContent = subtitle;
    const save = document.getElementById('btnGuardar');
    if (save) save.innerHTML = `<i class="ph ${icon}" aria-hidden="true"></i> ${label}`;
    const onlyPatient = document.getElementById('btnGuardarSoloPaciente');
    if (onlyPatient) onlyPatient.hidden = mode !== 'consulta';
}
function isStudentCategory() {
    const select = document.getElementById('formCategoria');
    return normalizeSearch(select?.selectedOptions[0]?.text).includes('estudiante');
}
// UX-13: ginecoobstétricos solo para sexo femenino; carrera, semestre y compromiso de tatuajes
// solo para estudiantes. Si el bloque ya tiene datos se muestra igual, para no ocultar historia.
function updateDemographicVisibility() {
    const form = document.getElementById('registroForm');
    if (!form) return;
    const female = document.getElementById('formSexo')?.value === 'Femenino';
    const student = isStudentCategory();
    const gineco = document.getElementById('ginecoGroup');
    if (gineco) gineco.hidden = !(female || [...gineco.querySelectorAll('input')].some(input => input.value.trim()));
    form.querySelectorAll('[data-student-only]').forEach(field => {
        field.hidden = !(student || field.querySelector('input')?.value.trim());
    });
    const commitment = document.getElementById('compromisoGroup');
    if (commitment) commitment.hidden = !(student || document.getElementById('formCompromisoTatuajes')?.checked);
}
function setCompromisoTatuajes(value) {
    const checkbox = document.getElementById('formCompromisoTatuajes');
    if (!checkbox) return;
    const text = String(value ?? '').trim();
    // Un texto antiguo distinto del estándar se conserva tal cual al volver a guardar.
    checkbox.value = text || checkbox.defaultValue;
    checkbox.checked = Boolean(text);
}
function serializeRegistro(form) {
    const data = new FormData(form);
    data.delete('solicitud_id');
    const entries = [...data.entries()].filter(([, value]) => typeof value === 'string');
    const file = form.elements.namedItem('archivo');
    return new URLSearchParams(entries).toString() + (file?.files?.length ? '|archivo' : '');
}
function takeRegistroSnapshot() {
    const form = document.getElementById('registroForm');
    if (form) form.dataset.snapshot = serializeRegistro(form);
}
function isRegistroDirty() {
    const form = document.getElementById('registroForm');
    if (!form || !isSurfaceOpen('patientModal') || form.dataset.snapshot === undefined) return false;
    return serializeRegistro(form) !== form.dataset.snapshot;
}
// openModal(cedula, categoriaId, nombres, apellidos, opciones):
//   opciones.citaId      → la consulta completa esa cita (envía cita_id).
//   opciones.consultaId  → modo edición de una consulta registrada.
//   opciones.soloPaciente→ solo identificación, demografía y antecedentes.
// Sin clinica.editar el formulario se limita siempre a los datos del paciente.
function openModal(cedula = '', categoriaId = '', nombres = '', apellidos = '', opciones = {}) {
    const form = document.getElementById('registroForm');
    if (!form || form.dataset.saving === 'true') return;
    const options = opciones && typeof opciones === 'object' ? opciones : {};
    const consultaId = Number(options.consultaId) || 0;
    const citaId = Number(options.citaId) || 0;
    const clinical = can('clinica.editar');
    if (consultaId > 0 && !clinical) { showNotification('error', 'No tiene permiso para corregir consultas.'); return; }
    if (!clinical && !can('pacientes.editar')) { showNotification('error', 'No tiene permiso para registrar pacientes.'); return; }
    const mode = consultaId > 0 ? 'edicion' : (options.soloPaciente || !clinical) ? 'paciente' : 'consulta';
    form.reset();
    form.noValidate = true;
    delete form.dataset.patientCedula;
    delete form.dataset.snapshot;
    delete form.dataset.pacienteId;
    if (typeof resetPatientLookupState === 'function') resetPatientLookupState();
    clearInlineErrors(form.id);
    setCompromisoTatuajes('');
    document.getElementById('formSoloPaciente').value = mode === 'paciente' ? '1' : '0';
    document.getElementById('formFechaCircunstancia').value = localDate();
    document.getElementById('formSolicitudId').value = newRequestId();
    const cita = document.getElementById('formCitaId');
    cita.value = citaId > 0 ? String(citaId) : '';
    cita.disabled = !(citaId > 0 && mode === 'consulta');
    document.getElementById('formConfirmarIdentidad').disabled = true;
    document.getElementById('formConsultaId').value = consultaId > 0 ? String(consultaId) : '';
    document.getElementById('formVersionEsperada').value = '';
    document.getElementById('formCedula').value = normalizeCedula(cedula);
    document.getElementById('formCategoria').value = categoriaId ? String(categoriaId) : '';
    document.getElementById('formNombres').value = nombres || '';
    document.getElementById('formApellidos').value = apellidos || '';
    setVisible(document.getElementById('fileError'), false);
    setVisible(document.getElementById('fileName'), false);
    setVisible(document.getElementById('patientLookupStatus'), false);
    setPatientSummary('');
    applyRegistroMode(mode);
    ['acc-paciente', 'acc-antecedentes', 'acc-vitales', 'acc-fisico', 'acc-diagnostico'].forEach(closeAccordion);
    if (mode === 'edicion') ['acc-vitales', 'acc-fisico', 'acc-diagnostico'].forEach(openAccordion);
    else if (mode === 'paciente') openAccordion('acc-paciente');
    else ['acc-paciente', 'acc-vitales'].forEach(openAccordion);
    updateDemographicVisibility();
    updateSectionStatus();
    let focus = null;
    if (mode === 'edicion') focus = document.getElementById('formMotivoEdicion');
    else if (cedula) focus = document.getElementById(mode === 'consulta' ? 'formMotivoConsulta' : 'formNombres');
    openSurface('patientModal', 'modalContent', 'patientModalTitle', focus);
    takeRegistroSnapshot();
    if (mode === 'edicion') { if (typeof loadConsultaForEdit === 'function') loadConsultaForEdit(consultaId); }
    else if (cedula && typeof buscarPacientePorCedula === 'function') buscarPacientePorCedula(cedula);
}
// Devuelve true si el formulario quedó cerrado. Con cambios sin guardar pide confirmación (UX-1).
async function closeModal(force = false) {
    const form = document.getElementById('registroForm');
    if (!form || form.dataset.saving === 'true') return false;
    if (!isSurfaceOpen('patientModal')) return true;
    if (force !== true && isRegistroDirty()) {
        if (registroCloseConfirming) return false;
        registroCloseConfirming = true;
        let confirmed = false;
        try {
            confirmed = await confirmDialog({
                title: registroMode === 'paciente' ? '¿Descartar los cambios del paciente?' : registroMode === 'edicion' ? '¿Descartar la corrección?' : '¿Descartar la consulta en curso?',
                text: 'Los datos escritos en este formulario no se han guardado y se perderán.',
                confirmText: 'Descartar', cancelText: 'Seguir editando'
            });
        } finally { registroCloseConfirming = false; }
        if (!confirmed || form.dataset.saving === 'true') return false;
    }
    activeRequests.get('patientLookup')?.abort();
    activeRequests.get('consultaEdit')?.abort();
    delete form.dataset.snapshot;
    closeSurface('patientModal', 'modalContent');
    return true;
}
function openCitaModal(pacienteId = 0, cedula = '', nombreCompleto = '') {
    const form = document.getElementById('citaForm');
    if (!form || form.dataset.saving === 'true') return;
    if (!can('citas.gestionar')) { showNotification('error', 'No tiene permiso para agendar citas.'); return; }
    form.reset();
    form.noValidate = true;
    delete form.dataset.lookupCedula;
    setVisible(document.getElementById('citaLookupStatus'), false);
    clearInlineErrors(form.id);
    document.getElementById('citaPacienteId').value = Number(pacienteId) || 0;
    document.getElementById('citaCedula').value = cedula ? normalizeCedula(cedula) : '';
    document.getElementById('citaPacienteNombreText').textContent = nombreCompleto;
    setVisible(document.getElementById('citaPacienteInfo'), Number(pacienteId) > 0);
    setVisible(document.getElementById('citaNuevoPacienteCampos'), false);
    ['citaNombres', 'citaApellidos', 'citaCategoria'].forEach(id => { const field = document.getElementById(id); if (field) field.required = false; });
    const fecha = form.elements.namedItem('fecha_cita');
    if (fecha) fecha.min = localDateTime(new Date(Date.now() + 60000));
    // Con el paciente ya identificado se empieza por la fecha y no se vuelve a buscar la cédula.
    openSurface('citaModal', 'citaModalContent', 'citaModalTitle', cedula ? fecha : null);
}
function closeCitaModal() {
    if (document.getElementById('citaForm')?.dataset.saving === 'true') return false;
    activeRequests.get('citaLookup')?.abort();
    closeSurface('citaModal', 'citaModalContent');
    return true;
}
function closeDrawer() {
    activeRequests.get('patientDetails')?.abort();
    closeSurface('patientDrawer', 'drawerContent');
    return true;
}
function validarArchivo(input) {
    const error = document.getElementById('fileError');
    const name = document.getElementById('fileName');
    setVisible(error, false);
    setVisible(name, false);
    if (!input) return true;
    input.setCustomValidity('');
    const file = input.files?.[0];
    if (!file) return true;
    let message = '';
    if (file.size > 10 * 1024 * 1024) message = 'El archivo supera el máximo de 10 MB.';
    else if (!['image/jpeg', 'image/png', 'application/pdf'].includes(file.type)) message = 'Seleccione un archivo JPG, PNG o PDF.';
    if (message) {
        input.value = '';
        if (error) { error.textContent = message; setVisible(error, true); }
        else showNotification('warning', message);
        return false;
    }
    if (name) { name.textContent = `Archivo seleccionado: ${file.name}`; setVisible(name, true); }
    return true;
}
// Recarga lo visible; la lista de pacientes se recarga al volver a su pestaña (FE-4).
async function refreshClinicalViews() {
    const tasks = [];
    if (typeof loadStats === 'function') tasks.push(loadStats());
    if (currentTab === 'pacientes' && typeof loadPacientes === 'function') tasks.push(loadPacientes());
    if (currentTab === 'citas' && typeof loadCitas === 'function') tasks.push(loadCitas());
    await Promise.allSettled(tasks);
    if (typeof invalidateMorbilidad === 'function') invalidateMorbilidad();
}

/* ---------- Menú de usuario ---------- */
function renderUserIdentity() {
    const user = currentUser();
    if (!user) return;
    const name = String(user.nombre_completo || user.usuario || 'Usuario');
    const initials = name.split(/\s+/).filter(Boolean).slice(0, 2).map(part => part.charAt(0)).join('').toUpperCase();
    document.querySelectorAll('[data-user-name]').forEach(element => { element.textContent = name; });
    document.querySelectorAll('[data-user-role]').forEach(element => { element.textContent = roleLabel(user.rol); });
    document.querySelectorAll('[data-user-initials]').forEach(element => { element.textContent = initials || 'U'; });
}
function userMenuItems() {
    return [...document.querySelectorAll('#userMenu [role="menuitem"]')].filter(item => !item.hidden);
}
function toggleUserMenu(force) {
    const button = document.getElementById('userMenuButton');
    const menu = document.getElementById('userMenu');
    if (!button || !menu) return;
    const open = typeof force === 'boolean' ? force : menu.hidden;
    menu.hidden = !open;
    button.setAttribute('aria-expanded', String(open));
    if (open) userMenuItems()[0]?.focus();
}
async function cambiarPassword() {
    toggleUserMenu(false);
    if (typeof Swal === 'undefined') { showNotification('error', 'No se pudo abrir el formulario de contraseña.'); return; }
    const user = currentUser();
    const result = await Swal.fire({
        titleText: 'Cambiar contraseña',
        html: `<form id="passwordForm" class="swal-form" novalidate onsubmit="return false">
            <input type="text" name="username" autocomplete="username" value="${escapeHTML(user?.usuario || '')}" hidden>
            <div class="field"><label for="pwdActual">Contraseña actual</label><input type="password" id="pwdActual" autocomplete="current-password" required></div>
            <div class="field"><label for="pwdNueva">Nueva contraseña</label><input type="password" id="pwdNueva" autocomplete="new-password" minlength="10" required aria-describedby="pwdHelp"><p id="pwdHelp" class="field-help">Al menos 10 caracteres y distinta de la actual.</p></div>
            <div class="field"><label for="pwdConfirmar">Repita la nueva contraseña</label><input type="password" id="pwdConfirmar" autocomplete="new-password" required></div>
        </form>`,
        showCancelButton: true, confirmButtonText: 'Guardar contraseña', cancelButtonText: 'Cancelar',
        focusConfirm: false, showLoaderOnConfirm: true,
        allowOutsideClick: () => !Swal.isLoading(),
        didOpen: popup => {
            popup.querySelector('#pwdActual')?.focus();
            popup.querySelectorAll('input').forEach(input => input.addEventListener('keydown', event => {
                if (event.key === 'Enter') { event.preventDefault(); Swal.clickConfirm(); }
            }));
        },
        preConfirm: async () => {
            const actual = document.getElementById('pwdActual').value;
            const nueva = document.getElementById('pwdNueva').value;
            const confirmacion = document.getElementById('pwdConfirmar').value;
            if (!actual) { Swal.showValidationMessage('Escriba su contraseña actual.'); return false; }
            if (nueva.length < 10) { Swal.showValidationMessage('La nueva contraseña debe tener al menos 10 caracteres.'); return false; }
            if (nueva === actual) { Swal.showValidationMessage('La nueva contraseña debe ser distinta de la actual.'); return false; }
            if (nueva !== confirmacion) { Swal.showValidationMessage('Las contraseñas nuevas no coinciden.'); return false; }
            try {
                const data = new FormData();
                data.append('actual', actual);
                data.append('nueva', nueva);
                return await apiRequest('api/cambiar_password.php', { method: 'POST', body: data, timeoutMs: 30000 });
            } catch (error) {
                // El 422 trae el motivo por campo («La contraseña actual no es correcta», etc.).
                Swal.showValidationMessage(error.errors?.actual || error.errors?.nueva || Object.values(error.errors || {})[0] || error.message);
                return false;
            }
        }
    });
    if (result.isConfirmed) {
        updateSession(result.value);
        showNotification('success', result.value?.message || 'La contraseña se actualizó correctamente.');
    }
}
async function cerrarSesion() {
    toggleUserMenu(false);
    try {
        await apiRequest('api/logout.php', { method: 'POST', skipAuthRedirect: true, timeoutMs: 10000 });
    } catch (error) {
        // Sin sesión en el servidor el resultado es el mismo; ante otro fallo la sesión seguiría abierta.
        if (error.status !== 401) { showNotification('error', `No se pudo cerrar la sesión. ${error.message}`); return; }
    }
    redirectToLogin('salida');
}

/* ---------- Eventos globales ---------- */
function warnBeforeUnload(event) {
    const form = document.getElementById('registroForm');
    if (isRegistroDirty() || form?.dataset.saving === 'true') {
        event.preventDefault();
        event.returnValue = '';
    }
}
window.addEventListener('beforeunload', warnBeforeUnload);

document.addEventListener('input', event => {
    const input = event.target;
    if (!(input instanceof HTMLInputElement || input instanceof HTMLTextAreaElement)) return;
    if (input.name === 'cedula' && input.type === 'text') reformatPreservingCaret(input, normalizeCedula);
    if (input.name === 'vital_ta') reformatPreservingCaret(input, formatTensionArterial);
    if (input.getAttribute('aria-invalid') === 'true') clearFieldError(input);
    if (input.form?.id === 'registroForm') updateSectionStatus();
});
document.addEventListener('change', event => {
    const field = event.target;
    if (field instanceof HTMLSelectElement && field.getAttribute('aria-invalid') === 'true') clearFieldError(field);
    if (field.form?.id !== 'registroForm') return;
    if (['formSexo', 'formCategoria', 'formCompromisoTatuajes'].includes(field.id)) updateDemographicVisibility();
    updateSectionStatus();
});
document.addEventListener('keydown', event => {
    if (document.querySelector('.swal2-container .swal2-popup:not(.swal2-toast)')) return;
    const menu = document.getElementById('userMenu');
    if (menu && !menu.hidden) {
        const items = userMenuItems();
        const index = items.indexOf(document.activeElement);
        if (event.key === 'Escape') { event.preventDefault(); toggleUserMenu(false); document.getElementById('userMenuButton')?.focus(); return; }
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            const step = event.key === 'ArrowDown' ? 1 : -1;
            items[(index + step + items.length) % items.length]?.focus();
            return;
        }
        if (event.key === 'Tab') toggleUserMenu(false);
    }
    const open = openSurfaceId();
    if (open) {
        if (event.key === 'Escape') {
            event.preventDefault();
            closeSurfaceById(open);
        }
        if (event.key === 'Tab') {
            const dialog = document.getElementById(open);
            const elements = [...dialog.querySelectorAll('button:not(:disabled), a[href], input:not([type="hidden"]):not(:disabled), select:not(:disabled), textarea:not(:disabled), [tabindex="0"]')].filter(element => element.getClientRects().length);
            const first = elements[0], last = elements[elements.length - 1];
            if (!dialog.contains(document.activeElement)) { event.preventDefault(); first?.focus(); }
            else if (event.shiftKey && (document.activeElement === first || document.activeElement === document.getElementById(dialog.querySelector('[role="dialog"]')?.id))) { event.preventDefault(); last?.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
        }
    } else if (event.key === 'Escape') toggleMobileSidebar(false);
});
document.addEventListener('click', event => {
    const menu = document.getElementById('userMenu');
    if (menu && !menu.hidden && !event.target.closest('#userMenu, #userMenuButton')) toggleUserMenu(false);
});
window.addEventListener('popstate', () => {
    if (historySkipPop) {
        // Este retroceso lo provocó el cierre de una superficie; no cambia de sección.
        historySkipPop = false;
        const pending = historyPendingSurface;
        historyPendingSurface = null;
        if (pending && isSurfaceOpen(pending)) history.pushState({ surface: pending }, '', location.href);
        return;
    }
    const open = openSurfaceId();
    if (open) {
        // Se repone la entrada y se usa el cierre normal: si el usuario no confirma, todo queda igual.
        history.pushState({ surface: open }, '', location.href);
        closeSurfaceById(open);
        return;
    }
    if (location.hash.slice(1) !== currentTab) switchTab(location.hash.slice(1), false);
});
window.addEventListener('hashchange', () => {
    if (openSurfaceId()) return;
    if (location.hash.slice(1) !== currentTab) switchTab(location.hash.slice(1), false);
});
MOBILE_LAYOUT.addEventListener('change', () => toggleMobileSidebar(false));

// Mantiene la sesión mientras alguien escribe en un formulario abierto (la inactividad es de 30 min).
let lastActivity = Date.now();
for (const eventName of ['input', 'keydown', 'pointerdown']) document.addEventListener(eventName, () => { lastActivity = Date.now(); }, { passive: true });
setInterval(() => {
    if (!sessionLost && hasUnsavedWork() && Date.now() - lastActivity < SESSION_KEEPALIVE_MS) refreshSession();
}, SESSION_KEEPALIVE_MS);
window.addEventListener('focus', () => { if (sessionLost) refreshSession(); });

document.addEventListener('DOMContentLoaded', () => {
    // Tras recargar, una entrada de superficie heredada no debe cerrar nada al pulsar Atrás.
    if (history.state?.surface) history.replaceState(null, '', location.href);
    applyPermissions();
    renderUserIdentity();
    // Respaldo: vincula etiquetas sin "for" en formularios que lo necesiten.
    document.querySelectorAll('form').forEach(form => {
        form.querySelectorAll('input:not([type="hidden"]),select,textarea').forEach((input, index) => {
            if (!input.id) input.id = `${form.id}-field-${index}`;
            const label = input.parentElement.querySelector('label');
            if (label && !label.htmlFor && !label.contains(input)) label.htmlFor = input.id;
        });
    });
    loadCategorias();
    switchTab(location.hash.slice(1) || 'dashboard', false);
});
