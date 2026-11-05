{{-- Résumé d'une transcription (V2) : résumé, points clés et chapitres. Même structure que l'analyse de script.
     Un clic sur un point ou un chapitre envoie « voxia-aller » ; le lecteur lance la lecture à cet instant.
     Le coût est affiché avant le lancement ; régénérer demande une confirmation. --}}
<section aria-labelledby="titre-resume" class="rounded-panneau border border-filet bg-panneau p-5"
    @if ($enCours) wire:poll.2s @endif>

    <div class="flex flex-wrap items-start justify-between gap-x-6 gap-y-4">
        <div class="grid min-w-0 flex-[1_1_280px] gap-1">
            <h2 id="titre-resume" class="font-titre text-[17px] font-semibold tracking-[-0.02em]">{{ __('resume.titre') }}</h2>
            <p class="m-0 text-sm text-texte-secondaire">{{ __('resume.sous_titre') }}</p>
        </div>

        @if (! $termine && $peutLancer)
            <div class="grid max-w-[320px] flex-[0_1_320px] justify-items-start gap-2">
                <label for="langue-resume" class="font-mono text-[11px] text-texte-discret">{{ __('resume.langue') }}</label>
                <select id="langue-resume" wire:model="langue" @disabled($enCours)
                    class="min-h-11 w-full rounded-champ border border-filet-contour bg-fond px-3 text-sm text-texte">
                    @foreach ($langues as $code)
                        <option value="{{ $code }}">{{ __('resume.langues.'.$code) }}</option>
                    @endforeach
                </select>
                <button type="button" wire:click="lancer" @disabled($enCours)
                    aria-describedby="aide-resume"
                    class="min-h-11 cursor-pointer rounded-bouton border border-filet-contour px-[18px] text-sm font-semibold whitespace-nowrap text-texte hover:border-accent hover:text-accent-texte disabled:cursor-not-allowed disabled:opacity-50">
                    {{ __('resume.bouton') }}
                </button>
                <p id="aide-resume" class="m-0 font-mono text-[11px] leading-normal text-texte-discret">
                    @if ($motifRefus)
                        {{ __('resume.refus.'.$motifRefus) }}
                    @else
                        {{ __('resume.cout', ['credits' => $cout]) }}
                    @endif
                </p>
            </div>
        @endif
    </div>

    @unless ($peutLancer || $termine)
        <p class="mt-4 text-sm text-texte-secondaire">{{ __('resume.plan_sans_acces') }}</p>
    @endunless

    @if ($statut !== null)
        <p role="status" aria-live="polite" class="mt-4 flex items-center gap-3 text-sm text-texte-secondaire">
            @if ($enCours)
                <span aria-hidden="true" class="vx-pulsation size-2 rounded-full bg-accent"></span>
            @endif
            {{ __('resume.statut.'.$statut->value) }}
            @if ($statut === \App\Enums\StatutSortieIa::Echec && $contenu !== null)
                <span class="font-mono text-[11px] text-texte-discret">{{ __('resume.echec_contenu') }}</span>
            @endif
        </p>
    @endif

    @if ($contenu !== null)
        <div class="mt-6 grid gap-6">
            <div>
                <h3 class="font-titre text-[15px] font-semibold text-texte">{{ __('resume.resume') }}</h3>
                <p class="mt-2 text-[15px] leading-relaxed text-texte-secondaire [overflow-wrap:anywhere]">{{ $contenu['resume'] }}</p>
            </div>

            @if ($contenu['points'] !== [])
                <div>
                    <h3 class="font-titre text-[15px] font-semibold text-texte">{{ __('resume.points') }}</h3>
                    <p class="mt-1 font-mono text-[11px] text-texte-discret">{{ __('resume.aide_horodatages') }}</p>
                    <ul class="mt-3 flex flex-col gap-2">
                        @foreach ($contenu['points'] as $point)
                            <li>
                                <button type="button" x-on:click="$dispatch('voxia-aller', { ms: {{ $point['debut_ms'] }} })"
                                    class="flex w-full cursor-pointer items-start gap-3 rounded-champ border border-filet bg-fond px-4 py-3 text-left hover:border-accent">
                                    <span class="shrink-0 font-mono text-[12px] text-accent-texte">[{{ \App\Support\Horodatage::format($point['debut_ms']) }}]</span>
                                    <span class="min-w-0 text-[15px] leading-snug text-texte [overflow-wrap:anywhere]">{{ $point['texte'] }}</span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($contenu['chapitres'] !== [])
                <div>
                    <h3 class="font-titre text-[15px] font-semibold text-texte">{{ __('resume.chapitres') }}</h3>
                    <ol class="mt-3 flex flex-col gap-2">
                        @foreach ($contenu['chapitres'] as $chapitre)
                            <li>
                                <button type="button" x-on:click="$dispatch('voxia-aller', { ms: {{ $chapitre['debut_ms'] }} })"
                                    class="block w-full cursor-pointer rounded-champ border border-filet bg-fond px-4 py-3 text-left hover:border-accent">
                                    <span class="block font-mono text-[12px] text-accent-texte">[{{ \App\Support\Horodatage::format($chapitre['debut_ms']) }}]</span>
                                    <span class="mt-1 block font-titre text-[16px] font-medium text-texte [overflow-wrap:anywhere]">{{ $chapitre['titre'] }}</span>
                                    <span class="mt-1 block text-sm text-texte-secondaire [overflow-wrap:anywhere]">{{ $chapitre['resume'] }}</span>
                                </button>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif
        </div>

        {{-- Copie et exports. Un format hors plan est affiché verrouillé, avec le plan qui le débloque. --}}
        <div class="mt-6 flex flex-wrap items-center gap-2" x-data="{ copie: false }">
            <button type="button" x-on:click="navigator.clipboard.writeText(@js($texteCopie)); copie = true"
                class="min-h-11 cursor-pointer rounded-bouton border border-filet-contour px-4 text-sm font-semibold text-texte hover:border-accent">
                <span x-show="! copie">{{ __('resume.copier') }}</span>
                <span x-show="copie" x-cloak>{{ __('resume.copie') }}</span>
            </button>

            @foreach ($formats as $format)
                @if ($peutExporter($format))
                    <a href="{{ $urlExport($format) }}"
                        class="inline-flex min-h-11 items-center rounded-bouton border border-filet-contour px-4 text-sm font-semibold text-texte hover:border-accent">
                        {{ __('resume.exporter') }} {{ $format->libelle() }}
                    </a>
                @else
                    <span class="inline-flex min-h-11 items-center gap-2 rounded-bouton border border-dashed border-filet-contour px-4 font-mono text-[12px] text-texte-discret"
                        title="{{ __('resume.verrou', ['plan' => $libellePlanDebloquant($format)]) }}">
                        {{ $format->libelle() }} · {{ $libellePlanDebloquant($format) }}
                    </span>
                @endif
            @endforeach
        </div>
    @endif

    {{-- Régénérer : coûte de nouveaux crédits, donc confirmation explicite. --}}
    @if ($termine)
        <div class="mt-6 border-t border-filet pt-4">
            @if ($confirmerRegeneration)
                <div role="alertdialog" aria-labelledby="titre-confirmation-resume" aria-describedby="texte-confirmation-resume"
                    class="grid gap-3">
                    <p id="titre-confirmation-resume" class="m-0 font-titre text-[15px] font-semibold text-texte">{{ __('resume.regenerer') }}</p>
                    <p id="texte-confirmation-resume" class="m-0 text-sm text-texte-secondaire">{{ __('resume.confirmer_regeneration', ['credits' => $cout]) }}</p>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" wire:click="regenerer"
                            class="min-h-11 cursor-pointer rounded-bouton bg-accent px-4 text-sm font-semibold text-sur-accent">{{ __('resume.confirmer') }}</button>
                        <button type="button" wire:click="annulerRegeneration"
                            class="min-h-11 cursor-pointer rounded-bouton border border-filet-contour px-4 text-sm font-semibold text-texte">{{ __('resume.annuler') }}</button>
                    </div>
                </div>
            @else
                <button type="button" wire:click="demanderRegeneration" @disabled($enCours)
                    class="min-h-11 cursor-pointer rounded-bouton border border-filet-contour px-4 text-sm font-semibold text-texte hover:border-accent disabled:cursor-not-allowed disabled:opacity-50">
                    {{ __('resume.regenerer') }}
                </button>
            @endif
        </div>
    @endif
</section>
