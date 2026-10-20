<?php
declare(strict_types=1);

/** Validate uploads before beginning a database transaction. */
function api_attachment_input(?array $file): ?array
{
    if ($file === null) return null;
    if (!isset($file['error']) || !is_int($file['error'])) throw new ApiException('El archivo adjunto no es válido.', 422, ['archivo' => 'Seleccione un solo archivo.']);
    if ($file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if (in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) throw new ApiException('El archivo supera el límite permitido de 10 MB.', 413, ['archivo' => 'El tamaño máximo es 10 MB.']);
    if ($file['error'] !== UPLOAD_ERR_OK) throw new ApiException('El archivo no se recibió completo. Vuelva a seleccionarlo.', 422, ['archivo' => 'No se pudo recibir el archivo.']);
    if (!is_string($file['tmp_name'] ?? null) || !is_uploaded_file($file['tmp_name'])) throw new ApiException('El archivo adjunto no es válido.', 422);
    $size = filesize($file['tmp_name']);
    if ($size === false || $size === 0) throw new ApiException('El archivo está vacío.', 422, ['archivo' => 'Seleccione un archivo con contenido.']);
    if ($size > 10 * 1024 * 1024) throw new ApiException('El archivo supera el límite permitido de 10 MB.', 413, ['archivo' => 'El tamaño máximo es 10 MB.']);
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf'];
    if (!isset($extensions[$mime])) throw new ApiException('Solo se permiten imágenes JPG, PNG o documentos PDF.', 422, ['archivo' => 'El tipo real del archivo no está permitido.']);
    if (str_starts_with($mime, 'image/')) {
        $dimensions = @getimagesize($file['tmp_name']);
        if (!$dimensions || ($dimensions['mime'] ?? '') !== $mime) throw new ApiException('La imagen adjunta no es válida.', 422, ['archivo' => 'Seleccione una imagen válida.']);
    }
    return ['temporary' => $file['tmp_name'], 'mime' => $mime, 'name' => bin2hex(random_bytes(24)) . '.' . $extensions[$mime]];
}

function api_attachment_store(array $file): string
{
    $directory = api_upload_directory();
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) throw new RuntimeException('Attachment directory unavailable.');
    $destination = $directory . DIRECTORY_SEPARATOR . $file['name'];
    if (!move_uploaded_file($file['temporary'], $destination)) throw new RuntimeException('Unable to store attachment.');
    chmod($destination, 0640);
    return $destination;
}

function api_attachment_path(string $stored): ?string
{
    if (preg_match('~^private/([a-f0-9]{48}\.(?:jpg|png|pdf))$~D', $stored, $match)) {
        $base = realpath(api_upload_directory());
        $candidate = $base ? realpath($base . DIRECTORY_SEPARATOR . $match[1]) : false;
    } elseif (preg_match('~^uploads/([^/\\\\]+\.(?:jpe?g|png|pdf))$~iD', $stored, $match)) {
        $base = realpath(dirname(__DIR__) . '/uploads');
        $candidate = $base ? realpath($base . DIRECTORY_SEPARATOR . $match[1]) : false;
    } else {
        return null;
    }
    if (!$base || !$candidate || !str_starts_with($candidate, $base . DIRECTORY_SEPARATOR) || !is_file($candidate)) return null;
    return $candidate;
}
