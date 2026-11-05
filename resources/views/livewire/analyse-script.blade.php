{{-- Analyse de la structure d'un script (cahier F7), reprise de la carte « Marketeurs » de la maquette :
     barre proportionnelle à la durée de chaque partie, puis le texte de chaque partie avec ses horodatages.
     Un clic sur une partie envoie « voxia-aller » ; le lecteur (lecture-transcription) lance la lecture à son début.
     La barre est décorative (aria-hidden) : la liste en dessous porte l'information pour les lecteurs d'écran. --}}
@php
    $couleurs = ['var(--vx-accent)', 'var(--vx-line-strong)', 'var(--vx-wave-idle)', 'var(--vx-line)', 'var(--vx-text)'];
    $hachures = 'repeating-linear-gradient(135deg, var(--vx-line) 0 6px, var(--vx-surface) 6px 12px)';
    $detectees = collect($parties)->where('detectee', true)->count();
@endphp

<section aria-labelledby="titre-analyse" class="mt-10 rounded-panneau border border-filet bg-panneau p-5"
    @if ($enCours) wire:poll.2s @endif>

    <div class="flex flex-wrap items-start justify-between gap-6">
        <div class="min-w-0">
            <h2 id="titre-analyse" class="text-lg font-semibold">{{ __('analyse.titre') }}</h2>
            <p class="mt-1 text-sm text-texte-secondaire">{{ __('analyse.sous_titre') }}</p>
        </div>

        @unless ($termine)
            <div class="flex flex-col items-start gap-2 sm:items-end">
                <button type="button" wire:click="analyser" @disabled($bloque || $enCours) aria-describedby="aide-analyse"
                    class="min-h-10 cursor-pointer rounded-bouton bg-accent px-5 text-[14px] font-semibold text-sur-accent hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50">
                    {{ __('analyse.bouton') }}
                </button>
                <p id="aide-analyse" class="max-w-xs font-mono text-[11px] text-texte-discret sm:text-right">
                    @if ($motifRefus)
                        {{ __('analyse.refus.'.$motifRefus, ['minutes' => $minutes]) }}
                    @else
                        {{ __('analyse.credit') }}
                    @endif
                </p>
            </div>
        @endunless
    </div>

    @if ($statut !== null)
        <p role="status" aria-live="polite" class="mt-4 flex items-center gap-3 text-sm text-texte-secondaire">
            @if ($enCours)
                <span aria-hidden="true" class="vx-pulsation size-2 rounded-full bg-accent"></span>
            @endif
            {{ __('analyse.statut.'.$statut->value) }}
        </p>
    @endif

    @if ($termine)
        {{-- Barre proportionnelle : une partie détectée pèse sa durée, une partie absente un poids fixe, hachuré. --}}
        <div class="mt-8 flex h-10 gap-[3px]" aria-hidden="true">
            @foreach ($parties as $partie)
                @if ($partie['detectee'])
                    <button type="button" tabindex="-1" x-on:click="$dispatch('voxia-aller', { ms: {{ $partie['debut'] }} })"
                        class="cursor-pointer rounded-[3px] hover:opacity-80"
                        style="flex: {{ $partie['poids'] }} 1 0; background: {{ $couleurs[$loop->index] }};"></button>
                @else
                    <span class="rounded-[3px]" style="flex: {{ $partie['poids'] }} 1 0; background-image: {{ $hachures }};"></span>
                @endif
            @endforeach
        </div>
        <div class="mt-2 flex gap-[3px] font-mono text-[11px] text-texte-discret" aria-hidden="true">
            @foreach ($parties as $partie)
                <span class="min-w-0 truncate" style="flex: {{ $partie['poids'] }} 1 0;">{{ __('analyse.types.'.$partie['type']->value) }}</span>
            @endforeach
        </div>

        {{-- Texte de chaque partie, dans l'ordre de la vidéo. Une partie détectée est un bouton qui lance la lecture. --}}
        <ol class="mt-8 flex flex-col gap-3">
            @foreach ($parties as $partie)
                <li>
                    @if ($partie['detectee'])
                        <button type="button" x-on:click="$dispatch('voxia-aller', { ms: {{ $partie['debut'] }} })"
                            class="block w-full cursor-pointer rounded-champ border border-filet bg-fond px-4 py-3 text-left hover:border-accent">
                            <span class="block font-mono text-[11px] text-accent-texte">
                                {{ __('analyse.types.'.$partie['type']->value) }} · {{ \App\Support\Horodatage::format($partie['debut']) }} → {{ \App\Support\Horodatage::format($partie['fin']) }}
                            </span>
                            <span class="mt-1.5 block font-titre text-[19px] font-medium leading-snug text-texte [overflow-wrap:anywhere]">« {{ $partie['texte'] }} »</span>
                        </button>
                    @else
                        <div class="rounded-champ border border-dashed border-filet-contour px-4 py-3" style="background-image: {{ $hachures }};">
                            <span class="block font-mono text-[11px] text-texte-discret">
                                {{ __('analyse.types.'.$partie['type']->value) }} · {{ __('analyse.non_detectee') }}
                            </span>
                        </div>
                    @endif
                </li>
            @endforeach
        </ol>

        @if ($detectees === 0)
            <p class="mt-4 text-sm text-texte-secondaire">{{ __('analyse.aucune_partie') }}</p>
        @endif

        <footer class="mt-8 border-t border-filet pt-5">
            <p class="font-mono text-xs text-texte-secondaire">{{ __('analyse.exporter') }}</p>
            <ul class="mt-3 flex flex-wrap items-start gap-3">
                @foreach ($formats as $format)
                    <li class="flex flex-col items-start">
                        @if ($peutExporter($format))
                            <a href="{{ $urlExport($format) }}" download
                                class="inline-flex min-h-10 items-center rounded-bouton border border-filet-contour px-4 font-mono text-xs text-texte hover:border-accent hover:text-accent-texte">
                                {{ $format->libelle() }}
                            </a>
                        @else
                            <button type="button" aria-haspopup="dialog" aria-describedby="verrou-analyse-{{ $format->value }}"
                                x-on:click="$dispatch('voxia-ouvrir', { id: 'upgrade-analyse-export-{{ $format->value }}' })"
                                class="min-h-10 cursor-pointer rounded-bouton border border-filet px-4 font-mono text-xs text-texte-discret hover:border-accent hover:text-accent-texte">
                                {{ $format->libelle() }}
                            </button>
                            <p id="verrou-analyse-{{ $format->value }}" class="mt-1 font-mono text-[11px] text-texte-discret">
                                {{ __('transcriptions.lecture.exporter_verrouille', ['plan' => $libellePlanDebloquant($format)]) }}
                            </p>

                            <x-upgrade.fenetre id="upgrade-analyse-export-{{ $format->value }}" :titre="__('upgrade.export.titre', ['format' => $format->libelle()])"
                                :retour="$retour" :plans="$plansAvec($format)">
                                <p class="text-texte">{{ __('upgrade.export.texte', ['format' => $format->libelle(), 'plan' => $libellePlanDebloquant($format)]) }}</p>
                            </x-upgrade.fenetre>
                        @endif
                    </li>
                @endforeach
            </ul>
        </footer>
    @endif

    {{-- Déclencheur « analyse de script » (cahier §2) : ouvert par proposerUpgradeAnalyse, seulement sans crédits IA. --}}
    <x-upgrade.fenetre id="upgrade-analyse" :titre="__('upgrade.analyse.titre')" :retour="$retour" :plans="['pro']">
        <p class="text-texte">{{ __('upgrade.analyse.texte') }}</p>
    </x-upgrade.fenetre>
</section>
