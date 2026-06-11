<?php
declare(strict_types=1);

// Cargar .env en desarrollo local
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    if (file_exists(__DIR__ . '/.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
        $dotenv->safeLoad();
    }
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/Alumno.php';
require_once __DIR__ . '/src/Recursada.php';
require_once __DIR__ . '/src/Intensificacion.php';
require_once __DIR__ . '/src/Notificacion.php';

session_start();

$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Helper: respuesta JSON
function jsonResponse(mixed $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

// Helper: redirigir
function redirect(string $url): never {
    header("Location: $url");
    exit;
}

// Helper: input JSON del body
function jsonBody(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}

// ── Rutas públicas ──────────────────────────────────────────────────────────

if ($uri === '/' && $method === 'GET') {
    $rol = $_SESSION['rol'] ?? '';
    if ($rol === 'alumno')      redirect('/alumno/panel');
    if ($rol === 'preceptora')  redirect('/preceptora/panel');
    redirect('/login');
}

if ($uri === '/login' && $method === 'GET') {
    require __DIR__ . '/views/login.php';
    exit;
}

if ($uri === '/login' && $method === 'POST') {
    $modo = $_POST['modo'] ?? '';
    if ($modo === 'alumno') {
        $dni = trim($_POST['dni'] ?? '');
        if ($dni === '' || !Auth::loginAlumno($dni)) {
            $error = 'DNI no encontrado.';
            require __DIR__ . '/views/login.php';
            exit;
        }
        redirect('/alumno/panel');
    } elseif ($modo === 'preceptora') {
        $usuario  = trim($_POST['usuario']  ?? '');
        $password = trim($_POST['password'] ?? '');
        if (!Auth::loginPreceptora($usuario, $password)) {
            $error = 'Usuario o contraseña incorrectos.';
            require __DIR__ . '/views/login.php';
            exit;
        }
        redirect('/preceptora/panel');
    }
    $error = 'Modo de login inválido.';
    require __DIR__ . '/views/login.php';
    exit;
}

if ($uri === '/logout') {
    Auth::logout();
    redirect('/login');
}

// ── Rutas alumno ─────────────────────────────────────────────────────────────

if ($uri === '/alumno/panel' && $method === 'GET') {
    Auth::requireAlumno();
    require __DIR__ . '/views/alumno/panel.php';
    exit;
}

// ── Push notifications ─────────────────────────────────────────────────────────

if ($uri === '/api/push/subscribe' && $method === 'POST') {
    Auth::requireAlumno();
    $sub = jsonBody();
    if (empty($sub['endpoint']) || empty($sub['keys']['p256dh']) || empty($sub['keys']['auth'])) {
        jsonResponse(['error' => 'Suscripción inválida'], 400);
    }
    Notificacion::saveSubscription((int)$_SESSION['id'], $sub);
    jsonResponse(['ok' => true]);
}

// ── Rutas preceptora ──────────────────────────────────────────────────────────

if ($uri === '/preceptora/panel' && $method === 'GET') {
    Auth::requirePreceptora();
    require __DIR__ . '/views/preceptora/panel.php';
    exit;
}

// AJAX: devuelve el HTML del panel de un alumno para el modal
if (preg_match('#^/preceptora/panel/alumno/(\d+)$#', $uri, $m) && $method === 'GET') {
    Auth::requirePreceptora();
    $id     = (int)$m[1];
    $alumno = Alumno::getById($id);
    if (!$alumno) { http_response_code(404); echo 'Alumno no encontrado'; exit; }
    require __DIR__ . '/views/preceptora/panel_alumno.php';
    exit;
}

if ($uri === '/preceptora/alumnos' && $method === 'GET') {
    Auth::requirePreceptora();
    require __DIR__ . '/views/preceptora/alumnos.php';
    exit;
}

if ($uri === '/preceptora/alumnos' && $method === 'POST') {
    Auth::requirePreceptora();
    $dni    = trim($_POST['dni']    ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $curso  = trim($_POST['curso']  ?? '');
    $legajo = trim($_POST['legajo'] ?? '');
    if ($dni === '' || $nombre === '' || $curso === '') {
        $error = 'DNI, nombre y curso son obligatorios.';
        require __DIR__ . '/views/preceptora/alumnos.php';
        exit;
    }
    try {
        Alumno::create($dni, $nombre, $curso, $legajo);
        redirect('/preceptora/alumnos');
    } catch (PDOException $e) {
        $error = 'Error al guardar: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        require __DIR__ . '/views/preceptora/alumnos.php';
        exit;
    }
}

if ($uri === '/preceptora/alumnos/csv' && $method === 'POST') {
    Auth::requirePreceptora();
    if (empty($_FILES['csv']['tmp_name'])) {
        $error = 'No se recibió archivo.';
        require __DIR__ . '/views/preceptora/alumnos.php';
        exit;
    }
    $importResult = Alumno::importarCSV($_FILES['csv']['tmp_name']);
    require __DIR__ . '/views/preceptora/alumnos.php';
    exit;
}

if ($uri === '/preceptora/alumnos/eliminar' && $method === 'POST') {
    Auth::requirePreceptora();
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) Alumno::delete($id);
    redirect('/preceptora/alumnos');
}

// ── Rutas preceptora — recursadas ─────────────────────────────────────────────

if ($uri === '/preceptora/recursada/crear' && $method === 'POST') {
    Auth::requirePreceptora();
    $body       = jsonBody();
    $alumno_id  = (int)($body['alumno_id']  ?? 0);
    $materia_id = (int)($body['materia_id'] ?? 0);
    $anio       = (int)($body['anio']       ?? date('Y'));
    $obs        = trim($body['observacion'] ?? '');
    $fecha      = trim($body['fecha']       ?? '');
    $horario    = trim($body['horario']     ?? '');
    if (!$alumno_id || !$materia_id) jsonResponse(['error' => 'Datos inválidos'], 400);
    $id = Recursada::crear($alumno_id, $materia_id, $anio, $obs, $fecha ?: null, $horario ?: null);
    jsonResponse(['ok' => true, 'id' => $id]);
}

if ($uri === '/preceptora/recursada/toggle' && $method === 'POST') {
    Auth::requirePreceptora();
    $body = jsonBody();
    $id   = (int)($body['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'id inválido'], 400);
    $nuevo = Recursada::toggleAprobada($id);
    jsonResponse(['ok' => true, 'aprobada' => $nuevo]);
}

if ($uri === '/preceptora/recursada/eliminar' && $method === 'POST') {
    Auth::requirePreceptora();
    $body = jsonBody();
    $id   = (int)($body['id'] ?? 0);
    if ($id > 0) Recursada::eliminar($id);
    jsonResponse(['ok' => true]);
}

// ── Intensificaciones ─────────────────────────────────────────────────────────

if ($uri === '/preceptora/intensificacion/guardar' && $method === 'POST') {
    Auth::requirePreceptora();
    $body       = jsonBody();
    $alumno_id  = (int)($body['alumno_id']  ?? 0);
    $materia_id = (int)($body['materia_id'] ?? 0);
    $anio       = (int)($body['anio']       ?? date('Y'));
    if (!$alumno_id || !$materia_id || empty($body['semana1_inicio']) || empty($body['semana2_inicio'])) {
        jsonResponse(['error' => 'Datos incompletos'], 400);
    }
    $id = Intensificacion::upsert($alumno_id, $materia_id, $anio, $body);
    jsonResponse(['ok' => true, 'id' => $id]);
}

if ($uri === '/preceptora/intensificacion/toggle' && $method === 'POST') {
    Auth::requirePreceptora();
    $body = jsonBody();
    $id   = (int)($body['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'id inválido'], 400);
    $nuevo = Intensificacion::toggleAprobada($id);
    jsonResponse(['ok' => true, 'aprobada' => $nuevo]);
}

if ($uri === '/preceptora/intensificacion/eliminar' && $method === 'POST') {
    Auth::requirePreceptora();
    $body = jsonBody();
    $id   = (int)($body['id'] ?? 0);
    if ($id > 0) Intensificacion::eliminar($id);
    jsonResponse(['ok' => true]);
}

// ── Rutas preceptora — editar alumno ─────────────────────────────────────────

if ($uri === '/preceptora/alumnos/editar' && $method === 'POST') {
    Auth::requirePreceptora();
    $id     = (int)($_POST['id']     ?? 0);
    $nombre = trim($_POST['nombre']  ?? '');
    $curso  = trim($_POST['curso']   ?? '');
    $legajo = trim($_POST['legajo']  ?? '');
    if (!$id || !$nombre || !$curso) jsonResponse(['error' => 'Datos inválidos'], 400);
    try {
        Alumno::update($id, $nombre, $curso, $legajo);
        redirect('/preceptora/panel');
    } catch (PDOException $e) {
        redirect('/preceptora/panel');
    }
}

// ── 404 ───────────────────────────────────────────────────────────────────────
http_response_code(404);
echo '<h1>404 — Página no encontrada</h1>';
