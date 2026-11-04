{{-- Héros (maquette Signal) : titre, champ de lien qui démarre le parcours, aperçu produit.
     Visiteur : inscription, lien conservé. Connecté : Nouvelle transcription, lien pré-rempli (LienController). --}}
@php
    $formats = collect(config('voxia.uploads.extensions'))->map(fn ($extension) => strtoupper($extension))->implode(', ');
    $destination = auth()->check() ? route('tableau-de-bord') : route('register');
    $erreur = $errors->first('lien');
    $decrit = collect([$erreur ? 'lien-erreur' : null, 'lien-depot'])->filter()->implode(' ');
@endphp

<section class="mx-auto max-w-[1240px] px-6 pt-24" aria-labelledby="titre-accueil">
    @if (session('statut') === 'compte-supprime')
        <p role="status" class="mb-8 border-l-2 border-accent py-1 pl-3 text-sm text-texte">
            {{ __('comptes.accueil.compte_supprime') }}
        </p>
    @endif

    <p class="mb-7 inline-flex items-center gap-2.5 rounded-full border border-filet bg-fond px-3 py-1.5 font-mono text-xs text-texte-secondaire">
        <span class="vx-pulsation size-[7px] rounded-full bg-accent" aria-hidden="true"></span>
        {{ __('accueil.hero.pastille') }}
    </p>

    <h1 id="titre-accueil" class="max-w-[15ch] text-[clamp(46px,7vw,104px)] leading-[0.96] font-semibold tracking-[-0.045em]">
        {{ __('accueil.hero.titre') }}
        <span class="font-serif font-normal italic tracking-[-0.02em] text-accent">{{ __('accueil.hero.titre_accent') }}</span>
    </h1>

    <div class="mt-10 flex flex-wrap items-end justify-between gap-10">
        <p class="max-w-[30em] flex-[1_1_340px] text-lg text-texte-secondaire">{{ __('accueil.hero.sous_titre') }}</p>

        <form method="post" action="{{ route('lien.store') }}" class="max-w-[560px] flex-[1_1_460px]" novalidate>
            @csrf

            <label for="lien" class="mb-2 block font-mono text-xs uppercase text-texte-discret">{{ __('accueil.hero.champ') }}</label>

            <div class="flex flex-wrap rounded-champ border border-filet-contour bg-panneau p-[5px] focus-within:border-accent">
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
                    class="min-h-[46px] min-w-0 flex-[1_1_240px] border-0 bg-transparent px-3.5 font-mono text-sm text-texte outline-none"
                >
                <x-bouton variante="clair" type="submit" class="min-h-[46px] shrink-0">{{ __('accueil.hero.bouton') }} →</x-bouton>
            </div>

            @if ($erreur)
                <x-message-erreur id="lien-erreur" class="mt-3">{{ $erreur }}</x-message-erreur>
            @endif

            <p id="lien-depot" class="mt-2.5 text-[13px] text-texte-discret">
                {!! __('accueil.hero.depot', [
                    'lien' => '<a href="'.e($destination).'" class="text-texte-secondaire hover:text-accent">'.e(__('accueil.hero.depot_lien')).'</a>',
                    'formats' => $formats,
                ]) !!}
            </p>
        </form>
    </div>

    <x-accueil.apercu />
</section>
