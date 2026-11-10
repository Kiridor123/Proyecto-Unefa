<?php
declare(strict_types=1);

/**
 * Guardia de sesión para páginas HTML.
 *
 * - En index.php (y cualquier página protegida): `require __DIR__ . '/config/page_guard.php';`
 *   Sin sesión válida redirige a login.php. Con sesión define $SALUD_SESSION
 *   = ['usuario' => [...], 'permisos' => [...], 'csrf_token' => '...'].
 * - En login.php: tras el require, llamar a page_guard_login_redirect() para
 *   enviar a index.php a quien ya tiene sesión. login.php nunca se redirige a sí mismo.
 */
if (!defined('API_HTML_RESPONSE')) define('API_HTML_RESPONSE', true);
require_once __DIR__ . '/bootstrap.php';

/** Datos de la sesión actual para el navegador, o null si no hay sesión válida. */
function page_guard_session(): ?array
{
    try {
        if (!auth_current_user()) return null;
        return api_session_payload();
    } catch (ApiException $error) {
        if ($error->status === 401) return null;
        throw $error;
    }
}

/** Para login.php: si ya hay sesión, ir directamente a la aplicación. */
function page_guard_login_redirect(): void
{
    $session = page_guard_session();
    auth_session_release();
    if ($session) {
        header('Location: index.php', true, 303);
        exit;
    }
}

header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('X-Frame-Options: SAMEORIGIN');

if (basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')) !== 'login.php') {
    $SALUD_SESSION = page_guard_session();
    auth_session_release();
    if (!$SALUD_SESSION) {
        $expired = !empty($_COOKIE[AUTH_SESSION_NAME]) ? '?expirada=1' : '';
        header('Location: login.php' . $expired, true, 303);
        exit;
    }
}
