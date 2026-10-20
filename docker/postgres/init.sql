-- Creación de tabla de categorías (Lista dinámica)
CREATE TABLE categorias_institucionales (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) UNIQUE NOT NULL
);

-- Insertar categorías por defecto
INSERT INTO categorias_institucionales (nombre) VALUES 
('Docente'), ('Administrativo'), ('Obrero'), ('Militar'), ('Estudiante');

-- Creación de tabla pacientes (Ampliada para Historia Clínica de Ingreso)
CREATE TABLE pacientes (
    id SERIAL PRIMARY KEY,
    cedula VARCHAR(20) UNIQUE NOT NULL,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    categoria_id INT NOT NULL,
    
    -- Datos demográficos y de contacto adicionales
    telefono VARCHAR(50),
    direccion TEXT,
    sexo VARCHAR(20),
    nivel_educativo VARCHAR(100),
    lugar_nacimiento VARCHAR(255),
    fecha_nacimiento DATE,
    
    -- Datos académicos
    carrera VARCHAR(150),
    semestre VARCHAR(50),
    
    -- Control de tatuajes
    tiene_tatuajes VARCHAR(20),
    compromiso_tatuajes TEXT,
    
    -- Antecedentes médicos personales patológicos
    antecedente_cardiovascular TEXT,
    antecedente_oseo TEXT,
    antecedente_respiratorio TEXT,
    antecedente_digestivo TEXT,
    antecedente_endocrino TEXT,
    antecedente_otros TEXT,
    antecedente_quirurgico TEXT,
    
    -- Antecedentes ginecológicos (si aplica)
    gineco_menarquia VARCHAR(50),
    gineco_sexarquia VARCHAR(50),
    gineco_aco VARCHAR(100),
    gineco_gestas VARCHAR(50),
    gineco_citologia VARCHAR(255),
    
    -- Antecedentes patológicos familiares
    antecedente_padre TEXT,
    antecedente_madre TEXT,
    antecedente_hermanos TEXT,
    antecedente_hijos TEXT,
    
    CONSTRAINT ck_pacientes_identidad CHECK (cedula ~ '^[VEJGCPN]-[0-9]{1,18}$' AND length(trim(nombres)) > 0 AND length(trim(apellidos)) > 0),
    CONSTRAINT fk_categoria
      FOREIGN KEY(categoria_id) 
      REFERENCES categorias_institucionales(id)
      ON DELETE RESTRICT
);

-- Creación de tabla consultas (Ampliada para evaluación médica y signos vitales)
CREATE TABLE consultas (
    id SERIAL PRIMARY KEY,
    paciente_id INT NOT NULL,
    fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_circunstancia DATE NOT NULL,
    resumen TEXT NOT NULL,
    fecha_inicio_reposo DATE,
    fecha_fin_reposo DATE,
    
    -- Detalles adicionales del caso clínico
    motivo_consulta TEXT,
    enfermedad_actual TEXT,
    diagnostico TEXT,
    
    -- Signos vitales
    vital_ta VARCHAR(50),
    vital_fc VARCHAR(50),
    vital_fr VARCHAR(50),
    vital_spo2 VARCHAR(50),
    vital_peso_talla VARCHAR(100),
    
    -- Examen físico general
    fisico_piel TEXT,
    fisico_cabeza TEXT,
    fisico_cuello TEXT,
    fisico_torax TEXT,
    fisico_abdomen TEXT,
    fisico_extremidades TEXT,
    fisico_neurologico TEXT,
    
    -- Laboratorios, plan e indicaciones
    laboratorios TEXT,
    plan_tratamiento TEXT,
    pendiente TEXT,
    
    CONSTRAINT ck_consultas_reposo CHECK ((fecha_inicio_reposo IS NULL AND fecha_fin_reposo IS NULL) OR (fecha_inicio_reposo IS NOT NULL AND fecha_fin_reposo IS NOT NULL AND fecha_fin_reposo >= fecha_inicio_reposo)),
    CONSTRAINT fk_paciente_consulta
      FOREIGN KEY(paciente_id) 
      REFERENCES pacientes(id)
      ON DELETE CASCADE
);

-- Creación de tabla archivos_adjuntos (Validación de tipo y tamaño se maneja en PHP)
CREATE TABLE archivos_adjuntos (
    id SERIAL PRIMARY KEY,
    consulta_id INT NOT NULL,
    ruta_archivo VARCHAR(255) NOT NULL,
    tipo_archivo VARCHAR(50) NOT NULL,
    fecha_subida TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT ck_adjuntos_mime CHECK (tipo_archivo IN ('image/jpeg', 'image/png', 'application/pdf')),
    CONSTRAINT fk_consulta_archivo
      FOREIGN KEY(consulta_id) 
      REFERENCES consultas(id)
      ON DELETE CASCADE
);

-- Creación de tabla citas (Agendamiento)
CREATE TABLE citas (
    id SERIAL PRIMARY KEY,
    paciente_id INT NOT NULL,
    fecha_cita TIMESTAMP NOT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'Pendiente',
    CONSTRAINT ck_citas_estado CHECK (estado IN ('Pendiente', 'Completada', 'Cancelada')),
    CONSTRAINT fk_paciente_cita
      FOREIGN KEY(paciente_id) 
      REFERENCES pacientes(id)
      ON DELETE CASCADE
);

-- Query paths used by history, dashboard, appointment conflict checks and reports.
CREATE INDEX IF NOT EXISTS idx_pacientes_categoria ON pacientes (categoria_id);
CREATE INDEX IF NOT EXISTS idx_consultas_paciente_fecha ON consultas (paciente_id, fecha_circunstancia DESC, id DESC);
CREATE INDEX IF NOT EXISTS idx_consultas_fecha ON consultas (fecha_circunstancia);
CREATE INDEX IF NOT EXISTS idx_consultas_reposo ON consultas (fecha_inicio_reposo, fecha_fin_reposo) WHERE fecha_inicio_reposo IS NOT NULL;
CREATE INDEX IF NOT EXISTS idx_adjuntos_consulta ON archivos_adjuntos (consulta_id);
CREATE INDEX IF NOT EXISTS idx_citas_paciente_fecha ON citas (paciente_id, fecha_cita);
CREATE INDEX IF NOT EXISTS idx_citas_pendientes_fecha ON citas (fecha_cita) WHERE estado = 'Pendiente';
