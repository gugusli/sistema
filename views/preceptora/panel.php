<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/db.php';

$alumnos = Alumno::getAll();
ob_start();
?>
<div class="panel-header">
    <h2 class="section-title">Alumnos</h2>
</div>

<!-- Buscador -->
<div class="busqueda-wrap">
    <div class="busqueda-inner">
        <span class="busqueda-icon">🔍</span>
        <input type="text" id="input-busqueda"
               placeholder="Buscar por DNI, nombre o legajo..."
               autocomplete="off">
    </div>
</div>

<!-- Lista de alumnos -->
<div class="alumnos-lista" id="alumnos-lista">
    <?php foreach ($alumnos as $a): ?>
    <div class="alumno-item"
         data-id="<?= (int)$a['id'] ?>"
         data-nombre="<?= htmlspecialchars($a['nombre'], ENT_QUOTES, 'UTF-8') ?>"
         data-curso="<?= htmlspecialchars($a['curso'], ENT_QUOTES, 'UTF-8') ?>"
         data-dni="<?= htmlspecialchars($a['dni'], ENT_QUOTES, 'UTF-8') ?>"
         data-legajo="<?= htmlspecialchars($a['legajo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <div class="alumno-info">
            <div class="alumno-nombre"><?= htmlspecialchars($a['nombre'], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="alumno-meta">
                <span>📚 <?= htmlspecialchars($a['curso'], ENT_QUOTES, 'UTF-8') ?></span>
                <span>🪪 DNI: <?= htmlspecialchars($a['dni'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php if (!empty($a['legajo'])): ?>
                <span>🪪 Legajo: <?= htmlspecialchars($a['legajo'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </div>
        </div>
        <button class="btn-sm btn-ver" data-id="<?= (int)$a['id'] ?>">Ver panel</button>
    </div>
    <?php endforeach; ?>
    <?php if (empty($alumnos)): ?>
    <p class="empty-msg">No hay alumnos registrados.</p>
    <?php endif; ?>
</div>

<!-- Modal -->
<div class="modal-overlay" id="modal-panel" onclick="if(event.target===this)closeModal()">
    <div class="modal-card modal-panel-card">
        <div class="modal-header">
            <h3 id="modal-titulo">Panel del alumno</h3>
            <button onclick="closeModal()" class="modal-close">✕</button>
        </div>
        <div id="modal-body" class="modal-body">
            <p class="empty-msg" style="border:none">Cargando...</p>
        </div>
    </div>
</div>

<style>
.alumnos-lista {
    display: flex;
    flex-direction: column;
    gap: .75rem;
}
.alumno-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    background: var(--fondo-card);
    border: 1px solid var(--borde);
    border-radius: var(--radio);
    padding: 1rem 1.25rem;
    box-shadow: var(--sombra);
    transition: box-shadow var(--transition), transform var(--transition);
}
.alumno-item:hover { box-shadow: var(--sombra-lg); transform: translateY(-1px); }
.alumno-nombre { font-weight: 700; color: var(--primario); font-size: .95rem; }
.alumno-meta {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    margin-top: .2rem;
    font-size: .8rem;
    color: var(--texto-suave);
}
.btn-ver { flex-shrink: 0; }

/* Modal grande */
.modal-panel-card {
    max-width: 700px !important;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
}
.modal-body {
    overflow-y: auto;
    padding: .5rem 0;
}
.modal-body .perfil-header { margin-bottom: 1rem; }

/* Buscador */
.busqueda-wrap { position: relative; max-width: 460px; margin-bottom: 1.25rem; }
.busqueda-inner {
    display: flex;
    align-items: center;
    background: #fff;
    border: 1.5px solid var(--borde);
    border-radius: var(--radio);
    overflow: hidden;
    box-shadow: var(--sombra);
}
.busqueda-icon { padding: 0 .75rem; font-size: 1rem; color: var(--gris); }
.busqueda-inner input {
    flex: 1;
    border: none;
    padding: .7rem .5rem;
    font-size: .95rem;
    outline: none;
    background: transparent;
}

/* Ocultar items al filtrar */
.alumno-item.oculto { display: none; }
</style>

<script>
// Búsqueda en vivo
const inputBusqueda = document.getElementById('input-busqueda');
const items = document.querySelectorAll('.alumno-item');

if (inputBusqueda) {
    inputBusqueda.addEventListener('input', () => {
        const q = inputBusqueda.value.trim().toLowerCase();
        items.forEach(item => {
            const texto = (item.dataset.nombre + ' ' + item.dataset.dni + ' ' + item.dataset.legajo + ' ' + item.dataset.curso).toLowerCase();
            item.classList.toggle('oculto', q !== '' && !texto.includes(q));
        });
    });
}

// Modal
let alumnoActualId = null;

function closeModal() {
    document.getElementById('modal-panel').classList.remove('open');
    alumnoActualId = null;
}

async function abrirModal(id, nombre, curso) {
    alumnoActualId = id;
    document.getElementById('modal-titulo').textContent = 'Panel: ' + nombre + ' — ' + curso;
    document.getElementById('modal-body').innerHTML = '<p class="empty-msg" style="border:none">Cargando...</p>';
    document.getElementById('modal-panel').classList.add('open');
    await cargarModal(id);
}

async function cargarModal(id) {
    try {
        const res = await fetch('/preceptora/panel/alumno/' + id);
        if (!res.ok) throw new Error('Error');
        const html = await res.text();
        // Inyectar HTML sin ejecutar scripts (los scripts están en panel.php)
        const body = document.getElementById('modal-body');
        // Strip scripts del HTML recibido antes de inyectar
        body.innerHTML = html.replace(/<script[\s\S]*?<\/script>/gi, '');
    } catch {
        document.getElementById('modal-body').innerHTML = '<div class="error-msg">Error al cargar datos del alumno.</div>';
    }
}

document.querySelectorAll('.btn-ver').forEach(btn => {
    btn.addEventListener('click', () => {
        const id    = btn.dataset.id;
        const item  = btn.closest('.alumno-item');
        abrirModal(id, item.dataset.nombre, item.dataset.curso);
    });
});

// ── Tabs ──
function showTab(name, btn) {
    ['recursa','intensifica'].forEach(t => {
        const el = document.getElementById('tab-' + t);
        if (el) el.style.display = t === name ? '' : 'none';
    });
    document.querySelectorAll('#modal-body .tab').forEach(t => t.classList.remove('active'));
    if (btn) btn.classList.add('active');
}

// ── Recursada ──
async function guardarRecursa(alumnoId) {
    const mat    = +document.getElementById('r-materia').value;
    const anio   = +document.getElementById('r-anio').value;
    const fecha  = document.getElementById('r-fecha').value;
    const horario = document.getElementById('r-horario').value;
    const obs    = document.getElementById('r-obs').value.trim();
    if (!mat) { alert('Seleccioná una materia.'); return; }
    const res = await fetch('/preceptora/recursada/crear', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ alumno_id:alumnoId, materia_id:mat, anio, fecha:fecha||null, horario:horario||null, observacion:obs })
    });
    if (res.ok) cargarModal(alumnoId);
    else alert('Error al guardar.');
}

async function toggleRecursa(id) {
    const res = await fetch('/preceptora/recursada/toggle', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ id })
    });
    if (!res.ok) { alert('Error'); return; }
    const d = await res.json();
    document.getElementById('rbadge-'+id).textContent = d.aprobada ? 'Aprobada' : 'Pendiente';
    document.getElementById('rbadge-'+id).className   = 'badge ' + (d.aprobada ? 'badge-aprobada' : 'badge-pendiente');
    document.getElementById('rbtn-'+id).textContent   = d.aprobada ? 'Desmarcar' : 'Aprobar';
    document.getElementById('rbtn-'+id).className     = 'btn-sm ' + (d.aprobada ? 'btn-danger' : '');
}

async function eliminarRecursa(id) {
    if (!confirm('¿Eliminar esta recursada?')) return;
    const res = await fetch('/preceptora/recursada/eliminar', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ id })
    });
    if (res.ok) document.getElementById('rrow-'+id)?.remove();
    else alert('Error al eliminar.');
}

// ── Intensificación ──
async function guardarIntensifica(alumnoId) {
    const mat  = +document.getElementById('i-materia').value;
    const anio = +document.getElementById('i-anio').value;
    const sem1 = document.getElementById('i-sem1').value;
    const sem2 = document.getElementById('i-sem2').value;
    if (!mat)  { alert('Seleccioná una materia.'); return; }
    if (!sem1) { alert('Completá la fecha de inicio de la Semana 1.'); return; }
    if (!sem2) { alert('Completá la fecha de inicio de la Semana 2.'); return; }

    const dias = ['lunes','martes','miercoles','jueves','viernes'];
    const body = { alumno_id:alumnoId, materia_id:mat, anio,
                   semana1_inicio:sem1, semana2_inicio:sem2,
                   observacion: document.getElementById('i-obs').value.trim() };
    dias.forEach(d => {
        body[d+'_horario'] = document.getElementById('i-'+d+'-h').value.trim() || null;
        body[d+'_semana']  = +document.getElementById('i-'+d+'-s').value;
    });

    const res = await fetch('/preceptora/intensificacion/guardar', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify(body)
    });
    if (res.ok) cargarModal(alumnoId);
    else alert('Error al guardar.');
}

async function toggleIntensifica(id) {
    const res = await fetch('/preceptora/intensificacion/toggle', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ id })
    });
    if (!res.ok) { alert('Error'); return; }
    const d = await res.json();
    document.getElementById('ibadge-'+id).textContent = d.aprobada ? 'Aprobada' : 'Pendiente';
    document.getElementById('ibadge-'+id).className   = 'badge ' + (d.aprobada ? 'badge-aprobada' : 'badge-pendiente');
    document.getElementById('ibtn-'+id).textContent   = d.aprobada ? 'Desmarcar' : 'Aprobar';
    document.getElementById('ibtn-'+id).className     = 'btn-sm ' + (d.aprobada ? 'btn-danger' : '');
}

async function eliminarIntensifica(id) {
    if (!confirm('¿Eliminar esta intensificación?')) return;
    const res = await fetch('/preceptora/intensificacion/eliminar', {
        method:'POST', headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ id })
    });
    if (res.ok) document.getElementById('icard-'+id)?.remove();
    else alert('Error al eliminar.');
}
</script>
<?php
$content = ob_get_clean();
$pageTitle = 'Panel — Preceptora';
require __DIR__ . '/../../views/layout.php';
