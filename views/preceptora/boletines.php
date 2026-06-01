<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/Boletin.php';
require_once __DIR__ . '/../../src/Alumno.php';

$periodos = Boletin::getPeriodos();
$alumnos  = Alumno::getAll();

ob_start();
?>
<div class="panel-header">
    <h2 class="section-title">Boletines de calificaciones</h2>
</div>

<?php if (!empty($error)): ?>
<div class="error-msg"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="two-col">
<div>
    <h3>Períodos</h3>
    <form method="POST" action="/preceptora/periodos" class="form-card">
        <div class="form-group">
            <label>Nombre del período</label>
            <input type="text" name="nombre" placeholder="Ej: 1° Bimestre 2025" maxlength="50" required>
        </div>
        <div class="form-group">
            <label>Año</label>
            <input type="number" name="anio" value="<?= date('Y') ?>" min="2020" max="2099" required>
        </div>
        <div class="form-group">
            <label>Orden (1-4)</label>
            <input type="number" name="orden" min="1" max="4" required>
        </div>
        <button type="submit" class="btn-primary">Agregar período</button>
    </form>

    <?php if (!empty($periodos)): ?>
    <table class="tabla" style="margin-top:1rem">
        <thead><tr><th>Período</th><th>Año</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($periodos as $p): ?>
        <tr>
            <td><?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= (int)$p['anio'] ?></td>
            <td>
                <form method="POST" action="/preceptora/periodos/eliminar" style="display:inline"
                      onsubmit="return confirm('¿Eliminar período y todas sus notas?')">
                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <button type="submit" class="btn-sm btn-danger">Eliminar</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<div>
    <h3>Ver boletín de alumno</h3>
    <div class="form-card">
        <div class="busqueda-wrap">
            <input type="text" id="input-busqueda" placeholder="Buscar por DNI o nombre..." autocomplete="off">
            <div id="resultados-busqueda" class="resultados-lista"></div>
        </div>
        <p class="hint" style="margin-top:.5rem">O seleccioná de la lista:</p>
        <select id="select-alumno" onchange="if(this.value) window.location='/preceptora/boletin/alumno?alumno_id='+this.value">
            <option value="">— Seleccioná alumno —</option>
            <?php foreach ($alumnos as $a): ?>
            <option value="<?= (int)$a['id'] ?>"><?= htmlspecialchars($a['nombre'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($a['curso'], ENT_QUOTES, 'UTF-8') ?>)</option>
            <?php endforeach; ?>
        </select>
    </div>
</div>
</div>

<style>
.busqueda-wrap { position:relative; }
.busqueda-wrap input { width:100%; padding:.6rem .9rem; border:1.5px solid #d0d7e8; border-radius:var(--radio); font-size:.95rem; box-sizing:border-box; }
.resultados-lista { position:absolute; top:100%; left:0; right:0; background:#fff; border:1.5px solid #d0d7e8; border-top:none; border-radius:0 0 var(--radio) var(--radio); z-index:100; max-height:200px; overflow-y:auto; }
.resultado-item { padding:.55rem .9rem; cursor:pointer; font-size:.9rem; border-bottom:1px solid #f0f0f0; }
.resultado-item:hover { background:#eef4ff; }
</style>

<script>
const input = document.getElementById('input-busqueda');
const lista = document.getElementById('resultados-busqueda');
let timer;
input.addEventListener('input', () => {
    clearTimeout(timer);
    const q = input.value.trim();
    if (q.length < 2) { lista.innerHTML = ''; return; }
    timer = setTimeout(async () => {
        const res = await fetch('/preceptora/buscar?q=' + encodeURIComponent(q));
        const data = await res.json();
        lista.innerHTML = '';
        if (!data.length) { lista.innerHTML = '<div class="resultado-item" style="color:#999">Sin resultados</div>'; return; }
        data.forEach(a => {
            const div = document.createElement('div');
            div.className = 'resultado-item';
            div.textContent = `${a.nombre} — DNI: ${a.dni} — ${a.curso}`;
            div.addEventListener('click', () => { window.location.href = '/preceptora/boletin/alumno?alumno_id=' + a.id; });
            lista.appendChild(div);
        });
    }, 300);
});
document.addEventListener('click', e => { if (!e.target.closest('.busqueda-wrap')) lista.innerHTML = ''; });
</script>
<?php
$content = ob_get_clean();
$pageTitle = 'Boletines — Preceptora';
require __DIR__ . '/../../views/layout.php';
