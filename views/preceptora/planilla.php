<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/Recursada.php';
require_once __DIR__ . '/../../config/db.php';

// Parámetros
$cursoParam  = trim($_GET['curso']       ?? '');
$anioLectivo = (int)($_GET['anio_lectivo'] ?? date('Y'));

// Todos los cursos disponibles
$cursos = DB::get()->query(
    'SELECT DISTINCT curso FROM alumnos ORDER BY curso ASC'
)->fetchAll(PDO::FETCH_COLUMN);

// Todas las materias (para columnas)
$materias = DB::get()->query(
    'SELECT * FROM materias ORDER BY nombre ASC'
)->fetchAll();

// Datos de la planilla
$filas = [];
$anioAlumno = 0;
if ($cursoParam !== '') {
    $filas      = Recursada::getPlanilla($cursoParam, $anioLectivo);
    $anioAlumno = $cursoParam !== '' ? Recursada::extraerAnio($cursoParam) : 0;
}

ob_start();
?>
<div class="panel-header">
    <h2 class="section-title">Planilla de trayectoria</h2>
</div>

<!-- Filtros -->
<form method="GET" action="/preceptora/planilla" class="planilla-filtros">
    <div class="form-group" style="margin:0">
        <label>Curso</label>
        <select name="curso" onchange="this.form.submit()">
            <option value="">— Seleccioná un curso —</option>
            <?php foreach ($cursos as $c): ?>
            <option value="<?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?>"
                    <?= $c === $cursoParam ? 'selected' : '' ?>>
                <?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group" style="margin:0">
        <label>Año lectivo</label>
        <input type="number" name="anio_lectivo" value="<?= $anioLectivo ?>"
               min="2020" max="2099" style="width:100px" onchange="this.form.submit()">
    </div>
</form>

<?php if ($cursoParam === ''): ?>
<p class="empty-msg" style="margin-top:1.5rem">Seleccioná un curso para ver la planilla.</p>
<?php elseif (empty($filas)): ?>
<p class="empty-msg" style="margin-top:1.5rem">No hay alumnos en este curso.</p>
<?php else: ?>

<div class="planilla-titulo">
    TRAYECTORIA <?= $anioLectivo ?> — <?= htmlspecialchars(strtoupper($cursoParam), ENT_QUOTES, 'UTF-8') ?>
    <?php if ($anioAlumno > 1): ?>
    <span class="planilla-subtitulo">INTENSIFICA MATERIAS DE <?= $anioAlumno - 1 ?>° EN:</span>
    <?php endif; ?>
</div>

<div class="table-wrap planilla-wrap">
<table class="tabla planilla-tabla" id="tabla-planilla">
    <thead>
        <tr>
            <th class="col-alumno">Alumno</th>
            <?php foreach ($materias as $m): ?>
            <th class="col-materia"><?= htmlspecialchars(strtoupper($m['nombre']), ENT_QUOTES, 'UTF-8') ?></th>
            <?php endforeach; ?>
            <th class="col-recursa">RECURSA</th>
            <th class="col-obs">OBSERVACIONES</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($filas as $alumno):
        $tieneAlguna = !empty($alumno['recursadas']);
    ?>
    <tr class="<?= $tieneAlguna ? 'fila-con-materias' : 'fila-sin-materias' ?>">
        <td class="col-alumno td-nombre">
            <a href="/preceptora/ficha?id=<?= (int)$alumno['id'] ?>" class="link-alumno">
                <?= htmlspecialchars($alumno['nombre'], ENT_QUOTES, 'UTF-8') ?>
            </a>
            <?php if (!empty($alumno['legajo'])): ?>
            <span class="legajo-mini"><?= htmlspecialchars($alumno['legajo'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </td>
        <?php foreach ($materias as $m):
            $rec = $alumno['recursadas'][$m['id']] ?? null;
        ?>
        <td class="col-materia td-celda <?= $rec ? 'celda-activa' : 'celda-vacia' ?>">
            <?php if ($rec): ?>
                <span class="celda-materia">
                    <?= htmlspecialchars(strtoupper(substr($m['nombre'], 0, 4)), ENT_QUOTES, 'UTF-8') ?>
                    <?= $anioAlumno ?>
                </span>
            <?php else: ?>
                <span class="celda-sin"></span>
            <?php endif; ?>
        </td>
        <?php endforeach; ?>
        <td class="col-recursa td-recursa">
            <?php if ($tieneAlguna): ?>
            <span class="recursa-si">SÍ</span>
            <?php else: ?>
            <span class="recursa-no">NO</span>
            <?php endif; ?>
        </td>
        <td class="col-obs td-obs">
            <?php
            // Juntar observaciones de todas las recursadas
            $obs = array_filter(array_column($alumno['recursadas'], 'observacion'));
            echo htmlspecialchars(implode(' / ', $obs), ENT_QUOTES, 'UTF-8');
            ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<p class="hint" style="margin-top:.75rem">
    Las celdas con color indican materias que el alumno intensifica este año.
    Para agregar o quitar materias, usá el <a href="/preceptora/ficha">legajo del alumno</a>.
</p>

<?php endif; ?>

<style>
/* Filtros */
.planilla-filtros {
    display: flex;
    gap: 1rem;
    align-items: flex-end;
    flex-wrap: wrap;
    background: #fff;
    padding: 1rem;
    border-radius: var(--radio);
    box-shadow: var(--sombra);
    border: 1px solid var(--borde);
    margin-bottom: 1.25rem;
}

/* Título planilla */
.planilla-titulo {
    font-size: 1rem;
    font-weight: 700;
    color: var(--primario);
    text-align: center;
    padding: .6rem 1rem;
    background: #eef4ff;
    border-radius: var(--radio) var(--radio) 0 0;
    border: 1px solid var(--borde);
    border-bottom: none;
    display: flex;
    flex-direction: column;
    gap: .2rem;
}
.planilla-subtitulo {
    font-size: .82rem;
    font-weight: 600;
    color: var(--texto-suave);
    letter-spacing: .04em;
}

/* Tabla planilla */
.planilla-wrap { border-radius: 0 0 var(--radio) var(--radio); }
.planilla-tabla { min-width: 700px; }

.planilla-tabla th {
    font-size: .72rem;
    padding: .5rem .4rem;
    text-align: center;
    white-space: nowrap;
    letter-spacing: .03em;
}
.planilla-tabla td {
    padding: .45rem .4rem;
    font-size: .82rem;
    border-bottom: 1px solid #e8ecf5;
    border-right: 1px solid #e8ecf5;
}

/* Columnas */
.col-alumno  { min-width: 160px; text-align: left !important; }
.col-materia { width: 70px; text-align: center; }
.col-recursa { width: 80px; text-align: center; }
.col-obs     { min-width: 140px; }

/* Nombre alumno */
.td-nombre { vertical-align: middle; }
.link-alumno {
    font-weight: 600;
    color: var(--primario);
    font-size: .85rem;
    display: block;
}
.link-alumno:hover { color: var(--acento); }
.legajo-mini {
    font-size: .72rem;
    color: var(--gris);
    display: block;
}

/* Celdas de materias */
.celda-activa {
    background: #dbeafe;
    text-align: center;
    vertical-align: middle;
}
.celda-vacia {
    background: #f0f0f0;
    text-align: center;
}
.celda-materia {
    font-size: .75rem;
    font-weight: 700;
    color: var(--primario);
    white-space: nowrap;
}

/* Filas */
.fila-sin-materias .td-nombre { color: var(--gris); }
.fila-sin-materias .link-alumno { color: var(--gris); }

/* Recursa */
.recursa-si {
    background: #fdecea;
    color: var(--rojo);
    border: 1px solid #f5b7b1;
    border-radius: 4px;
    padding: .15rem .5rem;
    font-size: .72rem;
    font-weight: 700;
}
.recursa-no {
    background: #eafaf1;
    color: var(--verde);
    border: 1px solid #a9dfbf;
    border-radius: 4px;
    padding: .15rem .5rem;
    font-size: .72rem;
    font-weight: 700;
}

/* Observaciones */
.td-obs { font-size: .78rem; color: var(--texto-suave); }

@media (max-width: 768px) {
    .planilla-filtros { flex-direction: column; }
    .planilla-tabla th, .planilla-tabla td { font-size: .7rem; padding: .35rem .3rem; }
    .col-alumno { min-width: 110px; }
}
</style>
<?php
$content = ob_get_clean();
$pageTitle = 'Planilla de trayectoria';
require __DIR__ . '/../../views/layout.php';
