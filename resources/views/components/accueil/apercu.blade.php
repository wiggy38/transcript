{{-- Aperçu produit (maquette Signal) : onde audio, transcription horodatée, phrase en cours surlignée, export.
     Les barres sont relevées de docs/maquette-signal.html : [hauteur %, décalage d'animation s, accent ?]. --}}
@php
    $barres = [
        [18, 0.00, true],
        [71, 0.11, true],
        [61, 0.22, true],
        [54, 0.33, true],
        [85, 0.44, true],
        [77, 0.55, true],
        [20, 0.66, true],
        [33, 0.77, true],
        [28, 0.88, true],
        [18, 0.99, true],
        [38, 1.10, true],
        [40, 1.21, true],
        [94, 1.32, true],
        [86, 1.43, true],
        [74, 1.54, true],
        [100, 0.05, true],
        [98, 0.16, true],
        [37, 0.27, true],
        [30, 0.38, true],
        [21, 0.49, true],
        [31, 0.60, true],
        [64, 0.71, true],
        [35, 0.82, true],
        [52, 0.93, true],
        [47, 1.04, true],
        [31, 1.15, true],
        [59, 1.26, false],
        [62, 1.37, false],
        [33, 1.48, false],
        [71, 1.59, false],
        [59, 0.10, false],
        [75, 0.21, false],
        [100, 0.32, false],
        [91, 0.43, false],
        [37, 0.54, false],
        [32, 0.65, false],
        [43, 0.76, false],
        [28, 0.87, false],
        [46, 0.98, false],
        [37, 1.09, false],
        [73, 1.20, false],
        [58, 1.31, false],
        [67, 1.42, false],
        [100, 1.53, false],
        [88, 0.04, false],
        [28, 0.15, false],
        [23, 0.26, false],
        [19, 0.37, false],
        [57, 0.48, false],
        [90, 0.59, false],
        [53, 0.70, false],
        [19, 0.81, false],
        [31, 0.92, false],
        [25, 1.03, false],
        [54, 1.14, false],
        [48, 1.25, false],
        [43, 1.36, false],
        [60, 1.47, false],
        [51, 1.58, false],
        [87, 0.09, false],
        [100, 0.20, false],
        [92, 0.31, false],
        [50, 0.42, false],
        [56, 0.53, false],
    ];
@endphp

<div class="relative mt-[72px] rounded-t-panneau border border-b-0 border-filet bg-panneau">
    <span aria-hidden="true" class="absolute -top-px -left-px size-3.5 rounded-tl-panneau border-t-2 border-l-2 border-accent"></span>
    <span aria-hidden="true" class="absolute -top-px -right-px size-3.5 rounded-tr-panneau border-t-2 border-r-2 border-accent"></span>

    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-filet px-5 py-3.5 font-mono text-xs text-texte-discret">
        <span class="inline-flex items-center gap-2.5">
            <span class="vx-pulsation size-[7px] rounded-full bg-accent" aria-hidden="true"></span>
            <span class="text-texte">{{ __('accueil.apercu.fenetre') }}</span>
        </span>
        <span>{{ __('accueil.apercu.source') }}</span>
    </div>

    <div class="flex flex-wrap">
        <div class="min-w-0 flex-[1_1_440px] px-7 py-8 md:border-r md:border-filet">
            <div class="relative flex h-[200px] items-center gap-px md:gap-[3px]" aria-hidden="true">
                @foreach ($barres as [$hauteur, $delai, $accent])
                    <span
                        class="vx-onde-barre min-w-0 flex-[1_1_0] rounded-sm {{ $accent ? 'bg-accent' : 'bg-[#4A4B47]' }}"
                        style="height: {{ $hauteur }}%; animation-delay: -{{ number_format($delai, 2, '.', '') }}s"
                    ></span>
                @endforeach

                <span class="absolute -top-3 -bottom-3 left-[40%] w-px bg-accent"></span>
                <span class="absolute -top-[34px] left-[40%] -translate-x-1/2 rounded-[3px] bg-accent px-2 py-0.5 font-mono text-[11px] font-medium text-fond">00:13</span>
            </div>

            <div class="mt-[18px] flex justify-between font-mono text-[11px] text-texte-discret" aria-hidden="true">
                <span>00:00</span><span>00:08</span><span>00:16</span><span>00:24</span><span>00:32</span>
            </div>
        </div>

        <div class="min-w-0 flex-[1_1_400px] px-7 py-6">
            <p class="mb-3 font-mono text-[11px] text-texte-discret">{{ __('accueil.apercu.panneau') }}</p>

            @foreach (trans('accueil.apercu.lignes') as $indice => $ligne)
                @if ($indice === 2)
                    {{-- Phrase en cours : surlignée. --}}
                    <div class="-mx-3 flex gap-4 rounded-champ bg-[#1B1C1A] px-3 py-2.5">
                        <span class="w-11 shrink-0 pt-[3px] font-mono text-xs text-accent">{{ $ligne['temps'] }}</span>
                        <p class="font-medium text-texte">{{ $ligne['texte'] }}</p>
                    </div>
                @elseif ($indice === 3)
                    {{-- Dernière phrase : frappe du texte, curseur clignotant. --}}
                    <div class="flex gap-4 py-2.5">
                        <span class="w-11 shrink-0 pt-[3px] font-mono text-xs text-texte-discret">{{ $ligne['temps'] }}</span>
                        <p class="text-texte-secondaire">
                            <span class="vx-frappe inline-block">{{ $ligne['texte'] }}</span><span class="vx-curseur ml-0.5 inline-block h-[1.1em] w-0.5 bg-accent align-[-3px]" aria-hidden="true"></span>
                        </p>
                    </div>
                @else
                    <div class="flex gap-4 py-2.5">
                        <span class="w-11 shrink-0 pt-[3px] font-mono text-xs text-texte-discret">{{ $ligne['temps'] }}</span>
                        <p class="text-texte-discret">{{ $ligne['texte'] }}</p>
                    </div>
                @endif
            @endforeach

            {{-- Boutons d'export de la maquette : décoratifs, hors du parcours clavier. --}}
            <div class="mt-[18px] flex flex-wrap gap-1.5" aria-hidden="true">
                @foreach (trans('accueil.apercu.boutons') as $bouton)
                    <button type="button" tabindex="-1" class="min-h-[34px] cursor-default rounded-bouton border border-filet-contour px-3 font-mono text-xs text-texte">{{ $bouton }}</button>
                @endforeach
            </div>
        </div>
    </div>
</div>
