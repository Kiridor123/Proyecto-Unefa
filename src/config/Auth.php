<?php
declare(strict_types=1);

/**
 * Autenticación por sesión, roles, CSRF y bitácora de auditoría.
 * Incluido por bootstrap.php. Las funciones api_* forman el contrato público
 * que usan los endpoints, los impresos y las páginas HTML.
 */

const AUTH_SESSION_NAME = 'SALUDSESSID';
const AUTH_IDLE_SECONDS = 1800;          // 30 minutos de inactividad.
const AUTH_ABSOLUTE_SECONDS = 43200;     // 12 horas como máximo por sesión.
const AUTH_MAX_ATTEMPTS = 5;
const AUTH_LOCK_MINUTES = 15;
const AUTH_PASSWORD_MIN = 10;
const AUTH_PASSWORD_MAX = 200;
const AUTH_INVALID_CREDENTIALS = 'Usuario o contraseña incorrectos.';

const AUTH_ROLES = ['administrador', 'medico', 'asistente'];
const AUTH_ROLE_LABELS = ['administrador' => 'Administrador', 'medico' => 'Médico', 'asistente' => 'Asistente'];
const AUTH_PERMISSIONS = [
    'administrador' => ['pacientes.ver', 'pacientes.editar', 'clinica.ver', 'clinica.editar', 'citas.gestionar', 'reportes.ver', 'usuarios.gestionar', 'auditoria.ver'],
    'medico' => ['pacientes.ver', 'pacientes.editar', 'clinica.ver', 'clinica.editar', 'citas.gestionar', 'reportes.ver'],
    'asistente' => ['pacientes.ver', 'pacientes.editar', 'citas.gestionar'],
];

/** Solo confía en cabeceras X-Forwarded-* cuando la instalación lo declara. */
function auth_trust_proxy(): bool
{
    return getenv('TRUST_PROXY') === '1';
}

function auth_forwarded(string $header): string
{
    $value = $_SERVER[$header] ?? '';
    if (!is_string($value) || $value === '') return '';
    return strtolower(trim(explode(',', $value)[0]));
}

function auth_is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
    return auth_trust_proxy() && auth_forwarded('HTTP_X_FORWARDED_PROTO') === 'https';
}

/** Host público esperado para la comprobación de origen. */
function auth_public_host(): string
{
    $forwarded = auth_trust_proxy() ? auth_forwarded('HTTP_X_FORWARDED_HOST') : '';
    return $forwarded !== '' ? $forwarded : strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
}

function auth_client_ip(): ?string
{
    $ip = auth_trust_proxy() ? auth_forwarded('HTTP_X_FORWARDED_FOR') : '';
    if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    return $ip !== '' ? substr($ip, 0, 45) : null;
}

/** Inicia la sesión con cookies endurecidas. No hace nada en CLI. */
function auth_session_start(): bool
{
    if (PHP_SAPI === 'cli') return false;
    if (session_status() === PHP_SESSION_ACTIVE) return true;
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.gc_maxlifetime', (string) AUTH_ABSOLUTE_SECONDS);
    session_name(AUTH_SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => auth_is_https(),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_cache_limiter('nocache');
    return session_start();
}

/** Destruye la sesión actual y su cookie. */
function auth_session_destroy(): void
{
    if (PHP_SAPI === 'cli') return;
    if (session_status() !== PHP_SESSION_ACTIVE) auth_session_start();
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 3600, 'path' => $params['path'] ?: '/', 'secure' => $params['secure'],
            'httponly' => true, 'samesite' => 'Strict',
        ]);
        session_destroy();
    }
    auth_cache(false);
}

/** Libera el bloqueo del archivo de sesión para no serializar peticiones paralelas. */
function auth_session_release(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
}

function auth_password_fingerprint(string $hash): string
{
    return hash('sha256', $hash);
}

/**
 * Caché del usuario de la petición. false = aún no resuelto.
 * @param array|false|null $set
 */
function auth_cache($set = null)
{
    static $user = false;
    if (func_num_args() > 0) $user = $set;
    return $user;
}

/** Devuelve el usuario autenticado, recargado desde la base de datos, o null. */
function auth_current_user(): ?array
{
    $cached = auth_cache();
    if ($cached !== false) return $cached;
    // Sin cookie no se crea una sesión vacía para visitantes anónimos.
    // (Tras iniciar sesión la sesión ya está activa aunque el navegador aún no tenga la cookie.)
    $hasSession = session_status() === PHP_SESSION_ACTIVE || !empty($_COOKIE[AUTH_SESSION_NAME]);
    if (PHP_SAPI === 'cli' || !$hasSession || !auth_session_start() || empty($_SESSION['uid'])) {
        auth_cache(null);
        return null;
    }
    $now = time();
    $created = (int) ($_SESSION['created'] ?? 0);
    $last = (int) ($_SESSION['last_activity'] ?? 0);
    if ($now - $last > AUTH_IDLE_SECONDS || $now - $created > AUTH_ABSOLUTE_SECONDS) {
        auth_session_destroy();
        return null;
    }
    $statement = api_db()->prepare('SELECT id, usuario, nombre_completo, rol, registro_profesional, activo, password_hash FROM usuarios WHERE id = ?');
    $statement->execute([(int) $_SESSION['uid']]);
    $row = $statement->fetch();
    // Una cuenta desactivada, eliminada o con contraseña restablecida pierde la sesión al instante.
    if (!$row || !$row['activo'] || !in_array($row['rol'], AUTH_ROLES, true)
        || !hash_equals((string) ($_SESSION['pwd'] ?? ''), auth_password_fingerprint($row['password_hash']))) {
        auth_session_destroy();
        return null;
    }
    $_SESSION['last_activity'] = $now;
    $user = [
        'id' => (int) $row['id'],
        'usuario' => $row['usuario'],
        'nombre_completo' => $row['nombre_completo'],
        'rol' => $row['rol'],
        'registro_profesional' => $row['registro_profesional'],
    ];
    auth_cache($user);
    return $user;
}

/** Abre una sesión autenticada nueva tras verificar credenciales. */
function auth_session_open(array $row): void
{
    auth_session_start();
    session_regenerate_id(true);
    $now = time();
    $_SESSION = [
        'uid' => (int) $row['id'],
        'pwd' => auth_password_fingerprint($row['password_hash']),
        'csrf' => bin2hex(random_bytes(32)),
        'created' => $now,
        'last_activity' => $now,
    ];
    auth_cache(false);
}

/**
 * Suma un intento fallido de contraseña a la cuenta (fila ya bloqueada con FOR UPDATE)
 * y, al llegar a AUTH_MAX_ATTEMPTS, la bloquea AUTH_LOCK_MINUTES. Devuelve true si la bloqueó.
 * Lo comparten el inicio de sesión y el cambio de contraseña.
 */
function auth_register_failed_attempt(PDO $db, int $userId, int $previousAttempts): bool
{
    $attempts = $previousAttempts + 1;
    if ($attempts >= AUTH_MAX_ATTEMPTS) {
        $update = $db->prepare('UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = now() + make_interval(mins => ?) WHERE id = ?');
        $update->execute([AUTH_LOCK_MINUTES, $userId]);
        return true;
    }
    $update = $db->prepare('UPDATE usuarios SET intentos_fallidos = ? WHERE id = ?');
    $update->execute([$attempts, $userId]);
    return false;
}

/**
 * Verifica credenciales con bloqueo progresivo. Devuelve el usuario público o lanza ApiException.
 */
function auth_login(string $username, string $password): array
{
    $db = api_db();
    $db->beginTransaction();
    try {
        $statement = $db->prepare('SELECT id, usuario, nombre_completo, rol, registro_profesional, activo, password_hash, intentos_fallidos,
            (bloqueado_hasta IS NOT NULL AND bloqueado_hasta > now()) AS bloqueado
            FROM usuarios WHERE usuario = ? FOR UPDATE');
        $statement->execute([$username]);
        $row = $statement->fetch();
        if (!$row) {
            // Igualar el coste de la verificación para no revelar si el usuario existe.
            password_hash($password, PASSWORD_DEFAULT);
            $db->commit();
            auth_audit_write(null, $username !== '' ? substr($username, 0, 50) : null, 'login_fallido', 'usuario', null, ['motivo' => 'credenciales']);
            throw new ApiException(AUTH_INVALID_CREDENTIALS, 401, [], 'invalid_credentials');
        }
        if ($row['bloqueado']) {
            // Misma respuesta y coste que unas credenciales erróneas: no revela que la cuenta
            // existe ni sirve de oráculo de la contraseña mientras dura el bloqueo.
            // El motivo solo queda en la auditoría.
            password_verify($password, $row['password_hash']);
            $db->commit();
            auth_audit_write((int) $row['id'], $row['usuario'], 'login_fallido', 'usuario', (int) $row['id'], ['motivo' => 'bloqueado']);
            throw new ApiException(AUTH_INVALID_CREDENTIALS, 401, [], 'invalid_credentials');
        }
        $valid = password_verify($password, $row['password_hash']);
        if (!$valid || !$row['activo']) {
            $lockedNow = !$valid && auth_register_failed_attempt($db, (int) $row['id'], (int) $row['intentos_fallidos']);
            $db->commit();
            $detail = ['motivo' => $valid ? 'inactivo' : 'credenciales'];
            if ($lockedNow) $detail['bloqueo'] = true;
            auth_audit_write((int) $row['id'], $row['usuario'], 'login_fallido', 'usuario', (int) $row['id'], $detail);
            throw new ApiException(AUTH_INVALID_CREDENTIALS, 401, [], 'invalid_credentials');
        }
        $hash = $row['password_hash'];
        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) $hash = password_hash($password, PASSWORD_DEFAULT);
        $update = $db->prepare('UPDATE usuarios SET intentos_fallidos = 0, bloqueado_hasta = NULL, ultimo_acceso = now(), password_hash = ? WHERE id = ?');
        $update->execute([$hash, $row['id']]);
        $db->commit();
    } catch (Throwable $error) {
        if ($db->inTransaction()) $db->rollBack();
        throw $error;
    }
    $row['password_hash'] = $hash;
    auth_session_open($row);
    $user = auth_current_user();
    if (!$user) throw new ApiException('No fue posible iniciar la sesión. Inténtelo de nuevo.', 500);
    api_audit('login_ok', 'usuario', $user['id']);
    return $user;
}

/** Valida una contraseña nueva. Devuelve el mensaje de error o null. */
function auth_password_problem($password): ?string
{
    if (!is_string($password) || $password === '') return 'Indique la contraseña.';
    if (!preg_match('//u', $password) || preg_match('/[\x00-\x1F\x7F]/', $password)) return 'La contraseña contiene caracteres no válidos.';
    $length = mb_strlen($password, 'UTF-8');
    if ($length < AUTH_PASSWORD_MIN) return 'La contraseña debe tener al menos ' . AUTH_PASSWORD_MIN . ' caracteres.';
    if ($length > AUTH_PASSWORD_MAX) return 'La contraseña admite como máximo ' . AUTH_PASSWORD_MAX . ' caracteres.';
    return null;
}

/** Comprueba la cabecera X-CSRF-Token de una petición autenticada. */
function auth_check_csrf(): void
{
    $expected = (string) ($_SESSION['csrf'] ?? '');
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if ($expected === '' || !is_string($sent) || $sent === '' || !hash_equals($expected, $sent)) {
        throw new ApiException('La verificación de seguridad de la sesión falló. Recargue la página e inténtelo de nuevo.', 403, [], 'csrf_invalid');
    }
}

/** Inserta un registro de auditoría sin interrumpir nunca la petición. */
function auth_audit_write(?int $userId, ?string $username, string $action, ?string $entity, ?int $entityId, array $detail): void
{
    try {
        $db = api_db();
        $savepoint = $db->inTransaction();
        if ($savepoint) $db->exec('SAVEPOINT salud_auditoria');
        try {
            $statement = $db->prepare('INSERT INTO auditoria (usuario_id, usuario, accion, entidad, entidad_id, detalle, ip) VALUES (?, ?, ?, ?, ?, CAST(? AS JSONB), ?)');
            $statement->execute([
                $userId, $username, substr($action, 0, 50), $entity !== null ? substr($entity, 0, 30) : null, $entityId,
                json_encode((object) $detail, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR),
                auth_client_ip(),
            ]);
            if ($savepoint) $db->exec('RELEASE SAVEPOINT salud_auditoria');
        } catch (Throwable $error) {
            if ($savepoint) $db->exec('ROLLBACK TO SAVEPOINT salud_auditoria');
            throw $error;
        }
    } catch (Throwable $error) {
        error_log('Audit failure: ' . get_class($error) . ' code=' . $error->getCode() . ' action=' . $action);
    }
}

// ---------------------------------------------------------------------------
// Contrato público
// ---------------------------------------------------------------------------

/** Usuario autenticado: id, usuario, nombre_completo, rol, registro_profesional. */
function api_user(): array
{
    $user = auth_current_user();
    if (!$user) throw new ApiException('Su sesión expiró. Inicie sesión de nuevo.', 401, [], 'auth_required');
    return $user;
}

/** Permisos de un rol, o del usuario actual si no se indica. */
function api_permissions(?string $rol = null): array
{
    if ($rol === null) {
        $user = auth_current_user();
        if (!$user) return [];
        $rol = $user['rol'];
    }
    return AUTH_PERMISSIONS[$rol] ?? [];
}

function api_can(string $permiso): bool
{
    return in_array($permiso, api_permissions(), true);
}

function api_require(string $permiso): void
{
    api_user();
    if (!api_can($permiso)) throw new ApiException('No tiene permiso para esta acción.', 403, [], 'forbidden');
}

/** Registra una acción. Nunca guardar texto clínico en $detalle. */
function api_audit(string $accion, ?string $entidad = null, ?int $entidadId = null, array $detalle = []): void
{
    try {
        $user = auth_current_user();
    } catch (Throwable $error) {
        $user = null;
    }
    auth_audit_write($user['id'] ?? null, $user['usuario'] ?? null, $accion, $entidad, $entidadId, $detalle);
}

function api_csrf_token(): string
{
    if (PHP_SAPI === 'cli') return '';
    if (!isset($_SESSION) && !auth_session_start()) return '';
    if (!empty($_SESSION['csrf']) && is_string($_SESSION['csrf'])) return $_SESSION['csrf'];
    if (session_status() !== PHP_SESSION_ACTIVE && !auth_session_start()) return '';
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

/** Datos de sesión que se entregan al navegador (sin secretos de la cuenta). */
function api_session_payload(): array
{
    $user = api_user();
    return [
        'usuario' => [
            'id' => $user['id'],
            'usuario' => $user['usuario'],
            'nombre_completo' => $user['nombre_completo'],
            'rol' => $user['rol'],
            'rol_nombre' => AUTH_ROLE_LABELS[$user['rol']] ?? $user['rol'],
            'registro_profesional' => $user['registro_profesional'],
        ],
        'permisos' => api_permissions($user['rol']),
        'csrf_token' => api_csrf_token(),
    ];
}
