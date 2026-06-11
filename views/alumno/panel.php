<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/Recursada.php';
require_once __DIR__ . '/../../src/Intensificacion.php';
require_once __DIR__ . '/../../src/Alumno.php';
require_once __DIR__ . '/../../src/Notificacion.php';

$alumno_id   = (int)$_SESSION['id'];
$alumno      = Alumno::getById($alumno_id);
$recursadas  = Recursada::getByAlumno($alumno_id);
$intensifica  = Intensificacion::getByAlumno($alumno_id);
$tieneSub     = (bool)Notificacion::getSubscriptionByAlumno($alumno_id);

ob_start();
?>

<?php if (!$tieneSub): ?>
<div class="banner-push" id="banner-push">
    ⚠️ Activá las notificaciones para recibir avisos
    <button onclick="subscribeToPush()">Activar</button>
    <button class="btn-cerrar" onclick="document.getElementById('banner-push').remove()">✕</button>
</div>
<?php endif; ?>

<!-- Perfil -->
<div class="perfil-header card">
    <div class="perfil-avatar">👤</div>
    <div class="perfil-info">
        <div class="perfil-nombre"><?= htmlspecialchars($alumno['nombre'], ENT_QUOTES, 'UTF-8') ?></div>
        <div class="perfil-meta">
            <span>📚 <?= htmlspecialchars($alumno['curso'], ENT_QUOTES, 'UTF-8') ?></span>
            <span>🪪 DNI: <?= htmlspecialchars($alumno['dni'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php if (!empty($alumno['legajo'])): ?>
            <span>📋 Legajo: <?= htmlspecialchars($alumno['legajo'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid" style="margin:1rem 0">
    <div class="stat-card rojo">
        <span class="stat-num"><?= count($recursadas) ?></span>
        <span class="stat-label">Recursa</span>
    </div>
    <div class="stat-card naranja">
        <span class="stat-num"><?= count($intensifica) ?></span>
        <span class="stat-label">Intensifica</span>
    </div>
    <div class="stat-card verde">
        <span class="stat-num"><?= count(array_filter($recursadas, fn($r) => $r['aprobada'])) + count(array_filter($intensifica, fn($i) => $i['aprobada'])) ?></span>
        <span class="stat-label">Aprobadas</span>
    </div>
</div>

<!-- Materias que RECURSA -->
<h2 class="section-title">Materias que recursa</h2>
<?php if (empty($recursadas)): ?>
<p class="empty-msg">No tenés materias en recursada.</p>
<?php else: ?>
<div class="tray-cards">
<?php foreach ($recursadas as $r): ?>
<div class="tray-card tray-recursa <?= $r['aprobada'] ? 'tray-aprobada' : '' ?>">
    <div class="tray-card-materia"><?= htmlspecialchars($r['materia_nombre'], ENT_QUOTES, 'UTF-8') ?></div>
    <div class="tray-card-meta">Año <?= (int)$r['anio'] ?></div>
    <?php if (!empty($r['fecha'])): ?>
    <div class="tray-card-fecha">
        📅 <?= htmlspecialchars(date('d/m/Y', strtotime($r['fecha'])), ENT_QUOTES, 'UTF-8') ?>
        <?php if (!empty($r['horario'])): ?>
        — 🕐 <?= htmlspecialchars(substr($r['horario'], 0, 5), ENT_QUOTES, 'UTF-8') ?> hs
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <span class="badge <?= $r['aprobada'] ? 'badge-aprobada' : 'badge-pendiente' ?>">
        <?= $r['aprobada'] ? 'Aprobada' : 'Pendiente' ?>
    </span>
    <?php if (!empty($r['observacion'])): ?>
    <div class="tray-card-obs">📝 <?= htmlspecialchars($r['observacion'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Materias que INTENSIFICA -->
<h2 class="section-title" style="margin-top:1.5rem">Materias que intensifica</h2>
<?php if (empty($intensifica)): ?>
<p class="empty-msg">No tenés materias para intensificar.</p>
<?php else: ?>
<div class="tray-cards">
<?php foreach ($intensifica as $int):
    $diasParsed = Intensificacion::parseDias($int);
?>
<div class="tray-card tray-intensifica <?= $int['aprobada'] ? 'tray-aprobada' : '' ?>">
    <div class="tray-card-header">
        <div class="tray-card-materia"><?= htmlspecialchars($int['materia_nombre'], ENT_QUOTES, 'UTF-8') ?></div>
        <span class="badge <?= $int['aprobada'] ? 'badge-aprobada' : 'badge-pendiente' ?>">
            <?= $int['aprobada'] ? 'Aprobada' : 'Pendiente' ?>
        </span>
    </div>
    <div class="tray-semanas">
        <span class="tray-semana-chip">📅 Sem 1: <?= htmlspecialchars(date('d/m/Y', strtotime($int['semana1_inicio'])), ENT_QUOTES, 'UTF-8') ?></span>
        <span class="tray-semana-chip">📅 Sem 2: <?= htmlspecialchars(date('d/m/Y', strtotime($int['semana2_inicio'])), ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <?php if (!empty($diasParsed)): ?>
    <div class="tray-horarios">
        <?php foreach ($diasParsed as $dia => $info): ?>
        <div class="tray-dia">
            <span class="tray-dia-nombre"><?= ucfirst($dia === 'miercoles' ? 'Miércoles' : $dia) ?></span>
            <span class="tray-dia-horario">🕐 <?= htmlspecialchars($info['horario'], ENT_QUOTES, 'UTF-8') ?></span>
            <span class="tray-dia-sem">
                <?= $info['semana1'] && $info['semana2'] ? 'Sem 1 y 2' : ($info['semana1'] ? 'Sem 1' : 'Sem 2') ?>
            </span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php if (!empty($int['observacion'])): ?>
    <div class="tray-card-obs">📝 <?= htmlspecialchars($int['observacion'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<style>
.perfil-header { display:flex; align-items:center; gap:1.25rem; margin-bottom:1rem; }
.perfil-avatar { font-size:3rem; line-height:1; flex-shrink:0; }
.perfil-nombre { font-size:1.15rem; font-weight:700; color:var(--primario); }
.perfil-meta   { display:flex; flex-wrap:wrap; gap:.6rem; margin-top:.3rem; font-size:.86rem; color:var(--texto-suave); }
.tray-cards { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:1rem; margin-bottom:.5rem; }
.tray-card { background:#fff; border-radius:var(--radio); box-shadow:var(--sombra); padding:1.1rem 1.2rem; border-left:4px solid var(--rojo); display:flex; flex-direction:column; gap:.45rem; }
.tray-intensifica { border-left-color: var(--naranja); }
.tray-aprobada    { border-left-color: var(--verde); opacity:.85; }
.tray-card-header { display:flex; justify-content:space-between; align-items:flex-start; gap:.5rem; }
.tray-card-materia { font-weight:700; color:var(--primario); font-size:.95rem; }
.tray-card-meta    { font-size:.82rem; color:var(--texto-suave); }
.tray-card-fecha   { font-size:.88rem; color:var(--texto); font-weight:600; }
.tray-card-obs     { font-size:.8rem; color:var(--gris); font-style:italic; }
.tray-semanas      { display:flex; flex-wrap:wrap; gap:.35rem; }
.tray-semana-chip  { background:#eef4ff; color:var(--secundario); border:1px solid #c8d8f0; border-radius:20px; padding:.15rem .6rem; font-size:.78rem; }
.tray-horarios     { display:flex; flex-direction:column; gap:.3rem; }
.tray-dia          { display:grid; grid-template-columns:80px 1fr auto; gap:.4rem; align-items:center; background:#f5f8ff; border-radius:5px; padding:.3rem .5rem; font-size:.82rem; }
.tray-dia-nombre   { font-weight:600; color:var(--primario); }
.tray-dia-horario  { color:var(--texto); }
.tray-dia-sem      { color:var(--texto-suave); font-size:.75rem; white-space:nowrap; }
@media(max-width:480px){ .perfil-header { flex-direction:column; text-align:center; } .perfil-meta { justify-content:center; } .tray-cards { grid-template-columns:1fr; } }
</style>
<?php
$content = ob_get_clean();
$pageTitle = 'Mi trayectoria';
require __DIR__ . '/../../views/layout.php';
