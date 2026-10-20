<?php
declare(strict_types=1);

namespace Config;

use DateTimeImmutable;

/** Pure request validation shared by the API and regression tests. */
final class Validation
{
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

    public static function number(string $value, float $minimum, float $maximum): bool
    {
        return (bool) preg_match('/^\d+(?:\.\d+)?$/D', $value) && (float) $value >= $minimum && (float) $value <= $maximum;
    }
}
