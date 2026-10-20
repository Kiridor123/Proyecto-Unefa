# Mejoras y verificación

Fecha: 20 de octubre de 2026.

Se conservaron PHP, PostgreSQL, los cuatro módulos y los formatos institucionales. El trabajo se repartió entre agentes de diseño, interacción, backend, revisión visual y documentación.

## Cambios

- Interfaz clínica consistente: navegación etiquetada, indicadores agrupados, tablas en escritorio, listas móviles, campos etiquetados y foco visible.
- Dirección `achromatic-ledger`, semilla `1683463685`, con estructura Open plane y un cambio de estructura para conservar etiquetas. Concepto: índice académico clínico, `seed:2dc76b0870da8ff7`. Las cuatro áreas mantienen acceso directo y equilibrado.
- Recursos del navegador locales: Tailwind compilado, Phosphor, SweetAlert2, Chart.js y Geist con sus licencias. Sin nuevas imágenes decorativas ni cifras clínicas inventadas.
- API con fechas e identificadores estrictos, métodos HTTP, errores controlados y rechazo de solicitudes POST externas.
- Guardado transaccional, protección contra citas duplicadas concurrentes, conservación de antecedentes y de citas futuras.
- Adjuntos privados comprobados por contenido, nombres generados y descargas por ID.
- Migraciones idempotentes, índices, comprobaciones de integridad y arranque condicionado a la disponibilidad de PostgreSQL.
- Búsquedas que descartan respuestas obsoletas y bloquean el guardado hasta resolver la historia clínica; recuperación explícita ante fallos.

## Resultados comprobados

| Comprobación | Resultado |
| --- | --- |
| Validación PHP | 58 aserciones aprobadas |
| Integración HTTP con PostgreSQL | 34 comprobaciones aprobadas |
| Interacción en Chrome | 40 comprobaciones aprobadas: 29 generales y 11 de búsquedas y guardado |
| Sintaxis PHP | 34 archivos sin errores |
| Sintaxis JavaScript | 5 módulos sin errores |
| Capturas finales | 18: cuatro áreas y dos diálogos a 1440, 768 y 390 px |
| Mediciones finales | Sin desbordamiento de página, excepciones JavaScript ni recursos externos; fuente local cargada |
| Inspector visual | 0 fallos; una advertencia aceptada por metadatos y ejes de 11–12 px |
| Revisión visual independiente | `ship`, revisión completa de las 18 capturas finales |
| Migración repetida | 0 cambios pendientes |
| Servicios locales | Web y PostgreSQL saludables |

La revisión detectó y corrigió tablas ocultas por una regla de visibilidad y gráficos sin terminar de dibujarse. Las pruebas añadidas verifican la presencia real de filas y barras. También se reprodujo y corrigió el guardado durante una búsqueda lenta.

Las pruebas de escritura utilizaron `docker-compose.test.yml`, con base y volúmenes separados. Ese entorno se eliminó al terminar; los registros de la base principal se conservaron. Las capturas y los informes técnicos locales están en `.impeccable/review` y `.taste-skill/qa` y se excluyen de Git.

## Sistema visual documentado

PRODUCT.md conserva las decisiones de producto y distingue las inferencias. DESIGN.md y `.impeccable/design.json` describen los valores construidos: 22 colores, tipografía Geist, superficies planas, navegación neutral y siete muestras de componentes.

El agente documental alcanzó su límite de uso; el agente principal completó directamente la comparación con el CSS y los componentes, corrigiendo las muestras y los metadatos restantes. La revisión visual independiente sí terminó con dictamen `ship`.

## Límites de alcance

La aplicación mantiene el acceso sin cuentas de la versión original; autenticación y permisos por función quedan pendientes para una instalación compartida. La configuración entregada escucha sólo en el equipo local. No se ha publicado en un servidor externo.

Las restricciones nuevas con `NOT VALID` preservan registros históricos; no validan retrospectivamente toda la base. Las dependencias de compilación Tailwind 3 conservan avisos de seguridad de sus analizadores transitivos; no están dentro del contenedor PHP ni se sirven al navegador. El estilo compilado y las bibliotecas del navegador están incluidos en `src`.

Las instrucciones para ejecutar, recompilar y repetir las pruebas están en README.md.
