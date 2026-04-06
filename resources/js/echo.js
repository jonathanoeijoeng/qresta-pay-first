import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});

// window.Echo = new Echo({
//     broadcaster: 'reverb',
//     key: 'kpj6cjj5e3nrpmljvwzt', // masukkan key aslimu
//     wsHost: 'qresta-reverb.hellojonathan.my.id',
//     wsPort: 443,
//     wssPort: 443,
//     forceTLS: true,
//     enabledTransports: ['ws', 'wss'],
// });
