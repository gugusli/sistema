<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/Previa.php';

$alumno_id = (int)$_SESSION['id'];
$previas   = Previa::getByAlumno($alumno_id);

ob_start();
?>
<h2 class="section-title">Historial de previas</h2>

<div class="filtros">
    <label>Estado:
        <select id="filtro-estado" onchange="filtrarHistorial()">
            <option value="">Todos</option>
            <option value="Pendiente">Pendiente</option>
            <option value="Aprobada">Aprobada</option>
            <option value="Ausente">Ausente</option>
        </select>
    </label>
</div>

<div class="table-wrap">
<table class="tabla" id="tabla-historial">
    <thead><tr><th>Fecha</th><th>Materia</th><th>Estado</th></tr></thead>
    <tbody>
    <?php foreach (array_reverse($previas) as $p): ?>
    <tr data-estado="<?= htmlspecialchars($p['estado'], ENT_QUOTES, 'UTF-8') ?>">
        <td><?= htmlspecialchars(date('d/m/Y', strtotime($p['fecha'])), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars($p['materia_nombre'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><span class="badge badge-<?= strtolower($p['estado']) ?>"><?= htmlspecialchars($p['estado'], ENT_QUOTES, 'UTF-8') ?></span></td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($previas)): ?>
    <tr><td colspan="3" class="empty-msg">Sin previas registradas.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>

<script>
function filtrarHistorial() {
    const val = document.getElementById('filtro-estado').value;
    document.querySelectorAll('#tabla-historial tbody tr[data-estado]').forEach(tr => {
        tr.style.display = (!val || tr.dataset.estado === val) ? '' : 'none';
    });
}
</script>
<?php
$content = ob_get_clean();
$pageTitle = 'Historial — Previas';
require __DIR__ . '/../../views/layout.php';
