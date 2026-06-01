<?php
declare(strict_types=1);

// Cargar .env si existe (desarrollo local)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    if (file_exists(__DIR__ . '/../.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->safeLoad();
    }
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Previa.php';
require_once __DIR__ . '/../src/Notificacion.php';

$proximas = Previa::getPreviasProximas(5);

if (empty($proximas)) {
    error_log('[cron] Sin previas próximas en este ciclo.');
    exit(0);
}

foreach ($proximas as $previa) {
    $sub = Notificacion::getSubscriptionByAlumno((int)$previa['alumno_id']);
    if (!$sub) {
        error_log("[cron] Alumno {$previa['alumno_id']} sin suscripción push. Previa ID {$previa['id']} omitida.");
        Previa::marcarPushEnviado((int)$previa['id']); // evitar reintentos infinitos
        continue;
    }

    $payload = [
        'titulo' => 'Previa en 5 minutos',
        'cuerpo' => "Previa en 5 minutos: {$previa['materia_nombre']} - Aula {$previa['aula']}",
        'url'    => '/alumno/dashboard',
    ];

    $ok = Notificacion::sendPush($sub, $payload);
    Previa::marcarPushEnviado((int)$previa['id']);

    if ($ok) {
        error_log("[cron] Push enviado OK. Previa ID {$previa['id']} - Alumno: {$previa['alumno_nombre']}");
    } else {
        error_log("[cron] Fallo al enviar push. Previa ID {$previa['id']} - Alumno: {$previa['alumno_nombre']}");
    }
}
