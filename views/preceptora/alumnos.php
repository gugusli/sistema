<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/Alumno.php';

$alumnos = Alumno::getAll();
ob_start();
?>
<div class="panel-header">
    <h2 class="section-title">Gestión de alumnos</h2>
</div>

<?php if (!empty($importResult)): ?>
<div class="import-result <?= empty($importResult['errores']) ? 'result-ok' : 'result-warn' ?>">
    <strong><?= (int)$importResult['exitos'] ?> alumno(s) importado(s)<?= !empty($importResult['errores']) ? ', ' . count($importResult['errores']) . ' error(es)' : '' ?>.</strong>
    <?php if (!empty($importResult['errores'])): ?>
    <ul><?php foreach ($importResult['errores'] as $e): ?><li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?></ul>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (!empty($error)): ?>
<div class="error-msg"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="two-col">
<div>
    <h3>Agregar alumno</h3>
    <form method="POST" action="/preceptora/alumnos" class="form-card">
        <div class="form-group">
            <label>DNI</label>
            <input type="text" name="dni" maxlength="10" required>
        </div>
        <div class="form-group">
            <label>Nombre completo</label>
            <input type="text" name="nombre" maxlength="100" required>
        </div>
        <div class="form-group">
            <label>Curso</label>
            <input type="text" name="curso" maxlength="20" placeholder="Ej: 5°1°" required>
        </div>
        <div class="form-group">
            <label>Número de legajo <span style="font-weight:400;color:var(--gris)">(opcional)</span></label>
            <input type="text" name="legajo" maxlength="20" placeholder="Ej: 2025-001">
        </div>
        <button type="submit" class="btn-primary">Agregar</button>
    </form>
</div>
<div>
    <h3>Importar CSV</h3>
    <p class="hint">Formato: <code>dni,nombre,curso</code> — sin encabezado, una fila por línea.</p>
    <form method="POST" action="/preceptora/alumnos/csv" enctype="multipart/form-data" class="form-card">
        <div class="form-group">
            <label>Archivo CSV</label>
            <input type="file" name="csv" accept=".csv,text/csv" required>
        </div>
        <button type="submit" class="btn-primary">Importar</button>
    </form>
</div>
</div>

<h3 style="margin-top:2rem">Listado de alumnos</h3>
<div class="table-wrap">
<table class="tabla">
    <thead><tr><th>Nombre</th><th>DNI</th><th>Legajo</th><th>Curso</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($alumnos as $a): ?>
    <tr>
        <td><?= htmlspecialchars($a['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars($a['dni'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars($a['legajo'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars($a['curso'], ENT_QUOTES, 'UTF-8') ?></td>
        <td>
            <a href="/preceptora/ficha?id=<?= (int)$a['id'] ?>" class="btn-sm">Ver legajo</a>
            <form method="POST" action="/preceptora/alumnos/eliminar" style="display:inline"
                  onsubmit="return confirm('¿Eliminar alumno?')">
                <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                <button type="submit" class="btn-sm btn-danger">Eliminar</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($alumnos)): ?>
    <tr><td colspan="4" class="empty-msg">No hay alumnos registrados.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
<?php
$content = ob_get_clean();
$pageTitle = 'Alumnos — Preceptora';
require __DIR__ . '/../../views/layout.php';
