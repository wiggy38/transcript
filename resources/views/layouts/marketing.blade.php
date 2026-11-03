@props(["titre" => null, "actif" => null])
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titre ?? __('interface.marque') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen antialiased">
    <a href="#contenu" class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:rounded-bouton focus:bg-accent focus:px-4 focus:py-2 focus:text-fond">
        {{ __('interface.acces.aller_contenu') }}
    </a>

    <header class="border-b border-filet">
        <div class="mx-auto flex max-w-[1240px] flex-wrap items-center justify-between gap-4 px-6 py-5">
            <x-marque />

            <nav aria-label="{{ __('interface.navigation.principale') }}">
                <ul class="flex flex-wrap items-center gap-x-7 gap-y-3 text-sm">
                    <li><a href="#usages" class="text-texte-secondaire no-underline hover:text-accent">{{ __('interface.navigation.usages') }}</a></li>
                    <li><a href="#fonctionnement" class="text-texte-secondaire no-underline hover:text-accent">{{ __('interface.navigation.fonctionnement') }}</a></li>
                    <li><a href="#tarifs" class="text-texte-secondaire no-underline hover:text-accent">{{ __('interface.navigation.tarifs') }}</a></li>
                    <li><a href="{{ route('login') }}" class="text-texte-secondaire no-underline hover:text-accent">{{ __('interface.navigation.connexion') }}</a></li>
                    <li><x-bouton href="{{ route('accueil') }}">{{ __('interface.navigation.essayer') }}</x-bouton></li>
                </ul>
            </nav>
        </div>
    </header>

    <main id="contenu" class="mx-auto w-full max-w-[1240px] px-6">
        {{ $slot }}
    </main>

    <footer class="mt-32 border-t border-filet">
        <div class="mx-auto flex max-w-[1240px] flex-wrap items-center justify-between gap-4 px-6 pt-7 text-sm text-texte-discret">
            <span>{{ __('interface.pied.droits', ['annee' => now()->year]) }}</span>
            <ul class="flex flex-wrap gap-6">
                <li><a href="#" class="text-texte-secondaire no-underline hover:text-accent">{{ __('interface.pied.confidentialite') }}</a></li>
                <li><a href="#" class="text-texte-secondaire no-underline hover:text-accent">{{ __('interface.pied.conditions') }}</a></li>
                <li><a href="#" class="text-texte-secondaire no-underline hover:text-accent">{{ __('interface.pied.contact') }}</a></li>
            </ul>
        </div>
        <p aria-hidden="true" class="mt-6 overflow-hidden px-4 text-center text-[clamp(120px,24vw,360px)] leading-[0.78] font-bold tracking-[-0.06em] whitespace-nowrap text-transparent [-webkit-text-stroke:1px_var(--color-filet-contour)]">voxia</p>
    </footer>

    @livewireScripts
</body>
</html>
