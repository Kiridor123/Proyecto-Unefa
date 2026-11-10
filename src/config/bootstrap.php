<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Validation.php';
require_once __DIR__ . '/Auth.php';

ini_set('display_errors', '0');
ini_set('log_errors', '1');
$appTimezone = getenv('APP_TIMEZONE') ?: 'America/Caracas';
date_default_timezone_set(in_array($appTimezone, DateTimeZone::listIdentifiers(), true) ? $appTimezone : 'America/Caracas');

final class ApiException extends RuntimeException
{
    public int $status;
    public array $errors;
    /** Código estable para el cliente (auth_required, forbidden, identity_mismatch…). */
    public ?string $errorCode;
    /** Datos adicionales que se agregan a la respuesta JSON. */
    public array $extra;

    public function __construct(string $message, int $status = 422, array $errors = [], ?string $errorCode = null, array $extra = [])
    {
        parent::__construct($message);
        $this->status = $status;
        $this->errors = $errors;
        $this->errorCode = $errorCode;
        $this->extra = $extra;
    }
}

function api_headers(): void
{
    header('Cache-Control: no-store, private');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('X-Frame-Options: SAMEORIGIN');
}

function api_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    api_headers();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
    exit;
}

function api_fail(Throwable $error): void
{
    $status = $error instanceof ApiException ? $error->status : ($error instanceof PDOException ? 503 : 500);
    $message = $error instanceof ApiException ? $error->getMessage() : 'No fue posible completar la operación. Inténtelo de nuevo.';
    if (!$error instanceof ApiException) {
        // Nunca registrar cuerpos de petición, argumentos SQL ni información clínica.
        error_log('API failure [' . basename($_SERVER['SCRIPT_NAME'] ?? 'cli') . ']: ' . get_class($error) . ' code=' . $error->getCode());
    }
    if (defined('API_HTML_RESPONSE') && API_HTML_RESPONSE) {
        http_response_code($status);
        api_headers();
        header('Content-Type: text/plain; charset=utf-8');
        echo $message;
        exit;
    }
    $payload = ['success' => false, 'message' => $message];
    if ($error instanceof ApiException) {
        if ($error->errorCode !== null) $payload['code'] = $error->errorCode;
        if ($error->errors) $payload['errors'] = $error->errors;
        $payload += $error->extra;
    }
    api_json($payload, $status);
}

set_exception_handler('api_fail');

/**
 * Comprueba método, origen, tamaño, sesión y token CSRF.
 * Los scripts públicos (login, health) definen `const API_PUBLIC = true;` antes del require.
 * Los que modifican la sesión (login, logout, cambio de contraseña) definen `const API_SESSION_WRITE = true;`.
 */
function api_method(string $method): void
{
    api_headers();
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== $method) {
        header('Allow: ' . $method);
        throw new ApiException('Método no permitido.', 405);
    }
    if ($method === 'POST') {
        if (($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '') === 'cross-site') throw new ApiException('La solicitud debe realizarse desde esta aplicación.', 403, [], 'origin_rejected');
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if ($origin !== '') {
            $expected = (auth_is_https() ? 'https' : 'http') . '://' . auth_public_host();
            if (strcasecmp(rtrim((string) $origin, '/'), $expected) !== 0) throw new ApiException('El origen de la solicitud no está permitido.', 403, [], 'origin_rejected');
        }
        $maximum = ini_get('post_max_size');
        $bytes = (int) $maximum;
        $suffix = strtolower(substr($maximum, -1));
        if ($suffix === 'g') $bytes *= 1024 * 1024 * 1024;
        elseif ($suffix === 'm') $bytes *= 1024 * 1024;
        elseif ($suffix === 'k') $bytes *= 1024;
        if ($bytes > 0 && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > $bytes) throw new ApiException('La solicitud supera el tamaño máximo permitido.', 413);
    }
    if (!defined('API_PUBLIC') || !API_PUBLIC) {
        api_user();
        if ($method !== 'GET') auth_check_csrf();
    }
    if (!defined('API_SESSION_WRITE') || !API_SESSION_WRITE) auth_session_release();
}

/** Cuerpo de la petición: formulario o JSON (application/json). */
function api_input(): array
{
    static $input = null;
    if ($input !== null) return $input;
    $type = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
    if (str_starts_with($type, 'application/json')) {
        $raw = file_get_contents('php://input');
        $decoded = $raw !== false && $raw !== '' ? json_decode($raw, true) : [];
        if (!is_array($decoded)) throw new ApiException('El cuerpo de la solicitud no es JSON válido.', 400);
        // Normalizar escalares a texto, como llegan los formularios.
        $input = array_map(static fn($value) => is_bool($value) ? ($value ? '1' : '0') : (is_int($value) || is_float($value) ? (string) $value : $value), $decoded);
        return $input;
    }
    return $input = $_POST;
}

/** Lee pagina/por_pagina. Devuelve [pagina, por_pagina, offset]. */
function api_pagination(array $input, int $default, int $maximum, array &$errors): array
{
    $page = 1;
    $perPage = $default;
    if (isset($input['pagina']) && $input['pagina'] !== '') {
        $page = Config\Validation::positiveId($input['pagina']) ?? 0;
        if ($page < 1 || $page > 100000) { $errors['pagina'] = 'Indique un número de página válido.'; $page = 1; }
    }
    if (isset($input['por_pagina']) && $input['por_pagina'] !== '') {
        $perPage = Config\Validation::positiveId($input['por_pagina']) ?? 0;
        if ($perPage < 1 || $perPage > $maximum) { $errors['por_pagina'] = "Indique entre 1 y {$maximum} registros por página."; $perPage = $default; }
    }
    return [$page, $perPage, ($page - 1) * $perPage];
}

function api_validate(array $errors): void
{
    if ($errors) throw new ApiException('Revise los campos indicados.', 422, $errors);
}

function api_db(): PDO
{
    return Config\Database::getInstance()->getConnection();
}

function api_id($value, string $field = 'id'): int
{
    $id = Config\Validation::positiveId($value);
    if ($id === null) throw new ApiException('El identificador no es válido.', 422, [$field => 'Indique un identificador entero positivo.']);
    return $id;
}

function api_category(PDO $db, int $id): void
{
    $statement = $db->prepare('SELECT id FROM categorias_institucionales WHERE id = ?');
    $statement->execute([$id]);
    if (!$statement->fetchColumn()) throw new ApiException('La categoría seleccionada no existe.', 422, ['categoria' => 'Seleccione una categoría válida.']);
}

function api_upload_directory(): string
{
    return rtrim(getenv('UPLOAD_DIR') ?: dirname(__DIR__, 2) . '/storage/attachments', '/\\');
}
