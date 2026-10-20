<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
api_method('GET');
api_json(['success' => true, 'data' => api_db()->query('SELECT id, nombre FROM categorias_institucionales ORDER BY id')->fetchAll()]);
