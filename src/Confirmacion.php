<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class Confirmacion {
    public static function confirmar(int $previa_id, int $alumno_id): void {
        $stmt = DB::get()->prepare(
            'INSERT INTO confirmaciones_lectura (previa_id, alumno_id)
             VALUES (?, ?) ON CONFLICT (previa_id, alumno_id) DO NOTHING'
        );
        $stmt->execute([$previa_id, $alumno_id]);
    }

    public static function getConfirmadas(int $alumno_id): array {
        $stmt = DB::get()->prepare(
            'SELECT previa_id FROM confirmaciones_lectura WHERE alumno_id = ?'
        );
        $stmt->execute([$alumno_id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function yaConfirmo(int $previa_id, int $alumno_id): bool {
        $stmt = DB::get()->prepare(
            'SELECT 1 FROM confirmaciones_lectura WHERE previa_id=? AND alumno_id=?'
        );
        $stmt->execute([$previa_id, $alumno_id]);
        return (bool)$stmt->fetchColumn();
    }

    public static function getAllParaPanel(): array {
        return DB::get()->query(
            'SELECT cl.previa_id, cl.alumno_id, cl.confirmado_at,
                    a.nombre AS alumno_nombre, m.nombre AS materia_nombre, p.fecha
             FROM confirmaciones_lectura cl
             JOIN alumnos a ON a.id = cl.alumno_id
             JOIN previas p ON p.id = cl.previa_id
             JOIN materias m ON m.id = p.materia_id
             ORDER BY cl.confirmado_at DESC'
        )->fetchAll();
    }
}
