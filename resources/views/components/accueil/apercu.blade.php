{{-- Aperçu du résultat (handoff, écran 1) : titre de la vidéo et badge « Terminé », onde de 48 barres, quatre
     lignes horodatées dont la troisième est en cours de lecture, pastilles des formats. Décoratif : le texte
     reste lisible sans animation. Les hauteurs de barres sont calculées ici, comme dans la maquette. --}}
@php
    $barres = collect(range(0, 47))->map(fn (int $i) => [
        'hauteur' => (int) round(22 + 78 * abs(sin($i * 1.7 + 0.6) * cos($i * 0.45))),
        'delai' => -(($i % 16) / 10),
        'accent' => $i < 18,
    ]);
    $lignes = collect(__('accueil.apercu.lignes'));
@endphp

<div class="mx-auto mt-12 w-full max-w-[920px] overflow-hidden rounded-panneau border border-filet bg-panneau text-left shadow-panneau">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-filet px-5 py-3.5">
        <p class="min-w-0 truncate text-petit font-medium text-texte">{{ __('accueil.apercu.fenetre') }}</p>
        <x-badge-statut statut="termine" />
    </div>

    <div class="grid gap-6 p-5 md:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)]">
        <div class="flex flex-col gap-3">
            <div class="flex h-[72px] items-center gap-[2px]" aria-label="{{ __('accueil.apercu.onde') }}" role="img">
                @foreach ($barres as $barre)
                    <span class="vx-onde-barre min-w-0 flex-1 rounded-full {{ $barre['accent'] ? 'bg-accent' : 'bg-onde-repos' }}"
                        style="height: {{ $barre['hauteur'] }}%; animation-delay: {{ $barre['delai'] }}s"></span>
                @endforeach
            </div>
            <p class="font-mono text-donnee text-texte-secondaire tabular-nums">{{ __('accueil.apercu.temps') }}</p>
        </div>

        <ol class="m-0 flex list-none flex-col gap-1.5 p-0 text-petit">
            @foreach ($lignes as $indice => $ligne)
                @php $enCours = $indice === 2; @endphp
                <li class="flex gap-3 rounded-champ px-2.5 py-2 {{ $enCours ? 'bg-surlignage text-sur-surlignage' : 'text-texte-secondaire' }}">
                    <span class="shrink-0 font-mono text-donnee tabular-nums {{ $enCours ? 'text-surlignage-temps' : 'text-texte-discret' }}">{{ $ligne['temps'] }}</span>
                    <span>{{ $ligne['texte'] }}</span>
                </li>
            @endforeach
        </ol>
    </div>

    <ul class="m-0 flex list-none flex-wrap gap-2 border-t border-filet bg-panneau-eleve px-5 py-3.5" aria-label="{{ __('accueil.apercu.fenetre') }}">
        @foreach (__('accueil.apercu.formats') as $format)
            <li class="rounded-pilule border border-filet-contour px-2.5 py-0.5 font-mono text-donnee text-texte-secondaire">{{ $format }}</li>
        @endforeach
    </ul>
</div>
