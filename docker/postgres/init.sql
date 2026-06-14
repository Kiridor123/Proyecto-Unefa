-- Creación de tabla de categorías (Lista dinámica)
CREATE TABLE categorias_institucionales (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) UNIQUE NOT NULL
);

-- Insertar categorías por defecto
INSERT INTO categorias_institucionales (nombre) VALUES 
('Docente'), ('Administrativo'), ('Obrero'), ('Militar'), ('Estudiante');

-- Creación de tabla pacientes
CREATE TABLE pacientes (
    id SERIAL PRIMARY KEY,
    cedula VARCHAR(20) UNIQUE NOT NULL,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    categoria_id INT NOT NULL,
    CONSTRAINT fk_categoria
      FOREIGN KEY(categoria_id) 
      REFERENCES categorias_institucionales(id)
      ON DELETE RESTRICT
);

-- Creación de tabla consultas
CREATE TABLE consultas (
    id SERIAL PRIMARY KEY,
    paciente_id INT NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_circunstancia DATE NOT NULL,
    resumen TEXT NOT NULL,
    fecha_inicio_reposo DATE,
    fecha_fin_reposo DATE,
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
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
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
    estado VARCHAR(20) DEFAULT 'Pendiente',
    CONSTRAINT fk_paciente_cita
      FOREIGN KEY(paciente_id) 
      REFERENCES pacientes(id)
      ON DELETE CASCADE
);
