# Salud UNEFA

Aplicación de gestión médica universitaria con pacientes, historias clínicas, consultas, citas y reportes de morbilidad. Conserva los formatos de impresión de historia, informe y reposo.

## Ejecutar localmente

Requiere Docker Desktop con Docker Compose. Los estilos y las bibliotecas del navegador ya están incluidos; no hace falta Node para ejecutar la aplicación.

```powershell
Copy-Item .env.example .env
# Ajustar las variables de .env; defina ADMIN_PASSWORD (mínimo 10 caracteres).
docker compose up -d --build --wait
```

Abrir [Salud UNEFA](http://localhost:8080). Por defecto, el servidor escucha únicamente en este equipo y PostgreSQL no publica un puerto al anfitrión. El inicio espera a que la base esté disponible y aplica las migraciones aditivas antes de iniciar Apache.

Para detener sin eliminar datos:

```powershell
docker compose down
```

Las variables disponibles están en `.env.example`: nombre, usuario y contraseña de PostgreSQL, zona horaria clínica, dirección y puerto web, administrador inicial (`ADMIN_USER`, `ADMIN_PASSWORD`) y `TRUST_PROXY`. Las contraseñas incluidas son exclusivamente para desarrollo local. Cambiar variables de conexión no cambia automáticamente las credenciales de un volumen PostgreSQL ya inicializado.

## Inicio de sesión, roles y auditoría

La aplicación exige iniciar sesión. Al aplicar las migraciones, si la tabla `usuarios` está vacía y existe `ADMIN_PASSWORD` (mínimo 10 caracteres), se crea el usuario `ADMIN_USER` (por defecto `admin`) con rol de administrador. Sin esa variable no se crea ninguna cuenta y el registro del contenedor muestra un aviso. Cambie la contraseña desde el menú de usuario tras el primer acceso; después puede quitar `ADMIN_PASSWORD` de `.env`.

| Permiso | Administrador | Médico | Asistente |
|---|:-:|:-:|:-:|
| Ver y editar identificación, contacto y datos demográficos de pacientes | ✔ | ✔ | ✔ |
| Ver historia clínica, consultas, adjuntos, impresos y reposos | ✔ | ✔ | — |
| Registrar y corregir consultas y antecedentes | ✔ | ✔ | — |
| Gestionar citas | ✔ | ✔ | ✔ |
| Reportes de morbilidad y exportación CSV | ✔ | ✔ | — |
| Gestionar usuarios y consultar el registro de actividad | ✔ | — | — |

- Sesiones con cookie `SALUDSESSID` (HttpOnly, SameSite=Strict, Secure con HTTPS), cierre por 30 minutos de inactividad y duración máxima de 12 horas. Desactivar una cuenta, cambiar su rol o restablecer su contraseña tiene efecto inmediato.
- Toda petición que modifica datos exige el token CSRF de la sesión (cabecera `X-CSRF-Token`) además de la comprobación de origen.
- Cinco intentos fallidos bloquean la cuenta 15 minutos; un administrador puede desbloquearla restableciendo la contraseña.
- El registro de actividad (tabla `auditoria`) guarda inicios de sesión, accesos a expedientes, consultas, adjuntos e impresos, y cambios de pacientes, consultas, citas y usuarios. Nunca guarda texto clínico: solo identificadores y nombres de campos.
- Las correcciones de una consulta exigen un motivo y conservan la versión anterior completa en `consulta_versiones`.

### Detrás de un proxy HTTPS

Si un proxy inverso de confianza termina HTTPS delante del contenedor, defina `TRUST_PROXY=1` para que se acepten `X-Forwarded-Proto` y `X-Forwarded-Host` en la comprobación de origen y en la cookie `Secure`. No lo active si el contenedor recibe tráfico directo: cualquier cliente podría falsificar esas cabeceras.

## Datos y archivos

- `postgres_data` conserva los expedientes, consultas, citas y versiones de migración.
- `medical_storage` conserva los nuevos adjuntos fuera del directorio público, en `/var/www/storage/attachments`.
- Los adjuntos históricos de `src/uploads` siguen disponibles solo mediante la descarga controlada por ID (con sesión y permiso clínico); Apache ya no los sirve directamente. Los logos institucionales (`logo_*`) permanecen públicos en esa carpeta.
- En `docker-compose.yml` el código (`./src`) se monta en solo lectura: la aplicación únicamente escribe en el volumen de adjuntos.
- Un adjunto por consulta, de hasta 10 MB; se aceptan PDF, JPEG y PNG comprobados por su contenido. El servidor genera el nombre y la extensión.
- Respalde tanto PostgreSQL como los adjuntos (ver «Respaldo y restauración»). Evite `docker compose down -v` en la instalación principal: elimina sus volúmenes.

El comando manual de migración es idempotente:

```powershell
docker compose exec -T web php api/migrate.php
```

Las migraciones no eliminan filas existentes. Las restricciones añadidas como `NOT VALID` comprueban nuevas escrituras y conservan datos históricos que aún no se hayan revisado.

## Respaldo y restauración

Con los contenedores en marcha, desde la raíz del repositorio:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\backup.ps1   # Windows
sh scripts/backup.sh                                         # Linux, macOS o Git Bash
```

Cada ejecución crea `backups/AAAAMMDD-HHMMSS/` con `db.dump` (pg_dump en formato personalizado, comprimido) y `adjuntos.tgz`, y conserva los 14 respaldos más recientes (`-Keep N` en PowerShell, `BACKUP_KEEP=N` en sh). La carpeta `backups/` está excluida de git. Contiene datos clínicos: cópiela a un medio cifrado fuera del servidor y pruebe la restauración periódicamente.

Programar una copia diaria:

```powershell
# Programador de tareas de Windows (todos los días a las 22:00)
schtasks /Create /SC DAILY /ST 22:00 /TN "Salud UNEFA respaldo" /TR "powershell -NoProfile -ExecutionPolicy Bypass -File C:\ruta\Proyecto-Unefa\scripts\backup.ps1"
```

```sh
# cron en Linux (todos los días a las 22:00)
0 22 * * * cd /ruta/Proyecto-Unefa && sh scripts/backup.sh >> backups/respaldo.log 2>&1
```

Restaurar (sustituye los datos actuales; detenga antes el uso de la aplicación):

```sh
RESPALDO=backups/AAAAMMDD-HHMMSS
docker compose up -d --wait
docker compose exec -T db sh -c 'pg_restore -U "$POSTGRES_USER" -d "$POSTGRES_DB" --clean --if-exists --no-owner' < "$RESPALDO/db.dump"
docker compose exec -T web tar -C /var/www/storage -xzf - < "$RESPALDO/adjuntos.tgz"
docker compose exec -T web chown -R www-data:www-data /var/www/storage/attachments
```

En Windows ejecute esos comandos desde Git Bash o `cmd.exe` (que conservan los bytes al redirigir con `<`); Windows PowerShell 5.1 no admite `<`.

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
npm run test:env   # docker compose -p salud-unefa-test -f docker-compose.test.yml up -d --build --wait
npm test           # suite HTTP (tests/api-smoke.mjs) y validación backend (tests/backend-validation.php)
```

`npm run test:api` y `npm run test:backend` ejecutan cada suite por separado. El entorno de pruebas crea el administrador `admin` con la contraseña `integration_admin_only` (solo para ese entorno); la suite HTTP inicia sesión con él (o con `ADMIN_USER`/`ADMIN_PASSWORD` si se definen) y crea un médico y un asistente.

La suite HTTP comprueba sesión y CSRF, permisos por rol, bloqueo por intentos fallidos, gestión de usuarios, auditoría, validación, métodos, origen, concurrencia e idempotencia, identidad de pacientes, corrección de consultas con versiones, reprogramación de citas, búsqueda y paginación, adjuntos, estadísticas, exportación CSV, impresión y cabeceras de seguridad (CSP, firma del servidor, carpetas y archivos no públicos). Solo admite el puerto local del entorno de pruebas. Los datos sintéticos quedan en ese entorno y nunca se insertan en la base principal.

Al terminar las pruebas, puede eliminar exclusivamente ese entorno:

```powershell
docker compose -p salud-unefa-test -f docker-compose.test.yml down -v
```

## Alcance de acceso

Antes de habilitar acceso compartido a expedientes reales: publique la aplicación solo mediante HTTPS (con `TRUST_PROXY=1` si hay un proxy de confianza), cambie las contraseñas de ejemplo, cree cuentas personales para cada profesional y programe respaldos fuera del servidor.

## Estructura

```text
src/api/                 API (sesión, usuarios, auditoría, pacientes, consultas, citas, reportes) e impresos
src/config/              Conexión, sesión y permisos (Auth.php), validación, adjuntos y migraciones
src/components/          Estructura compartida
src/views/               Resumen, pacientes, citas y morbilidad
src/modals/              Consulta, agenda y expediente
src/js/                  Interacciones y acceso a la API
src/css/                 Sistema visual y Tailwind generado
src/vendor/              Dependencias del navegador y licencias
src/fonts/               Fuente local y licencia
scripts/                 Compilación de recursos y respaldo (backup.ps1, backup.sh)
tests/                   Validación e integración HTTP
docker/                  Apache/PHP y esquema inicial PostgreSQL
```
