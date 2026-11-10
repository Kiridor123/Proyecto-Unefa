<?php
declare(strict_types=1);
const API_SESSION_WRITE = true;
require_once __DIR__ . '/../config/bootstrap.php';

api_method('POST');
$user = api_user();
$input = api_input();
$errors = [];
$current = $input['actual'] ?? '';
$new = $input['nueva'] ?? '';
if (!is_string($current) || $current === '') $errors['actual'] = 'Indique su contraseña actual.';
$problem = auth_password_problem($new);
if ($problem !== null) $errors['nueva'] = $problem;
elseif (is_string($current) && hash_equals($current, $new)) $errors['nueva'] = 'La contraseña nueva debe ser distinta de la actual.';
api_validate($errors);

$db = api_db();
try {
    $db->beginTransaction();
    $statement = $db->prepare('SELECT password_hash, intentos_fallidos FROM usuarios WHERE id = ? AND activo FOR UPDATE');
    $statement->execute([$user['id']]);
    $account = $statement->fetch();
    $hash = $account ? $account['password_hash'] : null;
    if (!is_string($hash)) throw new ApiException('Su sesión expiró. Inicie sesión de nuevo.', 401, [], 'auth_required');
    if (!password_verify($current, $hash)) {
        // Los fallos cuentan para el mismo bloqueo que el inicio de sesión: una sesión abierta
        // no sirve para adivinar la contraseña sin límite.
        $lockedNow = auth_register_failed_attempt($db, (int) $user['id'], (int) $account['intentos_fallidos']);
        $db->commit();
        api_audit('cambiar_password_fallido', 'usuario', $user['id'], $lockedNow ? ['bloqueo' => true] : []);
        if ($lockedNow) {
            auth_session_destroy();
            throw new ApiException('Demasiados intentos con una contraseña actual incorrecta. Por seguridad se cerró la sesión y la cuenta quedó bloqueada ' . AUTH_LOCK_MINUTES . ' minutos.', 401, [], 'auth_required');
        }
        throw new ApiException('Revise los campos indicados.', 422, ['actual' => 'La contraseña actual no es correcta.']);
    }
    $newHash = password_hash($new, PASSWORD_DEFAULT);
    $statement = $db->prepare('UPDATE usuarios SET password_hash = ?, intentos_fallidos = 0, bloqueado_hasta = NULL, actualizado_en = now() WHERE id = ?');
    $statement->execute([$newHash, $user['id']]);
    $db->commit();
} catch (Throwable $error) {
    if ($db->inTransaction()) $db->rollBack();
    throw $error;
}
// Nuevo identificador de sesión; el resto de sesiones de la cuenta deja de ser válido.
session_regenerate_id(true);
$_SESSION['pwd'] = auth_password_fingerprint($newHash);
$_SESSION['last_activity'] = time();
api_audit('cambiar_password', 'usuario', $user['id']);
$token = api_csrf_token();
auth_session_release();
api_json(['success' => true, 'message' => 'La contraseña se actualizó correctamente.', 'csrf_token' => $token]);
