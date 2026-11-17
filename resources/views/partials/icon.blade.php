@php
    $paths = [
        'home' => 'M4 5h6v6H4zM14 5h6v6h-6zM4 15h6v4H4zM14 15h6v4h-6z',
        'box' => 'M21 8l-9-5-9 5v8l9 5 9-5V8zM3 8l9 5 9-5M12 13v8',
        'drop' => 'M12 3s6 6.5 6 11a6 6 0 11-12 0c0-4.5 6-11 6-11z',
        'chart' => 'M4 20V10M10 20V4M16 20v-8M22 20H2',
        'logout' => 'M15 17l5-5-5-5M20 12H9M9 4H5v16h4',
        'plus' => 'M12 5v14M5 12h14',
        'check' => 'M5 13l4 4L19 7',
        'alert' => 'M12 9v4M12 17h.01M10.3 3.9L2.4 18a2 2 0 001.7 3h15.8a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z',
        'download' => 'M12 3v12m0 0l-4-4m4 4l4-4M4 21h16',
        'arrow' => 'M5 12h14M13 6l6 6-6 6',
        'menu' => 'M4 6h16M4 12h16M4 18h16',
        'edit' => 'M4 20h4L19 9l-4-4L4 16v4z',
        'trash' => 'M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3',
        'pond' => 'M3 7h18v11H3zM3 12h18M9 7v11M15 7v11',
        'users' => 'M16 8a4 4 0 11-8 0 4 4 0 018 0zM4 21a8 8 0 0116 0',
        'tag' => 'M3 12V4h8l10 10-8 8L3 12zM7.5 8.5h.01',
        'info' => 'M12 16v-4M12 8h.01M12 22a10 10 0 100-20 10 10 0 000 20z',
    ];
@endphp
<svg xmlns="http://www.w3.org/2000/svg" class="{{ $class ?? 'w-5 h-5' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $paths[$name] ?? '' }}"/></svg>
