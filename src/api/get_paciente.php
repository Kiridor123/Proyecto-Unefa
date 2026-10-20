<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
api_method('GET');
$params = [];
if (array_key_exists('id', $_GET)) {
    $where = 'p.id = ?';
    $params[] = api_id($_GET['id']);
} else {
    $errors = [];
    $cedula = Config\Validation::cedula(Config\Validation::text($_GET, 'cedula', 40, $errors, true));
    if (!Config\Validation::validCedula($cedula)) $errors['cedula'] = 'Indique una cédula válida (ej. V-12345678).';
    api_validate($errors);
    $where = 'p.cedula = ?';
    $params[] = $cedula;
}
$statement = api_db()->prepare('SELECT p.*, c.nombre AS categoria FROM pacientes p JOIN categorias_institucionales c ON c.id = p.categoria_id WHERE ' . $where);
$statement->execute($params);
$patient = $statement->fetch();
if (!$patient) throw new ApiException('El paciente no está registrado.', 404);
api_json(['success' => true, 'data' => $patient]);
