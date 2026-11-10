import assert from 'node:assert/strict';
import { randomUUID } from 'node:crypto';

// This suite creates clinical fixtures and user accounts. Run it only with docker-compose.test.yml.
const base = new URL(process.argv[2] || 'http://127.0.0.1:18080');
if (!['localhost', '127.0.0.1'].includes(base.hostname) || base.port !== '18080') {
  throw new Error('Use the isolated test environment at http://127.0.0.1:18080.');
}
const ADMIN_USER = process.env.ADMIN_USER || 'admin';
const ADMIN_PASSWORD = process.env.ADMIN_PASSWORD || 'integration_admin_only';
let checks = 0;
function check(name, assertion) {
  assertion();
  checks += 1;
  console.log(`PASS ${name}`);
}

/** HTTP client with its own session cookie and CSRF token. */
class Client {
  constructor(name) { this.name = name; this.cookie = ''; this.csrf = ''; }
  storeCookies(response) {
    for (const header of response.headers.getSetCookie?.() || []) {
      const [pair, ...attributes] = header.split(';');
      const [key, value] = pair.split('=');
      if (key.trim() !== 'SALUDSESSID') continue;
      const expired = attributes.some(item => /expires=/i.test(item) && new Date(item.split('=')[1]) < new Date());
      this.cookie = expired || value === 'deleted' || value === '' ? '' : `SALUDSESSID=${value}`;
    }
  }
  async raw(path, options = {}) {
    const headers = new Headers(options.headers || {});
    if (this.cookie) headers.set('Cookie', this.cookie);
    const method = (options.method || 'GET').toUpperCase();
    if (method !== 'GET' && this.csrf && options.csrf !== false && !headers.has('X-CSRF-Token')) headers.set('X-CSRF-Token', this.csrf);
    const response = await fetch(new URL(path, base), { ...options, headers, redirect: 'manual' });
    this.storeCookies(response);
    return response;
  }
  async api(path, { fields, file, ...options } = {}) {
    if (fields) {
      const body = new FormData();
      for (const [key, value] of Object.entries(fields)) body.set(key, String(value));
      if (file) body.set('archivo', new Blob([file.bytes], { type: file.type }), file.name);
      options = { method: 'POST', ...options, body };
    }
    const response = await this.raw(`/api/${path}`, options);
    const text = await response.text();
    assert.match(response.headers.get('content-type') || '', /application\/json/, `${path}: JSON response required (${response.status})`);
    let json;
    try { json = JSON.parse(text); } catch { throw new Error(`${path}: invalid JSON: ${text.slice(0, 160)}`); }
    return { response, json };
  }
  async login(usuario, password) {
    const result = await this.api('login.php', { fields: { usuario, password } });
    if (result.json.success) this.csrf = result.json.csrf_token;
    return result;
  }
}

const suffix = Date.now().toString(36);
const today = new Intl.DateTimeFormat('en-CA', { timeZone: 'America/Caracas', year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date());
const tomorrow = new Intl.DateTimeFormat('en-CA', { timeZone: 'America/Caracas', year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date(Date.now() + 86_400_000));
const dayAfter = new Intl.DateTimeFormat('en-CA', { timeZone: 'America/Caracas', year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date(Date.now() + 2 * 86_400_000));
const patient = {
  cedula: `V-${Date.now()}`, nombres: 'Prueba <img src=x onerror=alert(1)>',
  apellidos: "O'Connor", categoria: '1', sexo: 'Femenino',
  fecha_nacimiento: '2000-02-29', telefono: '0412-0000000',
  antecedente_quirurgico: 'Antecedente que debe conservarse',
};
const consultation = {
  ...patient, solo_paciente: '0', fecha_circunstancia: today,
  motivo_consulta: 'Consulta de integración', enfermedad_actual: 'Síntomas de prueba',
  diagnostico: 'Registro de verificación', plan_tratamiento: 'Seguimiento de prueba',
  inicio_reposo: today, fin_reposo: tomorrow, vital_ta: '120/80', vital_fc: '70',
};

// ---------------------------------------------------------------------------
// Sesión y autenticación
// ---------------------------------------------------------------------------
const anonymous = new Client('anónimo');
let result = await anonymous.api('health.php');
check('Database health is public', () => assert.equal(result.response.status, 200));
result = await anonymous.api('get_categorias.php');
check('API requires a session (401)', () => {
  assert.equal(result.response.status, 401);
  assert.equal(result.json.code, 'auth_required');
});
result = await anonymous.api('session.php');
check('Session endpoint without cookie is 401', () => assert.equal(result.response.status, 401));
let page = await anonymous.raw('/');
check('App page redirects to login without a session', () => {
  assert.equal(page.status, 303);
  assert.match(page.headers.get('location') || '', /login\.php/);
});
page = await anonymous.raw('/api/print_historia.php?paciente_id=1');
check('Printable documents require a session', () => assert.equal(page.status, 401));

const admin = new Client('admin');
result = await admin.login(ADMIN_USER, 'contraseña-incorrecta');
check('Wrong password gives a generic message', () => {
  assert.equal(result.response.status, 401);
  assert.equal(result.json.message, 'Usuario o contraseña incorrectos.');
});
result = await admin.login('usuario-que-no-existe', 'contraseña-incorrecta');
check('Unknown user gets the same generic message', () => assert.equal(result.json.message, 'Usuario o contraseña incorrectos.'));
result = await admin.login(ADMIN_USER, ADMIN_PASSWORD);
check('Administrator logs in', () => {
  assert.equal(result.response.status, 200, result.json.message);
  assert.equal(result.json.usuario.rol, 'administrador');
  assert.ok(result.json.permisos.includes('usuarios.gestionar'));
  assert.match(result.json.csrf_token, /^[0-9a-f]{64}$/);
  assert.ok(admin.cookie.startsWith('SALUDSESSID='));
});
result = await admin.api('session.php');
check('Session endpoint returns user, permissions and token', () => {
  assert.equal(result.json.usuario.usuario, ADMIN_USER);
  assert.equal(result.json.csrf_token, admin.csrf);
  assert.ok(!('password_hash' in result.json.usuario));
});
result = await admin.api('save_usuario.php', { fields: { usuario: `x${suffix}`, nombre_completo: 'Sin token', rol: 'medico', password: 'contraseña-larga-1' }, csrf: false });
check('Mutation without CSRF token is rejected (403)', () => {
  assert.equal(result.response.status, 403);
  assert.equal(result.json.code, 'csrf_invalid');
});
result = await admin.api('save_usuario.php', { fields: { usuario: `x${suffix}`, nombre_completo: 'Token falso', rol: 'medico', password: 'contraseña-larga-1' }, headers: { 'X-CSRF-Token': 'a'.repeat(64) } });
check('Mutation with a forged CSRF token is rejected', () => assert.equal(result.response.status, 403));

// ---------------------------------------------------------------------------
// Gestión de usuarios
// ---------------------------------------------------------------------------
const passwords = { medico: `Medico-${suffix}-clave`, asistente: `Asistente-${suffix}-clave`, bloqueo: `Bloqueo-${suffix}-clave` };
const accounts = {};
for (const [role, usuario, extra] of [['medico', `med.${suffix}`, { registro_profesional: 'MPPS 12345' }], ['asistente', `asis.${suffix}`, {}], ['medico', `bloq.${suffix}`, {}]]) {
  const key = usuario.startsWith('bloq') ? 'bloqueo' : role;
  result = await admin.api('save_usuario.php', { fields: { usuario, nombre_completo: `Usuario ${key} ${suffix}`, rol: role, activo: '1', password: passwords[key], ...extra } });
  check(`User ${key} created`, () => assert.equal(result.response.status, 201, result.json.message));
  accounts[key] = { id: result.json.id, usuario };
}
result = await admin.api('save_usuario.php', { fields: { usuario: accounts.medico.usuario, nombre_completo: 'Duplicado', rol: 'medico', password: 'otra-clave-larga' } });
check('Duplicate username rejected', () => assert.equal(result.response.status, 409));
result = await admin.api('save_usuario.php', { fields: { usuario: `corta.${suffix}`, nombre_completo: 'Clave corta', rol: 'medico', password: 'corta' } });
check('Short password rejected', () => assert.ok(result.response.status === 422 && result.json.errors.password));
result = await admin.api('save_usuario.php', { fields: { usuario: `Mayus ${suffix}`, nombre_completo: 'Usuario inválido', rol: 'medico', password: 'contraseña-larga-1' } });
check('Invalid username rejected', () => assert.ok(result.response.status === 422 && result.json.errors.usuario));
result = await admin.api('usuarios.php');
check('User list omits password hashes', () => {
  assert.ok(result.json.data.some(row => row.usuario === accounts.medico.usuario));
  assert.ok(result.json.data.every(row => !('password_hash' in row)));
});
const adminId = (await admin.api('session.php')).json.usuario.id;
result = await admin.api('save_usuario.php', { fields: { id: adminId, usuario: ADMIN_USER, nombre_completo: 'Administrador', rol: 'administrador', activo: '0' } });
check('Administrator cannot deactivate their own account', () => assert.equal(result.response.status, 409));
result = await admin.api('save_usuario.php', { fields: { id: adminId, usuario: ADMIN_USER, nombre_completo: 'Administrador', rol: 'medico', activo: '1' } });
check('Administrator cannot drop their own admin role', () => assert.equal(result.response.status, 409));

const doctor = new Client('médico');
result = await doctor.login(accounts.medico.usuario, passwords.medico);
check('Doctor logs in', () => assert.ok(result.json.success && result.json.permisos.includes('clinica.editar') && !result.json.permisos.includes('usuarios.gestionar')));
const assistant = new Client('asistente');
result = await assistant.login(accounts.asistente.usuario, passwords.asistente);
check('Assistant logs in without clinical permissions', () => assert.ok(result.json.success && !result.json.permisos.includes('clinica.ver')));

// Bloqueo tras 5 intentos fallidos.
const locked = new Client('bloqueo');
for (let attempt = 1; attempt <= 5; attempt += 1) {
  result = await locked.login(accounts.bloqueo.usuario, `incorrecta-${attempt}`);
  assert.equal(result.response.status, 401);
}
result = await locked.login(accounts.bloqueo.usuario, passwords.bloqueo);
check('Account locked after five failures even with the right password', () => {
  assert.equal(result.response.status, 401);
  assert.equal(result.json.code, 'invalid_credentials');
});
// Un usuario inexistente recibe exactamente la misma respuesta que una cuenta bloqueada.
const ghost = new Client('inexistente');
for (let attempt = 1; attempt <= 6; attempt += 1) result = await ghost.login(`noexiste-${suffix}`, `incorrecta-${attempt}`);
check('Lockout does not reveal whether a username exists', () => {
  assert.equal(result.response.status, 401);
  assert.equal(result.json.code, 'invalid_credentials');
});
result = await admin.api('usuarios.php');
check('Locked account shows its lock time', () => assert.ok(result.json.data.find(row => row.id === accounts.bloqueo.id)?.bloqueado_hasta));
passwords.bloqueo = `Nueva-${suffix}-clave`;
result = await admin.api('save_usuario.php', { fields: { id: accounts.bloqueo.id, usuario: accounts.bloqueo.usuario, nombre_completo: 'Cuenta desbloqueada', rol: 'medico', activo: '1', password: passwords.bloqueo } });
check('Password reset by an administrator', () => assert.equal(result.response.status, 200, result.json.message));
result = await locked.login(accounts.bloqueo.usuario, passwords.bloqueo);
check('Reset password lifts the lock', () => assert.equal(result.response.status, 200, result.json.message));
result = await admin.api('save_usuario.php', { fields: { id: accounts.bloqueo.id, usuario: accounts.bloqueo.usuario, nombre_completo: 'Cuenta desactivada', rol: 'medico', activo: '0' } });
result = await locked.api('session.php');
check('Deactivating an account ends its session immediately', () => assert.equal(result.response.status, 401));

// ---------------------------------------------------------------------------
// Flujo clínico (como médico)
// ---------------------------------------------------------------------------
result = await doctor.api('get_categorias.php');
check('Institutional categories', () => assert.ok(result.json.success && result.json.data.length >= 5));
patient.categoria = consultation.categoria = String(result.json.data[0].id);

for (const endpoint of ['save_registro.php', 'save_cita.php', 'update_cita_estado.php', 'update_consulta.php', 'reprogramar_cita.php', 'save_usuario.php']) {
  result = await admin.api(endpoint);
  check(`GET cannot mutate ${endpoint}`, () => assert.equal(result.response.status, 405));
}
result = await doctor.api('save_cita.php', { fields: { fecha_cita: `${tomorrow}T12:00`, cedula: patient.cedula }, headers: { Origin: 'https://untrusted.invalid' } });
check('Cross-origin mutation blocked', () => assert.equal(result.response.status, 403));
result = await doctor.api('save_registro.php', { fields: { ...patient, solo_paciente: '1', fecha_nacimiento: '2025-02-30' } });
check('Impossible birth date rejected', () => assert.equal(result.response.status, 422));
result = await doctor.api('get_morbilidad.php?fecha_inicio=2026-12-01&fecha_fin=2026-01-01');
check('Reversed report range rejected', () => assert.equal(result.response.status, 422));
result = await doctor.api('get_morbilidad.php?fecha_inicio=2015-01-01&fecha_fin=2026-01-01');
check('Report range longer than five years rejected', () => assert.equal(result.response.status, 422));
result = await doctor.api('get_paciente_detalle.php?id=1oops');
check('Malformed identifier rejected', () => assert.equal(result.response.status, 422));

result = await doctor.api('save_registro.php', { fields: { ...patient, solo_paciente: '1' } });
check('Patient created', () => assert.ok(result.json.success, result.json.message));
result = await doctor.api(`get_pacientes.php?q=${encodeURIComponent(patient.cedula)}`);
const savedPatient = result.json.data.find(row => row.cedula === patient.cedula);
check('Patient identity round-trips without interpreting markup', () => assert.equal(savedPatient?.nombres, patient.nombres));
check('Patient search by ID number returns exactly that patient', () => {
  assert.equal(result.json.data.length, 1);
  assert.deepEqual(result.json.meta, { total: 1, pagina: 1, por_pagina: 25 });
});
const patientId = savedPatient.id;
// La cédula se encuentra también sin guion o con puntos de miles.
const cedulaDigits = patient.cedula.replace(/\D/g, '');
for (const q of [patient.cedula.replace('-', ''), patient.cedula.toLowerCase(), cedulaDigits.replace(/^(\d{3})(\d{3})/, '$1.$2.')]) {
  result = await doctor.api(`get_pacientes.php?q=${encodeURIComponent(q)}`);
  check(`Patient search by ID number tolerates formatting: ${q}`, () => assert.ok(result.json.data.some(row => row.id === patientId)));
}
result = await doctor.api('get_pacientes.php?q=connor%20prueba');
check('Patient search by name ignores case and word order', () => assert.ok(result.json.data.some(row => row.id === patientId)));
result = await doctor.api('get_pacientes.php?por_pagina=1&pagina=1');
check('Patient list is paginated', () => assert.ok(result.json.data.length === 1 && result.json.meta.total >= 1 && result.json.meta.por_pagina === 1));
result = await doctor.api('get_pacientes.php?por_pagina=101');
check('Page size above 100 rejected', () => assert.equal(result.response.status, 422));

const appointment = { paciente_id: patientId, fecha_cita: `${tomorrow}T12:00`, motivo: 'Control anual' };
result = await doctor.api('save_cita.php', { fields: appointment });
check('Future appointment created', () => assert.ok(result.json.success, result.json.message));
result = await doctor.api('save_cita.php', { fields: appointment });
check('Duplicate appointment blocked', () => assert.equal(result.response.status, 409));
result = await doctor.api('save_cita.php', { fields: { ...appointment, fecha_cita: '9999-12-31T10:00' } });
check('Appointment more than two years ahead rejected', () => assert.equal(result.response.status, 422));
const concurrent = await Promise.all([
  doctor.api('save_cita.php', { fields: { ...appointment, motivo: 'Concurrente', fecha_cita: `${tomorrow}T14:00` } }),
  doctor.api('save_cita.php', { fields: { ...appointment, motivo: 'Concurrente', fecha_cita: `${tomorrow}T14:00` } }),
]);
check('Concurrent requests cannot double-book a patient', () => assert.deepEqual(concurrent.map(item => item.response.status).sort(), [201, 409]));

const png = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=', 'base64');
const clinicalFields = { ...consultation };
delete clinicalFields.telefono;
delete clinicalFields.antecedente_quirurgico;
result = await doctor.api('save_registro.php', { fields: clinicalFields, file: { bytes: png, type: 'image/png', name: 'evidencia.php' } });
check('Consultation and safe MIME-based attachment saved atomically', () => assert.ok(result.json.success, result.json.message));
result = await doctor.api(`get_paciente_detalle.php?id=${patientId}`);
const details = result.json.data;
check('Omitted history preserved', () => assert.equal(details.paciente.antecedente_quirurgico, patient.antecedente_quirurgico));
check('One clinical consultation persisted', () => assert.equal(details.consultas.length, 1));
check('Consultation records the treating doctor', () => assert.equal(details.consultas[0].medico_registro, 'MPPS 12345'));
check('Saving a consultation preserves future appointments', () => assert.ok(details.citas.every(row => row.estado === 'Pendiente')));
const consultId = details.consultas[0].id;
const appointmentId = details.citas.find(row => row.motivo === 'Control anual')?.id ?? details.citas[0].id;
const attachment = details.consultas[0].archivos[0];
check('Attachment has a controlled download endpoint', () => assert.match(attachment.ruta_archivo, /(?:download|archivo).*\.php\?id=/));
const download = await doctor.raw(`/${attachment.ruta_archivo.replace(/^(\.\.\/|\/)/, '')}`);
check('Download MIME and bytes are preserved', () => {
  assert.equal(download.status, 200);
  assert.match(download.headers.get('content-type'), /image\/png/);
});
const downloadedBytes = Buffer.from(await download.arrayBuffer());
check('Downloaded bytes match uploaded bytes', () => assert.deepEqual(downloadedBytes, png));
result = await doctor.api('download_archivo.php?id=999999');
check('Unknown attachment is 404', () => assert.equal(result.response.status, 404));
result = await doctor.api('download_archivo.php?id=../x');
check('Path-like attachment identifier rejected', () => assert.equal(result.response.status, 422));

result = await doctor.api('save_registro.php', { fields: clinicalFields, file: { bytes: '<?php echo "unsafe";', type: 'image/png', name: 'fake.png' } });
check('Forged attachment MIME rejected', () => assert.equal(result.response.status, 422));
result = await doctor.api(`get_paciente_detalle.php?id=${patientId}`);
check('Rejected upload leaves no partial consultation', () => assert.equal(result.json.data.consultas.length, 1));

// Reposo e identidad.
result = await doctor.api('save_registro.php', { fields: { ...clinicalFields, inicio_reposo: '1900-01-01', fin_reposo: '9999-12-31' } });
check('Absurd rest dates rejected', () => assert.equal(result.response.status, 422));
const twin = { cedula: `V-${Date.now() + 7}`, nombres: 'Ana María', apellidos: 'Pérez', categoria: patient.categoria, sexo: 'Femenino', solo_paciente: '1' };
result = await doctor.api('save_registro.php', { fields: twin });
check('Second patient created', () => assert.ok(result.json.success, result.json.message));
result = await doctor.api('save_registro.php', { fields: { ...twin, nombres: '  ana   maría ', apellidos: 'PÉREZ' } });
check('Cosmetic identity changes do not need confirmation', () => assert.equal(result.response.status, 200, result.json.message));
result = await doctor.api('save_registro.php', { fields: { ...twin, nombres: 'Otra Persona' } });
check('Different identity on an existing ID number needs confirmation (409)', () => {
  assert.equal(result.response.status, 409);
  assert.equal(result.json.code, 'identity_mismatch');
  assert.equal(result.json.actual.nombres, '  ana   maría '.trim());
  assert.equal(result.json.actual.sexo, 'Femenino');
});
result = await doctor.api('save_registro.php', { fields: { ...twin, nombres: 'Otra Persona', confirmar_identidad: '1' } });
check('Confirmed identity change is saved', () => assert.equal(result.response.status, 200, result.json.message));

// Idempotencia (solicitud_id).
const requestId = randomUUID();
const idempotent = { ...clinicalFields, solicitud_id: requestId, inicio_reposo: '', fin_reposo: '', motivo_consulta: '=HYPERLINK("http://x")' };
const firstSave = await doctor.api('save_registro.php', { fields: idempotent });
const retry = await doctor.api('save_registro.php', { fields: idempotent });
check('Retried submission with the same request id does not duplicate', () => {
  assert.equal(firstSave.response.status, 200, firstSave.json.message);
  assert.equal(retry.response.status, 200, retry.json.message);
  assert.equal(retry.json.duplicado, true);
  assert.equal(retry.json.consulta_id, firstSave.json.consulta_id);
});
result = await doctor.api(`get_paciente_detalle.php?id=${patientId}`);
check('Only one consultation added by the retried request', () => assert.equal(result.json.data.consultas.length, 2));

// Edición de consulta con versiones.
result = await doctor.api(`get_consulta.php?id=${consultId}`);
check('Consultation detail includes doctor, attachments and patient', () => {
  assert.equal(result.response.status, 200, result.json.message);
  assert.equal(result.json.consulta.id, consultId);
  assert.equal(result.json.consulta.archivos.length, 1);
  assert.equal(result.json.paciente.id, patientId);
  assert.deepEqual(result.json.versiones, []);
});
const editFields = { ...clinicalFields, consulta_id: consultId, diagnostico: 'Diagnóstico corregido', version_esperada: '0' };
result = await doctor.api('update_consulta.php', { fields: { ...editFields, motivo_edicion: '' } });
check('Correction requires a reason', () => assert.ok(result.response.status === 422 && result.json.errors.motivo_edicion));
result = await doctor.api('update_consulta.php', { fields: { ...editFields, motivo_edicion: 'Error de transcripción' } });
check('Consultation corrected', () => {
  assert.equal(result.response.status, 200, result.json.message);
  assert.equal(result.json.version, 1);
  assert.deepEqual(result.json.campos, ['diagnostico']);
});
result = await doctor.api('update_consulta.php', { fields: { ...editFields, diagnostico: 'Otra corrección', motivo_edicion: 'Edición concurrente' } });
check('Stale version is rejected (409)', () => {
  assert.equal(result.response.status, 409);
  assert.equal(result.json.code, 'version_conflict');
});
result = await doctor.api(`get_consulta.php?id=${consultId}`);
check('Previous version kept with its reason', () => {
  assert.equal(result.json.consulta.diagnostico, 'Diagnóstico corregido');
  assert.equal(result.json.versiones.length, 1);
  assert.equal(result.json.versiones[0].motivo, 'Error de transcripción');
  assert.ok(result.json.consulta.actualizado_en);
});

// Citas: reprogramación, filtros y estados.
result = await doctor.api('reprogramar_cita.php', { fields: { cita_id: appointmentId, fecha_cita: `${dayAfter}T09:00` } });
check('Pending appointment rescheduled', () => assert.equal(result.response.status, 200, result.json.message));
result = await doctor.api('reprogramar_cita.php', { fields: { cita_id: appointmentId, fecha_cita: `${tomorrow}T14:10` } });
check('Rescheduling respects the 30-minute rule', () => assert.equal(result.response.status, 409));
result = await doctor.api('reprogramar_cita.php', { fields: { cita_id: appointmentId, fecha_cita: '2020-01-01T09:00' } });
check('Rescheduling to the past rejected', () => assert.equal(result.response.status, 422));
result = await doctor.api(`get_citas.php?fecha=${dayAfter}&q=${encodeURIComponent(patient.cedula)}`);
check('Appointments filtered by day and search', () => {
  assert.equal(result.json.meta.total, 1);
  assert.equal(result.json.data[0].motivo, 'Control anual');
  assert.equal(typeof result.json.resumen.hoy_pendientes, 'number');
});
result = await doctor.api('get_citas.php?por_pagina=1&orden=desc');
check('Appointments are paginated', () => assert.ok(result.json.data.length <= 1 && result.json.meta.por_pagina === 1));
result = await doctor.api('get_citas.php?desde=2026-02-01&hasta=2026-01-01');
check('Reversed appointment range rejected', () => assert.equal(result.response.status, 422));
result = await doctor.api('update_cita_estado.php', { fields: { cita_id: appointmentId, estado: 'Cancelada' } });
check('Appointment cancellation', () => assert.ok(result.json.success, result.json.message));
result = await doctor.api('save_registro.php', { fields: { ...consultation, cita_id: appointmentId, inicio_reposo: '', fin_reposo: '' } });
check('Consultation linked to a closed appointment returns cita_no_pendiente', () => {
  assert.equal(result.response.status, 409);
  assert.equal(result.json.code, 'cita_no_pendiente');
});
result = await doctor.api('update_cita_estado.php', { fields: { cita_id: appointmentId, estado: 'Completada' } });
check('Terminal appointment cannot be silently changed', () => assert.equal(result.response.status, 409));
result = await doctor.api('reprogramar_cita.php', { fields: { cita_id: appointmentId, fecha_cita: `${dayAfter}T11:00` } });
check('Closed appointment cannot be rescheduled', () => assert.equal(result.response.status, 409));

// Reportes y panel.
result = await doctor.api(`get_morbilidad.php?fecha_inicio=${today}&fecha_fin=${today}`);
check('Morbidity report includes consultation in its date range', () => assert.ok(result.json.data.some(row => Number(row.id) === Number(consultId))));
check('Morbidity report omits contact data', () => assert.ok(result.json.data.every(row => !('telefono' in row) && !('direccion' in row))));
const csv = await doctor.raw(`/api/export_morbilidad.php?fecha_inicio=${today}&fecha_fin=${today}`);
const csvBytes = Buffer.from(await csv.arrayBuffer());
const csvText = csvBytes.toString('utf8');
check('Morbidity CSV export', () => {
  assert.equal(csv.status, 200);
  assert.match(csv.headers.get('content-type') || '', /text\/csv/);
  assert.equal(csv.headers.get('content-disposition'), `attachment; filename="morbilidad_${today}_${today}.csv"`);
  assert.deepEqual([...csvBytes.subarray(0, 3)], [0xef, 0xbb, 0xbf]);
  assert.ok(csvText.slice(1).startsWith('Fecha;Cédula;Apellidos;Nombres;Sexo;Edad;Categoría;Motivo;Diagnóstico;TA;Peso/Talla'));
  assert.ok(csvText.includes(patient.cedula));
});
check('CSV neutralizes spreadsheet formulas', () => {
  assert.ok(csvText.includes(`'=HYPERLINK`));
  assert.ok(!/;"?=HYPERLINK/.test(csvText));
});
result = await doctor.api('get_stats.php');
check('Dashboard category totals reconcile', () => assert.equal(result.json.data.categorias.reduce((sum, row) => sum + Number(row.cantidad), 0), result.json.data.total_pacientes));
check('Dashboard includes all six calendar months', () => assert.equal(result.json.data.historial_mensual.length, 6));
check('Dashboard lists today agenda and rests ending soon', () => {
  assert.equal(typeof result.json.data.citas_hoy, 'number');
  assert.ok(Array.isArray(result.json.data.agenda_hoy));
  assert.ok(result.json.data.reposos_por_vencer.some(row => Number(row.consulta_id) === Number(consultId)));
});

for (const path of [`print_historia.php?paciente_id=${patientId}`, `print_informe.php?consulta_id=${consultId}`, `print_reposo.php?consulta_id=${consultId}`, `print_morbilidad.php?fecha_inicio=${today}&fecha_fin=${today}`]) {
  const response = await doctor.raw(`/api/${path}`);
  const html = await response.text();
  check(`Printable document ${path.split('?')[0]} escapes patient markup`, () => {
    assert.equal(response.status, 200);
    assert.ok(!html.includes(patient.nombres));
    assert.ok(html.includes('&lt;img'));
    assert.ok(response.headers.get('content-security-policy'));
  });
}

// ---------------------------------------------------------------------------
// Permisos por rol (asistente)
// ---------------------------------------------------------------------------
for (const path of [`get_paciente_detalle.php?id=${patientId}`, `get_consulta.php?id=${consultId}`, `get_morbilidad.php?fecha_inicio=${today}&fecha_fin=${today}`, 'usuarios.php', 'get_auditoria.php']) {
  result = await assistant.api(path);
  check(`Assistant forbidden: ${path.split('?')[0]}`, () => {
    assert.equal(result.response.status, 403);
    assert.equal(result.json.code, 'forbidden');
  });
}
for (const path of [`print_informe.php?consulta_id=${consultId}`, `print_historia.php?paciente_id=${patientId}`, `export_morbilidad.php?fecha_inicio=${today}&fecha_fin=${today}`, `download_archivo.php?id=${attachment.ruta_archivo.split('=')[1]}`]) {
  const response = await assistant.raw(`/api/${path}`);
  check(`Assistant forbidden: ${path.split('?')[0]}`, () => assert.equal(response.status, 403));
}
result = await assistant.api(`get_paciente.php?id=${patientId}`);
check('Assistant sees identification and contact data only', () => {
  assert.equal(result.response.status, 200);
  assert.equal(result.json.data.telefono, patient.telefono);
  assert.ok(!('antecedente_quirurgico' in result.json.data));
});
result = await assistant.api('save_registro.php', { fields: { ...patient, telefono: '0414-1111111', antecedente_quirurgico: 'Intento de cambio', solo_paciente: '1' } });
check('Assistant updates demographic data', () => assert.equal(result.response.status, 200, result.json.message));
result = await doctor.api(`get_paciente.php?id=${patientId}`);
check('Assistant cannot change clinical history fields', () => {
  assert.equal(result.json.data.telefono, '0414-1111111');
  assert.equal(result.json.data.antecedente_quirurgico, patient.antecedente_quirurgico);
});
result = await assistant.api('save_registro.php', { fields: clinicalFields });
check('Assistant cannot register a consultation', () => assert.equal(result.response.status, 403));
result = await assistant.api('update_consulta.php', { fields: { ...editFields, version_esperada: '1', motivo_edicion: 'Sin permiso' } });
check('Assistant cannot correct a consultation', () => assert.equal(result.response.status, 403));
result = await assistant.api('save_cita.php', { fields: { paciente_id: patientId, fecha_cita: `${dayAfter}T15:00` } });
check('Assistant schedules appointments', () => assert.equal(result.response.status, 201, result.json.message));
result = await assistant.api('get_citas.php?estado=Pendiente');
check('Assistant lists appointments', () => assert.equal(result.response.status, 200));
result = await assistant.api('get_stats.php');
check('Assistant dashboard hides rests ending soon', () => assert.deepEqual(result.json.data.reposos_por_vencer, []));
result = await assistant.api('get_pacientes.php');
check('Assistant list hides last consultation dates', () => assert.ok(result.json.data.every(row => row.ultima_consulta === null)));

// ---------------------------------------------------------------------------
// Bitácora, contraseña y cierre de sesión
// ---------------------------------------------------------------------------
result = await admin.api('get_auditoria.php?accion=ver_expediente&por_pagina=200');
check('Audit log records record access', () => {
  assert.equal(result.response.status, 200);
  assert.ok(result.json.data.some(row => row.entidad_id === patientId && row.usuario === accounts.medico.usuario));
  assert.ok(result.json.meta.total >= 1);
});
result = await admin.api('get_auditoria.php?accion=editar_consulta');
check('Audit log stores field names, not clinical text', () => {
  const entry = result.json.data.find(row => row.entidad_id === consultId);
  assert.deepEqual(entry?.detalle?.campos, ['diagnostico']);
  assert.ok(!JSON.stringify(result.json.data).includes('Diagnóstico corregido'));
});
result = await admin.api(`get_auditoria.php?usuario_id=${accounts.bloqueo.id}&accion=login_fallido`);
check('Audit log records failed logins', () => assert.ok(result.json.meta.total >= 5));

result = await doctor.api('cambiar_password.php', { fields: { actual: 'incorrecta-123', nueva: `Otra-${suffix}-clave` } });
check('Password change checks the current password', () => assert.ok(result.response.status === 422 && result.json.errors.actual));
result = await doctor.api('cambiar_password.php', { fields: { actual: passwords.medico, nueva: `Otra-${suffix}-clave` } });
check('Password changed and session kept', () => {
  assert.equal(result.response.status, 200, result.json.message);
  assert.match(result.json.csrf_token, /^[0-9a-f]{64}$/);
});
doctor.csrf = result.json.csrf_token;
result = await doctor.api('session.php');
check('Session survives a password change', () => assert.equal(result.response.status, 200));
result = await doctor.api('logout.php', { method: 'POST' });
check('Logout', () => assert.equal(result.response.status, 200));
result = await doctor.api('session.php');
check('Session ends after logout', () => assert.equal(result.response.status, 401));

// Los fallos de «contraseña actual» cuentan para el bloqueo: una sesión abierta no permite adivinarla.
const pwdUser = `pwd.${suffix}`;
const pwdPassword = `Clave-${suffix}-cambio`;
result = await admin.api('save_usuario.php', { fields: { usuario: pwdUser, nombre_completo: 'Prueba de cambio de clave', rol: 'asistente', activo: '1', password: pwdPassword } });
check('User for password-guess test created', () => assert.equal(result.response.status, 201, result.json.message));
const guesser = new Client('cambio de clave');
await guesser.login(pwdUser, pwdPassword);
const guessStatuses = [];
for (let attempt = 1; attempt <= 5; attempt += 1) {
  result = await guesser.api('cambiar_password.php', { fields: { actual: `incorrecta-${attempt}`, nueva: `Otra-${suffix}-${attempt}-clave` } });
  guessStatuses.push(result.response.status);
}
check('Fifth wrong current password ends the session', () => {
  assert.deepEqual(guessStatuses, [422, 422, 422, 422, 401]);
  assert.equal(result.json.code, 'auth_required');
});
result = await guesser.api('session.php');
check('Session closed after too many current-password failures', () => assert.equal(result.response.status, 401));
result = await guesser.login(pwdUser, pwdPassword);
check('Account locked after too many current-password failures', () => assert.equal(result.response.status, 401));

// ---------------------------------------------------------------------------
// Infraestructura y cabeceras
// ---------------------------------------------------------------------------
const migration = await fetch(new URL('/api/migrate.php', base));
check('Migration is inaccessible from HTTP', () => assert.equal(migration.status, 404));
const config = await fetch(new URL('/config/Database.php', base));
check('Internal PHP configuration is inaccessible from HTTP', () => assert.equal(config.status, 403));
const home = await admin.raw('/');
check('Application page sends CSP and security headers', () => {
  assert.equal(home.status, 200);
  const csp = home.headers.get('content-security-policy') || '';
  assert.match(csp, /default-src 'self'/);
  assert.match(csp, /frame-ancestors 'self'/);
  assert.match(csp, /object-src 'none'/);
  assert.match(home.headers.get('x-content-type-options') || '', /nosniff/);
});
check('Server header does not reveal versions', () => assert.equal(home.headers.get('server'), 'Apache'));
const missing = await fetch(new URL('/no-existe-' + suffix, base));
const missingHtml = await missing.text();
check('Error pages carry no server signature', () => {
  assert.equal(missing.status, 404);
  assert.ok(!missingHtml.includes('<address>'));
});
for (const path of ['/js/', '/vendor/']) {
  const listing = await fetch(new URL(path, base));
  check(`No directory listing at ${path}`, () => assert.ok([403, 404].includes(listing.status)));
}
const logo = await fetch(new URL('/uploads/logo_unefa_1.png', base));
check('Institutional logo stays public', () => assert.equal(logo.status, 200));
const legacyUpload = await fetch(new URL('/uploads/6a2f2e7d54703_0b9c6c89.png', base));
check('Legacy uploads are not served directly', () => assert.equal(legacyUpload.status, 403));
// src se monta desde un sistema de archivos que puede no distinguir mayúsculas: cambiar
// la caja de la ruta no debe saltarse el bloqueo (403 si llega al directorio, 404 si no existe).
for (const path of ['/Uploads/6a2f2e7d54703_0b9c6c89.png', '/UPLOADS/6a2f2e7d54703_0b9c6c89.png', '/CONFIG/migrations/002_usuarios_auditoria.sql',
  '/Config/Database.php', '/Views/admin.php', '/Modals/consulta_modal.php', '/Components/head.php']) {
  const bypass = await fetch(new URL(path, base));
  check(`Case variant stays blocked: ${path}`, () => assert.ok([403, 404].includes(bypass.status), `HTTP ${bypass.status}`));
}

console.log(`\n${checks} integration checks passed. Fixtures remain only in the isolated test database.`);
