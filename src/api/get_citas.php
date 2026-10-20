<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
api_method('GET');
$errors = [];
$status = Config\Validation::text($_GET, 'estado', 20, $errors);
if ($status !== '' && !in_array($status, ['Pendiente', 'Completada', 'Cancelada'], true)) $errors['estado'] = 'Seleccione un estado válido.';
api_validate($errors);
$query = 'SELECT c.id, c.fecha_cita, c.estado, p.id AS paciente_id, p.cedula, p.nombres, p.apellidos, cat.nombre AS categoria
    FROM citas c JOIN pacientes p ON c.paciente_id = p.id JOIN categorias_institucionales cat ON p.categoria_id = cat.id';
$params = [];
if ($status !== '') { $query .= ' WHERE c.estado = ?'; $params[] = $status; }
$query .= ' ORDER BY c.fecha_cita ASC, c.id ASC';
$statement = api_db()->prepare($query);
$statement->execute($params);
api_json(['success' => true, 'data' => $statement->fetchAll()]);
