<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
api_method('GET');
$id = api_id($_GET['id'] ?? null);
$db = api_db();
$statement = $db->prepare('SELECT p.*, c.nombre AS categoria FROM pacientes p JOIN categorias_institucionales c ON p.categoria_id = c.id WHERE p.id = ?');
$statement->execute([$id]);
$patient = $statement->fetch();
if (!$patient) throw new ApiException('El paciente no existe.', 404);
$statement = $db->prepare('SELECT * FROM consultas WHERE paciente_id = ? ORDER BY fecha_circunstancia DESC, fecha_registro DESC, id DESC');
$statement->execute([$id]);
$consultations = $statement->fetchAll();
// One attachment query for the entire history, avoiding an extra query per visit.
$statement = $db->prepare('SELECT a.id, a.consulta_id, a.tipo_archivo, a.fecha_subida FROM archivos_adjuntos a JOIN consultas c ON c.id = a.consulta_id WHERE c.paciente_id = ? ORDER BY a.id');
$statement->execute([$id]);
$attachments = [];
foreach ($statement->fetchAll() as $attachment) {
    $attachment['ruta_archivo'] = 'api/download_archivo.php?id=' . $attachment['id'];
    $attachments[$attachment['consulta_id']][] = $attachment;
}
foreach ($consultations as &$consultation) $consultation['archivos'] = $attachments[$consultation['id']] ?? [];
unset($consultation);
$statement = $db->prepare('SELECT id, fecha_cita, estado FROM citas WHERE paciente_id = ? ORDER BY fecha_cita DESC, id DESC');
$statement->execute([$id]);
api_json(['success' => true, 'data' => ['paciente' => $patient, 'consultas' => $consultations, 'citas' => $statement->fetchAll()]]);
