<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/Previa.php';
require_once __DIR__ . '/../../src/Confirmacion.php';

$previas     = Previa::getAll();
$confirmadas = Confirmacion::getAllParaPanel();
$confirmSet  = [];
foreach ($confirmadas as $c) {
    $confirmSet[$c['previa_id'] . '_' . $c['alumno_id']] = true;
}

$cursos   = array_unique(array_column($previas, 'curso'));
$materias = array_unique(array_map(fn($p) => $p['materia_nombre'], $previas));
sort($cursos); sort($materias);

ob_start();
?>
<div class="panel-header">
    <h2 class="section-title">Panel de previas</h2>
    <a href="/preceptora/previa/nueva" class="btn-primary">+ Nueva previa</a>
</div>

<div class="filtros">
    <label>Curso:
        <select id="filtro-curso" onchange="filtrarPanel()">
            <option value="">Todos</option>
            <?php foreach ($cursos as $c): ?>
            <option value="<?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Materia:
        <select id="filtro-materia" onchange="filtrarPanel()">
            <option value="">Todas</option>
            <?php foreach ($materias as $m): ?>
            <option value="<?= htmlspecialchars($m, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($m, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </label>
</div>

<div class="table-wrap">
<table class="tabla" id="tabla-panel">
    <thead>
        <tr>
            <th>Alumno</th><th>Curso</th><th>Materia</th><th>Fecha</th>
            <th>Estado</th><th>Leyó</th><th>Acciones</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($previas as $p):
        $key  = $p['id'] . '_' . $p['alumno_id'];
        $leyo = isset($confirmSet[$key]);
    ?>
    <tr data-curso="<?= htmlspecialchars($p['curso'], ENT_QUOTES, 'UTF-8') ?>"
        data-materia="<?= htmlspecialchars($p['materia_nombre'], ENT_QUOTES, 'UTF-8') ?>">
        <td><?= htmlspecialchars($p['alumno_nombre'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars($p['curso'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars($p['materia_nombre'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars(date('d/m/Y', strtotime($p['fecha'])), ENT_QUOTES, 'UTF-8') ?></td>
        <td>
            <select class="select-estado" data-id="<?= (int)$p['id'] ?>" onchange="cambiarEstado(this)">
                <?php foreach (['Pendiente','Aprobada','Ausente'] as $est): ?>
                <option value="<?= $est ?>" <?= $p['estado'] === $est ? 'selected' : '' ?>><?= $est ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td class="td-leyo"><?= $leyo ? '<span class="leyo-si">✓</span>' : '<span class="leyo-no">✗</span>' ?></td>
        <td class="td-acciones">
            <a href="/preceptora/previa/editar?id=<?= (int)$p['id'] ?>" class="btn-sm">Editar</a>
            <button class="btn-sm btn-danger" onclick="eliminarPrevia(<?= (int)$p['id'] ?>)">Eliminar</button>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($previas)): ?>
    <tr><td colspan="7" class="empty-msg">No hay previas registradas.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
<?php
$content = ob_get_clean();
$pageTitle = 'Panel — Preceptora';
require __DIR__ . '/../../views/layout.php';
