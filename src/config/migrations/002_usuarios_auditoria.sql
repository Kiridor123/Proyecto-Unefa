-- Usuarios, roles, bitácora de auditoría, versiones de consultas e idempotencia.
-- Migración aditiva e idempotente: no elimina ni reescribe filas existentes.

CREATE TABLE IF NOT EXISTS usuarios (
    id SERIAL PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL,
    nombre_completo VARCHAR(150) NOT NULL,
    rol VARCHAR(20) NOT NULL,
    registro_profesional VARCHAR(50) NULL, -- MPPS o colegio, para médicos
    password_hash VARCHAR(255) NOT NULL,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    intentos_fallidos INT NOT NULL DEFAULT 0,
    bloqueado_hasta TIMESTAMPTZ NULL,
    ultimo_acceso TIMESTAMPTZ NULL,
    creado_en TIMESTAMPTZ NOT NULL DEFAULT now(),
    actualizado_en TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT uq_usuarios_usuario UNIQUE (usuario),
    CONSTRAINT ck_usuarios_usuario CHECK (usuario ~ '^[a-z0-9._-]{3,50}$'),
    CONSTRAINT ck_usuarios_rol CHECK (rol IN ('administrador', 'medico', 'asistente')),
    CONSTRAINT ck_usuarios_nombre CHECK (length(trim(nombre_completo)) > 0),
    CONSTRAINT ck_usuarios_intentos CHECK (intentos_fallidos >= 0)
);

-- Bitácora de solo inserción. Nunca debe contener texto clínico en "detalle".
CREATE TABLE IF NOT EXISTS auditoria (
    id BIGSERIAL PRIMARY KEY,
    usuario_id INT NULL REFERENCES usuarios(id) ON DELETE SET NULL,
    usuario VARCHAR(50) NULL,
    accion VARCHAR(50) NOT NULL,
    entidad VARCHAR(30) NULL,
    entidad_id INT NULL,
    detalle JSONB NOT NULL DEFAULT '{}'::jsonb,
    ip VARCHAR(45) NULL,
    creado_en TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX IF NOT EXISTS idx_auditoria_creado ON auditoria (creado_en DESC);
CREATE INDEX IF NOT EXISTS idx_auditoria_entidad ON auditoria (entidad, entidad_id);
CREATE INDEX IF NOT EXISTS idx_auditoria_usuario ON auditoria (usuario_id, creado_en DESC);

-- Autoría, edición e idempotencia de consultas.
ALTER TABLE consultas
    ADD COLUMN IF NOT EXISTS medico_id INT NULL REFERENCES usuarios(id) ON DELETE SET NULL,
    ADD COLUMN IF NOT EXISTS actualizado_en TIMESTAMPTZ NULL,
    ADD COLUMN IF NOT EXISTS actualizado_por INT NULL REFERENCES usuarios(id) ON DELETE SET NULL,
    ADD COLUMN IF NOT EXISTS solicitud_id UUID NULL;
CREATE UNIQUE INDEX IF NOT EXISTS uq_consultas_solicitud ON consultas (solicitud_id) WHERE solicitud_id IS NOT NULL;
CREATE INDEX IF NOT EXISTS idx_consultas_medico ON consultas (medico_id) WHERE medico_id IS NOT NULL;
CREATE INDEX IF NOT EXISTS idx_consultas_fin_reposo ON consultas (fecha_fin_reposo) WHERE fecha_fin_reposo IS NOT NULL;

CREATE TABLE IF NOT EXISTS consulta_versiones (
    id SERIAL PRIMARY KEY,
    consulta_id INT NOT NULL REFERENCES consultas(id) ON DELETE CASCADE,
    version INT NOT NULL,
    datos JSONB NOT NULL, -- fila anterior completa
    motivo TEXT NOT NULL,
    editado_por INT NULL REFERENCES usuarios(id) ON DELETE SET NULL,
    editado_en TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT uq_consulta_versiones UNIQUE (consulta_id, version),
    CONSTRAINT ck_consulta_versiones_version CHECK (version > 0)
);

ALTER TABLE citas
    ADD COLUMN IF NOT EXISTS motivo VARCHAR(500) NULL,
    ADD COLUMN IF NOT EXISTS creado_por INT NULL REFERENCES usuarios(id) ON DELETE SET NULL,
    ADD COLUMN IF NOT EXISTS actualizado_en TIMESTAMPTZ NULL;
CREATE INDEX IF NOT EXISTS idx_citas_fecha ON citas (fecha_cita, id);

ALTER TABLE pacientes
    ADD COLUMN IF NOT EXISTS actualizado_en TIMESTAMPTZ NULL;

-- Límites de cordura estáticos (BE-6/BE-7). NOT VALID conserva filas históricas sin revisar.
-- No hay restricción de fecha máxima en citas: PostgreSQL comprueba un CHECK NOT VALID en
-- cada UPDATE de la fila, y una cita antigua con un año mal escrito (p. ej. 2206) no podría
-- cancelarse ni completarse. El límite de 2 años lo validan save_cita y reprogramar_cita.
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_consultas_fecha_minima' AND conrelid = 'consultas'::regclass) THEN
        ALTER TABLE consultas ADD CONSTRAINT ck_consultas_fecha_minima CHECK (fecha_circunstancia >= DATE '1900-01-01') NOT VALID;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_consultas_reposo_duracion' AND conrelid = 'consultas'::regclass) THEN
        ALTER TABLE consultas ADD CONSTRAINT ck_consultas_reposo_duracion CHECK (
            fecha_inicio_reposo IS NULL OR fecha_fin_reposo IS NULL OR fecha_fin_reposo - fecha_inicio_reposo <= 366
        ) NOT VALID;
    END IF;
END $$;
