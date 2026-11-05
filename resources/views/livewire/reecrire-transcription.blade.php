{{-- Réécriture d'une transcription (V2) : article, newsletter, post LinkedIn, thread X. Un onglet par format généré.
     Chaque bloc a son bouton Copier ; le coût total est affiché avant le lancement ; régénérer demande une confirmation. --}}
<section aria-labelledby="titre-reecriture" class="rounded-panneau border border-filet bg-panneau p-5"
    @if ($enCours) wire:poll.2s @endif>

    <div class="grid gap-1">
        <h2 id="titre-reecriture" class="font-titre text-[17px] font-semibold tracking-[-0.02em]">{{ __('reecriture.titre') }}</h2>
        <p class="m-0 text-sm text-texte-secondaire">{{ __('reecriture.sous_titre') }}</p>
    </div>

    @if ($peutLancer)
        <div class="mt-5 grid gap-5 md:grid-cols-[1fr_280px]">
            <fieldset class="m-0 grid min-w-0 gap-2 border-0 p-0">
                <legend class="mb-2 font-mono text-[11px] text-texte-discret">{{ __('reecriture.formats_legende') }}</legend>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($formatsDisponibles as $format)
                        <label class="flex min-h-11 cursor-pointer items-center gap-3 rounded-champ border border-filet bg-fond px-4 text-sm text-texte has-[:checked]:border-accent">
                            <input type="checkbox" wire:model="formats" value="{{ $format->value }}" class="size-4 accent-accent">
                            <span>{{ $libelleFormat($format) }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div class="grid content-start gap-3">
                <div class="grid gap-1">
                    <label for="ton-reecriture" class="font-mono text-[11px] text-texte-discret">{{ __('reecriture.ton') }}</label>
                    <select id="ton-reecriture" wire:model="ton" @disabled($enCours)
                        class="min-h-11 w-full rounded-champ border border-filet-contour bg-fond px-3 text-sm text-texte">
                        @foreach ($tons as $t)
                            <option value="{{ $t->value }}">{{ __('reecriture.tons.'.$t->value) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid gap-1">
                    <label for="longueur-reecriture" class="font-mono text-[11px] text-texte-discret">{{ __('reecriture.longueur') }}</label>
                    <select id="longueur-reecriture" wire:model="longueur" @disabled($enCours)
                        aria-describedby="aide-longueur-reecriture"
                        class="min-h-11 w-full rounded-champ border border-filet-contour bg-fond px-3 text-sm text-texte">
                        @foreach ($longueurs as $l)
                            <option value="{{ $l->value }}">{{ __('reecriture.longueurs.'.$l->value) }}</option>
                        @endforeach
                    </select>
                    <p id="aide-longueur-reecriture" class="m-0 font-mono text-[11px] text-texte-discret">{{ __('reecriture.aide_longueur') }}</p>
                </div>

                <div class="grid gap-1">
                    <label for="langue-reecriture" class="font-mono text-[11px] text-texte-discret">{{ __('reecriture.langue') }}</label>
                    <select id="langue-reecriture" wire:model="langue" @disabled($enCours)
                        class="min-h-11 w-full rounded-champ border border-filet-contour bg-fond px-3 text-sm text-texte">
                        @foreach ($langues as $code)
                            <option value="{{ $code }}">{{ __('resume.langues.'.$code) }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="button" wire:click="lancer" @disabled($enCours || $formats === [])
                    aria-describedby="aide-reecriture"
                    class="min-h-11 cursor-pointer rounded-bouton border border-filet-contour px-[18px] text-sm font-semibold whitespace-nowrap text-texte hover:border-accent hover:text-accent-texte disabled:cursor-not-allowed disabled:opacity-50">
                    {{ __('reecriture.bouton') }}
                </button>
                <p id="aide-reecriture" class="m-0 font-mono text-[11px] leading-normal text-texte-discret">
                    @if ($motifRefus)
                        {{ __('reecriture.refus.'.$motifRefus, ['credits' => $cout]) }}
                    @else
                        {{ __('reecriture.cout', ['credits' => $cout, 'unitaire' => $coutParFormat]) }}
                    @endif
                </p>
            </div>
        </div>
    @else
        <p class="mt-4 text-sm text-texte-secondaire">{{ __('reecriture.plan_sans_acces') }}</p>
    @endif

    @if ($onglets !== [])
        <div role="tablist" aria-label="{{ __('reecriture.onglets') }}" class="mt-6 flex flex-wrap gap-2 border-b border-filet pb-3">
            @foreach ($onglets as $valeur => $sortie)
                <button type="button" role="tab" id="onglet-{{ $valeur }}"
                    aria-selected="{{ $ongletActif === $valeur ? 'true' : 'false' }}" aria-controls="panneau-reecriture"
                    wire:click="$set('onglet', '{{ $valeur }}')"
                    class="min-h-11 cursor-pointer rounded-bouton border px-4 text-sm font-semibold aria-selected:border-accent aria-selected:text-accent-texte {{ $ongletActif === $valeur ? 'border-accent text-accent-texte' : 'border-filet-contour text-texte' }}">
                    {{ $libelleFormat(\App\Enums\FormatReecriture::from($valeur)) }}
                </button>
            @endforeach
        </div>

        <div id="panneau-reecriture" role="tabpanel" aria-labelledby="onglet-{{ $ongletActif }}" class="mt-5">
            @if ($sortieActive !== null)
                <p role="status" aria-live="polite" class="mb-4 flex items-center gap-3 text-sm text-texte-secondaire">
                    @if ($sortieActive->statut->enCours())
                        <span aria-hidden="true" class="vx-pulsation size-2 rounded-full bg-accent"></span>
                    @endif
                    {{ __('reecriture.statut.'.$sortieActive->statut->value) }}
                </p>
            @endif

            @if ($contenu !== null)
                @switch($ongletActif)
                    @case('article')
                        <article class="grid gap-5">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <h3 class="m-0 min-w-0 font-titre text-[22px] leading-tight font-semibold text-texte [overflow-wrap:anywhere]">{{ $contenu['titre'] }}</h3>
                                @include('livewire.partials.bouton-copie', ['texte' => $contenu['titre'], 'libelle' => __('reecriture.blocs.titre')])
                            </div>

                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <p class="m-0 min-w-0 text-[15px] leading-relaxed text-texte-secondaire [overflow-wrap:anywhere]">{{ $contenu['introduction'] }}</p>
                                @include('livewire.partials.bouton-copie', ['texte' => $contenu['introduction'], 'libelle' => __('reecriture.blocs.introduction')])
                            </div>

                            @foreach ($contenu['intertitres'] as $bloc)
                                @php $texteBloc = $bloc['titre']."\n\n".implode("\n\n", $bloc['paragraphes']); @endphp
                                <section class="grid gap-3">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <h4 class="m-0 min-w-0 font-titre text-[17px] font-semibold text-texte [overflow-wrap:anywhere]">{{ $bloc['titre'] }}</h4>
                                        @include('livewire.partials.bouton-copie', ['texte' => $texteBloc, 'libelle' => $bloc['titre']])
                                    </div>
                                    @foreach ($bloc['paragraphes'] as $paragraphe)
                                        <p class="m-0 text-[15px] leading-relaxed text-texte-secondaire [overflow-wrap:anywhere]">{{ $paragraphe }}</p>
                                    @endforeach
                                </section>
                            @endforeach

                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h4 class="m-0 font-titre text-[17px] font-semibold text-texte">{{ __('reecriture.export.conclusion') }}</h4>
                                    <p class="mt-2 text-[15px] leading-relaxed text-texte-secondaire [overflow-wrap:anywhere]">{{ $contenu['conclusion'] }}</p>
                                </div>
                                @include('livewire.partials.bouton-copie', ['texte' => $contenu['conclusion'], 'libelle' => __('reecriture.export.conclusion')])
                            </div>
                        </article>
                        @break

                    @case('newsletter')
                        <div class="grid gap-5">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="m-0 font-mono text-[11px] text-texte-discret">{{ __('reecriture.export.objet') }}</p>
                                    <p class="mt-1 font-titre text-[17px] font-semibold text-texte [overflow-wrap:anywhere]">{{ $contenu['objet'] }}</p>
                                </div>
                                @include('livewire.partials.bouton-copie', ['texte' => $contenu['objet'], 'libelle' => __('reecriture.export.objet')])
                            </div>

                            <div class="min-w-0">
                                <p class="m-0 font-mono text-[11px] text-texte-discret">{{ __('reecriture.export.apercu') }}</p>
                                <p class="mt-1 text-[15px] text-texte-secondaire [overflow-wrap:anywhere]">{{ $contenu['apercu'] }}</p>
                            </div>

                            <p class="m-0 text-[15px] leading-relaxed text-texte [overflow-wrap:anywhere]">{{ $contenu['accroche'] }}</p>

                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="grid min-w-0 gap-3">
                                    @foreach ($contenu['corps'] as $paragraphe)
                                        <p class="m-0 text-[15px] leading-relaxed text-texte-secondaire [overflow-wrap:anywhere]">{{ $paragraphe }}</p>
                                    @endforeach
                                </div>
                                @include('livewire.partials.bouton-copie', ['texte' => implode("\n\n", $contenu['corps']), 'libelle' => __('reecriture.blocs.corps')])
                            </div>

                            <p class="m-0 text-[15px] font-semibold text-texte [overflow-wrap:anywhere]">{{ $contenu['appel_action'] }}</p>
                        </div>
                        @break

                    @case('linkedin')
                        @php $post = $contenu['accroche']."\n\n".implode("\n\n", $contenu['paragraphes']); @endphp
                        <div class="grid gap-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="grid min-w-0 gap-3">
                                    <p class="m-0 font-titre text-[17px] font-semibold text-texte [overflow-wrap:anywhere]">{{ $contenu['accroche'] }}</p>
                                    @foreach ($contenu['paragraphes'] as $paragraphe)
                                        <p class="m-0 text-[15px] leading-relaxed text-texte-secondaire [overflow-wrap:anywhere]">{{ $paragraphe }}</p>
                                    @endforeach
                                </div>
                                @include('livewire.partials.bouton-copie', ['texte' => $post, 'libelle' => __('reecriture.blocs.post')])
                            </div>
                            <p class="m-0 font-mono text-[11px] text-texte-discret">
                                {{ __('reecriture.compteur_linkedin', ['caracteres' => mb_strlen($post), 'max' => config('voxia.ia.reecriture.linkedin.caracteres_max')]) }}
                            </p>
                        </div>
                        @break

                    @case('thread')
                        <ol class="m-0 grid list-none gap-3 p-0">
                            @foreach ($messagesNumerotes as $message)
                                <li class="flex flex-wrap items-start justify-between gap-3 rounded-champ border border-filet bg-fond px-4 py-3">
                                    <p class="m-0 min-w-0 text-[15px] leading-snug text-texte [overflow-wrap:anywhere]">{{ $message }}</p>
                                    @include('livewire.partials.bouton-copie', ['texte' => $message, 'libelle' => __('reecriture.blocs.message')])
                                </li>
                            @endforeach
                        </ol>
                        @break
                @endswitch

                {{-- Exports : TXT selon le plan, Markdown et DOCX réservés au Business. Un format verrouillé nomme son plan. --}}
                <div class="mt-6 flex flex-wrap items-center gap-2 border-t border-filet pt-4">
                    @foreach ($exports as $export)
                        @if ($peutExporter($export))
                            <a href="{{ $urlExport(\App\Enums\FormatReecriture::from($ongletActif), $export) }}"
                                class="inline-flex min-h-11 items-center rounded-bouton border border-filet-contour px-4 text-sm font-semibold text-texte hover:border-accent">
                                {{ __('reecriture.exporter') }} {{ $export->libelle() }}
                            </a>
                        @else
                            <span class="inline-flex min-h-11 items-center gap-2 rounded-bouton border border-dashed border-filet-contour px-4 font-mono text-[12px] text-texte-discret"
                                title="{{ __('reecriture.verrou', ['plan' => ($planDebloquant($export) ? $nomPlan($planDebloquant($export)) : '')]) }}">
                                {{ $export->libelle() }} · {{ $planDebloquant($export) ? $nomPlan($planDebloquant($export)) : '' }}
                            </span>
                        @endif
                    @endforeach
                </div>

                {{-- Régénérer : 1 crédit, confirmation explicite. --}}
                @if ($sortieActive->statut === \App\Enums\StatutSortieIa::Termine)
                    <div class="mt-5">
                        @if ($formatAConfirmer === $ongletActif)
                            <div role="alertdialog" aria-labelledby="titre-confirmation-reecriture" aria-describedby="texte-confirmation-reecriture" class="grid gap-3">
                                <p id="titre-confirmation-reecriture" class="m-0 font-titre text-[15px] font-semibold text-texte">{{ __('reecriture.regenerer') }}</p>
                                <p id="texte-confirmation-reecriture" class="m-0 text-sm text-texte-secondaire">{{ __('reecriture.confirmer_regeneration', ['credits' => $coutParFormat]) }}</p>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" wire:click="regenerer"
                                        class="min-h-11 cursor-pointer rounded-bouton bg-accent px-4 text-sm font-semibold text-sur-accent">{{ __('reecriture.confirmer') }}</button>
                                    <button type="button" wire:click="annulerRegeneration"
                                        class="min-h-11 cursor-pointer rounded-bouton border border-filet-contour px-4 text-sm font-semibold text-texte">{{ __('reecriture.annuler') }}</button>
                                </div>
                            </div>
                        @else
                            <button type="button" wire:click="demanderRegeneration('{{ $ongletActif }}')" @disabled($enCours || ! $peutLancer)
                                class="min-h-11 cursor-pointer rounded-bouton border border-filet-contour px-4 text-sm font-semibold text-texte hover:border-accent disabled:cursor-not-allowed disabled:opacity-50">
                                {{ __('reecriture.regenerer') }}
                            </button>
                        @endif
                    </div>
                @endif
            @endif
        </div>
    @endif
</section>
