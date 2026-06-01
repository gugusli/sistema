<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class Alumno {
    public static function getById(int $id): array|false {
        $stmt = DB::get()->prepare('SELECT * FROM alumnos WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function getByDni(string $dni): array|false {
        $stmt = DB::get()->prepare('SELECT * FROM alumnos WHERE dni = ?');
        $stmt->execute([$dni]);
        return $stmt->fetch();
    }

    public static function getAll(): array {
        return DB::get()->query('SELECT * FROM alumnos ORDER BY nombre ASC')->fetchAll();
    }

    public static function create(string $dni, string $nombre, string $curso, string $legajo = ''): int {
        $stmt = DB::get()->prepare(
            'INSERT INTO alumnos (dni, nombre, curso, legajo) VALUES (?, ?, ?, ?) RETURNING id'
        );
        $stmt->execute([$dni, $nombre, $curso, $legajo ?: null]);
        return (int)$stmt->fetchColumn();
    }

    public static function importarCSV(string $rutaArchivo): array {
        $exitos = 0;
        $errores = [];
        $linea = 0;

        if (($fh = fopen($rutaArchivo, 'r')) === false) {
            return ['exitos' => 0, 'errores' => ['No se pudo abrir el archivo']];
        }

        $stmt = DB::get()->prepare(
            'INSERT INTO alumnos (dni, nombre, curso) VALUES (?, ?, ?)
             ON CONFLICT (dni) DO NOTHING'
        );

        while (($row = fgetcsv($fh)) !== false) {
            $linea++;
            if (count($row) < 3) {
                $errores[] = "Línea $linea: formato inválido";
                continue;
            }
            [$dni, $nombre, $curso] = array_map('trim', $row);
            if ($dni === '' || $nombre === '' || $curso === '') {
                $errores[] = "Línea $linea: campos vacíos";
                continue;
            }
            try {
                $stmt->execute([$dni, $nombre, $curso]);
                if ($stmt->rowCount() > 0) $exitos++;
                else $errores[] = "Línea $linea: DNI $dni ya existe";
            } catch (PDOException $e) {
                $errores[] = "Línea $linea: " . $e->getMessage();
            }
        }
        fclose($fh);
        return ['exitos' => $exitos, 'errores' => $errores];
    }

    public static function delete(int $id): void {
        $stmt = DB::get()->prepare('DELETE FROM alumnos WHERE id = ?');
        $stmt->execute([$id]);
    }

    /**
     * Ficha completa: datos del alumno + previas + historial.
     */
    public static function getFicha(int $id): array|false {
        $alumno = self::getById($id);
        if (!$alumno) return false;

        $stmt = DB::get()->prepare(
            'SELECT p.*, m.nombre AS materia_nombre
             FROM previas p
             JOIN materias m ON m.id = p.materia_id
             WHERE p.alumno_id = ?
             ORDER BY p.fecha DESC, p.horario DESC'
        );
        $stmt->execute([$id]);
        $alumno['previas'] = $stmt->fetchAll();

        return $alumno;
    }

    /**
     * Busca alumnos por legajo, DNI o nombre (búsqueda parcial).
     */
    public static function buscar(string $q): array {
        $like = '%' . $q . '%';
        $stmt = DB::get()->prepare(
            'SELECT * FROM alumnos
             WHERE legajo ILIKE ? OR dni ILIKE ? OR nombre ILIKE ?
             ORDER BY nombre ASC LIMIT 20'
        );
        $stmt->execute([$like, $like, $like]);
        return $stmt->fetchAll();
    }

    /**
     * Busca exacto por legajo.
     */
    public static function getByLegajo(string $legajo): array|false {
        $stmt = DB::get()->prepare('SELECT * FROM alumnos WHERE legajo = ?');
        $stmt->execute([$legajo]);
        return $stmt->fetch();
    }

    /**
     * Actualiza datos del alumno.
     */
    public static function update(int $id, string $nombre, string $curso, string $legajo): void {
        $stmt = DB::get()->prepare(
            'UPDATE alumnos SET nombre=?, curso=?, legajo=? WHERE id=?'
        );
        $stmt->execute([$nombre, $curso, $legajo ?: null, $id]);
    }
}
