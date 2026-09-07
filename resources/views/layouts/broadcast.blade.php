<!DOCTYPE html>
<html lang="pl" class="game-overlay-html game-overlay-bg-{{ $overlayBg }}{{ $overlayPreview ? ' game-overlay-preview' : '' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Overlay') — twentySix</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    @vite(['resources/css/game-overlay.css', 'resources/js/game-overlay.js'])
</head>
<body class="game-overlay game-overlay-bg-{{ $overlayBg }}{{ $overlayPreview ? ' game-overlay-preview' : '' }}">
    @yield('content')
</body>
</html>
