<?php
declare(strict_types=1);
// Público para que cerrar una sesión ya vencida también responda con éxito.
const API_PUBLIC = true;
const API_SESSION_WRITE = true;
require_once __DIR__ . '/../config/bootstrap.php';

api_method('POST');
$user = auth_current_user();
if ($user) api_audit('logout', 'usuario', $user['id']);
auth_session_destroy();
api_json(['success' => true, 'message' => 'Sesión cerrada.']);
