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
require_once __DIR__ . '/src/Previa.php';
require_once __DIR__ . '/src/Notificacion.php';
require_once __DIR__ . '/src/Confirmacion.php';
require_once __DIR__ . '/src/Boletin.php';
require_once __DIR__ . '/src/Recursada.php';

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
    if ($rol === 'alumno')      redirect('/alumno/dashboard');
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
        redirect('/alumno/dashboard');
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

if ($uri === '/alumno/dashboard' && $method === 'GET') {
    Auth::requireAlumno();
    require __DIR__ . '/views/alumno/dashboard.php';
    exit;
}

if ($uri === '/alumno/historial' && $method === 'GET') {
    Auth::requireAlumno();
    require __DIR__ . '/views/alumno/historial.php';
    exit;
}

if ($uri === '/alumno/trayectoria' && $method === 'GET') {
    Auth::requireAlumno();
    require __DIR__ . '/views/alumno/trayectoria.php';
    exit;
}

if ($uri === '/alumno/calendario' && $method === 'GET') {
    Auth::requireAlumno();
    require __DIR__ . '/views/alumno/calendario.php';
    exit;
}

if ($uri === '/alumno/boletin' && $method === 'GET') {
    Auth::requireAlumno();
    require __DIR__ . '/views/alumno/boletin.php';
    exit;
}

if ($uri === '/api/confirmacion' && $method === 'POST') {
    Auth::requireAlumno();
    $body      = jsonBody();
    $previa_id = (int)($body['previa_id'] ?? 0);
    $alumno_id = (int)$_SESSION['id'];
    if ($previa_id <= 0) jsonResponse(['error' => 'previa_id inválido'], 400);
    Confirmacion::confirmar($previa_id, $alumno_id);
    jsonResponse(['ok' => true]);
}

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

if ($uri === '/preceptora/previa/nueva' && $method === 'GET') {
    Auth::requirePreceptora();
    require __DIR__ . '/views/preceptora/nueva_previa.php';
    exit;
}

if ($uri === '/preceptora/previa/nueva' && $method === 'POST') {
    Auth::requirePreceptora();
    $data = [
        'alumno_id'  => (int)($_POST['alumno_id']  ?? 0),
        'materia_id' => (int)($_POST['materia_id'] ?? 0),
        'fecha'      => trim($_POST['fecha'] ?? ''),
        'estado'     => 'Pendiente',
    ];
    if (!$data['alumno_id'] || !$data['materia_id'] || !$data['fecha']) {
        $error = 'Todos los campos son obligatorios.';
        require __DIR__ . '/views/preceptora/nueva_previa.php';
        exit;
    }
    try {
        Previa::create($data);
        redirect('/preceptora/panel');
    } catch (PDOException $e) {
        $error = 'Error al guardar: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        require __DIR__ . '/views/preceptora/nueva_previa.php';
        exit;
    }
}

if (str_starts_with($uri, '/preceptora/previa/editar') && $method === 'GET') {
    Auth::requirePreceptora();
    require __DIR__ . '/views/preceptora/editar_previa.php';
    exit;
}

if ($uri === '/preceptora/previa/editar' && $method === 'POST') {
    Auth::requirePreceptora();
    $id   = (int)($_POST['id'] ?? 0);
    $data = [
        'alumno_id'  => (int)($_POST['alumno_id']  ?? 0),
        'materia_id' => (int)($_POST['materia_id'] ?? 0),
        'fecha'      => trim($_POST['fecha'] ?? ''),
    ];
    if (!$id || !$data['alumno_id'] || !$data['materia_id'] || !$data['fecha']) {
        $error = 'Todos los campos son obligatorios.';
        require __DIR__ . '/views/preceptora/editar_previa.php';
        exit;
    }
    Previa::update($id, $data);
    redirect('/preceptora/panel');
}

if ($uri === '/preceptora/previa/estado' && $method === 'POST') {
    Auth::requirePreceptora();
    $body   = jsonBody();
    $id     = (int)($body['id']     ?? 0);
    $estado = $body['estado'] ?? '';
    if (!$id || !in_array($estado, ['Pendiente','Aprobada','Ausente'], true)) {
        jsonResponse(['error' => 'Datos inválidos'], 400);
    }
    Previa::updateEstado($id, $estado);
    jsonResponse(['ok' => true]);
}

if ($uri === '/preceptora/previa/eliminar' && $method === 'POST') {
    Auth::requirePreceptora();
    $body = jsonBody();
    $id   = (int)($body['id'] ?? $_POST['id'] ?? 0);
    if ($id > 0) Previa::delete($id);
    jsonResponse(['ok' => true]);
}

// ── Rutas preceptora — búsqueda y ficha ──────────────────────────────────────

if ($uri === '/preceptora/buscar' && $method === 'GET') {
    Auth::requirePreceptora();
    $q = trim($_GET['q'] ?? '');
    if ($q === '') jsonResponse([]);
    jsonResponse(Alumno::buscar($q));
}

if (str_starts_with($uri, '/preceptora/ficha') && $method === 'GET') {
    Auth::requirePreceptora();
    require __DIR__ . '/views/preceptora/ficha.php';
    exit;
}

// ── Rutas preceptora — boletines ─────────────────────────────────────────────

if ($uri === '/preceptora/boletines' && $method === 'GET') {
    Auth::requirePreceptora();
    require __DIR__ . '/views/preceptora/boletines.php';
    exit;
}

if ($uri === '/preceptora/boletin/alumno' && $method === 'GET') {
    Auth::requirePreceptora();
    $id = (int)($_GET['alumno_id'] ?? 0);
    if (!$id) redirect('/preceptora/boletines');
    $alumno  = Alumno::getById($id);
    if (!$alumno) redirect('/preceptora/boletines');
    $boletin = Boletin::getBoletinAlumno($id);
    $periodos = Boletin::getPeriodos();
    $materias = DB::get()->query('SELECT * FROM materias ORDER BY nombre')->fetchAll();
    require __DIR__ . '/views/preceptora/boletin_alumno.php';
    exit;
}

if ($uri === '/preceptora/boletin/nota' && $method === 'POST') {
    Auth::requirePreceptora();
    $body       = jsonBody();
    $alumno_id  = (int)($body['alumno_id']  ?? 0);
    $materia_id = (int)($body['materia_id'] ?? 0);
    $periodo_id = (int)($body['periodo_id'] ?? 0);
    $nota       = (float)($body['nota']     ?? 0);
    if (!$alumno_id || !$materia_id || !$periodo_id || $nota < 1 || $nota > 10) {
        jsonResponse(['error' => 'Datos inválidos'], 400);
    }
    Boletin::upsertNota($alumno_id, $materia_id, $periodo_id, $nota);
    jsonResponse(['ok' => true]);
}

if ($uri === '/preceptora/boletin/nota/eliminar' && $method === 'POST') {
    Auth::requirePreceptora();
    $body       = jsonBody();
    $alumno_id  = (int)($body['alumno_id']  ?? 0);
    $materia_id = (int)($body['materia_id'] ?? 0);
    $periodo_id = (int)($body['periodo_id'] ?? 0);
    Boletin::deleteNota($alumno_id, $materia_id, $periodo_id);
    jsonResponse(['ok' => true]);
}

if ($uri === '/preceptora/periodos' && $method === 'POST') {
    Auth::requirePreceptora();
    $nombre = trim($_POST['nombre'] ?? '');
    $anio   = (int)($_POST['anio']  ?? 0);
    $orden  = (int)($_POST['orden'] ?? 0);
    if ($nombre === '' || !$anio || !$orden) {
        $error = 'Todos los campos son obligatorios.';
    } else {
        try {
            Boletin::createPeriodo($nombre, $anio, $orden);
        } catch (PDOException $e) {
            $error = 'Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }
    redirect('/preceptora/boletines');
}

if ($uri === '/preceptora/periodos/eliminar' && $method === 'POST') {
    Auth::requirePreceptora();
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) Boletin::deletePeriodo($id);
    redirect('/preceptora/boletines');
}

if ($uri === '/preceptora/recursantes' && $method === 'GET') {
    Auth::requirePreceptora();
    require __DIR__ . '/views/preceptora/recursantes.php';
    exit;
}

if ($uri === '/preceptora/planilla' && $method === 'GET') {
    Auth::requirePreceptora();
    require __DIR__ . '/views/preceptora/planilla.php';
    exit;
}

// ── Rutas preceptora — recursadas manuales ────────────────────────────────────

if ($uri === '/preceptora/recursada/crear' && $method === 'POST') {
    Auth::requirePreceptora();
    $body       = jsonBody();
    $alumno_id  = (int)($body['alumno_id']  ?? 0);
    $materia_id = (int)($body['materia_id'] ?? 0);
    $anio       = (int)($body['anio']       ?? date('Y'));
    $obs        = trim($body['observacion'] ?? '');
    if (!$alumno_id || !$materia_id) jsonResponse(['error' => 'Datos inválidos'], 400);
    $id = Recursada::crear($alumno_id, $materia_id, $anio, $obs);
    jsonResponse(['ok' => true, 'id' => $id]);
}

if ($uri === '/preceptora/recursada/eliminar' && $method === 'POST') {
    Auth::requirePreceptora();
    $body = jsonBody();
    $id   = (int)($body['id'] ?? 0);
    if ($id > 0) Recursada::eliminar($id);
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
        redirect('/preceptora/ficha?id=' . $id);
    } catch (PDOException $e) {
        $error = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        redirect('/preceptora/ficha?id=' . $id . '&error=' . urlencode($error));
    }
}

// ── 404 ───────────────────────────────────────────────────────────────────────
http_response_code(404);
echo '<h1>404 — Página no encontrada</h1>';
