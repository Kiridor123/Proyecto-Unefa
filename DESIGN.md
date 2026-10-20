---
name: Salud UNEFA
description: Sistema clínico para la atención universitaria
colors:
  primary: "#202a30"
  neutral-bg: "#f6f8f8"
  surface: "#ffffff"
  raised: "#f1f4f4"
  active: "#e7eded"
  border: "#e0e6e6"
  control: "#c4cece"
  ink: "#202a30"
  muted: "#5c696e"
  accent: "#256e78"
  negative: "#a6353a"
  positive: "#236b50"
  pending-bg: "#fff5de"
  pending-ink: "#7b5819"
  completed-bg: "#e9f4ee"
  completed-ink: "#236346"
  cancelled-bg: "#f1f3f3"
  cancelled-ink: "#5c686d"
  primary-hover: "#37454b"
  control-hover: "#9baaaa"
  selection-bg: "#dceced"
  selection-ink: "#123f45"
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
    lineHeight: 1.5
    letterSpacing: "-0.025em"
  meta:
    fontFamily: "Geist, system-ui, sans-serif"
    fontSize: "11px"
    fontWeight: 400
    lineHeight: 1.5
  title-mobile:
    fontFamily: "Geist, system-ui, sans-serif"
    fontSize: "23px"
    fontWeight: 500
    lineHeight: 1.25
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

**Creative North Star: "Índice académico clínico"**

La interfaz prioriza la lectura prolongada de información médica y el acceso reconocible a pacientes, consultas, citas y reportes. Conserva el nombre y los términos institucionales; cada pantalla usa controles web familiares y un título que nombra su tarea.

La identidad se expresa en una superficie fría clara, una tipografía precisa y una sola tinta clínica sobre los datos gráficos. La navegación recede mientras los nombres, las cifras y las acciones mantienen jerarquía.

**Key Characteristics:**

- Navegación etiquetada y consistente entre las cuatro áreas.
- Superficies planas con divisiones suaves.
- Cifras tabulares y estados descritos con palabras.
- Acciones principales oscuras y gráficos en teal.
- Geist alojada en el proyecto.

## Colors

La tinta oscura organiza las acciones; los neutrales fríos sostienen la lectura y el teal concentra la atención en los gráficos.

### Primary

La tinta clínica se usa en texto, títulos y botones principales. El teal se reserva para las series de datos, el foco, los enlaces clínicos y la marca lineal.

### Neutral

El fondo frío diferencia el lienzo de las superficies blancas. Los bordes separan regiones y los controles usan una línea un grado más fuerte. El texto secundario conserva contraste legible.

Los estados pendientes usan ámbar pálido, los completados verde pálido y los cancelados gris. La palabra del estado acompaña siempre al color.

**The Data Color Rule.** El color de una gráfica identifica los datos; no se repite como fondo de indicadores ni de navegación.

## Typography

Geist se carga desde src/fonts/geist-latin.woff2 bajo la licencia OFL. El sistema usa una sola familia; los identificadores conservan la misma voz del texto.

El título de pantalla es de peso medio; las cifras grandes son regulares. Las tablas usan números tabulares y etiquetas en estilo de oración. El texto secundario se mantiene entre once y doce píxeles; las filas de datos se leen a trece o catorce.

## Layout

El escritorio usa una barra lateral de doscientos cuarenta píxeles y un lienzo fluido con margen de treinta y seis. Bajo mil doscientos píxeles, la barra pasa a doscientos veinte y el margen a veinticuatro. El máximo de contenido es mil seiscientos píxeles, incluyendo márgenes. Las secciones usan una separación constante; los encabezados de panel tienen relleno de veinte por veintidós.

Por debajo de mil veinticuatro píxeles, el menú se pliega en un panel superpuesto. Bajo setecientos sesenta y ocho, el móvil usa márgenes de dieciocho píxeles, botones y campos de al menos cuarenta y cuatro de alto y métricas distribuidas en dos columnas. Las tablas se transforman en listas que conservan la identidad, los metadatos y las acciones de sus registros.

**The Scope Rule.** Cada conjunto de filtros se sitúa directamente sobre los resultados que modifica; su conteo corresponde a la selección.

## Elevation & Depth

Las superficies en reposo tienen un borde y no elevación. Las sombras pertenecen a los diálogos, el panel de historia clínica y las notificaciones. Los fondos de bloqueo son opacos y no aplican desenfoque decorativo.

La animación responde a abrir y cerrar un diálogo o un menú y al cambio de estado de los controles. Las gráficas se dibujan sin animación de entrada. El contenido respeta movimiento reducido.

## Shapes

Los paneles usan una curvatura corta; controles y etiquetas usan una curvatura menor. Los diálogos suavizan ligeramente las esquinas. Las iniciales del expediente se muestran en un disco neutral como identificación de una persona.

## Components

### Buttons

El botón principal es oscuro con texto blanco; el secundario es blanco con línea visible. La variante de texto es transparente y gana una superficie neutral al pasar el puntero. Los botones tienen una altura mínima, no fija, que aumenta en móvil. Las acciones repetidas de una fila son compactas y conservan sus nombres. El foco es una línea teal desplazada, presente también en enlaces y campos.

### Inputs

Los campos tienen etiquetas vinculadas, borde de control y fondo blanco. El error se muestra junto al campo y se expone con aria-invalid; los mensajes de ayuda usan texto secundario. En móvil los campos de escritura mantienen dieciséis píxeles; las fechas del reporte usan catorce.

### Navigation

Los cuatro destinos tienen iconos de una sola familia Phosphor y etiquetas visibles. El destino seleccionado usa una superficie gris neutral y aria-current. El menú móvil conserva los mismos nombres.

### Data panels

Los indicadores comparten una sola franja dividida. Las tablas usan líneas suaves y encabezados sin bandas de color. Las gráficas conservan unidades, ejes y datos reales. Las ausencias, la carga y los fallos tienen mensajes propios y permiten continuar o reintentar.

### Clinical forms

Los formularios clínicos se organizan en secciones desplegables. El registro y la agenda usan los mismos campos y botones que el resto de la aplicación. La historia se abre en un panel lateral con acciones de consulta, cita e impresión.

## Do's and Don'ts

### Do:

- **Do** conservar la identidad por cédula, nombre y categoría en escritorio y móvil.
- **Do** presentar las cifras reales desde las APIs y describir los datos ausentes.
- **Do** usar el mismo foco visible en campos, enlaces y botones.
- **Do** reservar la elevación a las superficies que flotan sobre el trabajo.

### Don't:

- **Don't** convertir los indicadores en tarjetas con iconos de colores.
- **Don't** utilizar información clínica o personas inventadas como decoración.
- **Don't** ocultar categorías, unidades o diagnósticos al adaptar una lista al móvil.
- **Don't** usar gradientes, vidrio, tipografía diminuta o animaciones de entrada en el trabajo diario.
