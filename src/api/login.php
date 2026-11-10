<?php
declare(strict_types=1);
const API_PUBLIC = true;
const API_SESSION_WRITE = true;
require_once __DIR__ . '/../config/bootstrap.php';

api_method('POST');
$input = api_input();
$errors = [];
$username = strtolower(Config\Validation::text($input, 'usuario', 50, $errors, true));
$password = $input['password'] ?? '';
if (!is_string($password) || $password === '') $errors['password'] = 'Indique la contraseña.';
elseif (strlen($password) > 1024) $errors['password'] = 'La contraseña no es válida.';
api_validate($errors);

auth_login($username, $password);
$session = api_session_payload();
auth_session_release();
api_json([
    'success' => true,
    'message' => 'Sesión iniciada.',
    'usuario' => [
        'id' => $session['usuario']['id'],
        'usuario' => $session['usuario']['usuario'],
        'nombre_completo' => $session['usuario']['nombre_completo'],
        'rol' => $session['usuario']['rol'],
        'rol_nombre' => $session['usuario']['rol_nombre'],
    ],
    'permisos' => $session['permisos'],
    'csrf_token' => $session['csrf_token'],
]);
