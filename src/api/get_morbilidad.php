<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
api_method('GET');
$errors = [];
[$start, $end] = Config\Validation::dateRange($_GET, $errors);
api_validate($errors);
$statement = api_db()->prepare('SELECT c.*, p.cedula, p.nombres, p.apellidos, p.sexo, p.fecha_nacimiento,
    p.carrera, p.semestre, p.telefono, p.direccion, cat.nombre AS categoria
    FROM consultas c JOIN pacientes p ON c.paciente_id = p.id
    JOIN categorias_institucionales cat ON p.categoria_id = cat.id
    WHERE c.fecha_circunstancia BETWEEN ? AND ? ORDER BY c.fecha_circunstancia ASC, c.id ASC');
$statement->execute([$start, $end]);
api_json(['success' => true, 'data' => $statement->fetchAll()]);
