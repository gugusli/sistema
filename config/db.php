<?php
declare(strict_types=1);

class DB {
    private static ?PDO $instance = null;

    public static function get(): PDO {
        if (self::$instance === null) {
            $dsn  = getenv('DB_DSN')  ?: $_ENV['DB_DSN']  ?? '';
            $user = getenv('DB_USER') ?: $_ENV['DB_USER'] ?? '';
            $pass = getenv('DB_PASS') ?: $_ENV['DB_PASS'] ?? '';

            // Agregar connect_timeout al DSN si no lo tiene
            if (!str_contains($dsn, 'connect_timeout')) {
                $dsn .= ';connect_timeout=5';
            }

            $intentos = 2;
            $ultimo   = null;
            while ($intentos-- > 0) {
                try {
                    self::$instance = new PDO($dsn, $user, $pass, [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                        PDO::ATTR_TIMEOUT            => 5,
                    ]);
                    $ultimo = null;
                    break;
                } catch (PDOException $e) {
                    $ultimo = $e;
                    self::$instance = null;
                    if ($intentos > 0) sleep(2);
                }
            }
            if ($ultimo !== null) throw $ultimo;
        }
        return self::$instance;
    }
}
