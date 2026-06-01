-- Migración: legajo y recursadas manuales
-- Ejecutar en Neon → Editor SQL

-- Agregar número de legajo a alumnos
ALTER TABLE alumnos ADD COLUMN IF NOT EXISTS legajo VARCHAR(20) UNIQUE;

-- Tabla de recursadas manuales (la preceptora marca qué materia recursa un alumno)
CREATE TABLE IF NOT EXISTS recursadas (
    id SERIAL PRIMARY KEY,
    alumno_id  INT REFERENCES alumnos(id) ON DELETE CASCADE,
    materia_id INT REFERENCES materias(id) ON DELETE CASCADE,
    anio       SMALLINT NOT NULL DEFAULT EXTRACT(YEAR FROM NOW())::SMALLINT,
    observacion TEXT,
    created_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(alumno_id, materia_id, anio)
);
