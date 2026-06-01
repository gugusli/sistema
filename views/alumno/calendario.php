<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/Previa.php';

$alumno_id = (int)$_SESSION['id'];
$previas   = Previa::getByAlumno($alumno_id);

// Agrupar por fecha
$porFecha = [];
foreach ($previas as $p) {
    $porFecha[$p['fecha']][] = $p;
}

// Mes a mostrar (parámetro ?mes=YYYY-MM, default hoy)
$mesParam = $_GET['mes'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $mesParam)) $mesParam = date('Y-m');
[$anio, $mes] = explode('-', $mesParam);
$anio = (int)$anio; $mes = (int)$mes;

$primerDia   = mktime(0, 0, 0, $mes, 1, $anio);
$diasEnMes   = (int)date('t', $primerDia);
$diaSemana   = (int)date('N', $primerDia); // 1=lun, 7=dom
$hoy         = date('Y-m-d');

// Navegación
$mesPrev = date('Y-m', mktime(0, 0, 0, $mes - 1, 1, $anio));
$mesSig  = date('Y-m', mktime(0, 0, 0, $mes + 1, 1, $anio));
$nombreMes = strftime('%B %Y', $primerDia) ?: date('F Y', $primerDia);

ob_start();
?>
<div class="cal-header">
    <a href="/alumno/calendario?mes=<?= $mesPrev ?>" class="btn-secondary btn-sm">&#8592;</a>
    <h2 class="section-title" style="margin:0"><?= ucfirst($nombreMes) ?></h2>
    <a href="/alumno/calendario?mes=<?= $mesSig ?>" class="btn-secondary btn-sm">&#8594;</a>
</div>

<div class="calendario">
    <?php foreach (['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'] as $d): ?>
    <div class="cal-dia-nombre"><?= $d ?></div>
    <?php endforeach; ?>

    <?php
    // Celdas vacías antes del primer día
    for ($i = 1; $i < $diaSemana; $i++): ?>
    <div class="cal-celda vacia"></div>
    <?php endfor; ?>

    <?php for ($dia = 1; $dia <= $diasEnMes; $dia++):
        $fecha = sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
        $esPrevias = isset($porFecha[$fecha]);
        $esHoy     = $fecha === $hoy;
        $clases    = 'cal-celda' . ($esHoy ? ' hoy' : '') . ($esPrevias ? ' tiene-previa' : '');
    ?>
    <div class="<?= $clases ?>">
        <span class="cal-num"><?= $dia ?></span>
        <?php if ($esPrevias): ?>
        <div class="cal-previas">
            <?php foreach ($porFecha[$fecha] as $p): ?>
            <span class="cal-badge badge-<?= strtolower($p['estado']) ?>" title="<?= htmlspecialchars($p['materia_nombre'] . ' - ' . substr($p['horario'],0,5) . ' - Aula ' . $p['aula'], ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($p['materia_nombre'], ENT_QUOTES, 'UTF-8') ?>
            </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endfor; ?>
</div>

<style>
.cal-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem; }
.calendario {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 4px;
    margin-top: .5rem;
}
.cal-dia-nombre {
    text-align: center;
    font-weight: 700;
    font-size: .8rem;
    color: #666;
    padding: .3rem 0;
}
.cal-celda {
    min-height: 70px;
    background: #fff;
    border-radius: 6px;
    padding: .35rem .4rem;
    box-shadow: 0 1px 3px rgba(0,0,0,.07);
    font-size: .82rem;
}
.cal-celda.vacia { background: transparent; box-shadow: none; }
.cal-celda.hoy { border: 2px solid var(--acento); }
.cal-celda.tiene-previa { background: #eef4ff; }
.cal-num { font-weight: 600; color: #333; display:block; margin-bottom:.2rem; }
.cal-celda.hoy .cal-num { color: var(--acento); }
.cal-previas { display:flex; flex-direction:column; gap:2px; }
.cal-badge {
    font-size: .7rem;
    padding: 1px 5px;
    border-radius: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    cursor: default;
}
.badge-pendiente { background:#fef9e7; color:#7d6608; border:1px solid #f0c040; }
.badge-aprobada  { background:#eafaf1; color:#1e8449; border:1px solid #27ae60; }
.badge-ausente   { background:#fdecea; color:#c0392b; border:1px solid #e74c3c; }
@media(max-width:600px){
    .cal-celda { min-height:50px; font-size:.7rem; }
    .cal-badge { display:none; }
    .cal-celda.tiene-previa::after { content:'●'; color:var(--acento); font-size:.7rem; }
}
</style>
<?php
$content = ob_get_clean();
$pageTitle = 'Calendario — Previas';
require __DIR__ . '/../../views/layout.php';
