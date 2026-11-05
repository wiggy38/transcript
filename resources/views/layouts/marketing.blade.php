@props(["titre" => null, "description" => null, "image" => null, "actif" => null])
@php
    $titre ??= __('interface.marque');
    $description ??= __('accueil.description');
    $image ??= asset('images/partage-voxia.png');
    $adresse = url()->current();
    $localeOg = app()->getLocale() === 'fr' ? 'fr_FR' : 'en_US';
    // Une adresse par langue sur les pages publiques (hreflang) ; vide ailleurs.
    $alternatives = \App\Support\Langue::alternatives(request());
@endphp
<!doctype html>
<html lang="{{ str_replace("_", "-", app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0D0B1A">
    <title>{{ $titre }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ $adresse }}">
    @foreach ($alternatives as $langue => $alternative)
        <link rel="alternate" hreflang="{{ $langue }}" href="{{ $alternative }}">
    @endforeach
    @if ($alternatives !== [])
        <link rel="alternate" hreflang="x-default" href="{{ url('/') }}">
    @endif

    {{-- Partage (Open Graph et Twitter) : carte au style Nocturne, 1200 × 630. --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ __('interface.marque') }}">
    <meta property="og:locale" content="{{ $localeOg }}">
    @foreach (array_keys($alternatives) as $langue)
        @if ($langue !== app()->getLocale())
            <meta property="og:locale:alternate" content="{{ $langue === 'fr' ? 'fr_FR' : 'en_US' }}">
        @endif
    @endforeach
    <meta property="og:title" content="{{ $titre }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $adresse }}">
    <meta property="og:image" content="{{ $image }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ __('accueil.partage.alt') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $titre }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="{{ $image }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen antialiased">
    <a href="#contenu" class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:rounded-bouton focus:bg-accent focus:px-4 focus:py-2 focus:text-sur-accent">
        {{ __('interface.acces.aller_contenu') }}
    </a>

    {{-- En-tête collant, fond légèrement translucide et flou (handoff). Menu replié sous 768 px. --}}
    <header class="sticky top-0 z-40 border-b border-filet bg-fond/88 backdrop-blur-[10px]" x-data="{ menu: false }">
        <div class="mx-auto flex w-full max-w-[1120px] flex-wrap items-center justify-between gap-x-4 gap-y-3 px-4 py-2.5">
            <x-marque />

            <button type="button" class="inline-flex min-h-11 items-center rounded-bouton border border-filet-contour px-3.5 text-sm font-medium text-texte md:hidden"
                aria-controls="menu-principal" :aria-expanded="menu.toString()" @click="menu = !menu">
                {{ __('interface.navigation.menu') }}
            </button>

            <nav id="menu-principal" aria-label="{{ __('interface.navigation.principale') }}"
                class="order-last w-full md:order-none md:w-auto" :class="menu ? 'block' : 'hidden md:block'">
                <ul class="m-0 flex list-none flex-col gap-x-5 gap-y-1 p-0 text-sm md:flex-row md:items-center md:gap-y-0">
                    <li><a href="{{ route('accueil') }}#fonctionnement" class="inline-flex min-h-11 items-center text-texte-secondaire no-underline hover:text-texte">{{ __('interface.navigation.fonctionnement') }}</a></li>
                    <li><a href="{{ route('accueil') }}#usages" class="inline-flex min-h-11 items-center text-texte-secondaire no-underline hover:text-texte">{{ __('interface.navigation.usages') }}</a></li>
                    <li><a href="{{ route('accueil') }}#tarifs" class="inline-flex min-h-11 items-center text-texte-secondaire no-underline hover:text-texte">{{ __('interface.navigation.tarifs') }}</a></li>
                    <li><a href="{{ route('accueil') }}#questions" class="inline-flex min-h-11 items-center text-texte-secondaire no-underline hover:text-texte">{{ __('interface.navigation.questions') }}</a></li>
                    @auth
                        <li><x-bouton href="{{ route('tableau-de-bord') }}" class="mt-2 md:mt-0">{{ __('interface.navigation.espace') }}</x-bouton></li>
                    @else
                        <li><a href="{{ route('login') }}" class="inline-flex min-h-11 items-center font-medium text-texte no-underline hover:text-accent-texte">{{ __('interface.navigation.connexion') }}</a></li>
                    @endauth
                </ul>
            </nav>

            <x-selecteur-langue />
        </div>
    </header>

    @isset($tete)
        <div class="border-b border-filet">
            {{ $tete }}
        </div>
    @endisset

    {{-- Pages de texte : conteneur centré. La landing gère sa largeur section par section (fonds pleine largeur). --}}
    <main id="contenu" class="{{ isset($tete) ? 'w-full' : 'mx-auto w-full max-w-[1120px] px-4' }}">
        {{ $slot }}
    </main>

    <footer class="mt-24 border-t border-filet bg-panneau">
        <div class="mx-auto flex w-full max-w-[1120px] flex-wrap items-center justify-between gap-4 px-4 py-7 text-aide text-texte-discret">
            <span>{{ __('interface.pied.droits', ['annee' => now()->year]) }}</span>
            <ul class="m-0 flex list-none flex-wrap gap-x-6 gap-y-2 p-0">
                <li><a href="{{ route('tarifs') }}" class="inline-flex min-h-11 items-center text-texte-secondaire no-underline hover:text-accent-texte">{{ __('interface.pied.tarifs') }}</a></li>
                <li><a href="{{ route('questions') }}" class="inline-flex min-h-11 items-center text-texte-secondaire no-underline hover:text-accent-texte">{{ __('interface.pied.questions') }}</a></li>
                <li><a href="{{ route('conditions') }}" class="inline-flex min-h-11 items-center text-texte-secondaire no-underline hover:text-accent-texte">{{ __('interface.pied.conditions') }}</a></li>
                <li><a href="{{ route('confidentialite') }}" class="inline-flex min-h-11 items-center text-texte-secondaire no-underline hover:text-accent-texte">{{ __('interface.pied.confidentialite') }}</a></li>
                <li><a href="{{ route('contact') }}" class="inline-flex min-h-11 items-center text-texte-secondaire no-underline hover:text-accent-texte">{{ __('interface.pied.contact') }}</a></li>
            </ul>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
