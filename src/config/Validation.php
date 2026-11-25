<?php
declare(strict_types=1);

namespace Config;

use DateTimeImmutable;

/** Pure request validation shared by the API and regression tests. */
final class Validation
{
    public const SEARCH_MAX_LENGTH = 100;
    public const SEARCH_MAX_TOKENS = 6;

    /** Accent folding maps for vanilla PostgreSQL (no unaccent extension required). */
    public const SQL_ACCENT_FROM = 'áéíóúüàèìòùâêîôûãõñçÁÉÍÓÚÜÀÈÌÒÙÂÊÎÔÛÃÕÑÇ';
    public const SQL_ACCENT_TO   = 'aeiouuaeiouaeiouaoncaeiouuaeiouaeiouaonc';
    /** Backslash ESCAPE literal compatible with both PDO's SQL lexer and PostgreSQL. */
    public const SQL_LIKE_CLAUSE = "LIKE ? ESCAPE E'\\\\'";

    private const ACCENT_MAP = [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u',
        'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
        'â' => 'a', 'ê' => 'e', 'î' => 'i', 'ô' => 'o', 'û' => 'u',
        'ã' => 'a', 'õ' => 'o', 'ñ' => 'n', 'ç' => 'c',
    ];

    public static function text(array $input, string $key, int $maxLength, array &$errors, bool $required = false): string
    {
        $value = $input[$key] ?? '';
        if (!is_string($value) && !is_int($value)) {
            $errors[$key] = 'El campo debe contener texto válido.';
            return '';
        }
        $value = trim(str_replace("\r\n", "\n", (string) $value));
        if (!preg_match('//u', $value) || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value)) {
            $errors[$key] = 'El campo contiene caracteres no válidos.';
            return '';
        }
        if ($required && $value === '') {
            $errors[$key] = 'Este campo es obligatorio.';
        } elseif (preg_match_all('/./us', $value) > $maxLength) {
            $errors[$key] = "El campo admite como máximo {$maxLength} caracteres.";
        }
        return $value;
    }

    /**
     * Sanitizes and validates a search query string:
     * trims, strips control/zero-width chars, collapses whitespace, and enforces max length.
     */
    public static function search(array $input, string $key, array &$errors, int $maxLength = self::SEARCH_MAX_LENGTH): string
    {
        $value = $input[$key] ?? '';
        if (!is_string($value) && !is_int($value)) {
            $errors[$key] = 'El término de búsqueda debe contener texto válido.';
            return '';
        }
        $raw = (string) $value;
        if (strlen($raw) > max(4096, $maxLength * 8)) {
            $errors[$key] = "La búsqueda admite como máximo {$maxLength} caracteres.";
            return '';
        }
        if (!preg_match('//u', $raw)) {
            $errors[$key] = 'El término de búsqueda contiene caracteres no válidos.';
            return '';
        }
        // Convert tabs/line breaks to spaces, strip ASCII & C1/zero-width control characters, then collapse whitespace.
        $cleaned = preg_replace('/[\t\r\n]+/', ' ', $raw) ?? '';
        $cleaned = preg_replace('/[\x00-\x1F\x7F]+/', '', $cleaned) ?? '';
        $cleaned = preg_replace('/[\x{0080}-\x{009F}\x{200B}-\x{200F}\x{FEFF}]+/u', '', $cleaned) ?? '';
        $cleaned = trim(preg_replace('/\s+/u', ' ', $cleaned) ?? '');
        if ($cleaned !== '' && preg_match_all('/./us', $cleaned) > $maxLength) {
            $errors[$key] = "La búsqueda admite como máximo {$maxLength} caracteres.";
            return '';
        }
        return $cleaned;
    }

    /** Splits a sanitized search query into bounded non-empty tokens. */
    public static function searchTokens(string $search, int $maxTokens = self::SEARCH_MAX_TOKENS): array
    {
        $trimmed = trim($search);
        if ($trimmed === '') return [];
        $tokens = preg_split('/\s+/u', $trimmed, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        return array_slice($tokens, 0, max(1, $maxTokens));
    }

    /** Lowercases and removes accents/diacritics in PHP to match sqlNormalize(). */
    public static function normalizeSearchTerm(string $value): string
    {
        $lower = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
        $folded = strtr($lower, self::ACCENT_MAP);
        return preg_replace('/\p{Mn}+/u', '', $folded) ?? $folded;
    }

    /** Escapes LIKE/ILIKE wildcards (\, %, _) for use with ESCAPE '\'. */
    public static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /** Builds a case/accent-folded, wildcard-safe substring pattern for LIKE ... ESCAPE '\'. */
    public static function likePattern(string $token): string
    {
        return '%' . self::escapeLike(self::normalizeSearchTerm($token)) . '%';
    }

    /**
     * Normalizes a token for cédula matching (strips spaces, dots and hyphens).
     * Returns empty string if the token contains non-alphanumeric chars (e.g. wildcards or punctuation).
     */
    public static function cedulaSearchToken(string $token): string
    {
        $cleaned = strtoupper((string) preg_replace('/[\s.\-]+/u', '', $token));
        return $cleaned !== '' && preg_match('/^[A-Z0-9]+$/D', $cleaned) ? $cleaned : '';
    }

    /**
     * PostgreSQL accent- and case-insensitive normalization using translate() + lower().
     * Only pass trusted column expressions, never user input.
     */
    public static function sqlNormalize(string $expression): string
    {
        return "translate(lower(COALESCE({$expression}, '')), '" . self::SQL_ACCENT_FROM . "', '" . self::SQL_ACCENT_TO . "')";
    }

    public static function enum(
        array $input,
        string $key,
        array $allowed,
        array &$errors,
        string $errorMessage = 'Seleccione un valor válido.',
        int $maxLength = 50
    ): string {
        $value = self::text($input, $key, $maxLength, $errors);
        if ($value !== '' && !in_array($value, $allowed, true)) {
            $errors[$key] = $errorMessage;
            return '';
        }
        return $value;
    }

    public static function optionalPositiveId(
        array $input,
        string $key,
        array &$errors,
        string $errorMessage = 'Indique un identificador válido.'
    ): ?int {
        if (!array_key_exists($key, $input) || $input[$key] === '' || $input[$key] === null) {
            return null;
        }
        $id = self::positiveId($input[$key]);
        if ($id === null) {
            $errors[$key] = $errorMessage;
            return null;
        }
        return $id;
    }

    public static function positiveId($value): ?int
    {
        if ((!is_string($value) && !is_int($value)) || !preg_match('/^[1-9][0-9]{0,9}$/D', (string) $value)) return null;
        $id = (int) $value;
        return $id <= 2147483647 ? $id : null;
    }

    public static function cedula(string $value): string
    {
        return strtoupper(preg_replace('/\s+/u', '', $value));
    }

    public static function validCedula(string $value): bool
    {
        return (bool) preg_match('/^[VEJGCPN]-[0-9]{1,18}$/D', $value);
    }

    public static function date(string $value): ?DateTimeImmutable
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value) || (int) substr($value, 0, 4) === 0) return null;
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $date : null;
    }

    public static function dateTime(string $value): ?DateTimeImmutable
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}(?::\d{2})?$/D', $value) || (int) substr($value, 0, 4) === 0) return null;
        foreach (['Y-m-d\TH:i', 'Y-m-d\TH:i:s', 'Y-m-d H:i:s'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!' . $format, $value);
            if ($date && $date->format($format) === $value) return $date;
        }
        return null;
    }

    public static function dateRange(array $input, array &$errors): array
    {
        $start = self::text($input, 'fecha_inicio', 10, $errors, true);
        $end = self::text($input, 'fecha_fin', 10, $errors, true);
        if (!self::date($start)) $errors['fecha_inicio'] = 'Indique una fecha de inicio válida (AAAA-MM-DD).';
        if (!self::date($end)) $errors['fecha_fin'] = 'Indique una fecha de fin válida (AAAA-MM-DD).';
        if (!$errors && $start > $end) $errors['fecha_fin'] = 'La fecha de fin debe ser igual o posterior a la fecha de inicio.';
        return [$start, $end];
    }

    public static function optionalDateRange(
        array $input,
        array &$errors,
        string $startKey = 'desde',
        string $endKey = 'hasta',
        ?string $maxSpan = null
    ): array {
        $start = self::text($input, $startKey, 10, $errors);
        $end = self::text($input, $endKey, 10, $errors);
        $startDate = $start !== '' ? self::date($start) : null;
        $endDate = $end !== '' ? self::date($end) : null;
        if ($start !== '' && !$startDate) $errors[$startKey] = 'Indique una fecha válida (AAAA-MM-DD).';
        if ($end !== '' && !$endDate) $errors[$endKey] = 'Indique una fecha válida (AAAA-MM-DD).';
        if ($startDate && $endDate) {
            if ($end < $start) {
                $errors[$endKey] = 'La fecha final debe ser igual o posterior a la inicial.';
            } elseif ($maxSpan !== null && $endDate > $startDate->modify($maxSpan)) {
                $errors[$endKey] = 'El rango de fechas supera el máximo permitido.';
            }
        }
        return [$start, $end];
    }

    public static function number(string $value, float $minimum, float $maximum): bool
    {
        return (bool) preg_match('/^\d+(?:\.\d+)?$/D', $value) && (float) $value >= $minimum && (float) $value <= $maximum;
    }
}
