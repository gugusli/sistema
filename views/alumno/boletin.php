<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/Boletin.php';

$alumno_id = (int)$_SESSION['id'];
$boletin   = Boletin::getBoletinAlumno($alumno_id);
$periodos  = Boletin::getPeriodos();

ob_start();
?>
<h2 class="section-title">Mi boletín</h2>

<?php if (empty($boletin)): ?>
<p class="empty-msg">Todavía no hay notas cargadas.</p>
<?php else: ?>
<div class="table-wrap">
<table class="tabla">
    <thead>
        <tr>
            <th>Materia</th>
            <?php foreach ($periodos as $per): ?>
            <th><?= htmlspecialchars($per['nombre'], ENT_QUOTES, 'UTF-8') ?></th>
            <?php endforeach; ?>
            <th>Promedio</th>
            <th>Condición</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($boletin as $materia => $data): ?>
    <tr>
        <td><?= htmlspecialchars($materia, ENT_QUOTES, 'UTF-8') ?></td>
        <?php foreach ($periodos as $per): ?>
        <td class="td-nota">
            <?php
            $nota = $data['notas'][$per['nombre']] ?? null;
            if ($nota !== null):
                $cls = $nota >= 7 ? 'nota-ok' : ($nota >= 4 ? 'nota-media' : 'nota-baja');
            ?>
            <span class="nota <?= $cls ?>"><?= number_format($nota, 1) ?></span>
            <?php else: ?>
            <span class="nota-vacia">—</span>
            <?php endif; ?>
        </td>
        <?php endforeach; ?>
        <td class="td-nota">
            <?php if ($data['promedio'] !== null):
                $cls = $data['promedio'] >= 7 ? 'nota-ok' : ($data['promedio'] >= 4 ? 'nota-media' : 'nota-baja');
            ?>
            <strong class="nota <?= $cls ?>"><?= number_format($data['promedio'], 2) ?></strong>
            <?php else: ?>—<?php endif; ?>
        </td>
        <td>
            <?php
            $cond = $data['condicion'];
            $condCls = match($cond) {
                'Aprobada' => 'badge-aprobada',
                'Previa'   => 'badge-pendiente',
                'Recursa'  => 'badge-ausente',
                default    => '',
            };
            ?>
            <span class="badge <?= $condCls ?>"><?= htmlspecialchars($cond, ENT_QUOTES, 'UTF-8') ?></span>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>

<style>
.td-nota { text-align:center; }
.nota { padding:.2rem .5rem; border-radius:4px; font-weight:700; font-size:.9rem; }
.nota-ok    { background:#eafaf1; color:#1e8449; }
.nota-media { background:#fef9e7; color:#7d6608; }
.nota-baja  { background:#fdecea; color:#c0392b; }
.nota-vacia { color:#aaa; }
</style>
<?php
$content = ob_get_clean();
$pageTitle = 'Boletín — Previas';
require __DIR__ . '/../../views/layout.php';
