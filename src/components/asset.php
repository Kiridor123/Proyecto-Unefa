<?php
declare(strict_types=1);

if (!function_exists('asset_url')) {
    /** Añade la fecha de modificación como versión para que el navegador no use copias antiguas tras una actualización. */
    function asset_url(string $path): string
    {
        $file = dirname(__DIR__) . '/' . ltrim($path, '/');
        $version = is_file($file) ? (string) filemtime($file) : '1';
        return htmlspecialchars($path . '?v=' . $version, ENT_QUOTES, 'UTF-8');
    }
}
