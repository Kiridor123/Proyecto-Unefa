<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../config/Morbilidad.php';

api_method('GET');
api_require('reportes.ver');
[$start, $end] = morbilidad_rango($_GET);
$rows = morbilidad_filas(api_db(), $start, $end);

/** Evita que una hoja de cálculo interprete el valor como fórmula. */
function csv_cell($value): string
{
    $text = str_replace(["\r\n", "\r"], "\n", trim((string) ($value ?? '')));
    if ($text !== '' && in_array($text[0], ['=', '+', '-', '@', "\t"], true)) $text = "'" . $text;
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

api_audit('exportar_morbilidad', 'reporte', null, ['fecha_inicio' => $start, 'fecha_fin' => $end, 'registros' => count($rows)]);
http_response_code(200);
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="morbilidad_' . $start . '_' . $end . '.csv"');
header('Content-Length: ' . strlen((string) $content));
echo $content;
exit;
