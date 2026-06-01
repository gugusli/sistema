// Filtros del panel
function filtrarPanel() {
    const curso   = document.getElementById('filtro-curso')?.value   || '';
    const materia = document.getElementById('filtro-materia')?.value || '';
    document.querySelectorAll('#tabla-panel tbody tr[data-curso]').forEach(tr => {
        const matchCurso   = !curso   || tr.dataset.curso   === curso;
        const matchMateria = !materia || tr.dataset.materia === materia;
        tr.style.display = (matchCurso && matchMateria) ? '' : 'none';
    });
}

// Cambiar estado inline (AJAX)
async function cambiarEstado(select) {
    const id     = select.dataset.id;
    const estado = select.value;
    try {
        const res = await fetch('/preceptora/previa/estado', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, estado })
        });
        if (!res.ok) alert('Error al actualizar estado.');
    } catch {
        alert('Error de red al actualizar estado.');
    }
}

// Eliminar previa
async function eliminarPrevia(id) {
    if (!confirm('¿Eliminar esta previa?')) return;
    try {
        const res = await fetch('/preceptora/previa/eliminar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        if (res.ok) location.reload();
        else alert('Error al eliminar.');
    } catch {
        alert('Error de red.');
    }
}
