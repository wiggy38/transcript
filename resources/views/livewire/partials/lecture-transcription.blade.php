{{-- Lecture d'une transcription terminée (cahier F2) : en-tête, lecteur, texte horodaté, recherche,
     copie, export. Rendu dans le composant SuiviTranscription ; wire:ignore garde le lecteur intact
     quand Livewire rafraîchit la page. Le comportement est dans resources/js/lecteur-transcription.js. --}}
@php
    $youtube = $transcription->source === \App\Enums\SourceTranscription::Youtube && $transcription->id_video !== null;
    $audioDisponible = $transcription->mp3_chemin !== null;
    $dureeMs = (int) ($transcription->duree_secondes ?? 0) * 1000;
    $langue = strtoupper((string) ($transcription->langue_detectee ?? $transcription->langue_forcee ?? '')) ?: '—';
    $mots = \Illuminate\Support\Number::format((int) $transcription->nombre_mots);
    $minutesApercu = intdiv((int) config('voxia.plans.free.max_video_seconds'), 60);
    // Déclencheur « aperçu » (cahier §2) : minutes que l'aperçu gratuit n'a pas transcrites.
    $minutesNonTranscrites = app(\App\Services\Plans\PlanFeatures::class)->minutesNonTranscrites((int) $transcription->duree_secondes);
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
    class="relative rounded-panneau border border-filet bg-panneau">

    {{-- Repères d'angle de la maquette --}}
    <span aria-hidden="true" class="pointer-events-none absolute -top-px -left-px size-3.5 rounded-tl-panneau border-t-2 border-l-2 border-accent"></span>
    <span aria-hidden="true" class="pointer-events-none absolute -top-px -right-px size-3.5 rounded-tr-panneau border-t-2 border-r-2 border-accent"></span>

    {{-- En-tête : titre, plateforme, durée, langue, nombre de mots --}}
    <header class="flex flex-wrap items-start justify-between gap-x-6 gap-y-3 border-b border-filet px-5 py-4">
        <div class="flex min-w-0 items-start gap-3">
            <span aria-hidden="true" class="vx-pulsation mt-2.5 size-2 shrink-0 rounded-full bg-accent"></span>
            <h1 id="titre-transcription" class="min-w-0 text-xl font-semibold leading-snug tracking-tight [overflow-wrap:anywhere]">
                {{ $transcription->titre ?? __('transcriptions.page.titre') }}
            </h1>
        </div>

        <ul class="flex flex-wrap items-center gap-x-3 gap-y-1 font-mono text-xs text-texte-discret">
            <li><span class="sr-only">{{ __('transcriptions.lecture.plateforme') }} : </span>{{ __('transcriptions.lecture.plateformes.'.$transcription->source->value) }}</li>
            <li aria-hidden="true">·</li>
            <li><span class="sr-only">{{ __('transcriptions.lecture.duree') }} : </span>{{ \App\Support\Horodatage::format($dureeMs) }}</li>
            <li aria-hidden="true">·</li>
            <li><span class="sr-only">{{ __('transcriptions.lecture.langue') }} : </span>{{ $langue }}</li>
            <li aria-hidden="true">·</li>
            <li>{{ __('transcriptions.lecture.mots', ['nombre' => $mots]) }}</li>
        </ul>
    </header>

    <div class="grid gap-8 p-5 lg:grid-cols-[minmax(0,380px)_minmax(0,1fr)]">

        {{-- Lecteur : fixé en haut sur grand écran, pour suivre le texte en défilant --}}
        <div x-ref="zoneLecteur" class="lg:sticky lg:top-6 lg:self-start">
            @if ($youtube)
                {{-- API IFrame officielle : l'iframe est créée par le script YouTube dans ce bloc. --}}
                <div class="relative aspect-video w-full overflow-hidden rounded-champ border border-filet bg-fond">
                    <div x-ref="youtube" class="absolute inset-0 size-full"></div>
                </div>
                <p x-show="erreurLecteur" role="alert" class="mt-3 text-sm text-erreur">{{ __('transcriptions.lecture.lecteur_erreur') }}</p>
            @elseif ($audioDisponible)
                <audio x-ref="audio" controls preload="metadata" class="w-full"
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

            <div class="mt-6">
                <x-onde-audio />
            </div>

            <p class="mt-4 font-mono text-sm tabular-nums text-texte-secondaire">
                <span x-text="formater(temps)">00:00</span>
                <span class="text-texte-discret">/ <span x-text="formater(dureeMs)">{{ \App\Support\Horodatage::format($dureeMs) }}</span></span>
            </p>
        </div>

        {{-- Texte : recherche, paragraphes, bandeau d'aperçu, export --}}
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2" role="search">
                <div class="min-w-0 flex-1 basis-60">
                    <label for="recherche-transcription" class="sr-only">{{ __('transcriptions.lecture.rechercher') }}</label>
                    <input id="recherche-transcription" type="search" x-model="recherche"
                        @input.debounce.150ms="rechercher()"
                        @keydown.enter.prevent="$event.shiftKey ? precedent() : suivant()"
                        @keydown.escape="effacerRecherche()"
                        placeholder="{{ __('transcriptions.lecture.rechercher') }}"
                        autocomplete="off"
                        class="min-h-10 w-full min-w-0 rounded-champ border border-filet-contour bg-fond px-3 text-texte placeholder:text-texte-discret">
                </div>
                <button type="button" @click="precedent()" :disabled="nombreResultats === 0"
                    class="min-h-10 cursor-pointer rounded-bouton border border-filet-contour px-3 text-sm text-texte hover:border-accent hover:text-accent-texte disabled:cursor-not-allowed disabled:opacity-50">
                    {{ __('transcriptions.lecture.precedent') }}
                </button>
                <button type="button" @click="suivant()" :disabled="nombreResultats === 0"
                    class="min-h-10 cursor-pointer rounded-bouton border border-filet-contour px-3 text-sm text-texte hover:border-accent hover:text-accent-texte disabled:cursor-not-allowed disabled:opacity-50">
                    {{ __('transcriptions.lecture.suivant') }}
                </button>
                <p aria-live="polite" class="w-full font-mono text-xs text-texte-discret sm:w-auto" x-text="messageRecherche"></p>
            </div>

            <div class="mt-3 flex flex-wrap items-center gap-2">
                <button type="button" @click="basculerHorodatages()" :aria-pressed="horodatagesVisibles ? 'true' : 'false'"
                    class="min-h-10 cursor-pointer rounded-bouton border px-3 font-mono text-xs"
                    :class="horodatagesVisibles ? 'border-accent text-accent-texte' : 'border-filet-contour text-texte'">
                    {{ __('transcriptions.lecture.horodatages') }}
                </button>
                <button type="button" @click="basculerSuivre()" :aria-pressed="suivre ? 'true' : 'false'"
                    class="min-h-10 cursor-pointer rounded-bouton border px-3 font-mono text-xs"
                    :class="suivre ? 'border-accent text-accent-texte' : 'border-filet-contour text-texte'">
                    {{ __('transcriptions.lecture.suivre') }}
                </button>
            </div>

            @if ($transcription->paragraphs->isEmpty())
                <p class="mt-8 text-texte-secondaire">{{ __('transcriptions.lecture.vide') }}</p>
            @else
                <ol x-ref="liste" class="mt-6 flex flex-col">
                    @foreach ($transcription->paragraphs as $paragraphe)
                        <li data-paragraphe data-debut-ms="{{ $paragraphe->debut_ms }}"
                            class="vx-paragraphe group relative flex gap-4 rounded-champ px-3 py-2.5 transition-colors duration-300 motion-reduce:transition-none data-[actif=true]:bg-surlignage">
                            <button type="button" @click="aller({{ $paragraphe->debut_ms }})"
                                aria-label="{{ __('transcriptions.lecture.lire_a', ['temps' => \App\Support\Horodatage::format($paragraphe->debut_ms)]) }}"
                                class="vx-horodatage shrink-0 cursor-pointer rounded-sm pt-1 font-mono text-xs tabular-nums text-texte-discret transition-colors duration-150 hover:text-accent-texte group-data-[actif=true]:text-accent-texte">
                                {{ \App\Support\Horodatage::format($paragraphe->debut_ms) }}
                            </button>
                            <p data-texte class="min-w-0 text-texte-secondaire [overflow-wrap:anywhere] group-data-[actif=true]:font-medium group-data-[actif=true]:text-texte">{{ $paragraphe->texte }}</p>
                        </li>
                    @endforeach
                </ol>
            @endif

            @if ($transcription->est_apercu)
                <aside class="mt-8 border-l-2 border-accent py-1 pl-4" aria-labelledby="bandeau-apercu">
                    <p id="bandeau-apercu" class="font-medium text-texte">{{ __('transcriptions.lecture.bandeau_pro') }}</p>
                    <p class="mt-1 text-sm text-texte-secondaire">{{ __('transcriptions.page.apercu', ['minutes' => $minutesApercu]) }}</p>
                    <p class="mt-3">
                        <button type="button" aria-haspopup="dialog" x-on:click="$dispatch('voxia-ouvrir', { id: 'upgrade-apercu' })"
                            class="cursor-pointer text-left text-accent-texte hover:underline">{{ __('transcriptions.lecture.passer_pro') }}</button>
                    </p>
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
            @endphp
            <footer class="mt-8 border-t border-filet pt-5" x-data="{ horodatages: true }">
                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" @click="copier()"
                        class="min-h-10 cursor-pointer rounded-bouton border border-filet-contour px-4 font-mono text-xs text-texte hover:border-accent hover:text-accent-texte">
                        {{ __('transcriptions.lecture.copier') }}
                    </button>
                    <span role="status" class="font-mono text-xs text-texte-discret" x-text="messageCopie"></span>
                </div>

                <div class="mt-4">
                    <label class="inline-flex min-h-10 cursor-pointer items-center gap-2 font-mono text-xs text-texte-secondaire">
                        <input type="checkbox" x-model="horodatages" class="size-4 accent-accent">
                        {{ __('transcriptions.lecture.avec_horodatages') }}
                    </label>
                </div>

                <ul class="mt-2 flex flex-wrap items-start gap-3">
                    @foreach (\App\Enums\FormatExport::cases() as $format)
                        <li class="flex flex-col items-start">
                            @if ($droits->peutExporter($compte, $format))
                                @php
                                    $urlAvec = \Illuminate\Support\Facades\URL::temporarySignedRoute('transcriptions.export', $expirationLien, [$transcription, $format, 'horodatages' => 1]);
                                    $urlSans = \Illuminate\Support\Facades\URL::temporarySignedRoute('transcriptions.export', $expirationLien, [$transcription, $format, 'horodatages' => 0]);
                                    $jsAvec = \Illuminate\Support\Js::from($urlAvec);
                                    $jsSans = \Illuminate\Support\Js::from($urlSans);
                                @endphp
                                <a href="{{ $urlAvec }}" x-bind:href="horodatages ? {{ $jsAvec }} : {{ $jsSans }}" download
                                    class="inline-flex min-h-10 items-center rounded-bouton border border-filet-contour px-4 font-mono text-xs text-texte hover:border-accent hover:text-accent-texte">
                                    {{ $format->libelle() }}
                                </a>
                            @else
                                @php
                                    $planDebloquant = $droits->planMinimalPour($format);
                                @endphp
                                {{-- Format verrouillé : le bouton ouvre la fenêtre d'upgrade (cahier §2, déclencheur annexe « export »). --}}
                                <button type="button" aria-haspopup="dialog" aria-describedby="verrou-{{ $format->value }}"
                                    x-on:click="$dispatch('voxia-ouvrir', { id: 'upgrade-export-{{ $format->value }}' })"
                                    class="min-h-10 cursor-pointer rounded-bouton border border-filet px-4 font-mono text-xs text-texte-discret hover:border-accent hover:text-accent-texte">
                                    {{ $format->libelle() }}
                                </button>
                                <p id="verrou-{{ $format->value }}" class="mt-1 font-mono text-[11px] text-texte-discret">
                                    {{ __('transcriptions.lecture.exporter_verrouille', ['plan' => $planDebloquant ? $droits->libellePlan($planDebloquant) : '']) }}
                                </p>

                                <x-upgrade.fenetre id="upgrade-export-{{ $format->value }}" :titre="__('upgrade.export.titre', ['format' => $format->libelle()])"
                                    :retour="route('transcriptions.show', $transcription)" :plans="$droits->plansAvec($format)" :transcription-id="$transcription->getKey()">
                                    <p class="text-texte">{{ __('upgrade.export.texte', ['format' => $format->libelle(), 'plan' => $planDebloquant ? $droits->libellePlan($planDebloquant) : '']) }}</p>
                                </x-upgrade.fenetre>
                            @endif
                        </li>
                    @endforeach

                    <li class="flex flex-col items-start">
                        @if ($transcription->mp3_chemin !== null)
                            <a href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('transcriptions.mp3', $expirationLien, [$transcription]) }}" download
                                class="inline-flex min-h-10 items-center rounded-bouton border border-filet-contour px-4 font-mono text-xs text-texte hover:border-accent hover:text-accent-texte">
                                {{ __('transcriptions.lecture.telecharger_mp3') }}
                            </a>
                        @else
                            {{-- MP3 supprimé par la règle de conservation (cahier F5) : le bouton le dit au lieu de disparaître. --}}
                            <button type="button" disabled aria-describedby="mp3-supprime"
                                class="min-h-10 cursor-not-allowed rounded-bouton border border-filet px-4 font-mono text-xs text-texte-discret">
                                {{ __('transcriptions.lecture.mp3_supprime_bouton') }}
                            </button>
                            <p id="mp3-supprime" class="mt-1 font-mono text-[11px] text-texte-discret">
                                {{ __('transcriptions.lecture.mp3_supprime', ['jours' => (int) config('voxia.retention.media_days')]) }}
                            </p>
                        @endif
                    </li>
                </ul>
            </footer>
        </div>
    </div>
</section>
