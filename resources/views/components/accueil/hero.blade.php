{{-- Héros (direction « Nocturne », landing B, handoff écran 1) : pastille des sources, titre, texte d'appui
     et formulaire du lien, qui démarre le parcours. Visiteur : inscription, lien conservé. Connecté :
     Nouvelle transcription, lien pré-rempli (LienController). --}}
@php
    $formats = collect(config('voxia.uploads.extensions'))->map(fn ($extension) => strtoupper($extension))->implode(', ');
    $destination = auth()->check() ? route('tableau-de-bord') : route('register');
    $erreur = $errors->first('lien');
    $decrit = collect([$erreur ? 'lien-erreur' : null, 'lien-depot'])->filter()->implode(' ');
@endphp

<section class="fond-heros mx-auto w-full max-w-[1120px] px-4 pt-8 pb-12 text-center" aria-labelledby="titre-accueil">
    @if (session('statut') === 'compte-supprime')
        <p role="status" class="mb-8 border-l-2 border-accent py-1 pl-3 text-left text-sm text-texte">
            {{ __('comptes.accueil.compte_supprime') }}
        </p>
    @endif

    <div class="mx-auto flex flex-col items-center gap-[18px]">
        {{-- Pastille des sources : le point « direct » pulse, les logos sont décoratifs --}}
        <p class="inline-flex max-w-full flex-wrap items-center justify-center gap-x-3.5 gap-y-1.5 rounded-panneau border border-filet-contour bg-panneau px-3.5 py-1.5 text-[13px] font-medium text-texte">
            <span class="size-1.5 vx-pulsation rounded-full bg-direct" aria-hidden="true"></span>
            <span class="inline-flex items-center gap-1.5 whitespace-nowrap">
                <svg width="18" height="13" viewBox="0 0 18 13" aria-hidden="true" focusable="false"><rect width="18" height="13" rx="3" fill="#FF0000"/><path d="M7 3.5v6l5-3z" fill="#FFFFFF"/></svg>
                {{ __('accueil.hero.sources.youtube') }}
            </span>
            <span class="inline-flex items-center gap-1.5 whitespace-nowrap">
                <svg width="14" height="16" viewBox="0 0 14 16" aria-hidden="true" focusable="false"><path d="M9 0h2.2c.2 1.9 1.4 3.2 3.3 3.4v2.3c-1.2 0-2.3-.4-3.3-1v5.9A4.6 4.6 0 1 1 6.6 6.9v2.4a2.2 2.2 0 1 0 2.2 2.2z" fill="currentColor"/></svg>
                {{ __('accueil.hero.sources.tiktok') }}
            </span>
            <span class="inline-flex items-center gap-1.5 whitespace-nowrap">
                <x-icone nom="fichier-audio" class="size-4 text-texte-secondaire" />
                {{ __('accueil.hero.sources.fichiers') }}
            </span>
        </p>

        <h1 id="titre-accueil" class="titre-h1 max-w-[1040px]">
            <span class="block">{{ __('accueil.hero.titre') }}</span>
            <span class="block text-accent-texte">{{ __('accueil.hero.titre_accent') }}</span>
        </h1>

        <p class="max-w-[34em] text-appui text-pretty text-texte-secondaire">{{ __('accueil.hero.sous_titre') }}</p>
    </div>

    {{-- Formulaire du lien : l'élément le plus important du héros --}}
    <form id="hero-lien" method="post" action="{{ route('lien.store') }}" class="mx-auto mt-8 max-w-[680px] rounded-panneau border border-filet bg-panneau p-5 text-left shadow-heros" novalidate>
        @csrf

        <div class="flex flex-col gap-2.5">
            <label for="lien" class="text-corps font-semibold text-texte">{{ __('accueil.hero.champ') }}</label>

            <div class="flex flex-wrap gap-2">
                <div class="relative min-w-0 flex-[1_1_260px]">
                    <span class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-texte-discret">
                        <x-icone nom="lien" class="size-3.5" />
                    </span>
                    <input
                        id="lien"
                        name="lien"
                        type="url"
                        inputmode="url"
                        value="{{ old('lien') }}"
                        placeholder="{{ __('accueil.hero.placeholder') }}"
                        required
                        aria-describedby="{{ $decrit }}"
                        @if ($erreur) aria-invalid="true" @endif
                        class="h-[54px] w-full min-w-0 rounded-champ border bg-fond pr-3.5 pl-[42px] font-mono text-[15px] text-texte placeholder:text-texte-discret {{ $erreur ? 'border-[1.5px] border-erreur' : 'border-filet-contour' }}"
                    >
                </div>
                <x-bouton variante="principal" taille="lg" type="submit">{{ __('accueil.hero.bouton') }}</x-bouton>
            </div>
        </div>

        @if ($erreur)
            <x-message-erreur id="lien-erreur" class="mt-3">{{ $erreur }}</x-message-erreur>
        @endif

        <div id="lien-depot" class="mt-3 flex flex-wrap items-center justify-between gap-x-4 gap-y-1 text-aide text-texte-discret">
            <p>
                {!! __('accueil.hero.depot', [
                    'lien' => '<a href="'.e($destination).'" class="text-texte-secondaire underline-offset-4 hover:text-accent-texte">'.e(__('accueil.hero.depot_lien')).'</a>',
                    'formats' => $formats,
                ]) !!}
            </p>
            <p>{{ __('accueil.hero.gratuit') }}</p>
        </div>
    </form>

    <x-accueil.apercu />
</section>
