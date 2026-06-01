function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = atob(base64);
    return Uint8Array.from([...raw].map(c => c.charCodeAt(0)));
}

async function subscribeToPush() {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        console.warn('Web Push no soportado en este navegador.');
        return;
    }
    const permission = await Notification.requestPermission();
    if (permission !== 'granted') return;

    try {
        const reg = await navigator.serviceWorker.ready;
        const sub = await reg.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC)
        });
        const res = await fetch('/api/push/subscribe', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(sub)
        });
        if (res.ok) {
            const banner = document.getElementById('banner-push');
            if (banner) banner.remove();
        }
    } catch (err) {
        console.error('Error al suscribir push:', err);
    }
}

if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/public/sw.js').catch(console.error);
}
