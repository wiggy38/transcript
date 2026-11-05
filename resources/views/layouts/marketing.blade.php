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
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
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

    {{-- Partage (Open Graph et Twitter) : carte au style Signal, 1200 × 630. --}}
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

    {{-- En-tête et héros partagent le fond quadrillé (maquette). --}}
    <div class="border-b border-filet {{ isset($tete) ? 'vx-grille' : '' }}">
        <header>
            <div class="mx-auto flex max-w-[1240px] flex-wrap items-center justify-between gap-4 px-6 py-5">
                <x-marque />

                <nav aria-label="{{ __('interface.navigation.principale') }}">
                    <ul class="flex flex-wrap items-center gap-x-7 gap-y-3 text-sm">
                        <li><a href="{{ route('accueil') }}#usages" class="text-texte-secondaire no-underline hover:text-accent-texte">{{ __('interface.navigation.usages') }}</a></li>
                        <li><a href="{{ route('accueil') }}#fonctionnement" class="text-texte-secondaire no-underline hover:text-accent-texte">{{ __('interface.navigation.fonctionnement') }}</a></li>
                        <li><a href="{{ route('accueil') }}#tarifs" class="text-texte-secondaire no-underline hover:text-accent-texte">{{ __('interface.navigation.tarifs') }}</a></li>
                        @auth
                            <li><x-bouton href="{{ route('tableau-de-bord') }}">{{ __('interface.navigation.espace') }}</x-bouton></li>
                        @else
                            <li><a href="{{ route('login') }}" class="text-texte-secondaire no-underline hover:text-accent-texte">{{ __('interface.navigation.connexion') }}</a></li>
                            <li><x-bouton href="{{ route('register') }}">{{ __('interface.navigation.essayer') }}</x-bouton></li>
                        @endauth
                    </ul>
                </nav>
                <x-selecteur-langue />
            </div>
        </header>

        @isset($tete)
            {{ $tete }}
        @endisset
    </div>

    {{-- Bandeau pleine largeur, hors du conteneur de page. --}}
    @isset($bandeau)
        {{ $bandeau }}
    @endisset

    <main id="contenu" class="mx-auto w-full max-w-[1240px] px-6">
        {{ $slot }}
    </main>

    <footer class="mt-32 border-t border-filet">
        <div class="mx-auto flex max-w-[1240px] flex-wrap items-center justify-between gap-4 px-6 pt-7 text-sm text-texte-discret">
            <span>{{ __('interface.pied.droits', ['annee' => now()->year]) }}</span>
            <ul class="flex flex-wrap gap-6">
                <li><a href="{{ route('tarifs') }}" class="text-texte-secondaire no-underline hover:text-accent-texte">{{ __('interface.pied.tarifs') }}</a></li>
                <li><a href="{{ route('questions') }}" class="text-texte-secondaire no-underline hover:text-accent-texte">{{ __('interface.pied.questions') }}</a></li>
                <li><a href="{{ route('confidentialite') }}" class="text-texte-secondaire no-underline hover:text-accent-texte">{{ __('interface.pied.confidentialite') }}</a></li>
                <li><a href="{{ route('conditions') }}" class="text-texte-secondaire no-underline hover:text-accent-texte">{{ __('interface.pied.conditions') }}</a></li>
                <li><a href="{{ route('contact') }}" class="text-texte-secondaire no-underline hover:text-accent-texte">{{ __('interface.pied.contact') }}</a></li>
            </ul>
        </div>
        <p aria-hidden="true" class="mt-6 overflow-hidden px-4 text-center text-[clamp(120px,24vw,360px)] leading-[0.78] font-bold tracking-[-0.06em] whitespace-nowrap text-transparent [-webkit-text-stroke:1px_var(--color-filet-contour)]">voxia</p>
    </footer>

    @livewireScripts
</body>
</html>
