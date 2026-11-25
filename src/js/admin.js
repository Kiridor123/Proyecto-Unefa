// Administración: usuarios (usuarios.gestionar) y registro de actividad (auditoria.ver).
let adminUsuarios = [];
let adminUsuariosCatalog = [];
let adminUsuariosTotalGeneral = 0;
let adminUsuariosVersion = 0;
let adminUsuariosSearchTimer = null;
let adminAuditoriaVersion = 0;
let adminAuditoriaSearchTimer = null;
let adminAuditoriaCargada = false;
const adminAuditoriaFiltro = { pagina: 1, por_pagina: 25 };
const updatingUsers = new Set();
const ADMIN_SEARCH_MAX = 100;
const ADMIN_ROLES_VALIDOS = new Set(['', 'administrador', 'medico', 'asistente']);
const ADMIN_ESTADOS_VALIDOS = new Set(['', 'activo', 'inactivo', 'bloqueado']);

function sanitizeAdminSearch(value) {
    return String(value ?? '')
        .replace(/[\t\r\n]+/g, ' ')
        .replace(/[\x00-\x1F\x7F]+/g, '')
        .replace(/\s+/g, ' ')
        .trim()
        .slice(0, ADMIN_SEARCH_MAX);
}

const ADMIN_ROLES = {
    administrador: { nombre: 'Administrador', ayuda: 'Acceso completo: pacientes, historia clínica, citas, reportes, usuarios y registro de actividad.' },
    medico: { nombre: 'Médico', ayuda: 'Pacientes, historia clínica, consultas, impresos, citas y reportes de morbilidad.' },
    asistente: { nombre: 'Asistente', ayuda: 'Identificación y datos de contacto de pacientes y gestión de citas. Sin acceso a la historia clínica ni a reportes.' }
};
const ADMIN_ACCIONES = {
    login_ok: 'Inicio de sesión',
    login_fallido: 'Inicio de sesión fallido',
    logout: 'Cierre de sesión',
    cambiar_password: 'Cambio de contraseña',
    cambiar_password_fallido: 'Cambio de contraseña fallido',
    crear_usuario: 'Creación de usuario',
    editar_usuario: 'Edición de usuario',
    guardar_usuario: 'Guardado de usuario',
    guardar_paciente: 'Guardado de paciente',
    crear_consulta: 'Registro de consulta',
    editar_consulta: 'Edición de consulta',
    ver_expediente: 'Consulta de expediente',
    ver_consulta: 'Consulta de una atención',
    descargar_adjunto: 'Descarga de adjunto',
    crear_cita: 'Programación de cita',
    guardar_cita: 'Programación de cita',
    reprogramar_cita: 'Reprogramación de cita',
    cambiar_estado_cita: 'Cambio de estado de cita',
    actualizar_estado_cita: 'Cambio de estado de cita',
    consultar_morbilidad: 'Consulta de morbilidad',
    exportar_morbilidad: 'Exportación de morbilidad (CSV)',
    imprimir_historia: 'Impresión de historia clínica',
    imprimir_informe: 'Impresión de informe médico',
    imprimir_reposo: 'Impresión de reposo',
    imprimir_recipe: 'Impresión de récipe médico',
    imprimir_morbilidad: 'Impresión de morbilidad'
};
const ADMIN_ENTIDADES = { paciente: 'Paciente', consulta: 'Consulta', cita: 'Cita', usuario: 'Usuario', reporte: 'Reporte' };

function adminSessionUserId() {
    return Number(window.SALUD_SESSION?.usuario?.id || 0);
}
// Convierte una marca de tiempo de PostgreSQL ("2026-11-09 10:22:33.12-04") en Date.
function parseServerTimestamp(value) {
    if (!value) return null;
    let text = String(value).trim().replace(' ', 'T');
    text = text.replace(/([+-]\d{2})$/, '$1:00').replace(/([+-]\d{2})(\d{2})$/, '$1:$2');
    const date = new Date(text);
    return Number.isNaN(date.getTime()) ? null : date;
}
function formatServerTimestamp(value) {
    const date = parseServerTimestamp(value);
    if (!date) return value ? formatDate(value, true) : 'Nunca';
    return `${formatDate(localDate(date))} · ${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
}
function userIsActive(user) {
    return user.activo === true || user.activo === 1 || user.activo === '1' || user.activo === 't' || user.activo === 'true';
}
function userLockedUntil(user) {
    const until = parseServerTimestamp(user.bloqueado_hasta);
    return until && until > new Date() ? until : null;
}
function createUserStatusBadge(user) {
    const badge = document.createElement('span');
    if (!userIsActive(user)) {
        badge.className = 'status-badge status-cancelled';
        badge.textContent = 'Inactivo';
        return badge;
    }
    const locked = userLockedUntil(user);
    if (locked) {
        badge.className = 'status-badge status-pending';
        badge.textContent = `Bloqueado hasta las ${String(locked.getHours()).padStart(2, '0')}:${String(locked.getMinutes()).padStart(2, '0')}`;
        return badge;
    }
    badge.className = 'status-badge status-completed';
    badge.textContent = 'Activo';
    return badge;
}
function roleName(role) {
    return ADMIN_ROLES[role]?.nombre || role || 'Sin rol';
}

// ---------- Pestañas internas ----------
function selectAdminTab(name, focus = false) {
    const tabs = { usuarios: ['adminTabUsuarios', 'adminPanelUsuarios'], auditoria: ['adminTabAuditoria', 'adminPanelAuditoria'] };
    if (!tabs[name]) name = 'usuarios';
    if (name === 'auditoria' && !vistaPuede('auditoria.ver')) name = 'usuarios';
    if (name === 'usuarios' && !vistaPuede('usuarios.gestionar') && vistaPuede('auditoria.ver')) name = 'auditoria';
    for (const [key, [tabId, panelId]] of Object.entries(tabs)) {
        const active = key === name;
        const tab = document.getElementById(tabId);
        tab?.setAttribute('aria-selected', String(active));
        if (tab) tab.tabIndex = active ? 0 : -1;
        document.getElementById(panelId)?.classList.toggle('hidden', !active);
        if (active && focus) tab?.focus();
    }
    if (name === 'auditoria' && !adminAuditoriaCargada) loadAuditoria();
}
function initAdminTabs() {
    const list = document.querySelector('#section-admin [role="tablist"]');
    if (!list) return;
    document.getElementById('adminTabUsuarios')?.addEventListener('click', () => selectAdminTab('usuarios'));
    document.getElementById('adminTabAuditoria')?.addEventListener('click', () => selectAdminTab('auditoria'));
    list.addEventListener('keydown', event => {
        if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
        event.preventDefault();
        const current = document.getElementById('adminTabAuditoria')?.getAttribute('aria-selected') === 'true' ? 'auditoria' : 'usuarios';
        const next = event.key === 'Home' ? 'usuarios' : event.key === 'End' ? 'auditoria' : current === 'usuarios' ? 'auditoria' : 'usuarios';
        selectAdminTab(next, true);
    });
}

// ---------- Carga principal (llamada por app.js al entrar en «admin») ----------
async function loadAdmin() {
    const canUsers = vistaPuede('usuarios.gestionar');
    const canAudit = vistaPuede('auditoria.ver');
    const auditTab = document.getElementById('adminTabAuditoria');
    const usersTab = document.getElementById('adminTabUsuarios');
    if (auditTab) auditTab.hidden = !canAudit;
    if (usersTab) usersTab.hidden = !canUsers;
    if (!canUsers && !canAudit) {
        showBlockError('adminSinPermiso', document.getElementById('adminPanelUsuarios'), 'No tiene permiso para acceder a la administración.', () => switchTab('dashboard'));
        setVisible(document.getElementById('adminPanelUsuarios'), false);
        return;
    }
    if (!canUsers) {
        // Solo registro de actividad: se muestra esa pestaña sin pedir la lista de usuarios.
        const yaCargada = adminAuditoriaCargada;
        selectAdminTab('auditoria');
        if (yaCargada) await loadAuditoria();
        return;
    }
    const tasks = [loadAdminUsuarios()];
    if (auditTab?.getAttribute('aria-selected') === 'true') tasks.push(loadAuditoria());
    await Promise.allSettled(tasks);
}

// ---------- Usuarios ----------
function adminUsuariosFilters() {
    const q = sanitizeAdminSearch(document.getElementById('adminFiltroUsuario')?.value);
    const rawRol = document.getElementById('adminFiltroRol')?.value || '';
    const rawEstado = document.getElementById('adminFiltroEstado')?.value || '';
    return {
        q,
        rol: ADMIN_ROLES_VALIDOS.has(rawRol) ? rawRol : '',
        estado: ADMIN_ESTADOS_VALIDOS.has(rawEstado) ? rawEstado : ''
    };
}
function adminUsuariosQueryParameters(filters) {
    const params = new URLSearchParams();
    if (filters.q) params.set('q', filters.q);
    if (filters.rol) params.set('rol', filters.rol);
    if (filters.estado) params.set('estado', filters.estado);
    return params;
}
async function loadAdminUsuarios() {
    clearTimeout(adminUsuariosSearchTimer);
    adminUsuariosSearchTimer = null;
    const version = ++adminUsuariosVersion;
    const loader = document.getElementById('adminUsuariosLoader');
    if (!loader) return;
    const filters = adminUsuariosFilters();
    const filtered = Boolean(filters.q || filters.rol || filters.estado);
    setVisible(document.getElementById('adminUsuariosError'), false);
    if (!adminUsuariosCatalog.length) {
        setVisible(document.getElementById('adminUsuariosEmpty'), false);
        setVisible(document.getElementById('adminUsuariosTableWrap'), false);
        setVisible(document.getElementById('adminUsuariosCards'), false);
        setVisible(loader, true, 'flex');
    }
    document.getElementById('adminPanelUsuarios')?.setAttribute('aria-busy', 'true');
    try {
        const params = adminUsuariosQueryParameters(filters);
        const url = params.toString() ? `api/usuarios.php?${params}` : 'api/usuarios.php';
        const json = await apiRequest(url, {}, 'adminUsuarios');
        if (version !== adminUsuariosVersion) return;
        adminUsuarios = Array.isArray(json.data) ? json.data : Array.isArray(json.usuarios) ? json.usuarios : [];
        adminUsuariosTotalGeneral = Number(json.meta?.total_general ?? (filtered ? Math.max(adminUsuariosCatalog.length, adminUsuarios.length) : adminUsuarios.length));
        if (!filtered) {
            adminUsuariosCatalog = adminUsuarios;
            fillAuditUserFilter();
        }
        renderAdminUsuarios();
    } catch (error) {
        if (error.name === 'AbortError' || version !== adminUsuariosVersion) return;
        if (error.status === 422) {
            showNotification('warning', error.message || 'Revise los filtros de usuarios.');
        } else {
            showBlockError('adminUsuariosError', loader, error.message, loadAdminUsuarios);
        }
    } finally {
        if (version === adminUsuariosVersion) {
            setVisible(loader, false);
            document.getElementById('adminPanelUsuarios')?.setAttribute('aria-busy', 'false');
        }
    }
}
function filteredAdminUsuarios() {
    const { q, rol: role, estado: status } = adminUsuariosFilters();
    const tokens = q ? normalizeSearch(q).split(/\s+/).filter(Boolean).slice(0, 6) : [];
    return adminUsuarios.filter(user => {
        if (tokens.length) {
            const haystack = normalizeSearch(`${user.usuario ?? ''} ${user.nombre_completo ?? ''} ${user.registro_profesional ?? ''}`);
            if (!tokens.every(token => haystack.includes(token))) return false;
        }
        if (role && user.rol !== role) return false;
        if (status === 'activo' && !userIsActive(user)) return false;
        if (status === 'inactivo' && userIsActive(user)) return false;
        if (status === 'bloqueado' && !(userIsActive(user) && userLockedUntil(user))) return false;
        return true;
    });
}
function userActions(user) {
    const actions = document.createElement('div');
    actions.className = 'record-actions';
    const isSelf = Number(user.id) === adminSessionUserId();
    const busy = updatingUsers.has(Number(user.id));
    const edit = textAction('Editar', 'ph-pencil-simple', () => openUsuarioModal(user));
    edit.setAttribute('aria-label', `Editar a ${user.nombre_completo || user.usuario}`);
    const reset = textAction('Contraseña', 'ph-key', () => openUsuarioModal(user, { password: true }), 'btn btn-text');
    reset.setAttribute('aria-label', `Restablecer la contraseña de ${user.nombre_completo || user.usuario}`);
    const active = userIsActive(user);
    const toggle = textAction(active ? 'Desactivar' : 'Activar', active ? 'ph-prohibit' : 'ph-check-circle', () => toggleUsuarioActivo(user), 'btn btn-text');
    toggle.setAttribute('aria-label', `${active ? 'Desactivar' : 'Activar'} la cuenta de ${user.nombre_completo || user.usuario}`);
    toggle.disabled = busy || (isSelf && active);
    if (isSelf && active) toggle.title = 'No puede desactivar su propia cuenta.';
    edit.disabled = busy;
    reset.disabled = busy;
    actions.append(edit, reset, toggle);
    return actions;
}
function renderAdminUsuarios() {
    const body = document.getElementById('adminUsuariosBody');
    const cards = document.getElementById('adminUsuariosCards');
    if (!body) return;
    const users = filteredAdminUsuarios();
    body.replaceChildren();
    cards?.replaceChildren();
    const selfId = adminSessionUserId();
    users.forEach(user => {
        const isSelf = Number(user.id) === selfId;
        const registro = user.registro_profesional ? `Reg. ${user.registro_profesional}` : '';
        const lastAccess = formatServerTimestamp(user.ultimo_acceso);

        const row = document.createElement('tr');
        if (!userIsActive(user)) row.className = 'is-muted';

        const loginCell = document.createElement('td');
        const loginStrong = document.createElement('strong');
        loginStrong.textContent = String(user.usuario || '');
        loginCell.append(loginStrong);
        if (isSelf) {
            const selfBadge = document.createElement('span');
            selfBadge.className = 'status-badge';
            selfBadge.textContent = 'Usted';
            loginCell.append(document.createTextNode(' '), selfBadge);
        }

        const nameCell = document.createElement('td');
        nameCell.append(document.createTextNode(String(user.nombre_completo || '')));
        if (registro) {
            const regSpan = document.createElement('span');
            regSpan.className = 'table-secondary';
            regSpan.textContent = registro;
            nameCell.append(regSpan);
        }

        const roleCell = document.createElement('td');
        roleCell.textContent = roleName(user.rol);

        const statusCell = document.createElement('td');
        statusCell.append(createUserStatusBadge(user));

        const accessCell = document.createElement('td');
        accessCell.className = 'tabular-nums';
        accessCell.textContent = lastAccess;

        const actionsCell = document.createElement('td');
        actionsCell.append(userActions(user));

        row.append(loginCell, nameCell, roleCell, statusCell, accessCell, actionsCell);
        body.append(row);

        if (cards) {
            const card = document.createElement('article');
            card.className = 'record-card';
            const identity = document.createElement('div');
            identity.className = 'record-identity';
            const who = document.createElement('div');
            const heading = document.createElement('h3');
            heading.textContent = String(user.nombre_completo || '');
            const sub = document.createElement('p');
            sub.textContent = `${user.usuario || ''} · ${roleName(user.rol)}${isSelf ? ' · Usted' : ''}`;
            who.append(heading, sub);
            identity.append(who, createUserStatusBadge(user));

            const meta = document.createElement('p');
            meta.className = 'record-meta';
            const accessSpan = document.createElement('span');
            accessSpan.textContent = `Último acceso: ${lastAccess}`;
            meta.append(accessSpan);
            if (registro) {
                const regMeta = document.createElement('span');
                regMeta.textContent = registro;
                meta.append(regMeta);
            }
            card.append(identity, meta, userActions(user));
            cards.append(card);
        }
    });
    const totalBase = Math.max(adminUsuariosTotalGeneral, users.length);
    const filters = adminUsuariosFilters();
    const isFiltered = Boolean(filters.q || filters.rol || filters.estado);
    const count = document.getElementById('adminUsuariosCount');
    if (count) count.textContent = !isFiltered ? `${users.length} ${users.length === 1 ? 'usuario' : 'usuarios'}` : `${users.length} de ${totalBase}`;
    setVisible(document.getElementById('adminUsuariosTableWrap'), users.length > 0);
    setVisible(cards, users.length > 0);
    const empty = document.getElementById('adminUsuariosEmpty');
    setVisible(empty, !users.length, 'flex');
    if (empty && !users.length) {
        empty.querySelector('h3').textContent = totalBase ? 'Sin coincidencias' : 'Aún no hay usuarios';
        empty.querySelector('p').textContent = totalBase ? 'Cambie la búsqueda o los filtros.' : 'Cree la primera cuenta del personal.';
    }
}
function userPayload(user, overrides = {}) {
    const data = new FormData();
    data.append('id', String(user.id));
    data.append('usuario', user.usuario || '');
    data.append('nombre_completo', user.nombre_completo || '');
    data.append('rol', user.rol || '');
    data.append('registro_profesional', user.registro_profesional || '');
    data.append('activo', userIsActive(user) ? '1' : '0');
    for (const [key, value] of Object.entries(overrides)) data.set(key, value);
    return data;
}
async function toggleUsuarioActivo(user) {
    const id = Number(user.id);
    if (updatingUsers.has(id)) return;
    const activate = !userIsActive(user);
    if (!activate && id === adminSessionUserId()) { showNotification('warning', 'No puede desactivar su propia cuenta.'); return; }
    const name = user.nombre_completo || user.usuario;
    const text = activate ? `${name} podrá volver a iniciar sesión.` : `${name} no podrá iniciar sesión. Su historial y sus registros se conservan.`;
    const confirmed = typeof Swal !== 'undefined' ? (await Swal.fire({
        titleText: activate ? 'Activar cuenta' : 'Desactivar cuenta', text, icon: activate ? 'question' : 'warning',
        showCancelButton: true, focusCancel: !activate, confirmButtonColor: '#202a30', cancelButtonColor: '#667078',
        confirmButtonText: activate ? 'Activar' : 'Desactivar', cancelButtonText: 'Volver'
    })).isConfirmed : window.confirm(text);
    if (!confirmed) return;
    updatingUsers.add(id);
    renderAdminUsuarios();
    try {
        const json = await apiRequest('api/save_usuario.php', { method: 'POST', body: userPayload(user, { activo: activate ? '1' : '0' }) });
        showNotification('success', json.message || (activate ? 'La cuenta se activó.' : 'La cuenta se desactivó.'));
    } catch (error) {
        showNotification('error', error.message, 6000);
    } finally {
        updatingUsers.delete(id);
        await loadAdminUsuarios();
    }
}

// ---------- Formulario de usuario ----------
function updateRoleHelp() {
    const role = document.getElementById('usuarioRol')?.value || '';
    const help = document.getElementById('usuarioRolAyuda');
    if (help) help.textContent = ADMIN_ROLES[role]?.ayuda || 'Seleccione el rol para ver sus permisos.';
    const registro = document.getElementById('usuarioRegistroAyuda');
    if (registro) registro.textContent = role === 'medico'
        ? 'Aparece junto a la firma en informes y reposos. Indíquelo para los médicos.'
        : 'Opcional. Aparece junto a la firma en informes y reposos.';
}
function openUsuarioModal(user = null, opciones = {}) {
    const form = document.getElementById('usuarioForm');
    if (!form) return;
    form.reset();
    clearInlineErrors(form.id);
    const editing = Boolean(user);
    const isSelf = editing && Number(user.id) === adminSessionUserId();
    document.getElementById('usuarioId').value = editing ? String(user.id) : '';
    document.getElementById('usuarioUsuario').value = user?.usuario || '';
    document.getElementById('usuarioNombre').value = user?.nombre_completo || '';
    document.getElementById('usuarioRol').value = user?.rol || '';
    document.getElementById('usuarioRegistro').value = user?.registro_profesional || '';
    const active = document.getElementById('usuarioActivo');
    active.checked = editing ? userIsActive(user) : true;
    active.disabled = isSelf;
    const role = document.getElementById('usuarioRol');
    role.disabled = isSelf;
    document.getElementById('usuarioActivoAyuda').textContent = isSelf
        ? 'No puede desactivar su propia cuenta ni cambiar su rol.'
        : 'Una cuenta inactiva no puede iniciar sesión; su historial se conserva.';
    document.getElementById('usuarioModalTitle').textContent = !editing ? 'Nuevo usuario' : opciones.password ? 'Restablecer contraseña' : 'Editar usuario';
    document.getElementById('usuarioModalSubtitle').textContent = editing
        ? `${user.nombre_completo || user.usuario} · ${roleName(user.rol)}`
        : 'Cree la cuenta y asigne el rol del personal.';
    document.getElementById('usuarioPasswordLegend').textContent = editing ? 'Restablecer contraseña' : 'Contraseña';
    document.getElementById('usuarioPasswordLabel').innerHTML = editing ? 'Nueva contraseña' : 'Contraseña <span aria-hidden="true">*</span>';
    document.getElementById('usuarioPasswordAyuda').textContent = editing
        ? 'Déjela en blanco para conservar la actual. Mínimo 10 caracteres; entréguela por un medio seguro.'
        : 'Mínimo 10 caracteres. Entréguela a la persona por un medio seguro.';
    const password = document.getElementById('usuarioPassword');
    password.required = !editing || Boolean(opciones.password);
    document.getElementById('btnGuardarUsuario').lastChild.textContent = editing && opciones.password ? 'Restablecer contraseña' : 'Guardar usuario';
    updateRoleHelp();
    openSurface('usuarioModal', 'usuarioModalContent', 'usuarioModalTitle');
    document.body.style.overflow = 'hidden';
    if (opciones.password) requestAnimationFrame(() => password.focus());
}
function closeUsuarioModal() {
    const form = document.getElementById('usuarioForm');
    if (form?.dataset.saving === 'true') return false;
    // closeSurface recalcula el bloqueo de desplazamiento y devuelve el foco al botón de origen.
    closeSurface('usuarioModal', 'usuarioModalContent');
    return true;
}
function validateUsuarioForm(form) {
    const errors = {};
    const editing = Boolean(form.elements.namedItem('id').value);
    const usuario = form.elements.namedItem('usuario').value.trim().toLowerCase();
    const nombre = form.elements.namedItem('nombre_completo').value.trim();
    const rol = form.elements.namedItem('rol').value;
    const password = form.elements.namedItem('password').value;
    const confirmation = form.elements.namedItem('password_confirmacion').value;
    if (!/^[a-z0-9._-]{3,50}$/.test(usuario)) errors.usuario = 'Use de 3 a 50 caracteres: minúsculas, números, punto, guion o guion bajo.';
    if (nombre.length < 3) errors.nombre_completo = 'Indique el nombre completo.';
    if (!ADMIN_ROLES[rol]) errors.rol = 'Seleccione un rol.';
    const passwordRequired = !editing || form.elements.namedItem('password').required;
    if (passwordRequired && !password) errors.password = editing ? 'Indique la nueva contraseña.' : 'Indique una contraseña para la cuenta.';
    else if (password && password.length < 10) errors.password = 'La contraseña debe tener al menos 10 caracteres.';
    if (password && password !== confirmation) errors.password_confirmacion = 'Las contraseñas no coinciden.';
    return errors;
}
document.getElementById('usuarioRol')?.addEventListener('change', updateRoleHelp);
document.getElementById('usuarioUsuario')?.addEventListener('input', event => {
    const input = event.target;
    const lower = input.value.toLowerCase().replace(/\s/g, '');
    if (lower !== input.value) input.value = lower;
});
document.getElementById('usuarioForm')?.addEventListener('submit', async function (event) {
    event.preventDefault();
    if (this.dataset.saving === 'true') return;
    const errors = validateUsuarioForm(this);
    if (Object.keys(errors).length) { handleInlineErrors(errors, this.id); return; }
    clearInlineErrors(this.id);
    const editing = Boolean(this.elements.namedItem('id').value);
    const original = editing ? adminUsuarios.find(user => String(user.id) === this.elements.namedItem('id').value) : null;
    const data = new FormData();
    if (editing) data.append('id', this.elements.namedItem('id').value);
    data.append('usuario', this.elements.namedItem('usuario').value.trim().toLowerCase());
    data.append('nombre_completo', this.elements.namedItem('nombre_completo').value.trim());
    // Los campos deshabilitados (propia cuenta) no viajan en el formulario: se envían los valores actuales.
    data.append('rol', this.elements.namedItem('rol').disabled ? (original?.rol || '') : this.elements.namedItem('rol').value);
    data.append('registro_profesional', this.elements.namedItem('registro_profesional').value.trim());
    const active = this.elements.namedItem('activo');
    data.append('activo', active.disabled ? (original && !userIsActive(original) ? '0' : '1') : active.checked ? '1' : '0');
    const password = this.elements.namedItem('password').value;
    if (password) data.append('password', password);
    const button = document.getElementById('btnGuardarUsuario');
    const label = button.innerHTML;
    setFormBusy(this, true);
    button.textContent = 'Guardando…';
    try {
        const json = await apiRequest('api/save_usuario.php', { method: 'POST', body: data });
        setFormBusy(this, false);
        closeUsuarioModal();
        showNotification('success', json.message || (editing ? 'El usuario se actualizó.' : 'El usuario se creó.'));
        await loadAdminUsuarios();
    } catch (error) {
        if (error.errors?.password_confirmacion === undefined && error.errors) handleInlineErrors(error.errors, this.id);
        showNotification('error', error.message, 6000);
    } finally {
        setFormBusy(this, false);
        button.innerHTML = label;
        ['password', 'password_confirmacion'].forEach(name => { const field = this.elements.namedItem(name); if (field && this.dataset.saving !== 'true' && document.getElementById('usuarioModal')?.classList.contains('hidden')) field.value = ''; });
    }
});
// El modal de usuario no está en la lista de app.js: gestiona aquí Escape y el foco.
document.addEventListener('keydown', event => {
    const modal = document.getElementById('usuarioModal');
    if (!modal || modal.classList.contains('hidden')) return;
    if (document.querySelector('.swal2-container .swal2-popup:not(.swal2-toast)')) return;
    if (event.key === 'Escape') { event.preventDefault(); event.stopImmediatePropagation(); closeUsuarioModal(); return; }
    if (event.key === 'Tab') {
        const elements = [...modal.querySelectorAll('button:not(:disabled), input:not([type="hidden"]):not(:disabled), select:not(:disabled), textarea:not(:disabled)')].filter(element => element.getClientRects().length);
        const first = elements[0], last = elements[elements.length - 1];
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
    }
}, true);
// UX-8: «Atrás» cierra el modal de usuario mediante el manejador común de app.js (SURFACE_IDS).

// ---------- Registro de actividad ----------
function fillAuditUserFilter() {
    const select = document.getElementById('auditUsuario');
    if (!select) return;
    const source = adminUsuariosCatalog.length ? adminUsuariosCatalog : adminUsuarios;
    const value = select.value;
    select.replaceChildren(new Option('Todos los usuarios', ''));
    [...source].sort((a, b) => String(a.usuario).localeCompare(String(b.usuario), 'es'))
        .forEach(user => select.add(new Option(`${user.usuario} · ${user.nombre_completo}`, String(user.id))));
    select.value = value;
}
function fillAuditActionFilter() {
    const select = document.getElementById('auditAccion');
    if (!select || select.options.length > 1) return;
    const seen = new Set();
    Object.entries(ADMIN_ACCIONES).sort((a, b) => a[1].localeCompare(b[1], 'es')).forEach(([value, label]) => {
        if (seen.has(label)) return;
        seen.add(label);
        select.add(new Option(label, value));
    });
}
function auditDetailText(detail) {
    let value = detail;
    if (typeof value === 'string') {
        try { value = JSON.parse(value); } catch { return value; }
    }
    if (!value || typeof value !== 'object') return value ? String(value) : '';
    const parts = Object.entries(value).filter(([, item]) => item !== null && item !== '' && !(Array.isArray(item) && !item.length)).map(([key, item]) => {
        const text = Array.isArray(item) ? item.join(', ') : typeof item === 'object' ? JSON.stringify(item) : String(item);
        return `${key.replace(/_/g, ' ')}: ${text}`;
    });
    const text = parts.join(' · ');
    return text.length > 180 ? `${text.slice(0, 179)}…` : text;
}
function auditEntityText(row) {
    if (!row.entidad) return '—';
    return `${ADMIN_ENTIDADES[row.entidad] || row.entidad}${row.entidad_id ? ` #${row.entidad_id}` : ''}`;
}
function auditUserText(row) {
    const name = row.nombre_completo || row.usuario_nombre || '';
    const login = row.usuario || '';
    if (name && login) return { main: name, secondary: login };
    return { main: name || login || 'Sin usuario', secondary: '' };
}
function auditParameters() {
    const form = document.getElementById('adminAuditoriaForm');
    const parameters = new URLSearchParams({ pagina: String(adminAuditoriaFiltro.pagina), por_pagina: String(adminAuditoriaFiltro.por_pagina) });
    const usuarioId = form?.elements.namedItem('usuario_id')?.value?.trim() || '';
    if (/^[1-9]\d{0,9}$/.test(usuarioId)) parameters.set('usuario_id', usuarioId);
    const accion = form?.elements.namedItem('accion')?.value?.trim() || '';
    if (accion && Object.hasOwn(ADMIN_ACCIONES, accion)) parameters.set('accion', accion);
    const entidad = form?.elements.namedItem('entidad')?.value?.trim() || '';
    if (entidad && Object.hasOwn(ADMIN_ENTIDADES, entidad)) parameters.set('entidad', entidad);
    const desde = form?.elements.namedItem('desde')?.value?.trim() || '';
    if (/^\d{4}-\d{2}-\d{2}$/.test(desde)) parameters.set('desde', desde);
    const hasta = form?.elements.namedItem('hasta')?.value?.trim() || '';
    if (/^\d{4}-\d{2}-\d{2}$/.test(hasta)) parameters.set('hasta', hasta);
    const q = sanitizeAdminSearch(form?.elements.namedItem('q')?.value || document.getElementById('auditBuscar')?.value);
    if (q) parameters.set('q', q);
    return parameters;
}
async function loadAuditoria() {
    if (!vistaPuede('auditoria.ver')) return;
    clearTimeout(adminAuditoriaSearchTimer);
    adminAuditoriaSearchTimer = null;
    fillAuditActionFilter();
    const form = document.getElementById('adminAuditoriaForm');
    const desde = form?.elements.namedItem('desde')?.value || '';
    const hasta = form?.elements.namedItem('hasta')?.value || '';
    if (desde && !/^\d{4}-\d{2}-\d{2}$/.test(desde)) { handleInlineErrors({ desde: 'Indique una fecha válida (AAAA-MM-DD).' }, 'adminAuditoriaForm'); return; }
    if (hasta && !/^\d{4}-\d{2}-\d{2}$/.test(hasta)) { handleInlineErrors({ hasta: 'Indique una fecha válida (AAAA-MM-DD).' }, 'adminAuditoriaForm'); return; }
    if (desde && hasta && hasta < desde) { handleInlineErrors({ hasta: 'La fecha final debe ser igual o posterior a la inicial.' }, 'adminAuditoriaForm'); return; }
    clearInlineErrors('adminAuditoriaForm');
    const version = ++adminAuditoriaVersion;
    const loader = document.getElementById('adminAuditoriaLoader');
    const table = document.getElementById('adminAuditoriaTableWrap');
    const cards = document.getElementById('adminAuditoriaCards');
    const pager = document.getElementById('adminAuditoriaPager');
    setVisible(document.getElementById('adminAuditoriaError'), false);
    setVisible(document.getElementById('adminAuditoriaEmpty'), false);
    setVisible(table, false);
    setVisible(cards, false);
    setVisible(pager, false);
    setVisible(loader, true, 'flex');
    document.getElementById('adminPanelAuditoria')?.setAttribute('aria-busy', 'true');
    try {
        const json = await apiRequest(`api/get_auditoria.php?${auditParameters()}`, {}, 'adminAuditoria');
        if (version !== adminAuditoriaVersion) return;
        adminAuditoriaCargada = true;
        const rows = Array.isArray(json.data) ? json.data : [];
        const meta = json.meta || { total: rows.length, pagina: 1, por_pagina: Math.max(rows.length, 1) };
        renderAuditoria(rows, meta);
        vistasRenderPager(pager, meta, pagina => { adminAuditoriaFiltro.pagina = pagina; loadAuditoria(); document.getElementById('adminAuditoriaTitle')?.scrollIntoView({ block: 'start' }); }, ['evento', 'eventos']);
    } catch (error) {
        if (error.name !== 'AbortError' && version === adminAuditoriaVersion) {
            if (error.status === 422 && error.errors) handleInlineErrors(error.errors, 'adminAuditoriaForm');
            showBlockError('adminAuditoriaError', loader, error.message, loadAuditoria);
        }
    } finally {
        if (version === adminAuditoriaVersion) {
            setVisible(loader, false);
            document.getElementById('adminPanelAuditoria')?.setAttribute('aria-busy', 'false');
        }
    }
}
function renderAuditoria(rows, meta) {
    const body = document.getElementById('adminAuditoriaBody');
    const cards = document.getElementById('adminAuditoriaCards');
    if (!body) return;
    body.replaceChildren();
    cards?.replaceChildren();
    rows.forEach(row => {
        const user = auditUserText(row);
        const action = ADMIN_ACCIONES[row.accion] || row.accion || '—';
        const detail = auditDetailText(row.detalle);
        const when = formatServerTimestamp(row.creado_en);
        const entity = auditEntityText(row);
        const failed = row.accion === 'login_fallido' || row.accion === 'cambiar_password_fallido';

        const tr = document.createElement('tr');
        const whenCell = document.createElement('td');
        whenCell.className = 'tabular-nums';
        whenCell.textContent = when;

        const userCell = document.createElement('td');
        userCell.append(document.createTextNode(user.main));
        if (user.secondary) {
            const loginSpan = document.createElement('span');
            loginSpan.className = 'table-secondary';
            loginSpan.textContent = user.secondary;
            userCell.append(loginSpan);
        }

        const actionCell = document.createElement('td');
        if (failed) {
            const badge = document.createElement('span');
            badge.className = 'status-badge status-pending';
            badge.textContent = action;
            actionCell.append(badge);
        } else {
            actionCell.textContent = action;
        }

        const entityCell = document.createElement('td');
        entityCell.textContent = entity;

        const detailCell = document.createElement('td');
        detailCell.className = 'audit-detail';
        detailCell.textContent = detail || '—';

        const ipCell = document.createElement('td');
        ipCell.className = 'tabular-nums';
        ipCell.textContent = String(row.ip || '—');

        tr.append(whenCell, userCell, actionCell, entityCell, detailCell, ipCell);
        body.append(tr);

        if (cards) {
            const card = document.createElement('article');
            card.className = 'record-card';
            const identity = document.createElement('div');
            identity.className = 'record-identity';
            const who = document.createElement('div');
            const heading = document.createElement('h3');
            heading.textContent = action;
            const sub = document.createElement('p');
            sub.textContent = `${user.main}${user.secondary ? ` · ${user.secondary}` : ''}`;
            who.append(heading, sub);
            identity.append(who);

            const metaP = document.createElement('p');
            metaP.className = 'record-meta';
            const whenSpan = document.createElement('span');
            whenSpan.textContent = when;
            const entSpan = document.createElement('span');
            entSpan.textContent = entity;
            metaP.append(whenSpan, entSpan);
            if (row.ip) {
                const ipSpan = document.createElement('span');
                ipSpan.textContent = `IP ${row.ip}`;
                metaP.append(ipSpan);
            }
            card.append(identity, metaP);
            if (detail) {
                const detailP = document.createElement('p');
                detailP.className = 'record-meta audit-detail';
                detailP.textContent = detail;
                card.append(detailP);
            }
            cards.append(card);
        }
    });
    const total = Number(meta?.total ?? rows.length);
    const count = document.getElementById('adminAuditoriaCount');
    if (count) count.textContent = `${total.toLocaleString('es-VE')} ${total === 1 ? 'evento' : 'eventos'}`;
    setVisible(document.getElementById('adminAuditoriaTableWrap'), rows.length > 0);
    setVisible(cards, rows.length > 0);
    setVisible(document.getElementById('adminAuditoriaEmpty'), !rows.length, 'flex');
}
document.getElementById('adminAuditoriaForm')?.addEventListener('submit', event => {
    event.preventDefault();
    adminAuditoriaFiltro.pagina = 1;
    loadAuditoria();
});
document.getElementById('auditLimpiar')?.addEventListener('click', () => {
    document.getElementById('adminAuditoriaForm')?.reset();
    clearInlineErrors('adminAuditoriaForm');
    adminAuditoriaFiltro.pagina = 1;
    loadAuditoria();
});
const auditBuscarInput = document.getElementById('auditBuscar');
auditBuscarInput?.addEventListener('input', () => {
    clearTimeout(adminAuditoriaSearchTimer);
    adminAuditoriaSearchTimer = setTimeout(() => {
        adminAuditoriaFiltro.pagina = 1;
        loadAuditoria();
    }, 300);
});
auditBuscarInput?.addEventListener('keydown', event => {
    if (event.key === 'Escape' && auditBuscarInput.value) {
        event.preventDefault();
        auditBuscarInput.value = '';
        adminAuditoriaFiltro.pagina = 1;
        loadAuditoria();
    }
});

const adminFiltroUsuarioInput = document.getElementById('adminFiltroUsuario');
adminFiltroUsuarioInput?.addEventListener('input', () => {
    clearTimeout(adminUsuariosSearchTimer);
    adminUsuariosSearchTimer = setTimeout(() => { loadAdminUsuarios(); }, 280);
});
adminFiltroUsuarioInput?.addEventListener('keydown', event => {
    if (event.key === 'Enter') {
        event.preventDefault();
        loadAdminUsuarios();
    } else if (event.key === 'Escape' && adminFiltroUsuarioInput.value) {
        event.preventDefault();
        adminFiltroUsuarioInput.value = '';
        loadAdminUsuarios();
    }
});
for (const id of ['adminFiltroRol', 'adminFiltroEstado']) {
    document.getElementById(id)?.addEventListener('change', () => loadAdminUsuarios());
}
initAdminTabs();
