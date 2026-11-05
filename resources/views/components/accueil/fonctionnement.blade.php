{{-- Fonctionnement en trois étapes (handoff, écran 1). Un rail relie les cercles numérotés ; il est masqué sous
     560 px (data-connecteur). Les illustrations sont décoratives. Le remplissage du rail est coupé sous
     prefers-reduced-motion (règle globale des jetons). --}}
<section id="fonctionnement" class="scroll-mt-24 border-t border-filet bg-fond py-16" aria-labelledby="titre-fonctionnement">
    <div class="mx-auto w-full max-w-[1120px] px-4">
    <div class="mb-10 flex flex-wrap items-end justify-between gap-4">
        <h2 id="titre-fonctionnement" class="titre-h2">
            {{ __('accueil.fonctionnement.titre') }}
            <span class="text-accent-texte">{{ __('accueil.fonctionnement.titre_accent') }}</span>
        </h2>
        <p class="max-w-[30em] text-[15px] text-texte-secondaire">{{ __('accueil.fonctionnement.sous_titre') }}</p>
    </div>

    <ol class="m-0 grid list-none grid-cols-[repeat(auto-fit,minmax(min(100%,250px),1fr))] gap-y-8 p-0">
        @foreach (__('accueil.fonctionnement.etapes') as $indice => $etape)
            @php $pleine = $indice === 2; @endphp
            <li class="relative flex min-w-0 flex-col">
                {{-- Rangée du cercle : rail sur toute la largeur, cercle centré --}}
                <div class="relative flex h-10 items-center justify-center">
                    @if ($indice < 2)
                        <span data-connecteur aria-hidden="true" class="absolute top-1/2 right-0 left-1/2 h-0.5 -translate-y-1/2 overflow-hidden bg-filet">
                            <span class="vx-remplissage block h-full origin-left bg-accent" style="animation: vx-line 4.8s linear {{ $indice * 0.8 }}s backwards infinite"></span>
                        </span>
                    @endif
                    <span class="relative z-10 flex size-10 items-center justify-center rounded-full font-mono text-[14px] font-medium shadow-[0_0_0_6px_var(--vx-bg)] {{ $pleine ? 'bg-accent text-sur-accent' : 'border border-filet-contour bg-panneau text-accent-texte' }}">
                        {{ $indice + 1 }}
                    </span>
                </div>

                <div class="mt-5 px-3">
                    <h3 class="text-[19px] leading-[1.25] font-semibold tracking-[-0.02em]">{{ $etape['titre'] }}</h3>
                    <p class="mt-2 text-petit text-texte-secondaire">{{ $etape['texte'] }}</p>

                    {{-- Mini-illustration décorative --}}
                    <div aria-hidden="true" class="mt-4 rounded-[14px] border border-filet bg-panneau p-3">
                        @if ($indice === 0)
                            <div class="flex items-center gap-2 rounded-champ border border-filet-contour bg-fond px-3 py-2 font-mono text-donnee text-texte-discret">
                                <x-icone nom="lien" class="size-3.5" />{{ __('accueil.fonctionnement.illustrations.lien') }}
                            </div>
                            <p class="my-2 text-center text-donnee text-texte-discret">{{ __('accueil.fonctionnement.illustrations.ou') }}</p>
                            <div class="flex items-center justify-center gap-2 rounded-champ border border-dashed border-filet-contour px-3 py-3 font-mono text-donnee text-texte-secondaire">
                                <x-icone nom="envoi" class="size-3.5" />{{ __('accueil.fonctionnement.illustrations.fichier') }}
                            </div>
                        @elseif ($indice === 1)
                            <div class="flex items-center justify-between gap-2">
                                <x-badge-statut statut="transcription" />
                                <span class="font-mono text-donnee text-texte-discret">{{ __('accueil.fonctionnement.illustrations.auto') }}</span>
                            </div>
                            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-panneau-eleve">
                                <span class="vx-indeterminee block h-full w-2/5 rounded-full bg-direct"></span>
                            </div>
                            <div class="mt-3 flex flex-col gap-1.5">
                                <span class="block h-2 w-4/5 rounded bg-filet"></span>
                                <span class="block h-2 w-3/5 rounded bg-filet"></span>
                            </div>
                        @else
                            <div class="flex flex-wrap gap-1.5">
                                @foreach (__('accueil.apercu.formats') as $format)
                                    <span class="rounded-pilule border border-filet-contour px-2.5 py-0.5 font-mono text-donnee text-texte-secondaire">{{ $format }}</span>
                                @endforeach
                            </div>
                            <span class="mt-3 inline-flex min-h-9 items-center rounded-bouton bg-accent px-3 font-mono text-donnee text-sur-accent">{{ __('accueil.fonctionnement.illustrations.copier') }}</span>
                        @endif
                    </div>
                </div>
            </li>
        @endforeach
    </ol>
    </div>
</section>
