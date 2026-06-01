<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/Alumno.php';
require_once __DIR__ . '/../../src/Boletin.php';
require_once __DIR__ . '/../../src/Recursada.php';

$id       = (int)($_GET['id'] ?? 0);
$ficha    = $id ? Alumno::getFicha($id) : null;
$boletin  = $id && $ficha ? Boletin::getBoletinAlumno($id) : [];
$periodos = Boletin::getPeriodos();
$recursadas = $id && $ficha ? Recursada::getByAlumno($id) : [];
$materias = DB::get()->query('SELECT * FROM materias ORDER BY nombre')->fetchAll();
$errorMsg = htmlspecialchars($_GET['error'] ?? '', ENT_QUOTES, 'UTF-8');

ob_start();
?>
<div class="panel-header">
    <h2 class="section-title">Legajo académico</h2>
</div>

<!-- Buscador -->
<div class="busqueda-wrap">
    <div class="busqueda-inner">
        <span class="busqueda-icon">🔍</span>
        <input type="text" id="input-busqueda"
               placeholder="Buscar por legajo, DNI o nombre..."
               value="<?= htmlspecialchars($_GET['q'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
               autocomplete="off">
    </div>
    <div id="resultados-busqueda" class="resultados-lista"></div>
</div>

<?php if ($errorMsg): ?>
<div class="error-msg"><?= $errorMsg ?></div>
<?php endif; ?>

<?php if ($ficha): ?>
<!-- ── Encabezado del legajo ── -->
<div class="legajo-header card">
    <div class="legajo-avatar">👤</div>
    <div class="legajo-info">
        <div class="legajo-nombre"><?= htmlspecialchars($ficha['nombre'], ENT_QUOTES, 'UTF-8') ?></div>
        <div class="legajo-meta">
            <?php if (!empty($ficha['legajo'])): ?>
            <span class="legajo-chip">🪪 Legajo: <strong><?= htmlspecialchars($ficha['legajo'], ENT_QUOTES, 'UTF-8') ?></strong></span>
            <?php endif; ?>
            <span class="legajo-chip">📋 DNI: <?= htmlspecialchars($ficha['dni'], ENT_QUOTES, 'UTF-8') ?></span>
            <span class="legajo-chip">📚 <?= htmlspecialchars($ficha['curso'], ENT_QUOTES, 'UTF-8') ?></span>
            <span class="legajo-chip">📅 Alta: <?= date('d/m/Y', strtotime($ficha['created_at'])) ?></span>
        </div>
    </div>
    <div class="legajo-acciones">
        <button class="btn-sm" onclick="document.getElementById('modal-editar').classList.add('open')">✏️ Editar datos</button>
        <a href="/preceptora/boletin/alumno?alumno_id=<?= $id ?>" class="btn-sm">📊 Cargar notas</a>
    </div>
</div>

<!-- Tabs -->
<div class="tabs" style="margin-top:1.5rem">
    <button class="tab active" onclick="showTab('previas', this)">Previas</button>
    <button class="tab" onclick="showTab('boletin', this)">Boletín</button>
    <button class="tab" onclick="showTab('recursadas', this)">Recursadas</button>
</div>

<!-- Tab: Previas -->
<div id="tab-previas" class="tab-content">
    <div class="panel-header" style="margin-top:1rem">
        <h3 class="section-title" style="font-size:1rem">Historial de previas</h3>
        <a href="/preceptora/previa/nueva" class="btn-sm">+ Nueva previa</a>
    </div>
    <?php if (empty($ficha['previas'])): ?>
    <p class="empty-msg">Sin previas registradas.</p>
    <?php else: ?>
    <div class="table-wrap">
    <table class="tabla">
        <thead><tr><th>Fecha</th><th>Materia</th><th>Horario</th><th>Aula</th><th>Estado</th></tr></thead>
        <tbody>
        <?php foreach ($ficha['previas'] as $p): ?>
        <tr>
            <td><?= htmlspecialchars(date('d/m/Y', strtotime($p['fecha'])), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($p['materia_nombre'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars(substr($p['horario'],0,5), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($p['aula'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><span class="badge badge-<?= strtolower($p['estado']) ?>"><?= htmlspecialchars($p['estado'], ENT_QUOTES, 'UTF-8') ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<!-- Tab: Boletín -->
<div id="tab-boletin" class="tab-content" style="display:none">
    <div style="margin-top:1rem">
    <?php if (empty($boletin)): ?>
    <p class="empty-msg">Sin notas cargadas. <a href="/preceptora/boletin/alumno?alumno_id=<?= $id ?>">Cargar notas →</a></p>
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
                <?php $nota = $data['notas'][$per['nombre']] ?? null; ?>
                <?php if ($nota !== null): $cls = $nota >= 7 ? 'ok' : ($nota >= 4 ? 'media' : 'baja'); ?>
                <span class="nota nota-<?= $cls ?>"><?= number_format($nota,1) ?></span>
                <?php else: ?><span style="color:#aaa">—</span><?php endif; ?>
            </td>
            <?php endforeach; ?>
            <td class="td-nota">
                <?php if ($data['promedio'] !== null): $cls = $data['promedio'] >= 7 ? 'ok' : ($data['promedio'] >= 4 ? 'media' : 'baja'); ?>
                <strong class="nota nota-<?= $cls ?>"><?= number_format($data['promedio'],2) ?></strong>
                <?php else: ?>—<?php endif; ?>
            </td>
            <td>
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
    <?php endif; ?>
    </div>
</div>

<!-- Tab: Recursadas -->
<div id="tab-recursadas" class="tab-content" style="display:none">
    <div style="margin-top:1rem">
    <div class="panel-header">
        <h3 class="section-title" style="font-size:1rem">Materias en recursada</h3>
        <button class="btn-sm btn-primary" onclick="document.getElementById('modal-recursada').classList.add('open')">+ Marcar recursada</button>
    </div>

    <?php if (empty($recursadas)): ?>
    <p class="empty-msg">No hay materias marcadas como recursada.</p>
    <?php else: ?>
    <div class="table-wrap">
    <table class="tabla">
        <thead><tr><th>Materia</th><th>Año</th><th>Observación</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($recursadas as $r): ?>
        <tr>
            <td><?= htmlspecialchars($r['materia_nombre'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= (int)$r['anio'] ?></td>
            <td><?= htmlspecialchars($r['observacion'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
            <td>
                <button class="btn-sm btn-danger" onclick="eliminarRecursada(<?= (int)$r['id'] ?>)">Eliminar</button>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
    </div>
</div>

<!-- Modal: editar alumno -->
<div class="modal-overlay" id="modal-editar" onclick="if(event.target===this)this.classList.remove('open')">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Editar datos del alumno</h3>
            <button onclick="document.getElementById('modal-editar').classList.remove('open')" class="modal-close">✕</button>
        </div>
        <form method="POST" action="/preceptora/alumnos/editar">
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="form-group">
                <label>Nombre completo</label>
                <input type="text" name="nombre" value="<?= htmlspecialchars($ficha['nombre'], ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="form-group">
                <label>Curso</label>
                <input type="text" name="curso" value="<?= htmlspecialchars($ficha['curso'], ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="form-group">
                <label>Número de legajo</label>
                <input type="text" name="legajo" value="<?= htmlspecialchars($ficha['legajo'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej: 2025-001">
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-primary">Guardar</button>
                <button type="button" class="btn-secondary" onclick="document.getElementById('modal-editar').classList.remove('open')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: marcar recursada -->
<div class="modal-overlay" id="modal-recursada" onclick="if(event.target===this)this.classList.remove('open')">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Marcar materia como recursada</h3>
            <button onclick="document.getElementById('modal-recursada').classList.remove('open')" class="modal-close">✕</button>
        </div>
        <div class="form-group">
            <label>Materia</label>
            <select id="sel-materia">
                <option value="">— Seleccioná —</option>
                <?php foreach ($materias as $m): ?>
                <option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars($m['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Año</label>
            <input type="number" id="sel-anio" value="<?= date('Y') ?>" min="2020" max="2099">
        </div>
        <div class="form-group">
            <label>Observación (opcional)</label>
            <input type="text" id="sel-obs" placeholder="Ej: Repitió por inasistencias">
        </div>
        <div class="form-actions">
            <button class="btn-primary" onclick="guardarRecursada()">Guardar</button>
            <button class="btn-secondary" onclick="document.getElementById('modal-recursada').classList.remove('open')">Cancelar</button>
        </div>
    </div>
</div>

<?php endif; ?>

<style>
/* Buscador */
.busqueda-wrap { position:relative; max-width:460px; margin-bottom:1.5rem; }
.busqueda-inner { display:flex; align-items:center; background:#fff; border:1.5px solid var(--borde); border-radius:var(--radio); overflow:hidden; box-shadow:var(--sombra); }
.busqueda-icon { padding:0 .75rem; font-size:1rem; color:var(--gris); }
.busqueda-inner input { flex:1; border:none; padding:.7rem .5rem; font-size:.95rem; outline:none; background:transparent; }
.resultados-lista { position:absolute; top:100%; left:0; right:0; background:#fff; border:1.5px solid var(--borde); border-top:none; border-radius:0 0 var(--radio) var(--radio); z-index:100; max-height:240px; overflow-y:auto; box-shadow:var(--sombra-lg); }
.resultado-item { padding:.6rem 1rem; cursor:pointer; font-size:.9rem; border-bottom:1px solid #f0f0f0; display:flex; flex-direction:column; gap:.15rem; }
.resultado-item:hover { background:#eef4ff; }
.resultado-item .res-nombre { font-weight:600; color:var(--primario); }
.resultado-item .res-meta { font-size:.78rem; color:var(--gris); }

/* Legajo header */
.legajo-header { display:flex; align-items:flex-start; gap:1.25rem; flex-wrap:wrap; }
.legajo-avatar { font-size:3.5rem; line-height:1; flex-shrink:0; }
.legajo-info { flex:1; min-width:200px; }
.legajo-nombre { font-size:1.3rem; font-weight:700; color:var(--primario); margin-bottom:.5rem; }
.legajo-meta { display:flex; flex-wrap:wrap; gap:.5rem; }
.legajo-chip { background:#eef4ff; color:var(--secundario); border:1px solid #c8d8f0; border-radius:20px; padding:.2rem .7rem; font-size:.82rem; }
.legajo-acciones { display:flex; gap:.5rem; flex-wrap:wrap; align-items:flex-start; margin-left:auto; }

/* Tabs */
.tabs { display:flex; gap:.35rem; border-bottom:2px solid var(--borde); }
.tab { background:transparent; border:none; padding:.6rem 1.1rem; font-size:.9rem; font-weight:600; color:var(--texto-suave); cursor:pointer; border-bottom:3px solid transparent; margin-bottom:-2px; transition:color var(--transition), border-color var(--transition); border-radius:6px 6px 0 0; }
.tab:hover { color:var(--primario); background:#f0f4fa; }
.tab.active { color:var(--primario); border-bottom-color:var(--naranja); }

/* Notas */
.td-nota { text-align:center; }
.nota { padding:.18rem .45rem; border-radius:4px; font-weight:700; font-size:.88rem; }
.nota-ok    { background:#eafaf1; color:#1e8449; }
.nota-media { background:#fef9e7; color:#7d6608; }
.nota-baja  { background:#fdecea; color:#c0392b; }

/* Modal */
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:500; align-items:center; justify-content:center; padding:1rem; }
.modal-overlay.open { display:flex; }
.modal-card { background:#fff; border-radius:var(--radio-lg); padding:1.75rem; width:100%; max-width:440px; box-shadow:var(--sombra-lg); }
.modal-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem; }
.modal-header h3 { font-size:1.05rem; font-weight:700; color:var(--primario); }
.modal-close { background:transparent; border:none; font-size:1.2rem; cursor:pointer; color:var(--gris); padding:.2rem .4rem; border-radius:4px; }
.modal-close:hover { background:#f0f0f0; }

@media(max-width:600px){
    .legajo-header { flex-direction:column; }
    .legajo-acciones { margin-left:0; }
    .tabs { overflow-x:auto; }
}
</style>

<script>
// Buscador
const input = document.getElementById('input-busqueda');
const lista = document.getElementById('resultados-busqueda');
let timer;
if (input) {
    input.addEventListener('input', () => {
        clearTimeout(timer);
        const q = input.value.trim();
        if (q.length < 2) { lista.innerHTML = ''; return; }
        timer = setTimeout(async () => {
            const res  = await fetch('/preceptora/buscar?q=' + encodeURIComponent(q));
            const data = await res.json();
            lista.innerHTML = '';
            if (!data.length) {
                lista.innerHTML = '<div class="resultado-item"><span class="res-nombre" style="color:#999">Sin resultados</span></div>';
                return;
            }
            data.forEach(a => {
                const div = document.createElement('div');
                div.className = 'resultado-item';
                div.innerHTML = `<span class="res-nombre">${a.nombre}</span><span class="res-meta">Legajo: ${a.legajo||'—'} · DNI: ${a.dni} · ${a.curso}</span>`;
                div.addEventListener('click', () => { window.location.href = '/preceptora/ficha?id=' + a.id; });
                lista.appendChild(div);
            });
        }, 280);
    });
    document.addEventListener('click', e => { if (!e.target.closest('.busqueda-wrap')) lista.innerHTML = ''; });
}

// Tabs
function showTab(name, btn) {
    document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-' + name).style.display = '';
    btn.classList.add('active');
}

// Recursada
async function guardarRecursada() {
    const materiaId = +document.getElementById('sel-materia').value;
    const anio      = +document.getElementById('sel-anio').value;
    const obs       = document.getElementById('sel-obs').value.trim();
    if (!materiaId) { alert('Seleccioná una materia.'); return; }
    const res = await fetch('/preceptora/recursada/crear', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ alumno_id: <?= $id ?>, materia_id: materiaId, anio, observacion: obs })
    });
    if (res.ok) location.reload();
    else alert('Error al guardar.');
}

async function eliminarRecursada(id) {
    if (!confirm('¿Eliminar esta recursada?')) return;
    const res = await fetch('/preceptora/recursada/eliminar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
    });
    if (res.ok) location.reload();
    else alert('Error al eliminar.');
}
</script>
<?php
$content = ob_get_clean();
$pageTitle = 'Legajo — Preceptora';
require __DIR__ . '/../../views/layout.php';
