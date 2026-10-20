import assert from 'node:assert/strict';

// This suite creates clinical fixtures. Run it only with docker-compose.test.yml.
const base = new URL(process.argv[2] || 'http://127.0.0.1:18080');
if (!['localhost', '127.0.0.1'].includes(base.hostname) || base.port !== '18080') {
  throw new Error('Use the isolated test environment at http://127.0.0.1:18080.');
}
let checks = 0;
function check(name, assertion) {
  assertion();
  checks += 1;
  console.log(`PASS ${name}`);
}
async function api(path, { fields, file, ...options } = {}) {
  if (fields) {
    const body = new FormData();
    for (const [key, value] of Object.entries(fields)) body.set(key, String(value));
    if (file) body.set('archivo', new Blob([file.bytes], { type: file.type }), file.name);
    options = { method: 'POST', ...options, body };
  }
  const response = await fetch(new URL(`/api/${path}`, base), options);
  const text = await response.text();
  assert.match(response.headers.get('content-type') || '', /application\/json/, `${path}: JSON response required`);
  let json;
  try { json = JSON.parse(text); } catch { throw new Error(`${path}: invalid JSON: ${text.slice(0, 160)}`); }
  return { response, json };
}
const today = new Intl.DateTimeFormat('en-CA', { timeZone: 'America/Caracas', year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date());
const tomorrow = new Intl.DateTimeFormat('en-CA', { timeZone: 'America/Caracas', year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date(Date.now() + 86_400_000));
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

let result = await api('health.php');
check('Database health', () => assert.equal(result.response.status, 200));
result = await api('get_categorias.php');
check('Institutional categories', () => assert.ok(result.json.success && result.json.data.length >= 5));
patient.categoria = consultation.categoria = String(result.json.data[0].id);

for (const endpoint of ['save_registro.php', 'save_cita.php', 'update_cita_estado.php']) {
  result = await api(endpoint);
  check(`GET cannot mutate ${endpoint}`, () => assert.equal(result.response.status, 405));
}
result = await api('save_cita.php', { fields: { fecha_cita: `${tomorrow}T12:00`, cedula: patient.cedula }, headers: { Origin: 'https://untrusted.invalid' } });
check('Cross-origin mutation blocked', () => assert.equal(result.response.status, 403));
result = await api('save_registro.php', { fields: { ...patient, solo_paciente: '1', fecha_nacimiento: '2025-02-30' } });
check('Impossible birth date rejected', () => assert.equal(result.response.status, 422));
result = await api('get_morbilidad.php?fecha_inicio=2026-12-01&fecha_fin=2026-01-01');
check('Reversed report range rejected', () => assert.equal(result.response.status, 422));
result = await api('get_paciente_detalle.php?id=1oops');
check('Malformed identifier rejected', () => assert.equal(result.response.status, 422));

result = await api('save_registro.php', { fields: { ...patient, solo_paciente: '1' } });
check('Patient created', () => assert.ok(result.json.success, result.json.message));
result = await api('get_pacientes.php');
const savedPatient = result.json.data.find(row => row.cedula === patient.cedula);
check('Patient identity round-trips without interpreting markup', () => assert.equal(savedPatient?.nombres, patient.nombres));
const patientId = savedPatient.id;

const appointment = { paciente_id: patientId, fecha_cita: `${tomorrow}T12:00` };
result = await api('save_cita.php', { fields: appointment });
check('Future appointment created', () => assert.ok(result.json.success, result.json.message));
result = await api('save_cita.php', { fields: appointment });
check('Duplicate appointment blocked', () => assert.equal(result.response.status, 409));
const concurrent = await Promise.all([
  api('save_cita.php', { fields: { ...appointment, fecha_cita: `${tomorrow}T14:00` } }),
  api('save_cita.php', { fields: { ...appointment, fecha_cita: `${tomorrow}T14:00` } }),
]);
check('Concurrent requests cannot double-book a patient', () => assert.deepEqual(concurrent.map(item => item.response.status).sort(), [201, 409]));

const png = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=', 'base64');
const clinicalFields = { ...consultation };
delete clinicalFields.telefono;
delete clinicalFields.antecedente_quirurgico;
result = await api('save_registro.php', { fields: clinicalFields, file: { bytes: png, type: 'image/png', name: 'evidencia.php' } });
check('Consultation and safe MIME-based attachment saved atomically', () => assert.ok(result.json.success, result.json.message));
result = await api(`get_paciente_detalle.php?id=${patientId}`);
const details = result.json.data;
check('Omitted history preserved', () => assert.equal(details.paciente.antecedente_quirurgico, patient.antecedente_quirurgico));
check('One clinical consultation persisted', () => assert.equal(details.consultas.length, 1));
check('Saving a consultation preserves future appointments', () => assert.ok(details.citas.every(row => row.estado === 'Pendiente')));
const consultId = details.consultas[0].id;
const appointmentId = details.citas[0].id;
const attachment = details.consultas[0].archivos[0];
check('Attachment has a controlled download endpoint', () => assert.match(attachment.ruta_archivo, /(?:download|archivo).*\.php\?id=/));
const download = await fetch(new URL(attachment.ruta_archivo.replace(/^\.\.\//, '/'), base));
check('Download MIME and bytes are preserved', () => {
  assert.equal(download.status, 200);
  assert.match(download.headers.get('content-type'), /image\/png/);
});
const downloadedBytes = Buffer.from(await download.arrayBuffer());
check('Downloaded bytes match uploaded bytes', () => assert.deepEqual(downloadedBytes, png));

result = await api('save_registro.php', { fields: clinicalFields, file: { bytes: '<?php echo "unsafe";', type: 'image/png', name: 'fake.png' } });
check('Forged attachment MIME rejected', () => assert.equal(result.response.status, 422));
result = await api(`get_paciente_detalle.php?id=${patientId}`);
check('Rejected upload leaves no partial consultation', () => assert.equal(result.json.data.consultas.length, 1));

result = await api('update_cita_estado.php', { fields: { cita_id: appointmentId, estado: 'Cancelada' } });
check('Appointment cancellation', () => assert.ok(result.json.success, result.json.message));
result = await api('update_cita_estado.php', { fields: { cita_id: appointmentId, estado: 'Completada' } });
check('Terminal appointment cannot be silently changed', () => assert.equal(result.response.status, 409));
result = await api(`get_morbilidad.php?fecha_inicio=${today}&fecha_fin=${today}`);
check('Morbidity report includes consultation in its date range', () => assert.ok(result.json.data.some(row => Number(row.id) === Number(consultId))));
result = await api('get_stats.php');
check('Dashboard category totals reconcile', () => assert.equal(result.json.data.categorias.reduce((sum, row) => sum + Number(row.cantidad), 0), result.json.data.total_pacientes));
check('Dashboard includes all six calendar months', () => assert.equal(result.json.data.historial_mensual.length, 6));

for (const path of [`print_historia.php?paciente_id=${patientId}`, `print_informe.php?consulta_id=${consultId}`, `print_reposo.php?consulta_id=${consultId}`, `print_morbilidad.php?fecha_inicio=${today}&fecha_fin=${today}`]) {
  const response = await fetch(new URL(`/api/${path}`, base));
  const html = await response.text();
  check(`Printable document ${path.split('?')[0]} escapes patient markup`, () => {
    assert.equal(response.status, 200);
    assert.ok(!html.includes(patient.nombres));
    assert.ok(html.includes('&lt;img'));
  });
}
const migration = await fetch(new URL('/api/migrate.php', base));
check('Migration is inaccessible from HTTP', () => assert.equal(migration.status, 404));
const config = await fetch(new URL('/config/Database.php', base));
check('Internal PHP configuration is inaccessible from HTTP', () => assert.equal(config.status, 403));
console.log(`\n${checks} integration checks passed. Fixtures remain only in the isolated test database.`);
