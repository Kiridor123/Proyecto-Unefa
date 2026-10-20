-- Additive upgrade for existing installations. No patient or consultation is removed.
ALTER TABLE pacientes
    ADD COLUMN IF NOT EXISTS telefono VARCHAR(50),
    ADD COLUMN IF NOT EXISTS direccion TEXT,
    ADD COLUMN IF NOT EXISTS sexo VARCHAR(20),
    ADD COLUMN IF NOT EXISTS nivel_educativo VARCHAR(100),
    ADD COLUMN IF NOT EXISTS lugar_nacimiento VARCHAR(255),
    ADD COLUMN IF NOT EXISTS fecha_nacimiento DATE,
    ADD COLUMN IF NOT EXISTS carrera VARCHAR(150),
    ADD COLUMN IF NOT EXISTS semestre VARCHAR(50),
    ADD COLUMN IF NOT EXISTS tiene_tatuajes VARCHAR(20),
    ADD COLUMN IF NOT EXISTS compromiso_tatuajes TEXT,
    ADD COLUMN IF NOT EXISTS antecedente_cardiovascular TEXT,
    ADD COLUMN IF NOT EXISTS antecedente_oseo TEXT,
    ADD COLUMN IF NOT EXISTS antecedente_respiratorio TEXT,
    ADD COLUMN IF NOT EXISTS antecedente_digestivo TEXT,
    ADD COLUMN IF NOT EXISTS antecedente_endocrino TEXT,
    ADD COLUMN IF NOT EXISTS antecedente_otros TEXT,
    ADD COLUMN IF NOT EXISTS antecedente_quirurgico TEXT,
    ADD COLUMN IF NOT EXISTS gineco_menarquia VARCHAR(50),
    ADD COLUMN IF NOT EXISTS gineco_sexarquia VARCHAR(50),
    ADD COLUMN IF NOT EXISTS gineco_aco VARCHAR(100),
    ADD COLUMN IF NOT EXISTS gineco_gestas VARCHAR(50),
    ADD COLUMN IF NOT EXISTS gineco_citologia VARCHAR(255),
    ADD COLUMN IF NOT EXISTS antecedente_padre TEXT,
    ADD COLUMN IF NOT EXISTS antecedente_madre TEXT,
    ADD COLUMN IF NOT EXISTS antecedente_hermanos TEXT,
    ADD COLUMN IF NOT EXISTS antecedente_hijos TEXT;

ALTER TABLE consultas
    ADD COLUMN IF NOT EXISTS motivo_consulta TEXT,
    ADD COLUMN IF NOT EXISTS enfermedad_actual TEXT,
    ADD COLUMN IF NOT EXISTS diagnostico TEXT,
    ADD COLUMN IF NOT EXISTS vital_ta VARCHAR(50),
    ADD COLUMN IF NOT EXISTS vital_fc VARCHAR(50),
    ADD COLUMN IF NOT EXISTS vital_fr VARCHAR(50),
    ADD COLUMN IF NOT EXISTS vital_spo2 VARCHAR(50),
    ADD COLUMN IF NOT EXISTS vital_peso_talla VARCHAR(100),
    ADD COLUMN IF NOT EXISTS fisico_piel TEXT,
    ADD COLUMN IF NOT EXISTS fisico_cabeza TEXT,
    ADD COLUMN IF NOT EXISTS fisico_cuello TEXT,
    ADD COLUMN IF NOT EXISTS fisico_torax TEXT,
    ADD COLUMN IF NOT EXISTS fisico_abdomen TEXT,
    ADD COLUMN IF NOT EXISTS fisico_extremidades TEXT,
    ADD COLUMN IF NOT EXISTS fisico_neurologico TEXT,
    ADD COLUMN IF NOT EXISTS laboratorios TEXT,
    ADD COLUMN IF NOT EXISTS plan_tratamiento TEXT,
    ADD COLUMN IF NOT EXISTS pendiente TEXT;

CREATE INDEX IF NOT EXISTS idx_pacientes_categoria ON pacientes (categoria_id);
CREATE INDEX IF NOT EXISTS idx_consultas_paciente_fecha ON consultas (paciente_id, fecha_circunstancia DESC, id DESC);
CREATE INDEX IF NOT EXISTS idx_consultas_fecha ON consultas (fecha_circunstancia);
CREATE INDEX IF NOT EXISTS idx_consultas_reposo ON consultas (fecha_inicio_reposo, fecha_fin_reposo) WHERE fecha_inicio_reposo IS NOT NULL;
CREATE INDEX IF NOT EXISTS idx_adjuntos_consulta ON archivos_adjuntos (consulta_id);
CREATE INDEX IF NOT EXISTS idx_citas_paciente_fecha ON citas (paciente_id, fecha_cita);
CREATE INDEX IF NOT EXISTS idx_citas_pendientes_fecha ON citas (fecha_cita) WHERE estado = 'Pendiente';
ALTER TABLE citas ALTER COLUMN estado SET DEFAULT 'Pendiente';

-- NOT VALID leaves historical rows intact while enforcing new writes and updates.
DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_pacientes_identidad' AND conrelid = 'pacientes'::regclass) THEN
        ALTER TABLE pacientes ADD CONSTRAINT ck_pacientes_identidad CHECK (
            cedula ~ '^[VEJGCPN]-[0-9]{1,18}$' AND length(trim(nombres)) > 0 AND length(trim(apellidos)) > 0
        ) NOT VALID;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_consultas_reposo' AND conrelid = 'consultas'::regclass) THEN
        ALTER TABLE consultas ADD CONSTRAINT ck_consultas_reposo CHECK (
            (fecha_inicio_reposo IS NULL AND fecha_fin_reposo IS NULL) OR
            (fecha_inicio_reposo IS NOT NULL AND fecha_fin_reposo IS NOT NULL AND fecha_fin_reposo >= fecha_inicio_reposo)
        ) NOT VALID;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_citas_estado' AND conrelid = 'citas'::regclass) THEN
        ALTER TABLE citas ADD CONSTRAINT ck_citas_estado CHECK (estado IS NOT NULL AND estado IN ('Pendiente', 'Completada', 'Cancelada')) NOT VALID;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_adjuntos_mime' AND conrelid = 'archivos_adjuntos'::regclass) THEN
        ALTER TABLE archivos_adjuntos ADD CONSTRAINT ck_adjuntos_mime CHECK (tipo_archivo IN ('image/jpeg', 'image/png', 'application/pdf')) NOT VALID;
    END IF;
END $$;
