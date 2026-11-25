<?php
declare(strict_types=1);

/*
 * Utilidades compartidas por los documentos imprimibles (historia, informe, reposo, récipe y morbilidad).
 * Solo define funciones: no produce salida ni consulta la base de datos por sí mismo.
 */

if (realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    http_response_code(404);
    exit;
}

const PRINT_INSTITUCION = 'Universidad Nacional Experimental Politécnica de la Fuerza Armada Nacional Bolivariana';

/** Escapa un valor para HTML. */
function print_e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Indica si un valor está vacío (NULL, '' o solo espacios). */
function print_blank($value): bool
{
    return trim((string) ($value ?? '')) === '';
}

/** Valor de una línea escapado, o el texto alternativo si está vacío. */
function print_val($value, string $empty = 'No registrado'): string
{
    return print_blank($value) ? print_e($empty) : print_e(trim((string) $value));
}

/** Texto libre multilínea: escapa y conserva los saltos de línea. */
function print_text($value, string $empty = 'No registrado'): string
{
    if (print_blank($value)) return print_e($empty);
    $text = str_replace(["\r\n", "\r"], "\n", trim((string) $value));
    return nl2br(print_e($text), false);
}

/** Primer valor no vacío de la lista, o NULL. */
function print_first(...$values): ?string
{
    foreach ($values as $value) {
        if (!print_blank($value)) return trim((string) $value);
    }
    return null;
}

/** Crea un DateTimeImmutable a partir de una fecha de la base de datos, o NULL si no es válida. */
function print_date($value): ?DateTimeImmutable
{
    if (print_blank($value)) return null;
    try {
        return new DateTimeImmutable((string) $value);
    } catch (Throwable $error) {
        return null;
    }
}

/** Fecha corta dd/mm/aaaa. */
function print_fecha_corta($value, string $empty = 'No registrada'): string
{
    $date = $value instanceof DateTimeInterface ? $value : print_date($value);
    return $date ? $date->format('d/m/Y') : $empty;
}

/** Fecha larga en español: «9 de noviembre de 2026». */
function print_fecha_larga(DateTimeInterface $date): string
{
    $meses = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    return $date->format('j') . ' de ' . $meses[(int) $date->format('n')] . ' de ' . $date->format('Y');
}

/** Edad cumplida en años a una fecha de referencia (o a hoy), o NULL si no hay fecha de nacimiento. */
function print_edad($fechaNacimiento, $fechaReferencia = null): ?int
{
    $nacimiento = print_date($fechaNacimiento);
    if (!$nacimiento) return null;
    $referencia = ($fechaReferencia instanceof DateTimeInterface ? $fechaReferencia : print_date($fechaReferencia)) ?? new DateTimeImmutable('today');
    if ($referencia < $nacimiento) return null;
    return $nacimiento->diff($referencia)->y;
}

/**
 * Fecha de emisión de un documento ligado a una consulta: la del registro de la consulta,
 * o la fecha de la consulta si falta. Nunca la fecha en que se imprime.
 */
function print_fecha_emision(array $consulta): DateTimeImmutable
{
    return print_date($consulta['fecha_registro'] ?? null)
        ?? print_date($consulta['fecha_circunstancia'] ?? null)
        ?? new DateTimeImmutable('today');
}

/** Texto discreto «Reimpreso el …» cuando el día de impresión no coincide con el de emisión. */
function print_reimpresion(DateTimeInterface $emision): ?string
{
    $hoy = new DateTimeImmutable('now');
    if ($hoy->format('Y-m-d') === $emision->format('Y-m-d')) return null;
    return 'Reimpreso el ' . $hoy->format('d/m/Y') . ' a las ' . $hoy->format('H:i') . '.';
}

/** «Nombre — Reg. X» del médico tratante, o NULL si la consulta no tiene médico asignado. */
function print_medico(array $row): ?string
{
    $nombre = print_first($row['medico_nombre'] ?? null);
    if ($nombre === null) return null;
    $registro = print_first($row['medico_registro'] ?? null);
    return $registro === null ? $nombre : $nombre . ' — Reg. ' . $registro;
}

/** Número entero (0–999) en letras, en la forma apocopada que precede a un sustantivo masculino («veintiún días»). */
function print_numero_letras(int $numero): string
{
    if ($numero < 0 || $numero > 999) return (string) $numero;
    if ($numero === 0) return 'cero';
    $unidades = ['', 'un', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve', 'diez',
        'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve', 'veinte',
        'veintiún', 'veintidós', 'veintitrés', 'veinticuatro', 'veinticinco', 'veintiséis', 'veintisiete', 'veintiocho', 'veintinueve'];
    $decenas = [3 => 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'];
    $centenas = [1 => 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos'];

    $partes = [];
    $c = intdiv($numero, 100);
    $resto = $numero % 100;
    if ($c > 0) $partes[] = ($numero === 100) ? 'cien' : $centenas[$c];
    if ($resto > 0) {
        if ($resto < 30) {
            $partes[] = $unidades[$resto];
        } else {
            $d = intdiv($resto, 10);
            $u = $resto % 10;
            $partes[] = $decenas[$d] . ($u > 0 ? ' y ' . $unidades[$u] : '');
        }
    }
    return implode(' ', $partes);
}

/** Línea del encabezado institucional con logos. $logoDer puede ser NULL (un solo logo, como en la historia). */
function print_header(array $lineas, array $logoIzq, ?array $logoDer = null): void
{
    ?>
    <header class="doc-header">
        <img class="doc-logo" src="<?= print_e($logoIzq['src']) ?>" alt="<?= print_e($logoIzq['alt']) ?>">
        <div class="doc-membrete">
            <?php foreach ($lineas as $linea): ?>
                <div><?= print_e($linea) ?></div>
            <?php endforeach; ?>
        </div>
        <?php if ($logoDer): ?>
            <img class="doc-logo" src="<?= print_e($logoDer['src']) ?>" alt="<?= print_e($logoDer['alt']) ?>">
        <?php else: ?>
            <span class="doc-logo doc-logo--vacio" aria-hidden="true"></span>
        <?php endif; ?>
    </header>
    <?php
}

/** Encabezado institucional estándar con ambos sellos (UNEFA y MPPD), compartido por informe, reposo y récipe. */
function print_header_institucional(): void
{
    print_header([
        'República Bolivariana de Venezuela',
        'Ministerio del Poder Popular para la Defensa',
        PRINT_INSTITUCION,
        'Vicerrectorado Región Los Llanos',
        'Núcleo Portuguesa — Extensión Acarigua',
    ], print_logo('unefa'), print_logo('mppd'));
}

/**
 * Recuadros formales de firma y sello para documentos clínicos.
 * Cuando $incluirDirectora es true, dibuja dos recuadros (médico y directora);
 * cuando es false, centra un único recuadro para la firma del médico (p. ej. récipe).
 */
function print_firmas_recuadros(?string $medico, bool $incluirDirectora = true, string $claseExtra = ''): void
{
    $clases = trim('firmas-recuadros' . ($incluirDirectora ? '' : ' firmas-recuadros--uno') . ' ' . $claseExtra);
    ?>
    <div class="<?= print_e($clases) ?>">
        <div class="firma-recuadro">
            <div class="firma-recuadro-encabezado">Firma del médico</div>
            <div class="firma-recuadro-cuerpo">
                <div class="firma-recuadro-espacio" aria-hidden="true"></div>
                <div class="firma-recuadro-linea"></div>
                <div class="firma-recuadro-pie">
                    <?php if ($medico !== null): ?>
                        <span class="fuerte"><?= print_e($medico) ?></span>
                    <?php else: ?>
                        <span>Médico tratante: ______________________</span>
                    <?php endif; ?>
                    <span class="firma-recuadro-nota">Firma y sello médico</span>
                </div>
            </div>
        </div>
        <?php if ($incluirDirectora): ?>
            <div class="firma-recuadro">
                <div class="firma-recuadro-encabezado">Firma de la directora</div>
                <div class="firma-recuadro-cuerpo">
                    <div class="firma-recuadro-espacio" aria-hidden="true"></div>
                    <div class="firma-recuadro-linea"></div>
                    <div class="firma-recuadro-pie">
                        <span class="fuerte">Directora</span>
                        <span class="firma-recuadro-nota">Dirección UNEFA — Extensión Acarigua · Firma y sello</span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/** Logos disponibles (en uploads/, que solo expone los logo_*). */
function print_logo(string $tipo): array
{
    $logos = [
        'unefa' => ['src' => '../uploads/logo_unefa_1.png', 'alt' => 'Logo de la UNEFA'],
        'mppd' => ['src' => '../uploads/logo_unefa_2.png', 'alt' => 'Escudo del Ministerio del Poder Popular para la Defensa'],
        'historia' => ['src' => '../uploads/logo_historia.jpg', 'alt' => 'Logo de la UNEFA'],
    ];
    return $logos[$tipo] ?? $logos['unefa'];
}

/**
 * Abre el documento: <head>, estilos comunes y barra de acciones de pantalla.
 * Opciones: orientacion ('portrait'|'landscape'), margen (CSS de @page), boton (texto del botón), clase (clase extra de la hoja).
 */
function print_page_start(string $titulo, array $opciones = []): void
{
    $landscape = ($opciones['orientacion'] ?? 'portrait') === 'landscape';
    $margen = $opciones['margen'] ?? '1.5cm';
    $boton = $opciones['boton'] ?? 'Imprimir';
    $clase = $opciones['clase'] ?? '';
    // Ancho de la hoja en pantalla: Carta (21,59 × 27,94 cm).
    $anchoHoja = $landscape ? '27.94cm' : '21.59cm';
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= print_e($titulo) ?></title>
    <style>
        @page { size: letter <?= $landscape ? 'landscape' : 'portrait' ?>; margin: <?= $margen ?>; }
        *, *::before, *::after { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; }
        body {
            margin: 0;
            padding: 24px 16px 48px;
            background: #e6ebf2;
            color: #111827;
            font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
            font-size: 11pt;
            line-height: 1.4;
        }
        .toolbar { display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; margin: 0 auto 20px; max-width: <?= $anchoHoja ?>; }
        .toolbar button {
            min-height: 40px; padding: 8px 18px; border-radius: 8px; border: 1px solid #94a3b8;
            background: #fff; color: #0f2c59; font: 600 14px/1 Arial, sans-serif; cursor: pointer;
        }
        .toolbar button:hover { background: #f8fafc; border-color: #64748b; }
        .toolbar button.primario { background: #0f2c59; border-color: #0f2c59; color: #fff; }
        .toolbar button.primario:hover { background: #163a70; border-color: #163a70; }
        .toolbar button:focus-visible { outline: 3px solid #c99700; outline-offset: 2px; }
        .toolbar .aviso { flex-basis: 100%; text-align: center; color: #334155; font-size: 13px; }
        .hoja {
            background: #fff; margin: 0 auto; width: 100%; max-width: <?= $anchoHoja ?>;
            padding: <?= $margen ?>; box-shadow: 0 10px 30px rgba(15, 44, 89, .16); overflow-wrap: anywhere;
        }

        /* Encabezado institucional UNEFA */
        .doc-header {
            position: relative; display: flex; align-items: center; justify-content: space-between; gap: 12px;
            border-bottom: 2px solid #0f2c59; padding-bottom: 9px; margin-bottom: 14px;
        }
        .doc-header::after {
            content: ""; position: absolute; left: 0; right: 0; bottom: -5px; height: 1.5px; background: #c99700;
        }
        .doc-logo { height: 58px; width: auto; max-width: 84px; object-fit: contain; flex: 0 0 auto; }
        .doc-logo--vacio { width: 58px; }
        .doc-membrete { flex: 1; text-align: center; font-weight: 700; text-transform: uppercase; font-size: 8.5pt; line-height: 1.25; letter-spacing: .01em; color: #0f2c59; }
        .doc-titulo {
            text-align: center; margin: 14px 0 12px; font-size: 13.5pt; font-weight: 700; color: #0f2c59;
            letter-spacing: .1em; text-transform: uppercase;
            text-decoration: underline; text-decoration-color: #c99700; text-decoration-thickness: 1.5px; text-underline-offset: 5px;
        }

        /* Elementos comunes */
        .etq { font-weight: 700; color: #1e3a5f; }
        .vacio { color: #526071; font-style: italic; }
        .fuerte { font-weight: 700; }
        .derecha, .tabla .derecha { text-align: right; }
        .centro, .tabla .centro { text-align: center; }
        .justificado { text-align: justify; }
        .pequeno { font-size: 8.5pt; }
        .tabla { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .tabla th, .tabla td { border: 1px solid #475569; padding: 3px 5px; vertical-align: top; text-align: left; }
        .tabla th { font-weight: 700; background: #edf2f8; color: #0f2c59; }
        .seccion { margin-bottom: 10px; }
        .seccion-titulo {
            font-weight: 700; text-transform: uppercase; font-size: 9.5pt; padding: 3px 6px;
            border: 1px solid #475569; border-bottom: 0; background: #edf2f8; color: #0f2c59;
            break-after: avoid; page-break-after: avoid;
        }

        /* Firmas clásicas (historia clínica y morbilidad) */
        .firmas { display: flex; justify-content: space-around; gap: 24px; text-align: center; break-inside: avoid; page-break-inside: avoid; }
        .firma { flex: 1; max-width: 240px; display: flex; flex-direction: column; align-items: center; font-size: 8.5pt; font-weight: 700; color: #0f2c59; }
        .firma .linea { width: 100%; border-bottom: 1px solid #1e293b; height: 46px; margin-bottom: 4px; }
        .firma .detalle { font-weight: 400; color: #1f2937; margin-top: 2px; }

        /* Recuadros formales de firma y sello */
        .firmas-recuadros {
            display: grid; grid-template-columns: 1fr 1fr; gap: 18px;
            break-inside: avoid; page-break-inside: avoid;
        }
        .firmas-recuadros--uno { grid-template-columns: minmax(250px, 320px); justify-content: center; }
        .firma-recuadro {
            border: 1.25px solid #163a70; border-radius: 3px; background: #fff;
            display: flex; flex-direction: column; break-inside: avoid; page-break-inside: avoid;
        }
        .firma-recuadro-encabezado {
            background: #edf2f8; border-bottom: 1px solid #cbd5e1; color: #0f2c59;
            text-align: center; font-size: 8.5pt; font-weight: 700; text-transform: uppercase;
            letter-spacing: .04em; padding: 4px 8px;
        }
        .firma-recuadro-cuerpo {
            flex: 1; display: flex; flex-direction: column; justify-content: flex-end;
            padding: 8px 12px 9px; text-align: center;
        }
        .firma-recuadro-espacio { height: 54px; }
        .firma-recuadro-linea { width: 84%; margin: 0 auto 5px; border-bottom: 1px solid #1e293b; }
        .firma-recuadro-pie { display: flex; flex-direction: column; gap: 2px; font-size: 8.5pt; color: #111827; line-height: 1.25; }
        .firma-recuadro-nota { font-size: 7.5pt; color: #475569; }

        .reimpresion { margin-top: 10px; font-size: 7.5pt; color: #526071; text-align: right; }

        @media print {
            body { padding: 0 !important; margin: 0 !important; background: #fff !important; min-height: 0 !important; }
            .toolbar { display: none !important; }
            .hoja { max-width: none; width: 100%; padding: 0 !important; margin: 0 !important; box-shadow: none !important; }
            .doc-header::after, .tabla th, .seccion-titulo, .firma-recuadro-encabezado {
                -webkit-print-color-adjust: exact; print-color-adjust: exact;
            }
            a { color: inherit; text-decoration: none; }
        }
        @media screen and (max-width: 640px) {
            body { padding: 12px 0 32px; }
            .hoja { padding: 16px; }
            .firmas-recuadros { grid-template-columns: 1fr; }
        }
        <?= $opciones['css'] ?? '' ?>
    </style>
</head>
<body>
    <div class="toolbar" role="toolbar" aria-label="Acciones del documento">
        <button type="button" class="primario" data-accion="imprimir"><?= print_e($boton) ?></button>
        <button type="button" data-accion="cerrar">Cerrar</button>
        <p class="aviso">Para un resultado fiel, imprima en papel Carta con escala al 100 %.</p>
    </div>
    <main class="hoja <?= print_e($clase) ?>">
    <?php
}

/** Cierra la hoja y el documento; lanza el diálogo de impresión al cargar. */
function print_page_end(?string $reimpresion = null): void
{
    if ($reimpresion !== null): ?>
        <p class="reimpresion"><?= print_e($reimpresion) ?></p>
    <?php endif; ?>
    </main>
    <script>
        (function () {
            document.querySelectorAll('[data-accion]').forEach(function (boton) {
                boton.addEventListener('click', function () {
                    if (boton.dataset.accion === 'imprimir') window.print();
                    else window.close();
                });
            });
            window.addEventListener('load', function () {
                setTimeout(function () { window.print(); }, 400);
            });
        })();
    </script>
</body>
</html>
<?php
}
