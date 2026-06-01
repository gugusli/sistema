<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/Previa.php';
require_once __DIR__ . '/../../src/Confirmacion.php';
require_once __DIR__ . '/../../src/Notificacion.php';
require_once __DIR__ . '/../../src/Recursada.php';

$alumno_id  = (int)$_SESSION['id'];
$hoy        = Previa::getByAlumnoFechaHoy($alumno_id);
$futuras    = Previa::getByAlumno($alumno_id);
$confirmadas = Confirmacion::getConfirmadas($alumno_id);
$tieneSub   = (bool)Notificacion::getSubscriptionByAlumno($alumno_id);
$recursadas = Recursada::getByAlumno($alumno_id);

ob_start();
?>
<?php if (!$tieneSub): ?>
<div class="banner-push" id="banner-push">
    ⚠️ Activá las notificaciones para recibir avisos automáticos
    <button onclick="subscribeToPush()">Activar</button>
    <button class="btn-cerrar" onclick="document.getElementById('banner-push').remove()">✕</button>
</div>
<?php endif; ?>

<?php if (!empty($recursadas)): ?>
<div class="recursadas-banner">
    <strong>📋 Materias en recursada:</strong>
    <div class="recursadas-chips">
        <?php foreach ($recursadas as $r): ?>
        <span class="recursada-chip"><?= htmlspecialchars($r['materia_nombre'], ENT_QUOTES, 'UTF-8') ?> <small>(<?= (int)$r['anio'] ?>)</small></span>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<h2 class="section-title">Previas de hoy</h2>

<?php if (empty($hoy)): ?>
<p class="empty-msg">No tenés previas programadas para hoy.</p>
<?php else: ?>
<div class="cards-hoy">
<?php foreach ($hoy as $p):
    $yaConfirmo = in_array($p['id'], $confirmadas, false);
?>
<div class="card-previa">
    <div class="card-materia"><?= htmlspecialchars($p['materia_nombre'], ENT_QUOTES, 'UTF-8') ?></div>
    <div class="card-info">📅 <?= htmlspecialchars(date('d/m/Y', strtotime($p['fecha'])), ENT_QUOTES, 'UTF-8') ?></div>
    <span class="badge badge-<?= strtolower($p['estado']) ?>"><?= htmlspecialchars($p['estado'], ENT_QUOTES, 'UTF-8') ?></span>
    <button class="btn-confirmar"
            data-id="<?= (int)$p['id'] ?>"
            <?= $yaConfirmo ? 'disabled' : '' ?>>
        <?= $yaConfirmo ? '✓ Visto' : 'Vi el aviso' ?>
    </button>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<h2 class="section-title" style="margin-top:2rem">Próximas previas</h2>
<?php if (empty($futuras)): ?>
<p class="empty-msg">No tenés previas registradas.</p>
<?php else: ?>
<div class="table-wrap">
<table class="tabla">
    <thead><tr><th>Fecha</th><th>Materia</th><th>Estado</th></tr></thead>
    <tbody>
    <?php foreach ($futuras as $p): ?>
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
.recursadas-banner {
    background: #fff8f0;
    border: 1.5px solid #f0c040;
    border-radius: var(--radio);
    padding: .85rem 1.1rem;
    margin-bottom: 1.25rem;
    display: flex;
    flex-direction: column;
    gap: .5rem;
}
.recursadas-chips { display: flex; flex-wrap: wrap; gap: .4rem; }
.recursada-chip {
    background: #fdecea;
    color: var(--rojo);
    border: 1px solid #f5b7b1;
    border-radius: 20px;
    padding: .2rem .75rem;
    font-size: .82rem;
    font-weight: 600;
}
.recursada-chip small { font-weight: 400; color: #c0392b; }
</style>
<?php
$content = ob_get_clean();
$pageTitle = 'Mis previas';
require __DIR__ . '/../../views/layout.php';
