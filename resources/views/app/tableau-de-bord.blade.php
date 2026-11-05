<x-layouts::app :titre="__('comptes.tableau.titre')" actif="nouvelle">
    @php
        // Sigles de la liste (maquette : YT, TT, FI) et pastilles de statut, couleurs de la maquette « Tableau de bord ».
        $sigles = ['youtube' => 'YT', 'tiktok' => 'TT', 'fichier' => 'FI'];
        $pastilles = [
            'en_attente' => ['classe' => 'bg-texte-secondaire/[18%] text-texte-secondaire', 'pulse' => false],
            'telechargement' => ['classe' => 'bg-direct/[18%] text-direct', 'pulse' => true],
            'transcription' => ['classe' => 'bg-direct/[18%] text-direct', 'pulse' => true],
            'termine' => ['classe' => 'bg-succes/[18%] text-succes', 'pulse' => false],
            'echec' => ['classe' => 'bg-erreur/[18%] text-erreur', 'pulse' => false],
        ];
    @endphp

    @if (session('statut') && __('comptes.statut.'.session('statut')) !== 'comptes.statut.'.session('statut'))
        <p role="status" class="mb-6 border-l-2 border-accent py-1 pl-3 text-sm text-texte">{{ __('comptes.statut.'.session('statut')) }}</p>
    @endif

    <div class="flex flex-wrap items-start gap-8">
        <div class="flex min-w-0 flex-[999_1_560px] flex-col gap-5">
            <div class="grid gap-1.5">
                <h1 class="titre-h2">{{ __('comptes.tableau.titre') }}</h1>
                <p class="text-[15px] text-texte-secondaire">{{ __('comptes.tableau.sous_titre') }}</p>
            </div>

            @unless ($verifie)
                <p role="status" class="flex flex-wrap items-center gap-x-2.5 gap-y-1.5 rounded-champ border border-avertissement/45 bg-avertissement/10 px-3.5 py-3 text-sm text-texte">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" class="shrink-0 text-avertissement">
                        <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                        <path d="m3 7 9 6 9-6"></path>
                    </svg>
                    <span class="flex-[1_1_240px]">{{ __('comptes.tableau.non_verifie') }}</span>
                    <a href="{{ route('verification.notice') }}" class="font-semibold whitespace-nowrap text-accent-texte underline">{{ __('comptes.verification.renvoyer') }}</a>
                </p>
            @endunless

            <livewire:nouvelle-transcription />
        </div>

        {{-- Tes transcriptions (maquette « Tableau de bord ») : cinq dernières, lien vers l'historique complet. --}}
        <section aria-labelledby="titre-historique" class="flex min-w-0 max-w-full flex-[1_1_300px] flex-col gap-3">
            <div class="flex items-baseline justify-between gap-3">
                <h2 id="titre-historique" class="m-0 font-titre text-[17px] font-semibold tracking-[-0.02em]">{{ __('comptes.tableau.historique') }}</h2>
                <a href="{{ route('historique') }}" class="text-[13px] whitespace-nowrap no-underline text-accent-texte">{{ __('comptes.tableau.tout') }}</a>
            </div>

            @if ($transcriptions->isEmpty())
                <div class="grid justify-items-start gap-2 rounded-panneau border border-dashed border-filet-contour p-5">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" class="text-texte-discret">
                        <path d="M4 6h3"></path><path d="M10 6h10"></path><path d="M4 12h3"></path><path d="M10 12h8"></path><path d="M4 18h3"></path><path d="M10 18h10"></path>
                    </svg>
                    <p class="m-0 text-sm text-texte-secondaire">{{ __('comptes.tableau.vide') }}</p>
                </div>
            @else
                <ul class="m-0 list-none overflow-hidden rounded-panneau border border-filet bg-panneau p-0 shadow-panneau">
                    @foreach ($transcriptions->take(5) as $transcription)
                        @php
                            $source = $transcription->source->value;
                            $pastille = $pastilles[$transcription->statut->value] ?? $pastilles['en_attente'];
                        @endphp
                        <li class="{{ $loop->first ? '' : 'border-t border-filet' }}">
                            <a href="{{ route('transcriptions.show', $transcription) }}"
                               class="grid grid-cols-[36px_minmax(0,1fr)] items-center gap-3 px-3.5 py-3 text-texte no-underline transition-colors hover:bg-panneau-eleve">
                                <span aria-hidden="true" class="grid size-9 place-items-center rounded-[10px] bg-panneau-eleve font-mono text-[10px] font-medium text-texte-secondaire">{{ $sigles[$source] ?? strtoupper(substr($source, 0, 2)) }}</span>
                                <span class="grid min-w-0 gap-1">
                                    <span class="block truncate text-sm font-medium">{{ $transcription->titre ?? $transcription->url_origine ?? '—' }}</span>
                                    <span class="flex flex-wrap items-center gap-x-2.5 gap-y-1 font-mono text-[11px] text-texte-discret">
                                        <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-px font-medium whitespace-nowrap {{ $pastille['classe'] }}">
                                            <span aria-hidden="true" class="size-[5px] rounded-full bg-current {{ $pastille['pulse'] ? 'vx-pulsation' : '' }}"></span>
                                            {{ __('interface.statuts.'.$transcription->statut->value) }}
                                        </span>
                                        <span>{{ $transcription->duree_secondes !== null ? \App\Support\Horodatage::format($transcription->duree_secondes * 1000) : '—' }}</span>
                                        <time datetime="{{ $transcription->created_at->toIso8601String() }}">{{ \App\Support\Formats::dateListe($transcription->created_at) }}</time>
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
