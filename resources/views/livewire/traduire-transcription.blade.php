{{-- Traduction d'une transcription (V2). Coût affiché avant lancement ; sélecteur de langue ; vue côte à côte
     (sur mobile, chaque segment s'empile : original puis traduction). Le segment en cours de lecture est signalé
     par le lecteur (événement « voxia-temps ») et surligné des deux côtés. --}}
<section aria-labelledby="titre-traduction" class="rounded-panneau border border-filet bg-panneau p-5"
    @if ($enCours) wire:poll.2s @endif>

    <div class="grid gap-1">
        <h2 id="titre-traduction" class="font-titre text-[17px] font-semibold tracking-[-0.02em]">{{ __('traduction.titre') }}</h2>
        <p class="m-0 text-sm text-texte-secondaire">{{ __('traduction.sous_titre') }}</p>
    </div>

    @if ($peutLancer)
        <div class="mt-5 grid gap-5 md:grid-cols-[1fr_280px]">
            <fieldset class="m-0 grid min-w-0 gap-2 border-0 p-0">
                <legend class="mb-2 font-mono text-[11px] text-texte-discret">{{ __('traduction.langues_legende') }}</legend>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($languesCibles as $code)
                        <label class="flex min-h-11 cursor-pointer items-center gap-3 rounded-champ border border-filet bg-fond px-4 text-sm text-texte has-[:checked]:border-accent">
                            <input type="checkbox" wire:model="langues" value="{{ $code }}" class="size-4 accent-accent">
                            <span>{{ $libelleLangue($code) }}</span>
                            @if (($sorties[$code] ?? null)?->statut === \App\Enums\StatutSortieIa::Termine)
                                <span class="ms-auto font-mono text-[11px] text-texte-discret">{{ __('traduction.statut.termine') }}</span>
                            @endif
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div class="grid content-start gap-3">
                <button type="button" wire:click="lancer" @disabled($langues === [])
                    aria-describedby="aide-traduction"
                    class="min-h-11 cursor-pointer rounded-bouton border border-filet-contour px-[18px] text-sm font-semibold whitespace-nowrap text-texte hover:border-accent hover:text-accent-texte disabled:cursor-not-allowed disabled:opacity-50">
                    {{ __('traduction.bouton') }}
                </button>
                <p id="aide-traduction" class="m-0 font-mono text-[11px] leading-normal text-texte-discret">
                    @if ($motifRefus)
                        {{ __('traduction.refus.'.$motifRefus, ['credits' => $cout]) }}
                    @elseif ($langues === [])
                        {{ __('traduction.aucune_langue') }}
                    @else
                        {{ __('traduction.cout', ['credits' => $cout]) }}
                    @endif
                </p>
            </div>
        </div>
    @else
        <p class="mt-4 text-sm text-texte-secondaire">{{ __('traduction.plan_sans_acces') }}</p>
    @endif

    @if ($sorties->isNotEmpty())
        <ul class="m-0 mt-4 grid list-none gap-1.5 p-0">
            @foreach ($sorties as $code => $sortie)
                <li class="flex flex-wrap items-baseline gap-x-3 font-mono text-xs text-texte-secondaire">
                    <span class="text-texte">{{ $libelleLangue($code) }}</span>
                    <span>{{ __('traduction.statut.'.$sortie->statut->value) }}</span>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($terminees !== [])
        <div class="mt-6 grid gap-3 border-t border-filet pt-4">
            <div role="group" aria-label="{{ __('traduction.selecteur') }}" class="flex flex-wrap gap-2">
                <button type="button" wire:click="$set('onglet', 'original')" aria-pressed="{{ $ongletActif === 'original' ? 'true' : 'false' }}"
                    class="min-h-9 cursor-pointer rounded-pilule border px-3.5 text-sm {{ $ongletActif === 'original' ? 'border-accent text-texte' : 'border-filet text-texte-secondaire' }}">
                    {{ __('traduction.original') }}
                </button>
                @foreach ($terminees as $code => $sortie)
                    <button type="button" wire:click="$set('onglet', '{{ $code }}')" aria-pressed="{{ $ongletActif === $code ? 'true' : 'false' }}" lang="{{ $code }}"
                        class="min-h-9 cursor-pointer rounded-pilule border px-3.5 text-sm {{ $ongletActif === $code ? 'border-accent text-texte' : 'border-filet text-texte-secondaire' }}">
                        {{ $libelleLangue($code) }}
                    </button>
                @endforeach
            </div>

            @if ($sortieActive)
                @if (($sortieActive->contenu['avertissements'] ?? 0) > 0)
                    <p class="m-0 rounded-champ border border-filet-contour bg-fond px-3 py-2 text-sm text-texte-secondaire" role="status">
                        {{ __('traduction.avertissements', ['nombre' => $sortieActive->contenu['avertissements']]) }}
                    </p>
                @endif

                {{-- Vue côte à côte à partir de md ; empilée (original puis traduction) sur mobile. --}}
                <div wire:key="vue-{{ $ongletActif }}"
                    x-data="vueTraduction(@js(['debuts' => array_column($segments, 'debut_ms'), 'fins' => array_column($segments, 'fin_ms')]))"
                    x-on:voxia-temps.window="surveiller($event.detail.ms)">
                    <div class="hidden gap-x-6 pb-2 font-mono text-[11px] text-texte-discret md:grid md:grid-cols-2" aria-hidden="true">
                        <span>{{ __('traduction.col_original') }} · {{ $libelleLangue($source) }}</span>
                        <span>{{ __('traduction.col_traduction') }} · {{ $libelleLangue($ongletActif) }}</span>
                    </div>

                    <ol class="m-0 grid list-none gap-1.5 p-0" aria-label="{{ __('traduction.titre') }}">
                        @foreach ($segments as $index => $segment)
                            <li class="grid gap-x-6 gap-y-1 rounded-champ px-3 py-2.5 transition-colors duration-300 motion-reduce:transition-none md:grid-cols-2 data-[actif=true]:bg-surlignage"
                                :data-actif="actif === {{ $index }} ? 'true' : 'false'">
                                <div lang="{{ $source }}" class="grid min-w-0 content-start gap-1">
                                    <span class="font-mono text-[11px] text-texte-discret md:hidden">{{ __('traduction.col_original') }}</span>
                                    <button type="button" x-on:click="$dispatch('voxia-aller', { ms: {{ $segment['debut_ms'] }} })"
                                        aria-label="{{ __('traduction.lire_a', ['temps' => \App\Support\Horodatage::format($segment['debut_ms'])]) }}"
                                        class="w-fit cursor-pointer border-0 bg-transparent p-0 font-mono text-[12px] tabular-nums text-texte-discret hover:text-accent-texte">
                                        {{ \App\Support\Horodatage::format($segment['debut_ms']) }}
                                    </button>
                                    <p class="m-0 text-[15px] text-texte-secondaire [overflow-wrap:anywhere]">{{ $segment['texte_original'] }}</p>
                                </div>
                                <div lang="{{ $ongletActif }}" class="grid min-w-0 content-start gap-1">
                                    <span class="font-mono text-[11px] text-texte-discret md:hidden">{{ __('traduction.col_traduction') }}</span>
                                    @if ($segment['trop_long'])
                                        <span class="font-mono text-[11px] text-texte-discret">{{ __('traduction.trop_long') }}</span>
                                    @endif
                                    <p class="m-0 text-[15px] text-texte [overflow-wrap:anywhere]">{{ $segment['texte'] }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>

                <div class="grid gap-2">
                    <p class="m-0 font-mono text-[11px] text-texte-discret">{{ __('traduction.exports.titre') }}</p>
                    <ul class="m-0 flex list-none flex-wrap gap-2 p-0">
                        @foreach ($exports as $format)
                            <li>
                                <a href="{{ $urlExport($ongletActif, $format) }}"
                                    class="inline-flex min-h-9 items-center rounded-pilule border border-filet-contour px-3 font-mono text-[13px] text-texte no-underline hover:border-accent">
                                    {{ __('traduction.exports.formats.'.$format->value) }}
                                </a>
                            </li>
                            @if ($avecHorodatages($format))
                                <li>
                                    <a href="{{ $urlExport($ongletActif, $format, false) }}"
                                        class="inline-flex min-h-9 items-center rounded-pilule border border-filet px-3 font-mono text-[13px] text-texte-secondaire no-underline hover:border-accent">
                                        {{ __('traduction.exports.formats.'.$format->value) }} · {{ __('traduction.exports.sans_horodatages') }}
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            @elseif ($originaux !== [])
                <ol class="m-0 grid list-none gap-1.5 p-0" aria-label="{{ __('traduction.original') }}">
                    @foreach ($originaux as $segment)
                        <li class="grid gap-1 rounded-champ px-3 py-2.5">
                            <span class="font-mono text-[12px] tabular-nums text-texte-discret">{{ \App\Support\Horodatage::format($segment['debut_ms']) }}</span>
                            <p class="m-0 text-[15px] text-texte-secondaire [overflow-wrap:anywhere]">{{ $segment['texte_original'] }}</p>
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>
    @endif
</section>
