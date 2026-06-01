<?php
declare(strict_types=1);
// $alumno, $boletin, $periodos, $materias vienen del router
?>
<?php ob_start(); ?>
<div class="panel-header">
    <h2 class="section-title">
        Boletín: <?= htmlspecialchars($alumno['nombre'], ENT_QUOTES, 'UTF-8') ?>
        <small style="font-size:.75em;color:#666"> — <?= htmlspecialchars($alumno['curso'], ENT_QUOTES, 'UTF-8') ?> — DNI: <?= htmlspecialchars($alumno['dni'], ENT_QUOTES, 'UTF-8') ?></small>
    </h2>
    <a href="/preceptora/boletines" class="btn-secondary">← Volver</a>
</div>

<?php if (empty($periodos)): ?>
<div class="error-msg">Primero creá al menos un período en la sección Boletines.</div>
<?php else: ?>

<div class="table-wrap">
<table class="tabla" id="tabla-boletin">
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
    <?php foreach ($materias as $mat): ?>
    <?php
        $matNombre = $mat['nombre'];
        $data      = $boletin[$matNombre] ?? ['notas' => [], 'promedio' => null, 'condicion' => 'Sin notas'];
    ?>
    <tr data-materia-id="<?= (int)$mat['id'] ?>">
        <td><?= htmlspecialchars($matNombre, ENT_QUOTES, 'UTF-8') ?></td>
        <?php foreach ($periodos as $per): ?>
        <td class="td-nota" data-periodo-id="<?= (int)$per['id'] ?>" data-periodo-nombre="<?= htmlspecialchars($per['nombre'], ENT_QUOTES, 'UTF-8') ?>">
            <?php $nota = $data['notas'][$per['nombre']] ?? null; ?>
            <input type="number" class="input-nota"
                   min="1" max="10" step="0.5"
                   value="<?= $nota !== null ? number_format($nota, 1, '.', '') : '' ?>"
                   placeholder="—"
                   data-alumno-id="<?= (int)$alumno['id'] ?>"
                   data-materia-id="<?= (int)$mat['id'] ?>"
                   data-periodo-id="<?= (int)$per['id'] ?>">
        </td>
        <?php endforeach; ?>
        <td class="td-promedio" style="text-align:center;font-weight:700">
            <?php if ($data['promedio'] !== null): ?>
            <span class="nota nota-<?= $data['promedio'] >= 7 ? 'ok' : ($data['promedio'] >= 4 ? 'media' : 'baja') ?>"><?= number_format($data['promedio'],2) ?></span>
            <?php else: ?>—<?php endif; ?>
        </td>
        <td class="td-condicion">
            <?php $cond = $data['condicion']; ?>
            <span class="badge badge-<?= match($cond){ 'Aprobada'=>'aprobada','Previa'=>'pendiente','Recursa'=>'ausente',default=>'' } ?>">
                <?= htmlspecialchars($cond, ENT_QUOTES, 'UTF-8') ?>
            </span>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<p class="hint" style="margin-top:.5rem">Editá las notas directamente en la tabla. Se guardan automáticamente al salir del campo.</p>
<?php endif; ?>

<style>
.input-nota {
    width: 60px;
    padding: .25rem .35rem;
    border: 1.5px solid #d0d7e8;
    border-radius: 5px;
    font-size: .88rem;
    text-align: center;
}
.input-nota:focus { border-color: var(--acento); outline: none; }
.input-nota.guardando { border-color: #f0c040; }
.input-nota.guardado  { border-color: #27ae60; }
.input-nota.error     { border-color: #e74c3c; }
.nota { padding:.15rem .45rem; border-radius:4px; font-weight:700; font-size:.88rem; }
.nota-ok    { background:#eafaf1; color:#1e8449; }
.nota-media { background:#fef9e7; color:#7d6608; }
.nota-baja  { background:#fdecea; color:#c0392b; }
</style>

<script>
document.querySelectorAll('.input-nota').forEach(input => {
    input.addEventListener('change', async () => {
        const val = input.value.trim();
        const alumnoId  = input.dataset.alumnoId;
        const materiaId = input.dataset.materiaId;
        const periodoId = input.dataset.periodoId;

        input.classList.remove('guardado','error');
        input.classList.add('guardando');

        try {
            let url, body;
            if (val === '' || isNaN(parseFloat(val))) {
                url  = '/preceptora/boletin/nota/eliminar';
                body = { alumno_id: +alumnoId, materia_id: +materiaId, periodo_id: +periodoId };
            } else {
                url  = '/preceptora/boletin/nota';
                body = { alumno_id: +alumnoId, materia_id: +materiaId, periodo_id: +periodoId, nota: parseFloat(val) };
            }
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            });
            input.classList.remove('guardando');
            if (res.ok) {
                input.classList.add('guardado');
                setTimeout(() => input.classList.remove('guardado'), 1500);
                // Recargar fila para actualizar promedio y condición
                setTimeout(() => location.reload(), 800);
            } else {
                input.classList.add('error');
            }
        } catch {
            input.classList.remove('guardando');
            input.classList.add('error');
        }
    });
});
</script>
<?php
$content = ob_get_clean();
$pageTitle = 'Boletín alumno — Preceptora';
require __DIR__ . '/../../views/layout.php';
