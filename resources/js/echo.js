import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

// Echo otomatis memakai meta[name="csrf-token"] untuk header X-CSRF-TOKEN
// pada POST /broadcasting/auth (otorisasi kanal privat).
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
    wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: '/broadcasting/auth',
});

const onRealtimeNotification = (event) => {
    console.log('[RealtimeNotification]', event);

    const target = document.getElementById('realtime-log');
    if (!target) {
        return;
    }

    const item = document.createElement('li');
    item.textContent = `${event.sent_at ?? ''} — ${event.message ?? JSON.stringify(event)}`;
    target.prepend(item);
};

// echo.js ikut termuat di halaman guest, jadi hanya berlangganan jika ada user.
// Echo.private() yang menambah awalan "private-"; di sini pakai nama kanal polos.
const { userId, userRole, userDivision } = document.body.dataset;

if (userId) {
    const channels = [`user.${userId}`];

    if (userRole) {
        channels.push(`role.${userRole}`);
    }

    if (userDivision) {
        channels.push(`division.${userDivision.toLowerCase()}`);
    }

    channels.forEach((channel) => {
        window.Echo.private(channel).listen('.RealtimeNotification', onRealtimeNotification);
    });
}
