-- Migración: planilla de trayectoria
-- Ejecutar en Neon → Editor SQL

-- Agregar año a materias (1 = primer año, 2 = segundo año, etc.)
ALTER TABLE materias ADD COLUMN IF NOT EXISTS anio SMALLINT NOT NULL DEFAULT 1;

-- Tabla de intensificaciones: alumno intensifica una materia de año anterior
-- materia_id = materia que intensifica (del año anterior)
-- materia_intensifica_id = materia equivalente del año actual del alumno (opcional, para mostrar en celda)
CREATE TABLE IF NOT EXISTS intensificaciones (
    id SERIAL PRIMARY KEY,
    alumno_id              INT REFERENCES alumnos(id) ON DELETE CASCADE,
    materia_id             INT REFERENCES materias(id) ON DELETE CASCADE,  -- materia de 1ro que intensifica
    materia_intensifica_id INT REFERENCES materias(id) ON DELETE SET NULL, -- materia de 2do (lo que cursa)
    anio_lectivo           SMALLINT NOT NULL DEFAULT EXTRACT(YEAR FROM NOW())::SMALLINT,
    UNIQUE(alumno_id, materia_id, anio_lectivo)
);

-- Actualizar materias existentes con su año
-- (ajustar según las materias reales de la escuela)
-- Las materias del seed son genéricas, acá las marcamos como año 1 por defecto
-- La preceptora puede editarlas desde el panel de materias
