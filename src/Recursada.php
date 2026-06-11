<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class Recursada {

    public static function getByAlumno(int $alumno_id): array {
        $stmt = DB::get()->prepare(
            'SELECT r.*, m.nombre AS materia_nombre
             FROM recursadas r
             JOIN materias m ON m.id = r.materia_id
             WHERE r.alumno_id = ?
             ORDER BY r.anio DESC, m.nombre ASC'
        );
        $stmt->execute([$alumno_id]);
        return $stmt->fetchAll();
    }

    public static function getAll(): array {
        $stmt = DB::get()->query(
            'SELECT r.*, a.nombre AS alumno_nombre, a.curso, a.legajo, m.nombre AS materia_nombre
             FROM recursadas r
             JOIN alumnos a ON a.id = r.alumno_id
             JOIN materias m ON m.id = r.materia_id
             ORDER BY m.nombre ASC, a.curso ASC, a.nombre ASC'
        );
        return $stmt->fetchAll();
    }

    public static function crear(int $alumno_id, int $materia_id, int $anio, string $observacion = '', ?string $fecha = null, ?string $horario = null): int {
        $stmt = DB::get()->prepare(
            'INSERT INTO recursadas (alumno_id, materia_id, anio, observacion, fecha, horario)
             VALUES (?, ?, ?, ?, ?, ?)
             ON CONFLICT (alumno_id, materia_id, anio) DO UPDATE SET
                observacion = EXCLUDED.observacion,
                fecha       = EXCLUDED.fecha,
                horario     = EXCLUDED.horario
             RETURNING id'
        );
        $stmt->execute([$alumno_id, $materia_id, $anio, $observacion ?: null, $fecha ?: null, $horario ?: null]);
        return (int)$stmt->fetchColumn();
    }

    public static function eliminar(int $id): void {
        $stmt = DB::get()->prepare('DELETE FROM recursadas WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function toggleAprobada(int $id): bool {
        $stmt = DB::get()->prepare(
            'UPDATE recursadas SET aprobada = NOT aprobada WHERE id = ? RETURNING aprobada'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? (bool)$row['aprobada'] : false;
    }

    public static function existe(int $alumno_id, int $materia_id, int $anio): bool {
        $stmt = DB::get()->prepare(
            'SELECT 1 FROM recursadas WHERE alumno_id=? AND materia_id=? AND anio=?'
        );
        $stmt->execute([$alumno_id, $materia_id, $anio]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Devuelve datos para la planilla de trayectoria de un curso.
     * Retorna: alumnos del curso + sus recursadas del año lectivo dado.
     */
    public static function getPlanilla(string $curso, int $anio_lectivo): array {
        // Alumnos del curso ordenados por nombre
        $stmt = DB::get()->prepare(
            'SELECT a.id, a.nombre, a.curso, a.legajo
             FROM alumnos a
             WHERE a.curso = ?
             ORDER BY a.nombre ASC'
        );
        $stmt->execute([$curso]);
        $alumnos = $stmt->fetchAll();

        // Todas las recursadas del curso para ese año lectivo
        $stmt = DB::get()->prepare(
            'SELECT r.alumno_id, r.materia_id, r.observacion, m.nombre AS materia_nombre
             FROM recursadas r
             JOIN alumnos a ON a.id = r.alumno_id
             JOIN materias m ON m.id = r.materia_id
             WHERE a.curso = ? AND r.anio = ?'
        );
        $stmt->execute([$curso, $anio_lectivo]);
        $recursadas = $stmt->fetchAll();

        // Indexar por alumno_id → materia_id
        $idx = [];
        foreach ($recursadas as $r) {
            $idx[$r['alumno_id']][$r['materia_id']] = $r;
        }

        // Armar filas
        foreach ($alumnos as &$a) {
            $a['recursadas'] = $idx[$a['id']] ?? [];
        }
        unset($a);

        return $alumnos;
    }

    /**
     * Extrae el número de año del curso (ej: "2°1°" → 2, "3ro A" → 3).
     */
    public static function extraerAnio(string $curso): int {
        preg_match('/(\d)/', $curso, $m);
        return isset($m[1]) ? (int)$m[1] : 0;
    }

    /**
     * Retorna recursadas de un alumno separadas en recursa e intensifica,
     * usando solo el año del curso del alumno (sin columna anio en materias).
     */
    public static function getByAlumnoDetalle(int $alumno_id): array {
        $stmt = DB::get()->prepare(
            'SELECT r.*, m.nombre AS materia_nombre,
                    a.curso AS alumno_curso
             FROM recursadas r
             JOIN materias m ON m.id = r.materia_id
             JOIN alumnos a ON a.id = r.alumno_id
             WHERE r.alumno_id = ?
             ORDER BY r.anio DESC, m.nombre ASC'
        );
        $stmt->execute([$alumno_id]);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$r) {
            $r['tipo'] = 'recursa';
        }
        unset($r);

        return [
            'recursa'     => $rows,
            'intensifica' => [],
            'anio_curso'  => self::extraerAnio($rows[0]['alumno_curso'] ?? ''),
        ];
    }
}
