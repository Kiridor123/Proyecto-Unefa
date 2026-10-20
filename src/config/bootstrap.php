<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Validation.php';

ini_set('display_errors', '0');
ini_set('log_errors', '1');
$appTimezone = getenv('APP_TIMEZONE') ?: 'America/Caracas';
date_default_timezone_set(in_array($appTimezone, DateTimeZone::listIdentifiers(), true) ? $appTimezone : 'America/Caracas');

final class ApiException extends RuntimeException
{
    public int $status;
    public array $errors;

    public function __construct(string $message, int $status = 422, array $errors = [])
    {
        parent::__construct($message);
        $this->status = $status;
        $this->errors = $errors;
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
        // Never log request bodies, SQL arguments or clinical information.
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
    if ($error instanceof ApiException && $error->errors) $payload['errors'] = $error->errors;
    api_json($payload, $status);
}

set_exception_handler('api_fail');

function api_method(string $method): void
{
    api_headers();
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== $method) {
        header('Allow: ' . $method);
        throw new ApiException('Método no permitido.', 405);
    }
    if ($method !== 'POST') return;
    if (($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '') === 'cross-site') throw new ApiException('La solicitud debe realizarse desde esta aplicación.', 403);
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin !== '') {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $expected = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? '');
        if (strcasecmp(rtrim($origin, '/'), $expected) !== 0) throw new ApiException('El origen de la solicitud no está permitido.', 403);
    }
    $maximum = ini_get('post_max_size');
    $bytes = (int) $maximum;
    $suffix = strtolower(substr($maximum, -1));
    if ($suffix === 'g') $bytes *= 1024 * 1024 * 1024;
    elseif ($suffix === 'm') $bytes *= 1024 * 1024;
    elseif ($suffix === 'k') $bytes *= 1024;
    if ($bytes > 0 && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > $bytes) throw new ApiException('La solicitud supera el tamaño máximo permitido.', 413);
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
