# Salud UNEFA

Aplicación de gestión médica universitaria con pacientes, historias clínicas, consultas, citas y reportes de morbilidad. Conserva los formatos de impresión de historia, informe y reposo.

## Ejecutar localmente

Requiere Docker Desktop con Docker Compose. Los estilos y las bibliotecas del navegador ya están incluidos; no hace falta Node para ejecutar la aplicación.

```powershell
Copy-Item .env.example .env
# Ajustar las variables de .env si es necesario.
docker compose up -d --build --wait
```

Abrir [Salud UNEFA](http://localhost:8080). Por defecto, el servidor escucha únicamente en este equipo y PostgreSQL no publica un puerto al anfitrión. El inicio espera a que la base esté disponible y aplica las migraciones aditivas antes de iniciar Apache.

Para detener sin eliminar datos:

```powershell
docker compose down
```

Las variables disponibles están en `.env.example`: nombre, usuario y contraseña de PostgreSQL, zona horaria clínica, dirección y puerto web. La contraseña incluida es exclusivamente para desarrollo local. Cambiar variables de conexión no cambia automáticamente las credenciales de un volumen PostgreSQL ya inicializado.

## Datos y archivos

- `postgres_data` conserva los expedientes, consultas, citas y versiones de migración.
- `medical_storage` conserva los nuevos adjuntos fuera del directorio público, en `/var/www/storage/attachments`.
- Los adjuntos históricos de `src/uploads` siguen disponibles mediante la descarga controlada por ID; los logos institucionales permanecen en esa carpeta.
- Un adjunto por consulta, de hasta 10 MB; se aceptan PDF, JPEG y PNG comprobados por su contenido. El servidor genera el nombre y la extensión.
- Respalde tanto PostgreSQL como los adjuntos. Evite `docker compose down -v` en la instalación principal: elimina sus volúmenes.

El comando manual de migración es idempotente:

```powershell
docker compose exec -T web php api/migrate.php
```

Las migraciones no eliminan filas existentes. Las restricciones añadidas como `NOT VALID` comprueban nuevas escrituras y conservan datos históricos que aún no se hayan revisado.

## Desarrollo del frontend

PHP 8.2, PDO/PostgreSQL 15, JavaScript sin framework y Tailwind CSS 3. La interfaz, los iconos Phosphor, los gráficos Chart.js, las alertas SweetAlert2 y la fuente Geist se sirven localmente. Las versiones están fijadas en `package-lock.json`; sus licencias están en `src/vendor` y `src/fonts`.

Con Node 22 o superior:

```powershell
npm ci
npm run build
```

Vuelva a compilar tras modificar clases Tailwind en PHP o JavaScript. `scripts/build-assets.mjs` copia las bibliotecas y genera `src/css/tailwind.css`; los archivos resultantes se incluyen en el proyecto. Los tokens y las reglas de interfaz se describen en `DESIGN.md`; el contexto de producto está en `PRODUCT.md`.

## Pruebas aisladas

Las pruebas que escriben datos usan una base y volúmenes separados, en el puerto 18080:

```powershell
docker compose -p salud-unefa-test -f docker-compose.test.yml up -d --build --wait
node tests/api-smoke.mjs
docker compose -p salud-unefa-test -f docker-compose.test.yml exec -T web php /var/www/tests/backend-validation.php
```

La suite HTTP comprueba validación, métodos, origen, concurrencia de citas, persistencia, adjuntos, estadísticas e impresión. Solo admite el puerto local del entorno de pruebas. Los datos sintéticos quedan en ese entorno y nunca se insertan en la base principal.

Al terminar las pruebas, puede eliminar exclusivamente ese entorno:

```powershell
docker compose -p salud-unefa-test -f docker-compose.test.yml down -v
```

## Alcance de acceso

Esta versión conserva el modelo de acceso de la aplicación original: no incluye inicio de sesión ni permisos por usuario. El enlace local sirve para desarrollo y revisión. Antes de habilitar acceso compartido a expedientes reales se necesitan autenticación, autorización por funciones, HTTPS y una política de respaldo y auditoría definida por la institución.

## Estructura

```text
src/api/                 API y documentos imprimibles
src/config/              Conexión, validación, adjuntos y migraciones
src/components/          Estructura compartida
src/views/               Resumen, pacientes, citas y morbilidad
src/modals/              Consulta, agenda y expediente
src/js/                  Interacciones y acceso a la API
src/css/                 Sistema visual y Tailwind generado
src/vendor/              Dependencias del navegador y licencias
src/fonts/               Fuente local y licencia
scripts/                 Compilación de recursos
tests/                   Validación e integración HTTP
docker/                  Apache/PHP y esquema inicial PostgreSQL
```
