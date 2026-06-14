<?php
require_once '../config/Database.php';

header('Content-Type: application/json');

try {
    $db = Config\Database::getInstance()->getConnection();
    
    $stmt = $db->query("SELECT id, nombre FROM categorias_institucionales ORDER BY id");
    $categorias = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'data' => $categorias]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al obtener categorías']);
}
