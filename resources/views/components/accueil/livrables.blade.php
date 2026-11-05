{{-- « Ce que tu récupères » (handoff, écran 1) : trois cartes, les icônes de fichier en haut, puis titre et texte.
     Couleur du bandeau selon le format : TXT texte secondaire, PDF erreur, SRT et VTT cyan « en direct », MP3 accent. --}}
@php
    $bandeaux = [
        'TXT' => 'fill-texte-secondaire',
        'PDF' => 'fill-erreur',
        'SRT' => 'fill-direct',
        'VTT' => 'fill-direct',
        'MP3' => 'fill-accent',
    ];
@endphp

<section id="livrables" class="scroll-mt-24 border-t border-filet bg-panneau py-16" aria-labelledby="titre-livrables">
    <div class="mx-auto w-full max-w-[1120px] px-4">
    <div class="mb-10">
        <h2 id="titre-livrables" class="titre-h2">{{ __('accueil.livrables.titre') }}</h2>
        <p class="mt-3 max-w-[34em] text-texte-secondaire">{{ __('accueil.livrables.sous_titre') }}</p>
    </div>

    <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,250px),1fr))] gap-4">
        @foreach (__('accueil.livrables.cartes') as $carte)
            <article class="flex min-w-0 flex-col gap-3.5 rounded-panneau border border-filet bg-fond p-5">
                <div class="flex flex-wrap gap-2">
                    @foreach ($carte['formats'] as $format)
                        <span class="inline-flex items-center gap-1.5 rounded-[10px] border border-filet-contour py-1 pr-2.5 pl-1.5 font-mono text-donnee text-texte-secondaire">
                            <svg width="22" height="26" viewBox="0 0 22 26" aria-hidden="true" focusable="false">
                                <path d="M1 1h14l6 6v18H1z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
                                <path d="M15 1v6h6" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
                                <rect x="4" y="13" width="14" height="6" rx="1" class="{{ $bandeaux[$format] ?? 'fill-texte-secondaire' }}"/>
                                <text x="11" y="18" text-anchor="middle" font-family="JetBrains Mono, monospace" font-size="5" font-weight="700" fill="var(--vx-bg)">{{ $format }}</text>
                            </svg>
                            .{{ strtolower($format) }}
                        </span>
                    @endforeach
                </div>

                <h3 class="text-[19px] leading-[1.25] font-semibold tracking-[-0.02em]">{{ $carte['titre'] }}</h3>
                <p class="text-petit text-texte-secondaire">{{ $carte['texte'] }}</p>
            </article>
        @endforeach
    </div>
    </div>
</section>
