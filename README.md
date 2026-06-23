# Salud UNEFA - Sistema de Gestión Médica

Un sistema web moderno, limpio y profesional diseñado para la gestión y control médico de los estudiantes, docentes y personal de la Universidad Nacional Experimental Politécnica de la Fuerza Armada Nacional Bolivariana (UNEFA).

---

## 🚀 Arquitectura y Stack Tecnológico

El proyecto está diseñado bajo una arquitectura monolítica ágil y desacoplada, utilizando contenedores para facilitar su despliegue y desarrollo local.

### Backend
- **PHP 8.2+**: Lenguaje del lado del servidor para procesar lógica y peticiones de la API.
- **PDO (PostgreSQL)**: Conexión segura y tipada a la base de datos relacional.
- **Fpdf/Tcpdf (si aplica para PDFs)**: Motor de generación de reportes impresos de historias clínicas e informes.

### Base de Datos
- **PostgreSQL 15**: Motor de base de datos relacional robusto y optimizado para transacciones de expedientes médicos.

### Frontend
- **Tailwind CSS (CDN)**: Framework de estilos CSS mobile-first para una interfaz pulida y consistente.
- **Phosphor Icons**: Librería moderna de iconos vectoriales SVG para todos los elementos interactivos.
- **SweetAlert2**: Motor de alertas emergentes y notificaciones toast de estado.
- **Chart.js**: Renderizado de gráficos analíticos interactivos y responsivos en el panel principal.

---

## 📁 Estructura del Proyecto

```text
Proyecto-Unefa/
├── docker/                 # Configuraciones específicas de contenedores
│   ├── php/                # Dockerfile de la app PHP y configuración de carga
│   └── postgres/           # Script init.sql para la creación y carga inicial de la base de datos
├── src/                    # Código fuente de la aplicación
│   ├── api/                # Endpoints del backend (PHP) para transacciones y consultas
│   ├── components/         # Componentes globales PHP (head, header, sidebar, footer)
│   ├── config/             # Archivos de conexión a base de datos y configuración global
│   ├── css/                # Hojas de estilo personalizadas (.css)
│   ├── js/                 # Controladores frontend e interacciones dinámicas por pestañas
│   ├── modals/             # Modales interactivos (Registrar consultas, agendar citas, drawer de detalle)
│   ├── uploads/            # Directorio de almacenamiento para informes y exámenes complementarios
│   ├── views/              # Vistas principales del sistema (dashboard, pacientes, citas, reportes)
│   └── index.php           # Punto de entrada y maquetación general del sistema
├── docker-compose.yml      # Orquestación de servicios locales (web + base de datos)
└── README.md               # Documentación general del proyecto
```

---

## 🛠️ Instalación y Configuración Local

El proyecto está completamente contenerizado con **Docker**, por lo que no es necesario instalar PHP o PostgreSQL en el sistema anfitrión.

### Prerrequisitos
- Tener instalado **Docker** y **Docker Compose**.

### Instrucciones de Despliegue

1. Clonar el repositorio e ingresar a la carpeta del proyecto:
   ```bash
   cd Proyecto-Unefa
   ```

2. Levantar la aplicación con Docker Compose:
   ```bash
   docker compose up -d --build
   ```
   *Nota: Este comando descargará la imagen de PostgreSQL, compilará el contenedor de PHP, montará los volúmenes en tiempo real e inicializará la base de datos automáticamente usando el archivo `./docker/postgres/init.sql`.*

3. Acceder al sistema desde tu navegador web:
   ```text
   http://localhost:8080
   ```

### Detener los Servicios
Para detener y limpiar los contenedores temporales manteniendo los datos persistentes:
```bash
docker compose down
```

---

## 🔑 Credenciales de Conexión (Por Defecto)

Las variables de entorno y credenciales configuradas en el archivo `docker-compose.yml` para el desarrollo local son:

| Servicio | Variable | Valor |
| :--- | :--- | :--- |
| **Database Host** | `DB_HOST` | `db` |
| **Database Port** | `DB_PORT` | `5432` |
| **Database Name** | `DB_NAME` | `unefa_medica` |
| **Database User** | `DB_USER` | `unefa_admin` |
| **Database Password**| `DB_PASSWORD` | `unefa_secure_pass` |

*Los datos de la base de datos se guardan de forma persistente en el volumen con nombre `postgres_data`.*

---

## ✨ Características Principales

### 📊 1. Panel de Control (Dashboard)
- Indicadores numéricos vivos: total de pacientes registrados, citas médicas pendientes, consultas ejecutadas en el mes y reposos médicos activos.
- Gráficos interactivos de distribución de atenciones por categoría y consultas mensuales.

### 👥 2. Gestión de Pacientes e Historia Clínica
- Filtros rápidos de búsqueda por Cédula de Identidad y Categoría Institucional.
- **Registro de Consultas Completo:** Formulario interactivo por secciones (acordeón) para recolectar datos personales, antecedentes familiares y patológicos, control de tatuajes, signos vitales, examen físico, plan de tratamiento, reposo y adjuntos JPG/PNG/PDF.
- **Drawer de Expediente (Lateral):** Panel deslizable que recupera el expediente clínico de un paciente en tiempo real, mostrando sus consultas previas, citas y permitiendo la impresión directa.
- **Impresión de Documentos:**
  - Historia Clínica de Ingreso Estudiantil.
  - Informe Médico Individual.
  - Certificado de Reposo Médico.

### 📅 3. Control de Citas Médicas
- Calendario para agendamiento de citas médicas.
- **Registro al Vuelo:** Si la cédula ingresada no existe, el sistema habilita dinámicamente un formulario simplificado para registrar al paciente al instante sin interrumpir el proceso de agendamiento.
- Gestión de transiciones de estado lógicas (Pendiente, Completada y Cancelada).

### 📝 4. Reportes de Morbilidad
- Generador de asistencia a jornadas médicas en rangos de fechas específicos.
- Exportación e impresión directa del formato oficial consolidado de morbilidad.

---

## 📱 Diseño Responsivo e Interacción Táctica (Premium UX)

El sistema ha sido refinado siguiendo lineamientos premium de diseño responsivo (Estrategia Mobile-First) para asegurar que todo el personal médico pueda interactuar con la app cómodamente desde cualquier tablet o smartphone:

- **Estructura Dual Adaptable:** 
  - En pantallas medianas y grandes se muestran tablas estructuradas tradicionales.
  - En móviles se despliega una interfaz de cuadrículas con **tarjetas informativas premium** que contienen iniciales del paciente estilizadas, formato de fecha amigable tipo calendario y detalles médicos ordenados.
- **Touch Targets WCAG:** Todos los botones de acción móvil dentro de las tarjetas cuentan con una zona táctil cómoda (mínimo `44px` de altura) y detienen la propagación de eventos para evitar clics accidentales.
- **Prevención de Zoom en iOS:** Las tipografías de los formularios cambian de tamaño dinámicamente (`text-base sm:text-sm`). Al tener mínimo `16px` de tamaño en móvil, se evita que navegadores móviles (Safari/Chrome en iOS) fuercen un zoom automático e invasivo al enfocar cualquier campo de entrada.
- **Pie de Modales Inteligente:** En pantallas pequeñas los botones de los modales y formularios se apilan de forma vertical inversa (`flex-col-reverse`), permitiendo que el botón primario de guardar quede siempre en la parte superior del grupo de acciones para mayor comodidad del dedo pulgar.
