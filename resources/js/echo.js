import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});

window.Echo.channel('realtime.notifications').listen('.RealtimeNotification', (event) => {
    console.log('[RealtimeNotification]', event);

    const target = document.getElementById('realtime-log');
    if (target) {
        const item = document.createElement('li');
        item.textContent = `${event.sent_at ?? ''} - ${event.message ?? JSON.stringify(event)}`;
        target.prepend(item);
    }
});