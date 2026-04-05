<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title . ' - ' . config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>
<script>
    // Langsung paksa class light di awal agar tidak flicker
    document.documentElement.classList.add('light');
    document.documentElement.classList.remove('dark');
    document.documentElement.style.colorScheme = 'light';

    // Override fungsi setItem khusus untuk flux.appearance
    const storageTarget = 'flux.appearance';
    localStorage.setItem(storageTarget, 'light');

    const originalSetItem = localStorage.setItem;
    localStorage.setItem = function(key, value) {
        if (key === storageTarget && value !== 'light') {
            console.warn('Flux mencoba mengganti ke dark mode, tapi diblokir.');
            return; // Abaikan jika coba diganti selain 'light'
        }
        originalSetItem.apply(this, arguments);
    };
</script>

{{--
<link rel="icon" href="/favicon.ico" sizes="any"> --}}
{{--
<link rel="icon" href="/favicon.svg" type="image/svg+xml"> --}}

<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="icon" href="{{ asset('logo.svg') }}" type="image/svg+xml">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
