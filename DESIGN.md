---
name: Salud UNEFA
description: Sistema clínico institucional para la atención universitaria UNEFA
colors:
  primary: "#0f2a56"
  primary-hover: "#183d7a"
  unefa-navy: "#0c234b"
  unefa-navy-elevated: "#173568"
  unefa-navy-muted: "#b8c9e4"
  unefa-gold: "#d49b1a"
  unefa-gold-bright: "#f0be49"
  accent: "#1a4b8c"
  accent-soft: "#e7effb"
  accent-ink: "#103263"
  neutral-bg: "#f4f6fa"
  surface: "#ffffff"
  raised: "#edf1f7"
  active: "#e1e8f4"
  border: "#dce3ee"
  control: "#bcc7d9"
  control-hover: "#8c9db8"
  ink: "#101d33"
  muted: "#4e5e76"
  negative: "#a1242b"
  positive: "#1d694d"
  pending-bg: "#fdf4dc"
  pending-ink: "#734d06"
  completed-bg: "#e7f4ee"
  completed-ink: "#195b42"
  cancelled-bg: "#edf1f7"
  cancelled-ink: "#4e5e76"
  overdue-bg: "#fcebed"
  overdue-ink: "#8b1d23"
  selection-bg: "#dce8fa"
  selection-ink: "#0c234b"
typography:
  title:
    fontFamily: "Geist, system-ui, sans-serif"
    fontSize: "24px"
    fontWeight: 500
    lineHeight: 1.25
    letterSpacing: "-0.025em"
  section:
    fontFamily: "Geist, system-ui, sans-serif"
    fontSize: "16px"
    fontWeight: 500
    lineHeight: 1.4
  metric:
    fontFamily: "Geist, system-ui, sans-serif"
    fontSize: "30px"
    fontWeight: 400
    lineHeight: 1.5
    letterSpacing: "-0.035em"
  body:
    fontFamily: "Geist, system-ui, sans-serif"
    fontSize: "14px"
    fontWeight: 400
    lineHeight: 1.5
  label:
    fontFamily: "Geist, system-ui, sans-serif"
    fontSize: "12px"
    fontWeight: 500
    lineHeight: 1.5
  row:
    fontFamily: "Geist, system-ui, sans-serif"
    fontSize: "13px"
    fontWeight: 400
    lineHeight: 1.5
  panel:
    fontFamily: "Geist, system-ui, sans-serif"
    fontSize: "15px"
    fontWeight: 500
    lineHeight: 1.4
  dialog:
    fontFamily: "Geist, system-ui, sans-serif"
    fontSize: "18px"
    fontWeight: 500
    lineHeight: 1.4
  brand:
    fontFamily: "Geist, system-ui, sans-serif"
    fontSize: "17px"
    fontWeight: 600
    lineHeight: 1.25
    letterSpacing: "-0.025em"
  meta:
    fontFamily: "Geist, system-ui, sans-serif"
    fontSize: "11px"
    fontWeight: 400
    lineHeight: 1.5
  title-mobile:
    fontFamily: "Geist, system-ui, sans-serif"
    fontSize: "20px"
    fontWeight: 500
    lineHeight: 1.2
  metric-mobile:
    fontFamily: "Geist, system-ui, sans-serif"
    fontSize: "28px"
    fontWeight: 400
    lineHeight: 1.5
rounded:
  chip: "4px"
  panel: "8px"
  control: "6px"
  dialog: "10px"
spacing:
  xs: "8px"
  sm: "12px"
  md: "16px"
  lg: "24px"
  gutter: "36px"
components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.surface}"
    rounded: "{rounded.control}"
    padding: "8px 13px"
  button-secondary:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    rounded: "{rounded.control}"
    padding: "8px 13px"
  button-text:
    backgroundColor: "transparent"
    textColor: "{colors.muted}"
    rounded: "{rounded.control}"
    padding: "8px"
  panel:
    backgroundColor: "{colors.surface}"
    rounded: "{rounded.panel}"
---

# Design System: Salud UNEFA

## Overview

**Creative North Star: "Índice institucional clínico · UNEFA"**

La interfaz prioriza la lectura prolongada de información médica y el acceso reconocible a pacientes, consultas, citas, reportes de morbilidad y administración, anclada en la identidad institucional oficial de la UNEFA. Conserva el nombre, los términos institucionales y el escudo oficial; cada pantalla mantiene la calma clínica del modo Operate con controles web familiares y jerarquía inequívoca.

La identidad institucional se expresa mediante una barra lateral azul marino UNEFA (`#0c234b`) rematada por una fina línea superior dorada (`#d49b1a`) y el escudo oficial transparente (`src/assets/logo-unefa.png`), mientras el lienzo clínico permanece en porcelana fría clara (`#f4f6fa`) con paneles blancos (`#ffffff`), tinta azul marino profundo (`#101d33`) y acentos azul real (`#1a4b8c`).

**Key Characteristics:**

- Identidad institucional UNEFA reconocible en la barra lateral azul marino, el encabezado móvil, el acceso de sesión y el favicon, usando el escudo oficial con transparencia real.
- Lienzo clínico calmo en porcelana clara con superficies blancas y divisiones sutiles tintadas al azul institucional.
- Cifras tabulares en azul marino institucional y estados descritos siempre con color y palabras.
- Acciones principales en azul marino UNEFA (`#0f2a56`), foco y gráficas en azul real (`#1a4b8c`), dorado institucional (`#d49b1a`) reservado a detalles estructurales de marca y alertas pendientes, y carmesí heráldico (`#a1242b`) para errores y vencimientos.
- Tipografía Geist alojada localmente en el proyecto.

## Colors

La paleta nace directamente de los colores heráldicos del escudo de la UNEFA (azul institucional profundo, dorado del borde y antorcha, carmesí de la cinta tricolor), calibrados para jornadas clínicas prolongadas con contraste WCAG AA/AAA en todos los estados.

### Institutional & Primary

- **Azul Marino UNEFA (`#0c234b`)**: superficie dedicada de la barra lateral de navegación y del panel de identidad en inicio de sesión. El texto secundario sobre azul marino usa azul claro tintado (`#b8c9e4`, contraste 9.3:1), nunca gris neutro.
- **Azul Institucional Primario (`#0f2a56`, hover `#183d7a`)**: botones de acción principal, cifras de indicadores KPI, pestañas activas de administración e iniciales de identidad.
- **Azul Real UNEFA (`#1a4b8c`, tinte `#e7effb`, tinta `#103263`)**: anillo de foco visible, enlaces clínicos, cursores de escritura, selecciones activas y serie principal de las gráficas en Chart.js.
- **Dorado Institucional (`#d49b1a`, brillo sobre marino `#f0be49`, tinte `#fdf4dc`, tinta `#734d06`)**: remate estructural superior de 3–4 px en navegación y acceso, icono activo en barra oscura, foco accesible sobre superficies azul marino y estados pendientes o reposos por vencer. Nunca se usa texto amarillo claro sobre fondo blanco.
- **Carmesí Heráldico (`#a1242b`, tinte `#fcebed`, tinta `#8b1d23`)**: errores de validación, citas vencidas y reposos que vencen hoy.

### Neutral

Los neutrales del lienzo están suavemente tintados hacia el azul institucional (`#f4f6fa` fondo, `#ffffff` paneles, `#edf1f7` superficies elevadas, `#dce3ee` bordes, `#bcc7d9` controles). El texto principal usa tinta azul oscuro (`#101d33`, 16.7:1 sobre blanco) y el secundario `#4e5e76` (6.4:1 sobre blanco).

Los estados completados usan verde clínico (`#e7f4ee` / `#195b42`), los pendientes dorado suave (`#fdf4dc` / `#734d06`), los cancelados gris azulado (`#edf1f7` / `#4e5e76`) y los vencidos carmesí suave (`#fcebed` / `#8b1d23`). La palabra del estado acompaña siempre al color.

**The Data Color Rule.** El azul real de las gráficas (`#1a4b8c`, hover `#0f2a56`) identifica los datos; no se compite con paletas multicolores ni fondos decorativos detrás de indicadores.

## Brand Assets

- `src/assets/logo-unefa.png`: escudo oficial de la UNEFA derivado de `src/uploads/logo_unefa_1.png` con recorte preciso, proporción heráldica natural (`256×296`) y fondo transparente limpio para renderizar sin recuadros blancos tanto sobre azul marino (`#0c234b`) como sobre superficies claras (`#f4f6fa` / `#ffffff`).
- `src/assets/favicon-unefa.png`: versión cuadrada centrada (`128×128`) con transparencia para `<link rel="icon">` y `<link rel="apple-touch-icon">`.
- `src/uploads/logo_unefa_1.png` y `src/uploads/logo_unefa_2.png`: se conservan intactos para los documentos clínicos oficiales imprimibles (`src/api/print_*.php`).

## Typography

Geist se carga desde `src/fonts/geist-latin.woff2` bajo licencia OFL. El sistema usa una sola familia; los identificadores y cifras tabulares conservan la misma voz del texto.

El título de pantalla es de peso medio (`24px`, `22px` en móvil); las cifras grandes son regulares (`30px`, `28px` en móvil) con `tabular-nums`. Las tablas usan números tabulares y etiquetas en estilo de oración. El texto secundario se mantiene entre `11px` y `12px`; las filas de datos se leen a `13px` o `14px`.

## Layout

El escritorio usa una barra lateral institucional de `240px` y un lienzo fluido con margen de `36px`. Bajo `1200px`, la barra pasa a `220px` y el margen a `24px`. El máximo de contenido es `1600px`, incluyendo márgenes.

Por debajo de `1024px`, la barra lateral azul marino se pliega en un panel deslizable y el encabezado muestra el escudo compacto de la UNEFA junto al título de pantalla. Bajo `768px`, el móvil usa márgenes de `18px`, botones y campos de al menos `44px` de alto y métricas en dos columnas. Las tablas se transforman en listas con identidad, metadatos y acciones visibles.

**The Scope Rule.** Cada conjunto de filtros se sitúa directamente sobre los resultados que modifica; su conteo corresponde a la selección.

## Elevation & Depth

Las superficies en reposo usan borde de `1px` y sin sombra. Las sombras tintadas al azul marino (`rgba(12, 35, 75, .18)`) pertenecen únicamente a diálogos, el panel lateral de historia clínica, menús emergentes y notificaciones. Los fondos de bloqueo son semitransparentes oscuros sin desenfoque decorativo.

La animación responde solo al estado de diálogos, menús y controles (`150–240ms`). Las gráficas se dibujan sin animación de entrada y el contenido respeta `prefers-reduced-motion`.

## Shapes

Los paneles usan radio de `8px`; controles y botones `6px`; etiquetas de estado `4px`; diálogos `10px`. Las iniciales del paciente y del usuario se muestran en un disco tintado al azul institucional.

## Components

### Buttons

El botón principal usa azul marino institucional (`#0f2a56`, hover `#183d7a`) con texto blanco; el secundario es blanco con borde visible (`#bcc7d9`) y texto en tinta oscura. La variante de texto es transparente y gana superficie `#e1e8f4` al pasar el puntero. El foco sobre superficies claras es un contorno azul real (`#1a4b8c`) de `2px` con `outline-offset: 3px`; sobre superficies azul marino usa dorado brillante (`#f0be49`).

### Inputs

Los campos tienen etiquetas vinculadas, borde `#bcc7d9`, fondo blanco, cursor azul real y placeholder con contraste AA (`#5c6d85`). Los errores se muestran en línea en carmesí (`#a1242b`) junto al campo y con `aria-invalid`.

### Navigation

La barra lateral azul marino UNEFA exhibe el escudo institucional, el nombre Salud UNEFA y los destinos de atención con iconos Phosphor y etiquetas visibles. El destino activo se distingue mediante superficie azul elevada (`#173568`), borde sutil dorado (`rgba(240,190,73,.32)`), icono dorado (`#f0be49`) y `aria-current="page"`.

### Data panels

Los indicadores comparten una sola franja dividida con cifras en azul marino institucional. Las tablas usan líneas suaves, encabezados sin bandas ruidosas y resaltado de fila `#f7f9fd`. Las gráficas de Chart.js consumen directamente los tokens CSS de la paleta UNEFA.

### Clinical forms

Los formularios clínicos se organizan en secciones desplegables con el mismo vocabulario de controles. La historia clínica se abre en un panel lateral con acciones de consulta, cita e impresión.

## Do's and Don'ts

### Do:

- **Do** usar el escudo oficial transparente (`src/assets/logo-unefa.png`) con proporción correcta y texto alternativo descriptivo.
- **Do** conservar el contraste AA/AAA usando texto secundario azul claro tintado (`#b8c9e4`) sobre superficies azul marino y nunca gris lavado.
- **Do** mantener los estados clínicos descritos siempre por texto además de color.
- **Do** reservar el dorado institucional para acentos estructurales, foco en marino y estados pendientes sin convertir la pantalla en decoración.

### Don't:

- **Don't** colocar logos con fondo blanco opaco sobre fondos de color o sin recortar.
- **Don't** usar texto amarillo o dorado claro sobre fondos blancos o claros.
- **Don't** convertir los indicadores en tarjetas ruidosas o agregar gradientes y vidrio decorativo al trabajo clínico diario.
- **Don't** utilizar datos o pacientes ficticios como relleno visual.
