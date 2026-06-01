<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class Boletin {

    // ── Periodos ──────────────────────────────────────────────────────────────

    public static function getPeriodos(): array {
        return DB::get()->query(
            'SELECT * FROM periodos ORDER BY anio DESC, orden ASC'
        )->fetchAll();
    }

    public static function createPeriodo(string $nombre, int $anio, int $orden): int {
        $stmt = DB::get()->prepare(
            'INSERT INTO periodos (nombre, anio, orden) VALUES (?, ?, ?) RETURNING id'
        );
        $stmt->execute([$nombre, $anio, $orden]);
        return (int)$stmt->fetchColumn();
    }

    public static function deletePeriodo(int $id): void {
        $stmt = DB::get()->prepare('DELETE FROM periodos WHERE id = ?');
        $stmt->execute([$id]);
    }

    // ── Notas ─────────────────────────────────────────────────────────────────

    /**
     * Devuelve el boletín completo de un alumno:
     * array[ materia_nombre => [ periodo_nombre => nota, ..., 'promedio' => x, 'condicion' => 'Aprobada'|'Previa'|'Recursa' ] ]
     */
    public static function getBoletinAlumno(int $alumno_id): array {
        $stmt = DB::get()->prepare(
            'SELECT n.nota, m.nombre AS materia, p.nombre AS periodo, p.orden, p.anio
             FROM notas n
             JOIN materias m ON m.id = n.materia_id
             JOIN periodos p ON p.id = n.periodo_id
             WHERE n.alumno_id = ?
             ORDER BY m.nombre ASC, p.anio ASC, p.orden ASC'
        );
        $stmt->execute([$alumno_id]);
        $rows = $stmt->fetchAll();

        $boletin = [];
        foreach ($rows as $r) {
            $mat = $r['materia'];
            $per = $r['periodo'];
            if (!isset($boletin[$mat])) {
                $boletin[$mat] = ['notas' => [], 'promedio' => null, 'condicion' => 'Sin notas'];
            }
            $boletin[$mat]['notas'][$per] = (float)$r['nota'];
        }

        foreach ($boletin as $mat => &$data) {
            $notas = array_values($data['notas']);
            if (count($notas) > 0) {
                $prom = array_sum($notas) / count($notas);
                $data['promedio'] = round($prom, 2);
                $data['condicion'] = self::calcularCondicion($notas, $prom);
            }
        }
        unset($data);

        return $boletin;
    }

    /**
     * Devuelve materias en condición de previa (promedio < 7 pero >= 4)
     * o recursada (promedio < 4 o más de 2 periodos con nota < 4).
     */
    public static function getMateriasEnPrevia(int $alumno_id): array {
        $boletin = self::getBoletinAlumno($alumno_id);
        $result  = [];
        foreach ($boletin as $materia => $data) {
            if (in_array($data['condicion'], ['Previa', 'Recursa'], true)) {
                $result[] = [
                    'materia'   => $materia,
                    'promedio'  => $data['promedio'],
                    'condicion' => $data['condicion'],
                ];
            }
        }
        return $result;
    }

    /**
     * Guarda o actualiza una nota.
     */
    public static function upsertNota(int $alumno_id, int $materia_id, int $periodo_id, float $nota): void {
        $stmt = DB::get()->prepare(
            'INSERT INTO notas (alumno_id, materia_id, periodo_id, nota)
             VALUES (?, ?, ?, ?)
             ON CONFLICT (alumno_id, materia_id, periodo_id)
             DO UPDATE SET nota = EXCLUDED.nota'
        );
        $stmt->execute([$alumno_id, $materia_id, $periodo_id, $nota]);
    }

    public static function deleteNota(int $alumno_id, int $materia_id, int $periodo_id): void {
        $stmt = DB::get()->prepare(
            'DELETE FROM notas WHERE alumno_id=? AND materia_id=? AND periodo_id=?'
        );
        $stmt->execute([$alumno_id, $materia_id, $periodo_id]);
    }

    /**
     * Alumnos recursantes por materia (condicion = Recursa).
     */
    public static function getRecursantesPorMateria(): array {
        // Trae todos los alumnos con notas y calcula condición
        $stmt = DB::get()->query(
            'SELECT DISTINCT n.alumno_id, a.nombre AS alumno_nombre, a.curso,
                    n.materia_id, m.nombre AS materia_nombre
             FROM notas n
             JOIN alumnos a ON a.id = n.alumno_id
             JOIN materias m ON m.id = n.materia_id
             ORDER BY m.nombre, a.curso, a.nombre'
        );
        $rows = $stmt->fetchAll();

        // Agrupar por alumno+materia y calcular condición
        $grupos = [];
        foreach ($rows as $r) {
            $key = $r['alumno_id'] . '_' . $r['materia_id'];
            if (!isset($grupos[$key])) {
                $grupos[$key] = [
                    'alumno_id'      => $r['alumno_id'],
                    'alumno_nombre'  => $r['alumno_nombre'],
                    'curso'          => $r['curso'],
                    'materia_id'     => $r['materia_id'],
                    'materia_nombre' => $r['materia_nombre'],
                ];
            }
        }

        $recursantes = [];
        foreach ($grupos as $key => $g) {
            $boletin = self::getBoletinAlumno($g['alumno_id']);
            $mat     = $g['materia_nombre'];
            if (isset($boletin[$mat]) && $boletin[$mat]['condicion'] === 'Recursa') {
                $g['promedio'] = $boletin[$mat]['promedio'];
                $recursantes[] = $g;
            }
        }
        return $recursantes;
    }

    // ── Helpers privados ──────────────────────────────────────────────────────

    private static function calcularCondicion(array $notas, float $promedio): string {
        // Recursa: promedio < 4 o 2+ periodos con nota < 4
        $bajas = count(array_filter($notas, fn($n) => $n < 4));
        if ($promedio < 4 || $bajas >= 2) return 'Recursa';
        // Previa: promedio < 7
        if ($promedio < 7) return 'Previa';
        return 'Aprobada';
    }
}
