<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/Morbilidad.php';

api_method('GET');
api_require('reportes.ver');

$db = api_db();
$filters = morbilidad_filtros($_GET, $db);
$rows = morbilidad_filas($db, $filters, null, false);
$start = $filters['fecha_inicio'];
$end = $filters['fecha_fin'];

/** Evita que una hoja de cálculo interprete el valor como fórmula (CSV/Excel formula injection). */
function csv_cell($value): string
{
    $raw = str_replace(["\r\n", "\r"], "\n", (string) ($value ?? ''));
    $text = trim($raw);
    if ($text !== '' && in_array($text[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
        $text = "'" . $text;
    }
    if (str_contains($text, "\n")) {
        $text = preg_replace('/\n(?=[\t ]*[=+\-@])/', "\n'", $text) ?? $text;
    }
    return $text;
}

/** Edad en años cumplidos a la fecha de la consulta. */
function csv_age(?string $birth, string $date): string
{
    if (!$birth) return '';
    $from = Config\Validation::date(substr($birth, 0, 10));
    $to = Config\Validation::date(substr($date, 0, 10));
    return $from && $to && $to >= $from ? (string) $from->diff($to)->y : '';
}

$output = fopen('php://temp', 'w+');
if ($output === false) throw new ApiException('No fue posible generar el archivo.', 500);
fwrite($output, "\xEF\xBB\xBF");
fputcsv($output, ['Fecha', 'Cédula', 'Apellidos', 'Nombres', 'Sexo', 'Edad', 'Categoría', 'Motivo', 'Diagnóstico', 'TA', 'Peso/Talla'], ';', '"', '');
foreach ($rows as $row) {
    $date = substr((string) $row['fecha_circunstancia'], 0, 10);
    $formatted = preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $parts) ? "{$parts[3]}/{$parts[2]}/{$parts[1]}" : $date;
    fputcsv($output, array_map('csv_cell', [
        $formatted, $row['cedula'], $row['apellidos'], $row['nombres'], $row['sexo'],
        csv_age($row['fecha_nacimiento'], $date), $row['categoria'], $row['motivo_consulta'],
        $row['diagnostico'], $row['vital_ta'], $row['vital_peso_talla'],
    ]), ';', '"', '');
}
rewind($output);
$content = stream_get_contents($output);
fclose($output);

api_audit('exportar_morbilidad', 'reporte', null, morbilidad_audit_detail($filters, count($rows)));
http_response_code(200);
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="morbilidad_' . $start . '_' . $end . '.csv"');
header('Content-Length: ' . strlen((string) $content));
echo $content;
exit;
