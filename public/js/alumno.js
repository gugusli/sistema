// Countdown en tiempo real para cards de hoy
function actualizarCountdowns() {
    document.querySelectorAll('.card-countdown[data-datetime]').forEach(el => {
        const target = new Date(el.dataset.datetime);
        const diff = target - Date.now();
        if (diff <= 0) {
            el.textContent = '¡Ahora!';
            el.classList.add('ahora');
            return;
        }
        const h = Math.floor(diff / 3600000);
        const m = Math.floor((diff % 3600000) / 60000);
        const s = Math.floor((diff % 60000) / 1000);
        el.textContent = `Faltan ${h}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')} hs`;
    });
}
actualizarCountdowns();
setInterval(actualizarCountdowns, 1000);

// Confirmar lectura
document.querySelectorAll('.btn-confirmar:not([disabled])').forEach(btn => {
    btn.addEventListener('click', async () => {
        const id = btn.dataset.id;
        btn.disabled = true;
        try {
            const res = await fetch('/api/confirmacion', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ previa_id: id })
            });
            if (res.ok) {
                btn.textContent = '✓ Visto';
            } else {
                btn.disabled = false;
            }
        } catch {
            btn.disabled = false;
        }
    });
});
