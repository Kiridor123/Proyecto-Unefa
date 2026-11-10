<?php
declare(strict_types=1);

/** Reglas compartidas de programación y reprogramación de citas. */

const CITA_ESTADOS = ['Pendiente', 'Completada', 'Cancelada'];
const CITA_MAX_ANTICIPACION = '+2 years';
const CITA_MOTIVO_MAX = 500;

/** Valida la fecha de una cita: futura y como mucho dos años adelante. Devuelve el error o null. */
function cita_fecha_problema(?DateTimeImmutable $date): ?string
{
    if (!$date) return 'Indique una fecha y hora válidas (AAAA-MM-DD HH:MM).';
    $now = new DateTimeImmutable();
    if ($date <= $now) return 'Indique una fecha y hora en el futuro.';
    if ($date > $now->modify(CITA_MAX_ANTICIPACION)) return 'La cita no puede programarse con más de dos años de anticipación.';
    return null;
}

/**
 * Comprueba que el paciente no tenga otra cita pendiente a menos de 30 minutos.
 * Debe llamarse con el paciente ya bloqueado (SELECT … FOR UPDATE).
 */
function cita_verificar_intervalo(PDO $db, int $patientId, string $appointmentDate, ?int $excludeId = null): void
{
    $statement = $db->prepare("SELECT id FROM citas WHERE paciente_id = ? AND estado = 'Pendiente'
        AND fecha_cita > CAST(? AS TIMESTAMP) - INTERVAL '30 minutes'
        AND fecha_cita < CAST(? AS TIMESTAMP) + INTERVAL '30 minutes'
        AND id <> ? LIMIT 1");
    $statement->execute([$patientId, $appointmentDate, $appointmentDate, $excludeId ?? 0]);
    if ($statement->fetchColumn()) {
        throw new ApiException('El paciente ya tiene una cita pendiente a menos de 30 minutos de esta hora.', 409, ['fecha_cita' => 'Seleccione otra hora.']);
    }
}
