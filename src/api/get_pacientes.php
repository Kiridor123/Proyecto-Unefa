<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
api_method('GET');
$statement = api_db()->query('SELECT p.id, p.cedula, p.nombres, p.apellidos, p.categoria_id, c.nombre AS categoria, latest.ultima_consulta
    FROM pacientes p JOIN categorias_institucionales c ON p.categoria_id = c.id
    LEFT JOIN (SELECT paciente_id, MAX(fecha_circunstancia) AS ultima_consulta FROM consultas GROUP BY paciente_id) latest ON latest.paciente_id = p.id
    ORDER BY latest.ultima_consulta DESC NULLS LAST, p.apellidos ASC, p.nombres ASC, p.id ASC');
api_json(['success' => true, 'data' => $statement->fetchAll()]);
