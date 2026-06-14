<?php
require_once __DIR__ . '/../config/Database.php';

header('Content-Type: text/plain');

try {
    $db = Config\Database::getInstance()->getConnection();
    echo "Conexión a la base de datos establecida.\n";

    // 1. Agregar columnas a la tabla 'pacientes'
    $alterPacientes = [
        "ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS telefono VARCHAR(50)",
        "ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS direccion TEXT",
        "ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS sexo VARCHAR(20)",
        "ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS nivel_educativo VARCHAR(100)",
        "ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS lugar_nacimiento VARCHAR(255)",
        "ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS fecha_nacimiento DATE",
        "ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS carrera VARCHAR(150)",
        "ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS semestre VARCHAR(50)",
        "ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS tiene_tatuajes VARCHAR(20)",
        "ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS compromiso_tatuajes TEXT",
        "ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS antecedente_cardiovascular TEXT",
        "ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS antecedente_oseo TEXT",
        "ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS antecedente_respiratorio TEXT",
        "ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS antecedente_digestivo TEXT",
        "ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS antecedente_endocrino TEXT",
        "ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS antecedente_otros TEXT",
        "ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS antecedente_quirurgico TEXT",
        "ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS gineco_menarquia VARCHAR(50)",
        "ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS gineco_sexarquia VARCHAR(50)",
        "ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS gineco_aco VARCHAR(100)",
        "ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS gineco_gestas VARCHAR(50)",
        "ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS gineco_citologia VARCHAR(255)",
        "ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS antecedente_padre TEXT",
        "ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS antecedente_madre TEXT",
        "ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS antecedente_hermanos TEXT",
        "ALTER TABLE pacientes ADD COLUMN IF NOT EXISTS antecedente_hijos TEXT"
    ];

    echo "Iniciando migración de la tabla 'pacientes'...\n";
    foreach ($alterPacientes as $query) {
        $db->exec($query);
        echo "Ejecutado: $query\n";
    }

    // 2. Agregar columnas a la tabla 'consultas'
    $alterConsultas = [
        "ALTER TABLE consultas ADD COLUMN IF NOT EXISTS motivo_consulta TEXT",
        "ALTER TABLE consultas ADD COLUMN IF NOT EXISTS enfermedad_actual TEXT",
        "ALTER TABLE consultas ADD COLUMN IF NOT EXISTS diagnostico TEXT",
        "ALTER TABLE consultas ADD COLUMN IF NOT EXISTS vital_ta VARCHAR(50)",
        "ALTER TABLE consultas ADD COLUMN IF NOT EXISTS vital_fc VARCHAR(50)",
        "ALTER TABLE consultas ADD COLUMN IF NOT EXISTS vital_fr VARCHAR(50)",
        "ALTER TABLE consultas ADD COLUMN IF NOT EXISTS vital_spo2 VARCHAR(50)",
        "ALTER TABLE consultas ADD COLUMN IF NOT EXISTS vital_peso_talla VARCHAR(100)",
        "ALTER TABLE consultas ADD COLUMN IF NOT EXISTS fisico_piel TEXT",
        "ALTER TABLE consultas ADD COLUMN IF NOT EXISTS fisico_cabeza TEXT",
        "ALTER TABLE consultas ADD COLUMN IF NOT EXISTS fisico_cuello TEXT",
        "ALTER TABLE consultas ADD COLUMN IF NOT EXISTS fisico_torax TEXT",
        "ALTER TABLE consultas ADD COLUMN IF NOT EXISTS fisico_abdomen TEXT",
        "ALTER TABLE consultas ADD COLUMN IF NOT EXISTS fisico_extremidades TEXT",
        "ALTER TABLE consultas ADD COLUMN IF NOT EXISTS fisico_neurologico TEXT",
        "ALTER TABLE consultas ADD COLUMN IF NOT EXISTS laboratorios TEXT",
        "ALTER TABLE consultas ADD COLUMN IF NOT EXISTS plan_tratamiento TEXT",
        "ALTER TABLE consultas ADD COLUMN IF NOT EXISTS pendiente TEXT"
    ];

    echo "\nIniciando migración de la tabla 'consultas'...\n";
    foreach ($alterConsultas as $query) {
        $db->exec($query);
        echo "Ejecutado: $query\n";
    }

    echo "\nMigración completada con éxito.\n";

} catch (Exception $e) {
    echo "ERROR en la migración: " . $e->getMessage() . "\n";
    exit(1);
}
