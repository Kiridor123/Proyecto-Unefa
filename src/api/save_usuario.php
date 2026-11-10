<?php
declare(strict_types=1);
// Mantiene la sesión abierta para actualizarla si el administrador cambia su propia contraseña.
const API_SESSION_WRITE = true;
require_once __DIR__ . '/../config/bootstrap.php';
use Config\Validation;

api_method('POST');
api_require('usuarios.gestionar');
$current = api_user();
$input = api_input();
$errors = [];
$id = null;
if (isset($input['id']) && $input['id'] !== '') {
    $id = Validation::positiveId($input['id']);
    if ($id === null) $errors['id'] = 'El identificador no es válido.';
}
$username = strtolower(Validation::text($input, 'usuario', 50, $errors, true));
if (!isset($errors['usuario']) && !preg_match('/^[a-z0-9._-]{3,50}$/D', $username)) {
    $errors['usuario'] = 'Use de 3 a 50 letras minúsculas sin acentos, números, punto, guion o guion bajo.';
}
$fullName = Validation::text($input, 'nombre_completo', 150, $errors, true);
$role = Validation::text($input, 'rol', 20, $errors, true);
if (!isset($errors['rol']) && !in_array($role, AUTH_ROLES, true)) $errors['rol'] = 'Seleccione un rol válido.';
$registry = Validation::text($input, 'registro_profesional', 50, $errors);
$activeValue = $input['activo'] ?? '1';
if (!in_array($activeValue, ['0', '1'], true)) $errors['activo'] = 'Indique si la cuenta está activa.';
$active = $activeValue !== '0';
$password = $input['password'] ?? '';
if ($password === '' || $password === null) {
    $password = null;
    if ($id === null) $errors['password'] = 'Indique una contraseña inicial.';
} else {
    $problem = auth_password_problem($password);
    if ($problem !== null) $errors['password'] = $problem;
}
api_validate($errors);

$db = api_db();
try {
    $db->beginTransaction();
    // Serializa los cambios de cuentas para que la regla del último administrador no tenga carreras.
    $db->query('SELECT pg_advisory_xact_lock(824319105)');
    $statement = $db->prepare('SELECT id FROM usuarios WHERE usuario = ? AND id <> ?');
    $statement->execute([$username, $id ?? 0]);
    if ($statement->fetchColumn()) throw new ApiException('Revise los campos indicados.', 409, ['usuario' => 'Ese nombre de usuario ya está en uso.'], 'duplicate_user');
    $hash = $password !== null ? password_hash($password, PASSWORD_DEFAULT) : null;

    if ($id === null) {
        $statement = $db->prepare('INSERT INTO usuarios (usuario, nombre_completo, rol, registro_profesional, password_hash, activo) VALUES (?, ?, ?, ?, ?, ?) RETURNING id');
        $statement->execute([$username, $fullName, $role, $registry !== '' ? $registry : null, $hash, $active ? 'true' : 'false']);
        $id = (int) $statement->fetchColumn();
        api_audit('crear_usuario', 'usuario', $id, ['usuario' => $username, 'rol' => $role, 'activo' => $active]);
        $message = 'El usuario se creó correctamente.';
        $status = 201;
    } else {
        $statement = $db->prepare('SELECT id, usuario, nombre_completo, rol, registro_profesional, activo FROM usuarios WHERE id = ? FOR UPDATE');
        $statement->execute([$id]);
        $previous = $statement->fetch();
        if (!$previous) throw new ApiException('El usuario no existe.', 404);
        $losesAdmin = $previous['rol'] === 'administrador' && $previous['activo'] && ($role !== 'administrador' || !$active);
        if ($losesAdmin && $id === $current['id']) {
            throw new ApiException('No puede quitarse el rol de administrador ni desactivar su propia cuenta.', 409, [$active ? 'rol' : 'activo' => 'Pida a otro administrador que haga este cambio.'], 'self_lockout');
        }
        if ($losesAdmin) {
            $statement = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE rol = 'administrador' AND activo AND id <> ?");
            $statement->execute([$id]);
            if ((int) $statement->fetchColumn() === 0) {
                throw new ApiException('Debe quedar al menos un administrador activo.', 409, [$active ? 'rol' : 'activo' => 'Es el último administrador activo.'], 'last_admin');
            }
        }
        $sql = 'UPDATE usuarios SET usuario = ?, nombre_completo = ?, rol = ?, registro_profesional = ?, activo = ?, actualizado_en = now()';
        $params = [$username, $fullName, $role, $registry !== '' ? $registry : null, $active ? 'true' : 'false'];
        if ($hash !== null) {
            // Restablecer la contraseña también levanta el bloqueo por intentos fallidos.
            $sql .= ', password_hash = ?, intentos_fallidos = 0, bloqueado_hasta = NULL';
            $params[] = $hash;
        }
        $statement = $db->prepare($sql . ' WHERE id = ?');
        $statement->execute([...$params, $id]);
        $changed = [];
        foreach (['usuario' => $username, 'nombre_completo' => $fullName, 'rol' => $role, 'registro_profesional' => $registry !== '' ? $registry : null] as $field => $value) {
            if ((string) ($previous[$field] ?? '') !== (string) ($value ?? '')) $changed[] = $field;
        }
        if ((bool) $previous['activo'] !== $active) $changed[] = 'activo';
        if ($hash !== null) $changed[] = 'password';
        $detail = ['usuario' => $username, 'campos' => $changed];
        if (in_array('rol', $changed, true)) $detail['rol'] = $role;
        if (in_array('activo', $changed, true)) $detail['activo'] = $active;
        api_audit('editar_usuario', 'usuario', $id, $detail);
        $message = 'El usuario se actualizó correctamente.';
        $status = 200;
    }
    $db->commit();
    if ($hash !== null && $id === $current['id']) $_SESSION['pwd'] = auth_password_fingerprint($hash);
    auth_session_release();
    api_json(['success' => true, 'message' => $message, 'id' => $id], $status);
} catch (Throwable $error) {
    if ($db->inTransaction()) $db->rollBack();
    throw $error;
}
