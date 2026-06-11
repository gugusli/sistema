-- Migración: agregar fecha y horario a recursadas
-- Ejecutar en Neon → Editor SQL

ALTER TABLE recursadas ADD COLUMN IF NOT EXISTS fecha   DATE;
ALTER TABLE recursadas ADD COLUMN IF NOT EXISTS horario TIME;
