<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/Recursada.php';
require_once __DIR__ . '/../../src/Intensificacion.php';

// $alumno viene del router (index.php)
if (!isset($alumno)) { http_response_code(404); echo 'Alumno no encontrado'; exit; }
$alumno_id    = (int)$alumno['id'];
$recursadas   = Recursada::getByAlumno($alumno_id);
$intensifica  = Intensificacion::getByAlumno($alumno_id);
$materias_all = DB::get()->query('SELECT id, nombre FROM materias ORDER BY nombre')->fetchAll();
$anio_actual  = (int)date('Y');
$dias         = ['lunes','martes','miercoles','jueves','viernes'];
$dias_label   = ['Lunes','Martes','Miércoles','Jueves','Viernes'];
?>

<!-- ── Tabs ── -->
<div class="tabs" style="margin-bottom:1.25rem">
    <button class="tab active" onclick="showTab('recursa',this)">Recursa (<?= count($recursadas) ?>)</button>
    <button class="tab" onclick="showTab('intensifica',this)">Intensifica (<?= count($intensifica) ?>)</button>
</div>

<!-- ══ TAB: RECURSA ══ -->
<div id="tab-recursa">

    <!-- Lista de recursadas -->
    <?php if (empty($recursadas)): ?>
    <p class="empty-msg">No tiene materias en recursada.</p>
    <?php else: ?>
    <div class="table-wrap" style="margin-bottom:1.25rem">
    <table class="tabla">
        <thead><tr><th>Materia</th><th>Año</th><th>Fecha examen</th><th>Horario</th><th>Estado</th><th>Observación</th><th>Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($recursadas as $r): ?>
        <tr id="rrow-<?= (int)$r['id'] ?>">
            <td><?= htmlspecialchars($r['materia_nombre'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= (int)$r['anio'] ?></td>
            <td><?= $r['fecha'] ? htmlspecialchars(date('d/m/Y', strtotime($r['fecha'])), ENT_QUOTES, 'UTF-8') : '—' ?></td>
            <td><?= $r['horario'] ? htmlspecialchars(substr($r['horario'], 0, 5), ENT_QUOTES, 'UTF-8') : '—' ?></td>
            <td>
                <span class="badge <?= $r['aprobada'] ? 'badge-aprobada' : 'badge-pendiente' ?>"
                      id="rbadge-<?= (int)$r['id'] ?>">
                    <?= $r['aprobada'] ? 'Aprobada' : 'Pendiente' ?>
                </span>
            </td>
            <td><?= htmlspecialchars($r['observacion'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
            <td class="td-acciones">
                <button class="btn-sm <?= $r['aprobada'] ? 'btn-danger' : '' ?>"
                        id="rbtn-<?= (int)$r['id'] ?>"
                        onclick="toggleRecursa(<?= (int)$r['id'] ?>)">
                    <?= $r['aprobada'] ? 'Desmarcar' : 'Aprobar' ?>
                </button>
                <button class="btn-sm btn-danger"
                        onclick="eliminarRecursa(<?= (int)$r['id'] ?>)">✕</button>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>

    <!-- Formulario agregar recursada -->
    <details class="form-desplegable">
        <summary>+ Agregar materia en recursada</summary>
        <div class="form-desplegable-body">
            <div class="form-row">
                <div class="form-group">
                    <label>Materia</label>
                    <select id="r-materia">
                        <option value="">— Seleccioná —</option>
                        <?php foreach ($materias_all as $m): ?>
                        <option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars($m['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Año lectivo</label>
                    <input type="number" id="r-anio" value="<?= $anio_actual ?>" min="2020" max="2099">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>📅 Fecha de examen</label>
                    <input type="date" id="r-fecha">
                </div>
                <div class="form-group">
                    <label>🕐 Horario</label>
                    <input type="time" id="r-horario">
                </div>
                <div class="form-group">
                    <label>Observación (opcional)</label>
                    <input type="text" id="r-obs" placeholder="Ej: Repitió por inasistencias">
                </div>
            </div>
            <button class="btn-primary" onclick="guardarRecursa(<?= $alumno_id ?>)">Guardar</button>
        </div>
    </details>
</div>

<!-- ══ TAB: INTENSIFICA ══ -->
<div id="tab-intensifica" style="display:none">

    <!-- Lista de intensificaciones -->
    <?php if (empty($intensifica)): ?>
    <p class="empty-msg">No tiene materias para intensificar.</p>
    <?php else: ?>
    <?php foreach ($intensifica as $int):
        $diasParsed = Intensificacion::parseDias($int);
    ?>
    <div class="int-card" id="icard-<?= (int)$int['id'] ?>">
        <div class="int-card-header">
            <div>
                <strong><?= htmlspecialchars($int['materia_nombre'], ENT_QUOTES, 'UTF-8') ?></strong>
                <span class="badge <?= $int['aprobada'] ? 'badge-aprobada' : 'badge-pendiente' ?>"
                      id="ibadge-<?= (int)$int['id'] ?>" style="margin-left:.5rem">
                    <?= $int['aprobada'] ? 'Aprobada' : 'Pendiente' ?>
                </span>
            </div>
            <div class="int-card-acciones">
                <button class="btn-sm <?= $int['aprobada'] ? 'btn-danger' : '' ?>"
                        id="ibtn-<?= (int)$int['id'] ?>"
                        onclick="toggleIntensifica(<?= (int)$int['id'] ?>)">
                    <?= $int['aprobada'] ? 'Desmarcar' : 'Aprobar' ?>
                </button>
                <button class="btn-sm btn-danger" onclick="eliminarIntensifica(<?= (int)$int['id'] ?>)">✕</button>
            </div>
        </div>
        <div class="int-semanas">
            <span class="int-semana-chip">📅 Semana 1: <?= htmlspecialchars(date('d/m/Y', strtotime($int['semana1_inicio'])), ENT_QUOTES, 'UTF-8') ?></span>
            <span class="int-semana-chip">📅 Semana 2: <?= htmlspecialchars(date('d/m/Y', strtotime($int['semana2_inicio'])), ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <?php if (!empty($diasParsed)): ?>
        <div class="int-horarios">
            <?php foreach ($diasParsed as $dia => $info): ?>
            <div class="int-dia">
                <span class="int-dia-nombre"><?= ucfirst($dia === 'miercoles' ? 'Miércoles' : $dia) ?></span>
                <span class="int-dia-horario"><?= htmlspecialchars($info['horario'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="int-dia-semanas">
                    <?php if ($info['semana1'] && $info['semana2']): ?>Sem 1 y 2
                    <?php elseif ($info['semana1']): ?>Solo Sem 1
                    <?php else: ?>Solo Sem 2<?php endif; ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($int['observacion'])): ?>
        <div class="int-obs">📝 <?= htmlspecialchars($int['observacion'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <!-- Formulario agregar intensificación -->
    <details class="form-desplegable" style="margin-top:1rem">
        <summary>+ Agregar materia a intensificar</summary>
        <div class="form-desplegable-body">
            <div class="form-row">
                <div class="form-group">
                    <label>Materia</label>
                    <select id="i-materia">
                        <option value="">— Seleccioná —</option>
                        <?php foreach ($materias_all as $m): ?>
                        <option value="<?= (int)$m['id'] ?>"><?= htmlspecialchars($m['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Año lectivo</label>
                    <input type="number" id="i-anio" value="<?= $anio_actual ?>" min="2020" max="2099">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>📅 Inicio Semana 1</label>
                    <input type="date" id="i-sem1">
                </div>
                <div class="form-group">
                    <label>📅 Inicio Semana 2</label>
                    <input type="date" id="i-sem2">
                </div>
            </div>

            <p class="hint">Completá los horarios por día. Dejá vacío si no va ese día. Indicá en qué semana(s) aplica.</p>

            <div class="horarios-grid">
                <?php foreach ($dias as $i => $dia): ?>
                <div class="horario-fila">
                    <span class="horario-dia-label"><?= $dias_label[$i] ?></span>
                    <input type="text" class="input-horario" id="i-<?= $dia ?>-h"
                           placeholder="08:00-09:30">
                    <select class="select-semana" id="i-<?= $dia ?>-s">
                        <option value="12">Ambas semanas</option>
                        <option value="1">Solo semana 1</option>
                        <option value="2">Solo semana 2</option>
                    </select>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="form-group" style="margin-top:.75rem">
                <label>Observación (opcional)</label>
                <input type="text" id="i-obs" placeholder="Ej: Mismo horario que el año pasado">
            </div>
            <button class="btn-primary" onclick="guardarIntensifica(<?= $alumno_id ?>)">Guardar</button>
        </div>
    </details>
</div>

<style>
/* Tabs */
.tabs { display:flex; gap:.35rem; border-bottom:2px solid var(--borde); }
.tab { background:transparent; border:none; padding:.55rem 1rem; font-size:.88rem; font-weight:600; color:var(--texto-suave); cursor:pointer; border-bottom:3px solid transparent; margin-bottom:-2px; border-radius:6px 6px 0 0; transition:color .15s,border-color .15s; }
.tab.active { color:var(--primario); border-bottom-color:var(--naranja); }
.tab:hover { background:#f0f4fa; color:var(--primario); }
.form-desplegable { border:1.5px solid var(--borde); border-radius:var(--radio); overflow:hidden; }
.form-desplegable summary { padding:.7rem 1rem; cursor:pointer; font-weight:600; font-size:.9rem; color:var(--secundario); background:#f5f8ff; list-style:none; }
.form-desplegable summary::-webkit-details-marker { display:none; }
.form-desplegable[open] summary { border-bottom:1px solid var(--borde); }
.form-desplegable-body { padding:1.1rem; background:#fff; }
.form-row { display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:.75rem; margin-bottom:.75rem; }
.horarios-grid { display:flex; flex-direction:column; gap:.5rem; margin:.5rem 0; }
.horario-fila { display:grid; grid-template-columns:80px 1fr 1fr; gap:.5rem; align-items:center; }
.horario-dia-label { font-size:.85rem; font-weight:600; color:var(--texto); }
.input-horario { padding:.35rem .5rem; border:1.5px solid var(--borde); border-radius:6px; font-size:.85rem; }
.select-semana { padding:.35rem .4rem; border:1.5px solid var(--borde); border-radius:6px; font-size:.82rem; }
.int-card { background:#fff; border:1.5px solid var(--borde); border-radius:var(--radio); padding:1rem; margin-bottom:.85rem; }
.int-card-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:.6rem; flex-wrap:wrap; gap:.5rem; }
.int-card-acciones { display:flex; gap:.35rem; }
.int-semanas { display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:.6rem; }
.int-semana-chip { background:#eef4ff; color:var(--secundario); border:1px solid #c8d8f0; border-radius:20px; padding:.18rem .7rem; font-size:.8rem; }
.int-horarios { display:grid; grid-template-columns:repeat(auto-fill,minmax(140px,1fr)); gap:.4rem; margin-bottom:.5rem; }
.int-dia { background:#f5f8ff; border-radius:6px; padding:.4rem .6rem; font-size:.8rem; }
.int-dia-nombre { font-weight:700; color:var(--primario); display:block; }
.int-dia-horario { color:var(--texto); }
.int-dia-semanas { color:var(--texto-suave); font-size:.75rem; display:block; }
.int-obs { font-size:.82rem; color:var(--gris); font-style:italic; margin-top:.4rem; }
</style>
