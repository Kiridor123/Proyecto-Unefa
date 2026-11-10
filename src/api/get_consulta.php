<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';

api_method('GET');
api_require('clinica.ver');
$id = api_id($_GET['id'] ?? null);
$db = api_db();
$statement = $db->prepare('SELECT c.*, u.nombre_completo AS medico_nombre, u.registro_profesional AS medico_registro,
        e.nombre_completo AS actualizado_por_nombre,
        (SELECT COUNT(*) FROM consulta_versiones v WHERE v.consulta_id = c.id)::int AS version_actual
    FROM consultas c
    LEFT JOIN usuarios u ON u.id = c.medico_id
    LEFT JOIN usuarios e ON e.id = c.actualizado_por
    WHERE c.id = ?');
$statement->execute([$id]);
$consultation = $statement->fetch();
if (!$consultation) throw new ApiException('La consulta no existe.', 404);

$statement = $db->prepare('SELECT id, tipo_archivo, fecha_subida FROM archivos_adjuntos WHERE consulta_id = ? ORDER BY id');
$statement->execute([$id]);
$attachments = [];
foreach ($statement->fetchAll() as $attachment) {
    $attachment['ruta_archivo'] = 'api/download_archivo.php?id=' . $attachment['id'];
    $attachments[] = $attachment;
}
$consultation['archivos'] = $attachments;

$statement = $db->prepare('SELECT p.id, p.cedula, p.nombres, p.apellidos, p.sexo, p.fecha_nacimiento, c.nombre AS categoria
    FROM pacientes p JOIN categorias_institucionales c ON c.id = p.categoria_id WHERE p.id = ?');
$statement->execute([$consultation['paciente_id']]);
$patient = $statement->fetch();

$statement = $db->prepare('SELECT v.version, v.motivo, u.nombre_completo AS editado_por_nombre, v.editado_en
    FROM consulta_versiones v LEFT JOIN usuarios u ON u.id = v.editado_por
    WHERE v.consulta_id = ? ORDER BY v.version DESC');
$statement->execute([$id]);
$versions = $statement->fetchAll();

api_audit('ver_consulta', 'consulta', $id, ['paciente_id' => (int) $consultation['paciente_id']]);
api_json(['success' => true, 'consulta' => $consultation, 'paciente' => $patient ?: null, 'versiones' => $versions]);
