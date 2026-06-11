<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';

class Intensificacion {

    public static function getByAlumno(int $alumno_id): array {
        $stmt = DB::get()->prepare(
            'SELECT i.*, m.nombre AS materia_nombre
             FROM intensificaciones i
             JOIN materias m ON m.id = i.materia_id
             WHERE i.alumno_id = ?
             ORDER BY i.anio_lectivo DESC, m.nombre ASC'
        );
        $stmt->execute([$alumno_id]);
        return $stmt->fetchAll();
    }

    public static function upsert(int $alumno_id, int $materia_id, int $anio, array $data): int {
        $stmt = DB::get()->prepare(
            'INSERT INTO intensificaciones
                (alumno_id, materia_id, anio_lectivo,
                 semana1_inicio, semana2_inicio,
                 lunes_horario, martes_horario, miercoles_horario, jueves_horario, viernes_horario,
                 lunes_semana,  martes_semana,  miercoles_semana,  jueves_semana,  viernes_semana,
                 observacion)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
             ON CONFLICT (alumno_id, materia_id, anio_lectivo) DO UPDATE SET
                semana1_inicio    = EXCLUDED.semana1_inicio,
                semana2_inicio    = EXCLUDED.semana2_inicio,
                lunes_horario     = EXCLUDED.lunes_horario,
                martes_horario    = EXCLUDED.martes_horario,
                miercoles_horario = EXCLUDED.miercoles_horario,
                jueves_horario    = EXCLUDED.jueves_horario,
                viernes_horario   = EXCLUDED.viernes_horario,
                lunes_semana      = EXCLUDED.lunes_semana,
                martes_semana     = EXCLUDED.martes_semana,
                miercoles_semana  = EXCLUDED.miercoles_semana,
                jueves_semana     = EXCLUDED.jueves_semana,
                viernes_semana    = EXCLUDED.viernes_semana,
                observacion       = EXCLUDED.observacion
             RETURNING id'
        );
        $stmt->execute([
            $alumno_id, $materia_id, $anio,
            $data['semana1_inicio'], $data['semana2_inicio'],
            $data['lunes_horario']     ?: null,
            $data['martes_horario']    ?: null,
            $data['miercoles_horario'] ?: null,
            $data['jueves_horario']    ?: null,
            $data['viernes_horario']   ?: null,
            $data['lunes_semana']      ?? 12,
            $data['martes_semana']     ?? 12,
            $data['miercoles_semana']  ?? 12,
            $data['jueves_semana']     ?? 12,
            $data['viernes_semana']    ?? 12,
            $data['observacion']       ?: null,
        ]);
        return (int)$stmt->fetchColumn();
    }

    public static function eliminar(int $id): void {
        DB::get()->prepare('DELETE FROM intensificaciones WHERE id = ?')->execute([$id]);
    }

    public static function toggleAprobada(int $id): bool {
        $stmt = DB::get()->prepare(
            'UPDATE intensificaciones SET aprobada = NOT aprobada WHERE id = ? RETURNING aprobada'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? (bool)$row['aprobada'] : false;
    }

    /**
     * Genera el array de días con su horario y en qué semana(s) aplica.
     * Útil para mostrar en la vista.
     */
    public static function parseDias(array $intensificacion): array {
        $dias = ['lunes','martes','miercoles','jueves','viernes'];
        $result = [];
        foreach ($dias as $dia) {
            $horario = $intensificacion[$dia . '_horario'] ?? null;
            $semana  = (int)($intensificacion[$dia . '_semana'] ?? 12);
            if ($horario) {
                $result[$dia] = [
                    'horario' => $horario,
                    'semana1' => in_array($semana, [1, 12]),
                    'semana2' => in_array($semana, [2, 12]),
                ];
            }
        }
        return $result;
    }
}
