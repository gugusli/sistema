<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class Previa {
    private static string $baseSelect = '
        SELECT p.*, a.nombre AS alumno_nombre, a.curso, m.nombre AS materia_nombre
        FROM previas p
        JOIN alumnos a ON a.id = p.alumno_id
        JOIN materias m ON m.id = p.materia_id
    ';

    public static function getByAlumno(int $alumno_id): array {
        $stmt = DB::get()->prepare(self::$baseSelect . ' WHERE p.alumno_id = ? ORDER BY p.fecha ASC, p.horario ASC');
        $stmt->execute([$alumno_id]);
        return $stmt->fetchAll();
    }

    public static function getByAlumnoFechaHoy(int $alumno_id): array {
        $stmt = DB::get()->prepare(self::$baseSelect . ' WHERE p.alumno_id = ? AND p.fecha = CURRENT_DATE ORDER BY p.horario ASC');
        $stmt->execute([$alumno_id]);
        return $stmt->fetchAll();
    }

    public static function getAll(): array {
        return DB::get()->query(self::$baseSelect . ' ORDER BY p.fecha ASC, p.horario ASC')->fetchAll();
    }

    public static function getByCurso(string $curso): array {
        $stmt = DB::get()->prepare(self::$baseSelect . ' WHERE a.curso = ? ORDER BY p.fecha ASC');
        $stmt->execute([$curso]);
        return $stmt->fetchAll();
    }

    public static function getByMateria(int $materia_id): array {
        $stmt = DB::get()->prepare(self::$baseSelect . ' WHERE p.materia_id = ? ORDER BY p.fecha ASC');
        $stmt->execute([$materia_id]);
        return $stmt->fetchAll();
    }

    public static function getById(int $id): array|false {
        $stmt = DB::get()->prepare(self::$baseSelect . ' WHERE p.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function create(array $data): int {
        $stmt = DB::get()->prepare(
            'INSERT INTO previas (alumno_id, materia_id, fecha, estado)
             VALUES (:alumno_id, :materia_id, :fecha, :estado) RETURNING id'
        );
        $stmt->execute([
            ':alumno_id'  => $data['alumno_id'],
            ':materia_id' => $data['materia_id'],
            ':fecha'      => $data['fecha'],
            ':estado'     => $data['estado'] ?? 'Pendiente',
        ]);
        return (int)$stmt->fetchColumn();
    }

    public static function update(int $id, array $data): void {
        $stmt = DB::get()->prepare(
            'UPDATE previas SET alumno_id=:alumno_id, materia_id=:materia_id,
             fecha=:fecha WHERE id=:id'
        );
        $stmt->execute([
            ':alumno_id'  => $data['alumno_id'],
            ':materia_id' => $data['materia_id'],
            ':fecha'      => $data['fecha'],
            ':id'         => $id,
        ]);
    }

    public static function updateEstado(int $id, string $estado): void {
        $stmt = DB::get()->prepare('UPDATE previas SET estado=? WHERE id=?');
        $stmt->execute([$estado, $id]);
    }

    public static function delete(int $id): void {
        $stmt = DB::get()->prepare('DELETE FROM previas WHERE id=?');
        $stmt->execute([$id]);
    }

    public static function getPreviasProximas(int $minutos = 5): array {
        $stmt = DB::get()->prepare(
            "SELECT p.*, a.nombre AS alumno_nombre, m.nombre AS materia_nombre
             FROM previas p
             JOIN alumnos a ON a.id = p.alumno_id
             JOIN materias m ON m.id = p.materia_id
             WHERE p.estado = 'Pendiente'
               AND p.push_enviado = false
               AND (p.fecha + p.horario) BETWEEN NOW() AND NOW() + (:min * INTERVAL '1 minute')"
        );
        $stmt->execute([':min' => $minutos]);
        return $stmt->fetchAll();
    }

    public static function marcarPushEnviado(int $id): void {
        $stmt = DB::get()->prepare('UPDATE previas SET push_enviado=true WHERE id=?');
        $stmt->execute([$id]);
    }
}
