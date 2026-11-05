@props(["titre" => null, "entete" => "border-b border-filet bg-panneau", "principal" => "mx-auto grid w-full max-w-[1120px] items-start gap-12 px-4 py-10 lg:grid-cols-[minmax(0,420px)_minmax(0,1fr)] lg:items-center lg:gap-20 lg:py-16"])
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0D0B1A">
    <title>{{ $titre ?? __('interface.marque') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen antialiased">
    <a href="#contenu" class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:rounded-bouton focus:bg-accent focus:px-4 focus:py-2 focus:text-sur-accent">
        {{ __('interface.acces.aller_contenu') }}
    </a>

    {{-- Connexion et inscription (maquettes « Connexion » et « Inscription ») : en-tête minimal, sans navigation d'application. --}}
    <header class="{{ $entete }}">
        <div class="mx-auto flex max-w-[1120px] items-center justify-between gap-4 px-4 py-3">
            <x-marque />
            <x-selecteur-langue />
        </div>
    </header>

    <main id="contenu" class="{{ $principal }}">
        {{ $slot }}
    </main>

    <x-pied-page />

    @livewireScripts
</body>
</html>
