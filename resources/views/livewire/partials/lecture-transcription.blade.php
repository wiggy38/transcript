{{-- Lecture d'une transcription terminée (cahier F2), reprise de la maquette « Transcription - Nocturne » :
     en-tête, lecteur à gauche, texte horodaté, recherche, copie et export à droite. Rendu dans le composant
     SuiviTranscription ; wire:ignore garde le lecteur intact quand Livewire rafraîchit la page.
     Le comportement est dans resources/js/lecteur-transcription.js. --}}
@php
    $youtube = $transcription->source === \App\Enums\SourceTranscription::Youtube && $transcription->id_video !== null;
    $audioDisponible = $transcription->mp3_chemin !== null;
    $dureeMs = (int) ($transcription->duree_secondes ?? 0) * 1000;
    $dureeSecondes = max(1, (int) round($dureeMs / 1000));
    $langue = strtoupper((string) ($transcription->langue_detectee ?? $transcription->langue_forcee ?? '')) ?: '—';
    $mots = \Illuminate\Support\Number::format((int) $transcription->nombre_mots);
    $minutesApercu = intdiv((int) config('voxia.plans.free.max_video_seconds'), 60);
    // Déclencheur « aperçu » (cahier §2) : minutes que l'aperçu gratuit n'a pas transcrites.
    $minutesNonTranscrites = app(\App\Services\Plans\PlanFeatures::class)->minutesNonTranscrites((int) $transcription->duree_secondes);
    // Onde de la maquette : 48 barres, les 17 premières (partie lue) en accent, le reste au repos.
    $ondes = [];
    for ($i = 0; $i < 48; $i++) {
        $ondes[] = [
            'hauteur' => (int) round(22 + 78 * abs(sin($i * 1.7 + 0.6) * cos($i * 0.45))),
            'delai' => '-'.(($i % 16) / 10).'s',
            'lue' => $i < 17,
        ];
    }
@endphp

<section aria-labelledby="titre-transcription" wire:ignore
    x-data="lecteurTranscription(@js([
        'source' => $youtube ? 'youtube' : 'audio',
        'idVideo' => $youtube ? $transcription->id_video : null,
        'dureeMs' => $dureeMs,
        'positionInitiale' => $positionInitiale,
        'libelles' => [
            'lecteur' => __('transcriptions.lecture.lecteur_youtube'),
            'copieOk' => __('transcriptions.lecture.copie_ok'),
            'copieEchec' => __('transcriptions.lecture.copie_echec'),
            'aucun' => __('transcriptions.lecture.aucun_resultat'),
            'sur' => __('transcriptions.lecture.resultat_sur'),
        ],
    ]))"
    x-on:voxia-aller.window="aller($event.detail.ms)"
    :data-horodatages="horodatagesVisibles ? 'affiches' : 'masques'"
    :data-lecture="lecture ? 'lecture' : 'pause'"
    x-on:wheel.window.passive="surInteraction($event)"
    x-on:touchmove.window.passive="surInteraction($event)"
    x-on:keydown.window="surInteraction($event)"
    x-on:pointerdown.window="surInteraction($event)"
    class="rounded-panneau border border-filet bg-panneau shadow-panneau">

    {{-- En-tête : statut, titre, plateforme, durée, langue, nombre de mots (pastilles) --}}
    <header class="flex flex-wrap items-start justify-between gap-x-6 gap-y-2.5 border-b border-filet px-5 py-[18px]">
        <div class="grid min-w-0 gap-2">
            <x-statut-transcription :statut="$transcription->statut" class="justify-self-start" />
            <h1 id="titre-transcription" class="titre-page">
                {{ $transcription->titre ?? __('transcriptions.page.titre') }}
            </h1>
        </div>

        <ul class="m-0 flex list-none flex-wrap gap-1.5 p-0">
            <li class="whitespace-nowrap rounded-pilule border border-filet px-2.5 py-[3px] font-mono text-xs leading-normal text-texte-secondaire">
                <span class="sr-only">{{ __('transcriptions.lecture.plateforme') }} : </span>{{ __('transcriptions.lecture.plateformes.'.$transcription->source->value) }}
            </li>
            <li class="whitespace-nowrap rounded-pilule border border-filet px-2.5 py-[3px] font-mono text-xs leading-normal text-texte-secondaire">
                <span class="sr-only">{{ __('transcriptions.lecture.duree') }} : </span>{{ \App\Support\Horodatage::format($dureeMs) }}
            </li>
            <li class="whitespace-nowrap rounded-pilule border border-filet px-2.5 py-[3px] font-mono text-xs leading-normal text-texte-secondaire">
                <span class="sr-only">{{ __('transcriptions.lecture.langue') }} : </span>{{ $langue }}
            </li>
            <li class="whitespace-nowrap rounded-pilule border border-filet px-2.5 py-[3px] font-mono text-xs leading-normal text-texte-secondaire">
                {{ __('transcriptions.lecture.mots', ['nombre' => $mots]) }}
            </li>
        </ul>
    </header>

    <div class="flex flex-wrap gap-7 p-5">

        {{-- Lecteur : fixé en haut sur grand écran, pour suivre le texte en défilant --}}
        <div x-ref="zoneLecteur" class="sticky top-4 grid max-w-[380px] flex-[1_1_280px] items-start content-start gap-3.5 self-start">
            @if ($youtube)
                {{-- API IFrame officielle : l'iframe est créée par le script YouTube dans ce bloc. --}}
                <div class="relative aspect-video w-full overflow-hidden rounded-champ border border-filet bg-fond">
                    <div x-ref="youtube" class="absolute inset-0 size-full"></div>
                </div>
                <p x-show="erreurLecteur" role="alert" class="m-0 text-sm text-erreur">{{ __('transcriptions.lecture.lecteur_erreur') }}</p>
            @elseif ($audioDisponible)
                {{-- Lecteur audio de la maquette : bouton lecture / pause et curseur. L'élément audio reste caché et porte le son. --}}
                <div class="grid grid-cols-[44px_minmax(0,1fr)] items-center gap-3 rounded-[14px] border border-filet bg-fond p-3">
                    <button type="button" @click="basculerLecture()"
                        x-bind:aria-label="lecture ? {{ \Illuminate\Support\Js::from(__('transcriptions.lecture.pause')) }} : {{ \Illuminate\Support\Js::from(__('transcriptions.lecture.lire')) }}"
                        class="grid size-11 cursor-pointer place-items-center rounded-bouton bg-accent text-sur-accent hover:opacity-90">
                        <svg x-show="lecture" x-cloak width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="6" y="4" width="4" height="16" rx="1"/><rect x="14" y="4" width="4" height="16" rx="1"/></svg>
                        <svg x-show="! lecture" x-cloak width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 4.5v15l12.5-7.5z"/></svg>
                    </button>
                    <input type="range" min="0" value="0" step="1" max="{{ $dureeSecondes }}"
                        x-bind:max="Math.max(1, Math.round(dureeMs / 1000))" x-bind:value="Math.floor(temps / 1000)"
                        @input="positionner($event.target.value)"
                        aria-label="{{ __('transcriptions.lecture.position') }}"
                        class="w-full accent-accent">
                </div>
                <audio x-ref="audio" preload="metadata" class="hidden"
                    src="{{ route('transcriptions.audio', $transcription) }}"
                    aria-label="{{ __('transcriptions.lecture.lecteur_audio') }}"
                    @timeupdate="synchroniser()" @seeked="synchroniser()"
                    @play="lecture = true; synchroniser()" @pause="lecture = false; synchroniser()" @ended="lecture = false"
                    @loadedmetadata="dureeMs = Math.round($el.duration * 1000) || dureeMs">
                    {{ __('transcriptions.lecture.audio_navigateur') }}
                </audio>
            @else
                <x-message-erreur>{{ __('transcriptions.lecture.audio_indisponible') }}</x-message-erreur>
            @endif

            <div aria-hidden="true" class="flex h-14 items-center gap-0.5">
                @foreach ($ondes as $onde)
                    <span class="vx-onde-barre min-w-px flex-[1_1_0] rounded-[1px] {{ $onde['lue'] ? 'bg-accent' : 'bg-onde-repos' }}"
                        style="height: {{ $onde['hauteur'] }}%; animation-delay: {{ $onde['delai'] }};"></span>
                @endforeach
            </div>

            <p class="m-0 font-mono text-sm leading-normal tabular-nums text-texte-secondaire">
                <span x-text="formater(temps)">00:00</span>
                <span class="text-texte-discret">/ <span x-text="formater(dureeMs)">{{ \App\Support\Horodatage::format($dureeMs) }}</span></span>
            </p>
        </div>

        {{-- Texte : recherche, horodatages, paragraphes, bandeau d'aperçu, copie et export --}}
        <div class="grid min-w-0 flex-[2_1_380px] content-start gap-3.5">
            <div role="search" class="flex flex-wrap items-center gap-2">
                <label for="recherche-transcription" class="sr-only">{{ __('transcriptions.lecture.rechercher') }}</label>
                <div class="relative min-w-0 flex-[1_1_220px]">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"
                        class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-texte-discret"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    <input id="recherche-transcription" type="search" x-model="recherche"
                        @input.debounce.150ms="rechercher()"
                        @keydown.enter.prevent="$event.shiftKey ? precedent() : suivant()"
                        @keydown.escape="effacerRecherche()"
                        placeholder="{{ __('transcriptions.lecture.rechercher') }}"
                        autocomplete="off"
                        class="min-h-11 w-full min-w-0 rounded-champ border border-filet-contour bg-fond py-0 pr-3 pl-9 text-[15px] text-texte placeholder:text-texte-discret">
                </div>
                <button type="button" @click="precedent()" :disabled="nombreResultats === 0"
                    class="min-h-11 cursor-pointer rounded-bouton border border-filet-contour px-3 text-sm text-texte hover:border-accent hover:text-accent-texte disabled:cursor-not-allowed disabled:opacity-50">
                    {{ __('transcriptions.lecture.precedent') }}
                </button>
                <button type="button" @click="suivant()" :disabled="nombreResultats === 0"
                    class="min-h-11 cursor-pointer rounded-bouton border border-filet-contour px-3 text-sm text-texte hover:border-accent hover:text-accent-texte disabled:cursor-not-allowed disabled:opacity-50">
                    {{ __('transcriptions.lecture.suivant') }}
                </button>
                <p aria-live="polite" class="m-0 whitespace-nowrap font-mono text-xs leading-normal text-texte-discret" x-text="messageRecherche"></p>
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="button" @click="basculerHorodatages()" :aria-pressed="horodatagesVisibles ? 'true' : 'false'"
                    class="inline-flex min-h-9 cursor-pointer items-center gap-1.5 whitespace-nowrap rounded-pilule border px-3 font-mono text-xs leading-normal"
                    :class="horodatagesVisibles ? 'border-accent-texte bg-[color-mix(in_oklab,var(--vx-accent)_14%,transparent)] text-accent-texte' : 'border-filet-contour text-texte'">
                    <svg x-show="horodatagesVisibles" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>
                    {{ __('transcriptions.lecture.horodatages') }}
                </button>
                <button type="button" @click="basculerSuivre()" :aria-pressed="suivre ? 'true' : 'false'"
                    class="inline-flex min-h-9 cursor-pointer items-center whitespace-nowrap rounded-pilule border px-3 font-mono text-xs leading-normal"
                    :class="suivre ? 'border-accent-texte bg-[color-mix(in_oklab,var(--vx-accent)_14%,transparent)] text-accent-texte' : 'border-filet-contour text-texte'">
                    {{ __('transcriptions.lecture.suivre') }}
                </button>
            </div>

            @if ($transcription->paragraphs->isEmpty())
                <p class="m-0 text-texte-secondaire">{{ __('transcriptions.lecture.vide') }}</p>
            @else
                <ol x-ref="liste" class="m-0 grid list-none gap-0.5 p-0">
                    @foreach ($transcription->paragraphs as $paragraphe)
                        <li data-paragraphe data-debut-ms="{{ $paragraphe->debut_ms }}"
                            class="vx-paragraphe group relative flex items-baseline gap-3.5 rounded-champ px-3 py-2.5 transition-colors duration-300 motion-reduce:transition-none data-[actif=true]:bg-surlignage">
                            <button type="button" @click="aller({{ $paragraphe->debut_ms }})"
                                aria-label="{{ __('transcriptions.lecture.lire_a', ['temps' => \App\Support\Horodatage::format($paragraphe->debut_ms)]) }}"
                                class="vx-horodatage -my-1 shrink-0 cursor-pointer rounded-sm p-1 font-mono text-xs leading-normal font-medium tabular-nums text-texte-discret transition-colors duration-150 hover:text-accent-texte group-data-[actif=true]:text-surlignage-temps">
                                {{ \App\Support\Horodatage::format($paragraphe->debut_ms) }}
                            </button>
                            <p data-texte class="m-0 min-w-0 text-[15px] text-texte-secondaire [overflow-wrap:anywhere]">{{ $paragraphe->texte }}</p>
                        </li>
                    @endforeach
                </ol>
            @endif

            @if ($transcription->est_apercu)
                <aside class="grid gap-1.5 rounded-[14px] border border-accent/45 bg-accent/8 p-4" aria-labelledby="bandeau-apercu">
                    <p id="bandeau-apercu" class="m-0 font-semibold text-texte">{{ __('transcriptions.lecture.bandeau_pro') }}</p>
                    <p class="m-0 text-sm text-texte-secondaire">{{ __('transcriptions.page.apercu', ['minutes' => $minutesApercu]) }}</p>
                    <button type="button" aria-haspopup="dialog" x-on:click="$dispatch('voxia-ouvrir', { id: 'upgrade-apercu' })"
                        class="mt-1 min-h-10 cursor-pointer justify-self-start rounded-bouton bg-accent px-4 text-sm font-semibold text-sur-accent hover:opacity-90">{{ __('transcriptions.lecture.passer_pro') }}</button>
                </aside>

                {{-- Déclencheur « aperçu » : le bandeau ouvre cette fenêtre, la transcription reste à l'écran.
                     Le paiement garde l'identifiant : l'aperçu est relancé en version complète après paiement. --}}
                <x-upgrade.fenetre id="upgrade-apercu" :titre="__('upgrade.apercu.titre')" :retour="route('transcriptions.show', $transcription)"
                    :transcription-id="$transcription->getKey()">
                    <p class="text-texte">{{ __('upgrade.apercu.texte', ['minutes' => $minutesNonTranscrites, 'duree' => \App\Support\Duree::formater((int) $transcription->duree_secondes)]) }}</p>
                </x-upgrade.fenetre>
            @endif

            {{-- Barre d'export (cahier F4) : les formats du plan sont des liens, les autres sont verrouillés
                 avec le plan qui les débloque. Les SRT et VTT gardent toujours leurs horodatages. --}}
            @php
                $compte = auth()->user();
                $droits = app(\App\Services\Plans\PlanFeatures::class);
                // Liens signés (cahier Sécurité) : valides le temps défini par voxia.telechargement.
                $expirationLien = now()->addMinutes((int) config('voxia.telechargement.duree_lien_minutes'));
                $iconeTelechargement = 'M12 3v12M7 10l5 5 5-5M5 21h14';
                $iconeVerrou = 'M5 11h14v10H5zM8 11V7a4 4 0 0 1 8 0v4';
            @endphp
            <footer class="mt-1.5 grid gap-3.5 border-t border-filet pt-[18px]" x-data="{ horodatages: true }">
                <div class="flex flex-wrap items-center gap-x-[18px] gap-y-2.5">
                    <button type="button" @click="copier()"
                        class="inline-flex min-h-11 cursor-pointer items-center gap-2 rounded-bouton bg-texte px-[18px] text-sm font-semibold text-fond hover:opacity-90">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true"><rect x="8" y="8" width="13" height="13" rx="2"/><path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3"/></svg>
                        {{ __('transcriptions.lecture.copier') }}
                    </button>
                    <label class="inline-flex min-h-11 cursor-pointer items-center gap-2 text-sm text-texte-secondaire">
                        <input type="checkbox" x-model="horodatages" class="size-[18px] accent-accent">
                        {{ __('transcriptions.lecture.avec_horodatages') }}
                    </label>
                    <span role="status" class="font-mono text-xs leading-normal text-succes" x-text="messageCopie"></span>
                </div>

                <ul aria-label="{{ __('transcriptions.lecture.telecharger') }}" class="m-0 flex list-none flex-wrap items-start gap-2 p-0">
                    @foreach (\App\Enums\FormatExport::cases() as $format)
                        <li class="grid max-w-[170px] gap-1">
                            @if ($droits->peutExporter($compte, $format))
                                @php
                                    $urlAvec = \Illuminate\Support\Facades\URL::temporarySignedRoute('transcriptions.export', $expirationLien, [$transcription, $format, 'horodatages' => 1]);
                                    $urlSans = \Illuminate\Support\Facades\URL::temporarySignedRoute('transcriptions.export', $expirationLien, [$transcription, $format, 'horodatages' => 0]);
                                    $jsAvec = \Illuminate\Support\Js::from($urlAvec);
                                    $jsSans = \Illuminate\Support\Js::from($urlSans);
                                @endphp
                                <a href="{{ $urlAvec }}" x-bind:href="horodatages ? {{ $jsAvec }} : {{ $jsSans }}" download
                                    class="inline-flex min-h-11 items-center gap-2 whitespace-nowrap rounded-bouton border border-filet-contour px-3.5 font-mono text-[13px] font-medium text-texte hover:border-accent hover:text-accent-texte">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $iconeTelechargement }}"/></svg>
                                    {{ $format->libelle() }}
                                </a>
                            @else
                                @php
                                    $planDebloquant = $droits->planMinimalPour($format);
                                    $libellePlan = $planDebloquant ? $droits->libellePlan($planDebloquant) : '';
                                @endphp
                                {{-- Format verrouillé : le bouton ouvre la fenêtre d'upgrade (cahier §2, déclencheur annexe « export »). --}}
                                <button type="button" aria-haspopup="dialog" aria-describedby="verrou-{{ $format->value }}"
                                    x-on:click="$dispatch('voxia-ouvrir', { id: 'upgrade-export-{{ $format->value }}' })"
                                    class="inline-flex min-h-11 cursor-pointer items-center gap-2 whitespace-nowrap rounded-bouton border border-filet px-3.5 font-mono text-[13px] font-medium text-texte-discret hover:border-accent hover:text-accent-texte">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $iconeVerrou }}"/></svg>
                                    {{ $format->libelle() }}@if ($libellePlan !== '') · {{ $libellePlan }}@endif
                                </button>
                                <p id="verrou-{{ $format->value }}" class="m-0 font-mono text-[11px] leading-normal text-texte-discret">
                                    {{ __('transcriptions.lecture.exporter_verrouille', ['plan' => $libellePlan]) }}
                                </p>

                                <x-upgrade.fenetre id="upgrade-export-{{ $format->value }}" :titre="__('upgrade.export.titre', ['format' => $format->libelle()])"
                                    :retour="route('transcriptions.show', $transcription)" :plans="$droits->plansAvec($format)" :transcription-id="$transcription->getKey()">
                                    <p class="text-texte">{{ __('upgrade.export.texte', ['format' => $format->libelle(), 'plan' => $libellePlan]) }}</p>
                                </x-upgrade.fenetre>
                            @endif
                        </li>
                    @endforeach

                    <li class="grid max-w-[170px] gap-1">
                        @if ($transcription->mp3_chemin !== null)
                            <a href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('transcriptions.mp3', $expirationLien, [$transcription]) }}" download
                                class="inline-flex min-h-11 items-center gap-2 whitespace-nowrap rounded-bouton border border-filet-contour px-3.5 font-mono text-[13px] font-medium text-texte hover:border-accent hover:text-accent-texte">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $iconeTelechargement }}"/></svg>
                                {{ __('transcriptions.lecture.telecharger_mp3') }}
                            </a>
                        @else
                            {{-- MP3 supprimé par la règle de conservation (cahier F5) : le bouton le dit au lieu de disparaître. --}}
                            <button type="button" disabled aria-describedby="mp3-supprime"
                                class="inline-flex min-h-11 cursor-not-allowed items-center gap-2 whitespace-nowrap rounded-bouton border border-filet px-3.5 font-mono text-[13px] font-medium text-texte-discret">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $iconeVerrou }}"/></svg>
                                {{ __('transcriptions.lecture.mp3_supprime_bouton') }}
                            </button>
                            <p id="mp3-supprime" class="m-0 font-mono text-[11px] leading-normal text-texte-discret">
                                {{ __('transcriptions.lecture.mp3_supprime', ['jours' => (int) config('voxia.retention.media_days')]) }}
                            </p>
                        @endif
                    </li>
                </ul>
            </footer>
        </div>
    </div>
</section>
