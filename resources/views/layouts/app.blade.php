@props(["titre" => null, "actif" => null])
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

    {{-- En-tête de l'application (maquettes « Nocturne ») : marque, navigation, jauge de quota, langue, déconnexion. --}}
    <header class="border-b border-filet bg-panneau">
        <div class="mx-auto flex max-w-[1120px] flex-wrap items-center gap-x-7 gap-y-2.5 px-4 py-2.5">
            <x-marque compacte />

            <nav aria-label="{{ __('interface.application.principale') }}">
                <ul class="flex flex-wrap items-center gap-1">
                    @foreach ([
                        'nouvelle' => 'tableau-de-bord',
                        'historique' => 'historique',
                        'compte' => 'reglages',
                    ] as $cle => $route)
                        <li>
                            <a href="{{ route($route) }}" @if (($actif ?? null) === $cle) aria-current="page" @endif
                               class="inline-flex min-h-9 items-center rounded-[10px] px-3 text-sm no-underline transition-colors duration-150 {{ ($actif ?? null) === $cle ? 'bg-panneau-eleve font-medium text-texte' : 'text-texte-secondaire hover:bg-panneau-eleve hover:text-texte' }}">
                                {{ __('interface.application.'.$cle) }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            <div class="ml-auto flex flex-wrap items-center gap-x-[18px] gap-y-2.5">
                @auth
                    <x-jauge-quota />
                @endauth
                <x-selecteur-langue />
                @auth
                    <form method="post" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="cursor-pointer whitespace-nowrap px-1 text-sm text-texte-secondaire hover:text-texte">{{ __('comptes.deconnexion') }}</button>
                    </form>
                @endauth
            </div>
        </div>
    </header>

    <main id="contenu" class="mx-auto w-full max-w-[1120px] px-4 pt-8 pb-16">
        {{ $slot }}
    </main>

    <x-pied-page />

    @livewireScripts
</body>
</html>
