<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/Recursada.php';

$todas = Recursada::getAll();

// Agrupar por materia
$porMateria = [];
foreach ($todas as $r) {
    $porMateria[$r['materia_nombre']][] = $r;
}
ksort($porMateria);

ob_start();
?>
<div class="panel-header">
    <h2 class="section-title">Alumnos recursantes</h2>
</div>

<?php if (empty($porMateria)): ?>
<p class="empty-msg">No hay alumnos marcados como recursantes. Podés marcarlos desde el legajo de cada alumno.</p>
<?php else: ?>

<?php foreach ($porMateria as $materia => $alumnos): ?>
<div class="recursantes-bloque">
    <h3 class="recursantes-materia"><?= htmlspecialchars($materia, ENT_QUOTES, 'UTF-8') ?></h3>
    <div class="table-wrap">
    <table class="tabla">
        <thead><tr><th>Alumno</th><th>Legajo</th><th>Curso</th><th>Año</th><th>Observación</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($alumnos as $a): ?>
        <tr>
            <td><?= htmlspecialchars($a['alumno_nombre'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($a['legajo'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($a['curso'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= (int)$a['anio'] ?></td>
            <td><?= htmlspecialchars($a['observacion'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
            <td>
                <a href="/preceptora/ficha?id=<?= (int)$a['alumno_id'] ?>" class="btn-sm">Ver legajo</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<style>
.recursantes-bloque { margin-bottom: 2rem; }
.recursantes-materia {
    font-size: 1rem;
    font-weight: 700;
    color: var(--primario);
    border-left: 4px solid var(--naranja);
    padding-left: .6rem;
    margin-bottom: .6rem;
}
</style>
<?php
$content = ob_get_clean();
$pageTitle = 'Recursantes — Preceptora';
require __DIR__ . '/../../views/layout.php';
