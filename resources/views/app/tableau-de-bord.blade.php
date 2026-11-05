<x-layouts::app :titre="__('comptes.tableau.titre')" actif="nouvelle">
    @if (session('statut') && __('comptes.statut.'.session('statut')) !== 'comptes.statut.'.session('statut'))
        <p role="status" class="mb-6 border-l-2 border-accent py-1 pl-3 text-sm text-texte">{{ __('comptes.statut.'.session('statut')) }}</p>
    @endif

    <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_340px] lg:items-start">
        <div class="flex min-w-0 max-w-[720px] flex-col gap-6">
            <div class="flex flex-col gap-1.5">
                <h1 class="titre-h2">{{ __('comptes.tableau.titre') }}</h1>
                <p class="text-texte-secondaire">{{ __('comptes.tableau.sous_titre') }}</p>
            </div>

            @unless ($verifie)
                <p role="status" class="flex flex-wrap items-center gap-x-2.5 gap-y-1.5 rounded-champ border border-avertissement/45 bg-avertissement/10 px-3.5 py-3 text-sm text-texte">
                    <span>{{ __('comptes.tableau.non_verifie') }}</span>
                    <a href="{{ route('verification.notice') }}" class="font-semibold whitespace-nowrap text-accent-texte hover:underline">{{ __('comptes.verification.renvoyer') }}</a>
                </p>
            @endunless

            <livewire:nouvelle-transcription />
        </div>

        {{-- Transcriptions récentes (maquette « Tableau de bord ») : cinq dernières, lien vers l'historique complet. --}}
        <section aria-labelledby="titre-historique" class="flex min-w-0 flex-col gap-4">
            <div class="flex items-baseline justify-between gap-4">
                <h2 id="titre-historique" class="text-[15px] font-semibold">{{ __('comptes.tableau.historique') }}</h2>
                <a href="{{ route('historique') }}" class="text-sm whitespace-nowrap text-accent-texte hover:underline">{{ __('comptes.tableau.tout') }}</a>
            </div>

            @if ($transcriptions->isEmpty())
                <div class="flex flex-col items-start gap-2 rounded-panneau border border-dashed border-filet-contour bg-panneau p-5">
                    <p class="text-texte-secondaire">{{ __('comptes.tableau.vide') }}</p>
                </div>
            @else
                <ul class="flex flex-col divide-y divide-filet overflow-hidden rounded-panneau border border-filet bg-panneau">
                    @foreach ($transcriptions->take(5) as $transcription)
                        <li>
                            <a href="{{ route('transcriptions.show', $transcription) }}"
                               class="flex items-center gap-3.5 px-4 py-3.5 text-texte no-underline transition-colors hover:bg-panneau-eleve">
                                <span aria-hidden="true" class="grid size-9 shrink-0 place-items-center rounded-bouton bg-panneau-eleve font-mono text-[11px] text-texte-secondaire">
                                    {{ strtoupper(substr($transcription->source->value, 0, 2)) }}
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate font-medium">{{ $transcription->titre ?? $transcription->url_origine ?? '—' }}</span>
                                    <span class="mt-1 flex flex-wrap items-center gap-x-2.5 gap-y-1 font-mono text-[11px] text-texte-discret">
                                        <x-badge-statut :statut="$transcription->statut" />
                                        @if ($transcription->duree_secondes !== null)
                                            <span>{{ \App\Support\Horodatage::format($transcription->duree_secondes * 1000) }}</span>
                                        @endif
                                        <time datetime="{{ $transcription->created_at->toIso8601String() }}">{{ \App\Support\Formats::date($transcription->created_at) }}</time>
                                    </span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</x-layouts::app>
