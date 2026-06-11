-- Migración: trayectoria completa (intensificaciones)
-- Ejecutar en Neon → Editor SQL

-- Columnas en recursadas
ALTER TABLE recursadas ADD COLUMN IF NOT EXISTS aprobada BOOLEAN NOT NULL DEFAULT false;
ALTER TABLE recursadas ADD COLUMN IF NOT EXISTS push_enviado BOOLEAN NOT NULL DEFAULT false;
ALTER TABLE recursadas ADD COLUMN IF NOT EXISTS fecha DATE;
ALTER TABLE recursadas ADD COLUMN IF NOT EXISTS horario TIME;

-- Intensificaciones: materia que el alumno intensifica en dos semanas fijas
CREATE TABLE IF NOT EXISTS intensificaciones (
    id          SERIAL PRIMARY KEY,
    alumno_id   INT REFERENCES alumnos(id) ON DELETE CASCADE,
    materia_id  INT REFERENCES materias(id) ON DELETE CASCADE,
    anio_lectivo SMALLINT NOT NULL DEFAULT EXTRACT(YEAR FROM NOW())::SMALLINT,

    -- Fechas de inicio de cada semana
    semana1_inicio DATE NOT NULL,
    semana2_inicio DATE NOT NULL,

    -- Horarios por día (NULL = no tiene clase ese día)
    -- Formato: 'HH:MM-HH:MM' o NULL
    lunes_horario     VARCHAR(20),
    martes_horario    VARCHAR(20),
    miercoles_horario VARCHAR(20),
    jueves_horario    VARCHAR(20),
    viernes_horario   VARCHAR(20),

    -- Días activos por semana (para mostrar en qué semana va cada día)
    -- 1 = semana 1, 2 = semana 2, 12 = ambas semanas
    lunes_semana     SMALLINT DEFAULT 12,
    martes_semana    SMALLINT DEFAULT 12,
    miercoles_semana SMALLINT DEFAULT 12,
    jueves_semana    SMALLINT DEFAULT 12,
    viernes_semana   SMALLINT DEFAULT 12,

    aprobada    BOOLEAN NOT NULL DEFAULT false,
    observacion TEXT,
    created_at  TIMESTAMP DEFAULT NOW(),
    UNIQUE(alumno_id, materia_id, anio_lectivo)
);
