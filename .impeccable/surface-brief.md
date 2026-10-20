# Salud UNEFA · Contrato de diseño

Modo: Operate. Implementación directa en código para la aplicación existente. El usuario autorizó mejoras completas y confirmó uso equilibrado de todas las áreas.

## Inventario y decisiones

Pacientes identificados por cédula y categoría; consultas con fecha, signos vitales y diagnóstico; citas con fecha/hora y estado; reposos con vigencia; reportes de atenciones por rango de fechas; formatos oficiales con logos existentes; personal médico como usuario operativo inferido; datos exclusivamente desde las APIs. No se crean cifras clínicas ni personas de muestra.

## Direcciones consideradas antes del sorteo

1. Cuaderno de jornada universitaria: lectura compacta de actividad, tinta sobria y medidas alineadas.
2. Señalización de campus: jerarquía clara, orientación por palabras y cuatro destinos estables.
3. Publicación académica contemporánea: blancos funcionales, tipografía de lectura y datos con escala exacta.
4. Formularios institucionales: alineación consistente de etiquetas y datos sin ornamento.
5. Carteleras de servicio universitario: acciones evidentes, contraste y densidad adaptada a consulta rápida.
6. Archivo documental: continuidad entre el expediente, su historia y los impresos oficiales.
7. Índice académico clínico: una misma lógica de nombres y cifras atraviesa resumen, directorio, agenda y morbilidad.

Concept-seed: seed:2dc76b0870da8ff7; índice asignado 7. El índice aporta tipografía, densidad, neutralidad y continuidad por cédula; la navegación y los controles son patrones web conocidos.

## Contrato

THESIS: acceder a los cuatro trabajos principales de la atención universitaria desde un sistema legible, consistente y confiable.
OWN-WORLD: índice académico clínico; fondo frío muy claro, superficies blancas, tinta oscura, gráfica teal, tipografía Geist local, cifras tabulares y tablas con reglas suaves.
STORY: identificar la actividad; buscar un expediente o una cita; registrar la atención; consultar el periodo y producir el formato institucional.
FIRST VIEWPORT: sidebar etiquetada Salud UNEFA, título de sección, una acción principal, indicadores agrupados y las dos lecturas de actividad. La firma es la ruta de atención: expedientes, agenda y morbilidad como tres acciones operativas contiguas, cada una con su objeto clínico explícito.
FORM: concept seed:2dc76b0870da8ff7, corroborado en .impeccable/questions/roll.json; draw --app achromatic-ledger, seed 1683463685, Open plane. Shell redibujada una vez porque el rail de iconos ocultaba las cuatro áreas a un personal que necesita reconocerlas por nombre. Se conserva el movimiento del estilo: chrome neutral y cifras en tinta, un solo color en los gráficos. Cara elegida: fondo frío de la atención clínica y gráfica teal #256e78. No se incorpora decoración médica.

## Challengers del sorteo

- Catálogo de círculos: una cédula podría funcionar como código de índice; declinado en identificación y claridad por la densidad extrema. Aporte de disciplina: cada objeto mantiene identidad y dato de referencia visibles.
- J-card de casete: turnos y fechas podrían leerse como pistas; declinado en ambos ejes. Aporte: alineación estricta de cifras y duraciones.
- Léxico con margen: expedientes podrían ser entradas con índice; competitivo en legibilidad, pierde reconocimiento clínico si usa su voz editorial. Se conserva dirección asignada y se exige jerarquía textual sin ornamentación.
- Muestra bitmap: la tipografía podría ordenar categorías; declinado en ambos ejes por lectura prolongada. Aporte: una escala tipográfica coherente, sin tamaños accidentales.
- Horario de bolsillo: citas por fecha/hora podrían adoptar el ritmo; competitivo en escaneo, pierde claridad por color y rakes. Se conserva dirección asignada y se exige alineación de agenda.
- Panel split-flap: citas como salidas temporales; declinado en ambos ejes por contraste teatral y movimiento impropio. Aporte: estados estables que cambian sin desplazar las columnas.

## Preguntas y evidencia por sección

Resumen: ¿qué actividad tiene el servicio y cómo continúo? KPIs reales, consultas por mes y pacientes por categoría; registrar consulta a un clic.
Pacientes: ¿dónde está el expediente que necesito? Búsqueda por cédula/categoría, listado legible y apertura de historia.
Citas: ¿qué atenciones están agendadas y cuáles requieren cambio? Búsqueda, estado y fechas con agendamiento inmediato.
Morbilidad: ¿qué consultas hubo en el periodo que debo reportar? Rango de fechas, resultados e impresión disponible tras la consulta.

## Verificación prevista

Capturas desktop 1440, tablet 768 y móvil 390; navegación, búsqueda, estados vacíos y errores; carga de fuente local, cifras tabulares, foco visible, controles táctiles, tablas dentro de su marco y diálogos coherentes. El agente raíz coordina captura y revisor final independiente.
