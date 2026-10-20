# Producto · Salud UNEFA

<!-- impeccable:product-schema 1 -->

## Platform

web

## Usuarios

El README identifica como destinatarios de la atención a estudiantes, docentes y personal de UNEFA. El usuario confirmó que la mejora debe dar un uso equilibrado a pacientes, citas, reportes y panel. El personal que registra y consulta información médica es el usuario operativo inferido del flujo existente; no se ha confirmado una matriz de roles.

## Propósito

Gestionar pacientes, consultas e historia clínica, citas médicas y reportes de morbilidad desde una misma aplicación de atención universitaria.

## Contexto operativo

El registro clínico contiene identificación por cédula, categoría institucional, antecedentes, signos vitales, evaluación, tratamiento, reposo y adjuntos. La aplicación permite imprimir historia, informe, reposo y morbilidad. Las citas admiten los estados Pendiente, Completada y Cancelada.

## Capacidades y restricciones

El código existente usa PHP y PDO/PostgreSQL. El frontend carga Tailwind compilado, Phosphor, SweetAlert2, Chart.js y la fuente Geist desde archivos locales del proyecto. La mejora preserva sus cuatro áreas y formatos de impresión. Las métricas y los listados provienen de las APIs; una ausencia de datos debe mostrarse sin inventar actividad médica. El despliegue local está definido en Docker Compose.

## Compromisos de marca

Se conserva el nombre Salud UNEFA y la terminología institucional del proyecto. Los logos existentes están en src/uploads y se usan en los formatos oficiales. No hay un manual de marca proporcionado.

## Evidencia disponible

README.md, src/views, src/js, src/api y docker/postgres/init.sql contienen las funciones y campos reales. Los logos existentes son activos del proyecto. Los archivos subidos por pacientes no son material decorativo. No se han aportado testimonios, cifras de eficacia ni datos para afirmaciones promocionales.

## Principios

- Dar acceso directo y equilibrado a las cuatro áreas operativas.
- Mantener cada dato clínico y acción reconocibles y legibles.
- Distinguir la carga, la ausencia de resultados y los fallos de conexión.
- Conservar los flujos, documentos y datos reales durante la mejora.
