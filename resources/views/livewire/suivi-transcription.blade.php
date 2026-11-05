{{-- Statut d'une transcription. Interrogé toutes les 3 secondes tant que le traitement n'est pas fini.
     Une fois terminée, la transcription remplace le statut : lecteur, texte horodaté, export. --}}
<div @if ($enCours) wire:poll.3s @endif>
    @if ($termine)
        @include('livewire.partials.lecture-transcription')
        {{-- Hors de la zone wire:ignore du lecteur : le composant doit pouvoir se rafraîchir seul. --}}
        <div class="mt-8">
            <livewire:analyse-script :transcription="$transcription" />
        </div>
    @else
        {{-- Étapes dérivées du statut (maquette « Transcription », état en cours) : aucune donnée nouvelle. --}}
        @php
            $etapes = [
                'telechargement' => __('interface.statuts.telechargement'),
                'transcription' => __('interface.statuts.transcription'),
                'termine' => __('interface.statuts.termine'),
            ];
            $rang = match ($transcription->statut->value) {
                'telechargement' => 0,
                'transcription' => 1,
                default => -1,
            };
        @endphp

        <section aria-labelledby="titre-transcription" class="max-w-[720px]">
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                <x-badge-statut :statut="$transcription->statut" />
                <span class="font-mono text-xs text-texte-discret">{{ __('transcriptions.page.lancee_le', ['date' => \App\Support\Formats::dateHeure($transcription->created_at)]) }}</span>
            </div>

            <h1 id="titre-transcription" class="mt-3 text-[22px] leading-tight font-semibold tracking-tight [overflow-wrap:anywhere]">
                {{ $transcription->titre ?? __('transcriptions.page.titre') }}
            </h1>

            @if ($transcription->url_origine)
                <p class="mt-2 font-mono text-sm break-all text-texte-discret">{{ $transcription->url_origine }}</p>
            @endif

            <div class="mt-8 flex flex-col gap-5 rounded-panneau border border-filet bg-panneau p-5" aria-live="polite">
                <ol aria-label="{{ __('transcriptions.page.etapes') }}" class="grid gap-3 sm:grid-cols-3">
                    @foreach ($etapes as $cle => $libelle)
                        @php
                            $indice = array_search($cle, array_keys($etapes), true);
                            $fait = $rang > $indice;
                            $actuelle = $rang === $indice;
                        @endphp
                        <li @if ($actuelle) aria-current="step" @endif
                            class="flex items-center gap-3 rounded-champ border px-3.5 py-3 text-sm {{ $actuelle ? 'border-accent text-texte' : ($fait ? 'border-filet text-texte-secondaire' : 'border-filet text-texte-discret') }}">
                            <span aria-hidden="true" class="grid size-6 shrink-0 place-items-center rounded-full font-mono text-[11px] {{ $fait ? 'bg-accent text-sur-accent' : ($actuelle ? 'border border-accent text-accent-texte' : 'border border-filet-contour') }}">
                                {{ $fait ? '✓' : $indice + 1 }}
                            </span>
                            <span>{{ $libelle }}</span>
                        </li>
                    @endforeach
                </ol>

                @if ($transcription->statut->value === 'echec')
                    <x-message-erreur>
                        {{ __('videos.motifs.'.($transcription->motif_echec?->value ?? 'erreur_technique')) }}
                    </x-message-erreur>
                @elseif ($enCours)
                    <p class="text-texte-secondaire">{{ __('transcriptions.page.en_cours') }}</p>
                    <p class="flex items-center gap-3 text-sm text-texte-discret">
                        <span aria-hidden="true" class="vx-pulsation size-2 rounded-full bg-direct"></span>
                        {{ __('transcriptions.page.actualisation') }}
                    </p>
                @endif
            </div>
        </section>
    @endif

    <p class="mt-6 text-sm">
        <a href="{{ route('tableau-de-bord') }}" class="text-accent-texte hover:underline">{{ __('transcriptions.page.nouvelle') }}</a>
    </p>
</div>
