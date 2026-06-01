<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../src/Previa.php';

$id     = (int)($_GET['id'] ?? 0);
$previa = Previa::getById($id);
if (!$previa) { header('Location: /preceptora/panel'); exit; }

$alumnos  = DB::get()->query('SELECT id, nombre, curso FROM alumnos ORDER BY nombre')->fetchAll();
$materias = DB::get()->query('SELECT id, nombre FROM materias ORDER BY nombre')->fetchAll();

ob_start();
?>
<div class="form-page">
    <h2 class="section-title">Editar previa</h2>
    <?php if (!empty($error)): ?>
    <div class="error-msg"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <form method="POST" action="/preceptora/previa/editar" class="form-card">
        <input type="hidden" name="id" value="<?= (int)$previa['id'] ?>">
        <div class="form-group">
            <label>Alumno</label>
            <select name="alumno_id" required>
                <?php foreach ($alumnos as $a): ?>
                <option value="<?= (int)$a['id'] ?>" <?= $a['id'] == $previa['alumno_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($a['nombre'], ENT_QUOTES, 'UTF-8') ?>
                    (<?= htmlspecialchars($a['curso'], ENT_QUOTES, 'UTF-8') ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Materia</label>
            <select name="materia_id" required>
                <?php foreach ($materias as $m): ?>
                <option value="<?= (int)$m['id'] ?>" <?= $m['id'] == $previa['materia_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($m['nombre'], ENT_QUOTES, 'UTF-8') ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Fecha</label>
            <input type="date" name="fecha" value="<?= htmlspecialchars($previa['fecha'], ENT_QUOTES, 'UTF-8') ?>" required>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-primary">Actualizar</button>
            <a href="/preceptora/panel" class="btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
<?php
$content = ob_get_clean();
$pageTitle = 'Editar previa';
require __DIR__ . '/../../views/layout.php';
