<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
api_method('GET');
api_db()->query('SELECT 1')->fetchColumn();
api_json(['success' => true, 'status' => 'ok']);
