<?php
declare(strict_types=1);
const API_PUBLIC = true;
require_once __DIR__ . '/../config/bootstrap.php';
api_method('GET');
api_db()->query('SELECT 1')->fetchColumn();
api_json(['success' => true, 'status' => 'ok']);
