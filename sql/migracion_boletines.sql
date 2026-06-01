-- Migración: agregar tablas de boletines
-- Ejecutar en Neon → Editor SQL

CREATE TABLE IF NOT EXISTS periodos (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    anio SMALLINT NOT NULL,
    orden SMALLINT NOT NULL,
    UNIQUE(anio, orden)
);

CREATE TABLE IF NOT EXISTS notas (
    id SERIAL PRIMARY KEY,
    alumno_id INT REFERENCES alumnos(id) ON DELETE CASCADE,
    materia_id INT REFERENCES materias(id) ON DELETE CASCADE,
    periodo_id INT REFERENCES periodos(id) ON DELETE CASCADE,
    nota NUMERIC(4,2) CHECK (nota >= 1 AND nota <= 10),
    UNIQUE(alumno_id, materia_id, periodo_id)
);
