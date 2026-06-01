<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/Previa.php';
require_once __DIR__ . '/../../src/Recursada.php';
require_once __DIR__ . '/../../src/Alumno.php';

$alumno_id  = (int)$_SESSION['id'];
$alumno     = Alumno::getById($alumno_id);
$previas    = Previa::getByAlumno($alumno_id);
$recursadas = Recursada::getByAlumno($alumno_id);

$aprobadas  = count(array_filter($previas, fn($p) => $p['estado'] === 'Aprobada'));
$ausentes   = count(array_filter($previas, fn($p) => $p['estado'] === 'Ausente'));
$pendientes = count(array_filter($previas, fn($p) => $p['estado'] === 'Pendiente'));

ob_start();
?>
<h2 class="section-title">Mi trayectoria</h2>

<!-- Perfil -->
<div class="tray-perfil card">
    <div class="tray-avatar">👤</div>
    <div class="tray-info">
        <div class="tray-nombre"><?= htmlspecialchars($alumno['nombre'], ENT_QUOTES, 'UTF-8') ?></div>
        <div class="tray-meta">
            <span>📚 <?= htmlspecialchars($alumno['curso'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php if (!empty($alumno['legajo'])): ?>
            <span>🪪 Legajo: <?= htmlspecialchars($alumno['legajo'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
            <span>🪪 DNI: <?= htmlspecialchars($alumno['dni'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid" style="margin-top:1.25rem">
    <div class="stat-card verde">
        <span class="stat-num"><?= $aprobadas ?></span>
        <span class="stat-label">Aprobadas</span>
    </div>
    <div class="stat-card rojo">
        <span class="stat-num"><?= $ausentes ?></span>
        <span class="stat-label">Ausentes</span>
    </div>
    <div class="stat-card">
        <span class="stat-num"><?= $pendientes ?></span>
        <span class="stat-label">Pendientes</span>
    </div>
    <div class="stat-card naranja">
        <span class="stat-num"><?= count($recursadas) ?></span>
        <span class="stat-label">En recursada</span>
    </div>
</div>

<!-- Materias en recursada -->
<?php if (!empty($recursadas)): ?>
<h3 class="section-title" style="margin-top:2rem">Materias en recursada</h3>
<div class="recursadas-grid">
    <?php foreach ($recursadas as $r): ?>
    <div class="recursada-card">
        <div class="recursada-materia"><?= htmlspecialchars($r['materia_nombre'], ENT_QUOTES, 'UTF-8') ?></div>
        <div class="recursada-anio">Año <?= (int)$r['anio'] ?></div>
        <?php if (!empty($r['observacion'])): ?>
        <div class="recursada-obs"><?= htmlspecialchars($r['observacion'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Historial -->
<h3 class="section-title" style="margin-top:2rem">Historial de previas</h3>
<?php if (empty($previas)): ?>
<p class="empty-msg">Sin previas registradas.</p>
<?php else: ?>
<div class="table-wrap">
<table class="tabla">
    <thead><tr><th>Fecha</th><th>Materia</th><th>Estado</th></tr></thead>
    <tbody>
    <?php foreach (array_reverse($previas) as $p): ?>
    <tr>
        <td><?= htmlspecialchars(date('d/m/Y', strtotime($p['fecha'])), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars($p['materia_nombre'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><span class="badge badge-<?= strtolower($p['estado']) ?>"><?= htmlspecialchars($p['estado'], ENT_QUOTES, 'UTF-8') ?></span></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>

<style>
.tray-perfil { display:flex; align-items:center; gap:1.25rem; }
.tray-avatar { font-size:3rem; line-height:1; }
.tray-nombre { font-size:1.2rem; font-weight:700; color:var(--primario); }
.tray-meta   { display:flex; flex-wrap:wrap; gap:.75rem; margin-top:.35rem; font-size:.88rem; color:var(--texto-suave); }

.recursadas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}
.recursada-card {
    background: #fdecea;
    border: 1.5px solid #f5b7b1;
    border-radius: var(--radio);
    padding: 1rem 1.1rem;
    display: flex;
    flex-direction: column;
    gap: .3rem;
}
.recursada-materia { font-weight: 700; color: var(--rojo); font-size: .95rem; }
.recursada-anio    { font-size: .8rem; color: #c0392b; }
.recursada-obs     { font-size: .8rem; color: #888; font-style: italic; }

@media(max-width:480px){
    .tray-perfil { flex-direction:column; text-align:center; }
    .tray-meta { justify-content:center; }
}
</style>
<?php
$content = ob_get_clean();
$pageTitle = 'Mi trayectoria';
require __DIR__ . '/../../views/layout.php';
