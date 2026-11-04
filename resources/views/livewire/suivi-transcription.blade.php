{{-- Statut d'une transcription. Interrogé toutes les 3 secondes tant que le traitement n'est pas fini.
     Une fois terminée, la transcription remplace le statut : lecteur, texte horodaté, export. --}}
<div @if ($enCours) wire:poll.3s @endif>
    @if ($termine)
        @include('livewire.partials.lecture-transcription')
        {{-- Hors de la zone wire:ignore du lecteur : le composant doit pouvoir se rafraîchir seul. --}}
        <livewire:analyse-script :transcription="$transcription" />
    @else
        <section aria-labelledby="titre-transcription" class="max-w-[720px]">
            <div class="flex flex-wrap items-center gap-4">
                <x-badge-statut :statut="$transcription->statut" />
                <span class="font-mono text-xs text-texte-discret">{{ __('transcriptions.page.lancee_le', ['date' => $transcription->created_at->translatedFormat('d/m/Y H:i')]) }}</span>
            </div>

            <h1 id="titre-transcription" class="mt-4 text-3xl font-semibold leading-tight tracking-tight [overflow-wrap:anywhere]">
                {{ $transcription->titre ?? __('transcriptions.page.titre') }}
            </h1>

            @if ($transcription->url_origine)
                <p class="mt-2 break-all font-mono text-sm text-texte-discret">{{ $transcription->url_origine }}</p>
            @endif

            <x-panneau class="mt-8 p-5" aria-live="polite">
                @switch ($transcription->statut->value)
                    @case('echec')
                        <x-message-erreur>
                            {{ __('videos.motifs.'.($transcription->motif_echec?->value ?? 'erreur_technique')) }}
                        </x-message-erreur>
                        @break

                    @default
                        <p class="text-texte-secondaire">{{ __('transcriptions.page.en_cours') }}</p>
                @endswitch

                @if ($enCours)
                    <p class="mt-3 flex items-center gap-3 text-sm text-texte-discret">
                        <span aria-hidden="true" class="vx-pulsation size-2 rounded-full bg-accent"></span>
                        {{ __('transcriptions.page.actualisation') }}
                    </p>
                @endif
            </x-panneau>
        </section>
    @endif

    <p class="mt-6 text-sm">
        <a href="{{ route('tableau-de-bord') }}" class="text-accent hover:underline">{{ __('transcriptions.page.nouvelle') }}</a>
    </p>
</div>
