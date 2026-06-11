<?php
declare(strict_types=1);

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    if (file_exists(__DIR__ . '/../.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->safeLoad();
    }
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Recursada.php';
require_once __DIR__ . '/../src/Notificacion.php';

// Buscar recursadas con fecha de examen próxima (en las próximas 24hs)
$stmt = DB::get()->prepare(
    "SELECT r.*, a.nombre AS alumno_nombre, a.id AS alumno_id, m.nombre AS materia_nombre
     FROM recursadas r
     JOIN alumnos a ON a.id = r.alumno_id
     JOIN materias m ON m.id = r.materia_id
     WHERE r.fecha = CURRENT_DATE
       AND r.horario IS NOT NULL
       AND r.push_enviado IS DISTINCT FROM true"
);
$stmt->execute();
$proximas = $stmt->fetchAll();

if (empty($proximas)) {
    error_log('[cron] Sin recursadas con examen próximo.');
    exit(0);
}

foreach ($proximas as $r) {
    $sub = Notificacion::getSubscriptionByAlumno((int)$r['alumno_id']);
    if (!$sub) {
        error_log("[cron] Alumno {$r['alumno_id']} sin suscripción. Recursada ID {$r['id']} omitida.");
        continue;
    }

    $horario = $r['horario'] ? substr($r['horario'], 0, 5) : '';
    $payload = [
        'titulo' => 'Recordatorio de examen',
        'cuerpo' => "Hoy {$r['materia_nombre']} a las {$horario} hs",
        'url'    => '/alumno/panel',
    ];

    $ok = Notificacion::sendPush($sub, $payload);

    // Marcar como enviado para no re-enviar
    DB::get()->prepare('UPDATE recursadas SET push_enviado = true WHERE id = ?')->execute([(int)$r['id']]);

    if ($ok) {
        error_log("[cron] Push enviado OK. Recursada ID {$r['id']} - {$r['alumno_nombre']}");
    } else {
        error_log("[cron] Fallo push. Recursada ID {$r['id']} - {$r['alumno_nombre']}");
    }
}
