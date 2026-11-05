{{-- Statut d'une transcription. Interrogé toutes les 3 secondes tant que le traitement n'est pas fini.
     Une fois terminée, la transcription remplace le statut : lecteur, texte horodaté, export. --}}
<div @if ($enCours) wire:poll.3s @endif>
    @if ($termine)
        @include('livewire.partials.lecture-transcription')
        {{-- Hors de la zone wire:ignore du lecteur : le composant doit pouvoir se rafraîchir seul. --}}
        <div class="mt-5">
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
            // Échec : la maquette marque la première étape en erreur, sans savoir où le traitement a cassé.
            $echec = $transcription->statut->value === 'echec';
        @endphp

        <section aria-labelledby="titre-suivi" class="grid max-w-[720px] gap-3.5">
            <div class="flex flex-wrap items-center gap-x-3.5 gap-y-2">
                <x-statut-transcription :statut="$transcription->statut" />
                <span class="font-mono text-xs leading-normal text-texte-discret">{{ __('transcriptions.page.lancee_le', ['date' => \App\Support\Formats::dateHeure($transcription->created_at)]) }}</span>
            </div>

            <h1 id="titre-suivi" class="titre-page">
                {{ $transcription->titre ?? __('transcriptions.page.titre') }}
            </h1>

            @if ($transcription->url_origine)
                <p class="m-0 font-mono text-[13px] leading-normal break-all text-texte-discret [overflow-wrap:anywhere]">{{ $transcription->url_origine }}</p>
            @endif

            <div class="mt-1.5 grid gap-4 rounded-panneau border border-filet bg-panneau p-5 shadow-panneau" aria-live="polite">
                <ol aria-label="{{ __('transcriptions.page.etapes') }}" class="m-0 grid list-none grid-cols-3 gap-2 p-0">
                    @foreach ($etapes as $cle => $libelle)
                        @php
                            $indice = array_search($cle, array_keys($etapes), true);
                            $fait = ! $echec && $rang > $indice;
                            $actuelle = ! $echec && $rang === $indice;
                            $erreur = $echec && $indice === 0;
                        @endphp
                        <li @if ($actuelle) aria-current="step" @endif class="grid gap-2">
                            <span class="relative h-1.5 overflow-hidden rounded-full {{ $fait ? 'bg-succes' : ($erreur ? 'bg-erreur' : 'bg-panneau-eleve') }}">
                                @if ($actuelle)
                                    <span class="vx-indeterminee absolute inset-0 w-2/5 rounded-full bg-direct"></span>
                                @endif
                            </span>
                            <span class="font-mono text-xs leading-normal {{ $actuelle || $erreur ? 'font-medium' : '' }} {{ $actuelle ? 'text-direct' : ($erreur ? 'text-erreur' : ($fait ? 'text-texte-secondaire' : 'text-texte-discret')) }}"><span>{{ $libelle }}</span></span>
                        </li>
                    @endforeach
                </ol>

                @if ($echec)
                    <p role="alert" class="m-0 flex gap-2.5 rounded-champ bg-[color-mix(in_oklab,var(--vx-error)_12%,transparent)] px-3.5 py-3 text-sm text-texte">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true" class="mt-0.5 shrink-0 text-erreur"><circle cx="12" cy="12" r="9"/><path d="M12 7.5v5.5"/><path d="M12 16.5h.01"/></svg>
                        <span>{{ __('videos.motifs.'.($transcription->motif_echec?->value ?? 'erreur_technique')) }}</span>
                    </p>
                @elseif ($enCours)
                    <div class="grid gap-2">
                        <p class="m-0 text-texte-secondaire">{{ __('transcriptions.page.en_cours') }}</p>
                        <p class="m-0 flex items-center gap-2.5 text-[13px] text-texte-discret">
                            <span aria-hidden="true" class="vx-pulsation size-[7px] rounded-full bg-direct"></span>
                            {{ __('transcriptions.page.actualisation') }}
                        </p>
                    </div>
                @endif
            </div>
        </section>
    @endif

    <p class="mt-6">
        <a href="{{ route('tableau-de-bord') }}" class="text-sm font-semibold">{{ __('transcriptions.page.nouvelle') }}</a>
    </p>
</div>
