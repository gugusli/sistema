<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class Auth {
    public static function loginAlumno(string $dni): bool {
        $stmt = DB::get()->prepare('SELECT id, nombre FROM alumnos WHERE dni = ?');
        $stmt->execute([$dni]);
        $alumno = $stmt->fetch();
        if (!$alumno) return false;

        session_regenerate_id(true);
        $_SESSION['rol']      = 'alumno';
        $_SESSION['id']       = $alumno['id'];
        $_SESSION['nombre']   = $alumno['nombre'];
        return true;
    }

    public static function loginPreceptora(string $usuario, string $password): bool {
        $stmt = DB::get()->prepare('SELECT id, password_hash FROM preceptoras WHERE usuario = ?');
        $stmt->execute([$usuario]);
        $row = $stmt->fetch();
        if (!$row || !password_verify($password, $row['password_hash'])) return false;

        session_regenerate_id(true);
        $_SESSION['rol']    = 'preceptora';
        $_SESSION['id']     = $row['id'];
        $_SESSION['nombre'] = $usuario;
        return true;
    }

    public static function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function requireAlumno(): void {
        if (($_SESSION['rol'] ?? '') !== 'alumno') {
            header('Location: /login'); exit;
        }
    }

    public static function requirePreceptora(): void {
        if (($_SESSION['rol'] ?? '') !== 'preceptora') {
            header('Location: /login'); exit;
        }
    }
}
