@props(["titre" => null, "actif" => null])
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $titre ?? __('interface.marque') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen antialiased">
    <a href="#contenu" class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:rounded-bouton focus:bg-accent focus:px-4 focus:py-2 focus:text-fond">
        {{ __('interface.acces.aller_contenu') }}
    </a>

    <header class="border-b border-filet">
        <div class="mx-auto flex max-w-[1240px] flex-wrap items-center gap-x-8 gap-y-4 px-6 py-4">
            <x-marque />

            {{-- Liens provisoires (#) : les écrans de l'application restent à maquetter. --}}
            <nav aria-label="{{ __('interface.application.principale') }}">
                <ul class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
                    <li>
                        <a href="{{ route('tableau-de-bord') }}" @if (($actif ?? null) === 'nouvelle') aria-current="page" @endif
                           class="{{ ($actif ?? null) === 'nouvelle' ? 'text-accent' : 'text-texte-secondaire hover:text-texte' }} no-underline">
                            {{ __('interface.application.nouvelle') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('historique') }}" @if (($actif ?? null) === 'historique') aria-current="page" @endif
                           class="{{ ($actif ?? null) === 'historique' ? 'text-accent' : 'text-texte-secondaire hover:text-texte' }} no-underline">
                            {{ __('interface.application.historique') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('reglages') }}" @if (($actif ?? null) === 'compte') aria-current="page" @endif
                           class="{{ ($actif ?? null) === 'compte' ? 'text-accent' : 'text-texte-secondaire hover:text-texte' }} no-underline">
                            {{ __('interface.application.compte') }}
                        </a>
                    </li>
                </ul>
            </nav>

            {{-- Compteur de quota (toujours visible pour un compte connecté), sélecteur de langue. --}}
            <div class="ml-auto flex flex-wrap items-center gap-6">
                @auth
                    <x-compteur-plan />
                @endauth
                <x-selecteur-langue />
                @auth
                    <form method="post" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="cursor-pointer text-sm text-texte-secondaire hover:text-accent">{{ __('comptes.deconnexion') }}</button>
                    </form>
                @endauth
            </div>
        </div>
    </header>

    <main id="contenu" class="mx-auto w-full max-w-[1240px] px-6 py-10">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
