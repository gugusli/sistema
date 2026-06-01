<?php
declare(strict_types=1);
$vapidPublic = htmlspecialchars(getenv('VAPID_PUBLIC') ?: '', ENT_QUOTES, 'UTF-8');
$rol    = $_SESSION['rol'] ?? '';
$nombre = htmlspecialchars($_SESSION['nombre'] ?? '', ENT_QUOTES, 'UTF-8');
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1F3864">
    <title><?= htmlspecialchars($pageTitle ?? 'Sistema de Previas — E.E.S.T. N°5', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/public/css/base.css">
    <?php if ($rol === 'alumno'): ?>
    <link rel="stylesheet" href="/public/css/alumno.css">
    <?php elseif ($rol === 'preceptora'): ?>
    <link rel="stylesheet" href="/public/css/preceptora.css">
    <?php endif; ?>
    <script>const VAPID_PUBLIC = '<?= $vapidPublic ?>';</script>
</head>
<body>

<?php if ($rol): ?>
<nav class="navbar" role="navigation" aria-label="Navegación principal">
    <a href="/" class="navbar-brand" aria-label="Inicio">
        <img src="/public/logo.png" alt="Logo E.E.S.T. N°5" class="navbar-logo"
             onerror="this.style.display='none'">
        <div class="navbar-brand-text">
            <span>E.E.S.T. N°5</span>
            <span>Sistema de Previas</span>
        </div>
    </a>

    <button class="navbar-toggle" id="nav-toggle" aria-label="Menú" aria-expanded="false">
        <span></span><span></span><span></span>
    </button>

    <div class="navbar-links" id="nav-links" role="menubar">
        <?php if ($rol === 'alumno'): ?>
            <a href="/alumno/dashboard"   <?= $uri === '/alumno/dashboard'   ? 'class="active"' : '' ?> role="menuitem">Inicio</a>
            <a href="/alumno/calendario"  <?= $uri === '/alumno/calendario'  ? 'class="active"' : '' ?> role="menuitem">Calendario</a>
            <a href="/alumno/historial"   <?= $uri === '/alumno/historial'   ? 'class="active"' : '' ?> role="menuitem">Historial</a>
            <a href="/alumno/trayectoria" <?= $uri === '/alumno/trayectoria' ? 'class="active"' : '' ?> role="menuitem">Trayectoria</a>
        <?php elseif ($rol === 'preceptora'): ?>
            <a href="/preceptora/panel"       <?= $uri === '/preceptora/panel'       ? 'class="active"' : '' ?> role="menuitem">Panel</a>
            <a href="/preceptora/alumnos"     <?= $uri === '/preceptora/alumnos'     ? 'class="active"' : '' ?> role="menuitem">Alumnos</a>
            <a href="/preceptora/ficha"       <?= str_starts_with($uri, '/preceptora/ficha') ? 'class="active"' : '' ?> role="menuitem">Legajos</a>
            <a href="/preceptora/planilla"    <?= $uri === '/preceptora/planilla'    ? 'class="active"' : '' ?> role="menuitem">Planilla</a>
            <a href="/preceptora/recursantes" <?= $uri === '/preceptora/recursantes' ? 'class="active"' : '' ?> role="menuitem">Recursantes</a>
        <?php endif; ?>
        <span class="navbar-user" title="<?= $nombre ?>">👤 <?= $nombre ?></span>
        <a href="/logout" class="btn-logout" role="menuitem">Salir</a>
    </div>
</nav>
<?php endif; ?>

<main class="container" id="main-content">
    <?= $content ?? '' ?>
</main>

<footer class="footer">
    <img src="/public/logo.png" alt="" aria-hidden="true" onerror="this.style.display='none'">
    E.E.S.T. N°5 Berazategui &copy; <?= date('Y') ?> — Sistema de Previas
</footer>

<?php if ($rol === 'alumno'): ?>
<script src="/public/js/push.js"></script>
<script src="/public/js/alumno.js"></script>
<?php elseif ($rol === 'preceptora'): ?>
<script src="/public/js/preceptora.js"></script>
<?php endif; ?>

<script>
// Hamburger menu
const toggle = document.getElementById('nav-toggle');
const links  = document.getElementById('nav-links');
if (toggle && links) {
    toggle.addEventListener('click', () => {
        const open = links.classList.toggle('open');
        toggle.classList.toggle('open', open);
        toggle.setAttribute('aria-expanded', open);
    });
    // Cerrar al hacer click en un link
    links.querySelectorAll('a').forEach(a => {
        a.addEventListener('click', () => {
            links.classList.remove('open');
            toggle.classList.remove('open');
            toggle.setAttribute('aria-expanded', 'false');
        });
    });
    // Cerrar al hacer click fuera
    document.addEventListener('click', e => {
        if (!e.target.closest('.navbar')) {
            links.classList.remove('open');
            toggle.classList.remove('open');
            toggle.setAttribute('aria-expanded', 'false');
        }
    });
}
</script>
</body>
</html>
