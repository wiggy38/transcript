{{-- Historique (cahier F5), maquette « Historique - Nocturne » : liste triée par date et paginée, recherche plein texte
     (Pro et Pack). En Gratuit, le champ reste visible et un bandeau propose le passage au Pro. --}}
@php
    use App\Support\Horodatage;

    // Sigles de la miniature (maquette : YT, TT, FI) et couleurs du statut, comme sur le tableau de bord.
    $sigles = ['youtube' => 'YT', 'tiktok' => 'TT', 'fichier' => 'FI'];
@endphp

<x-layouts::app :titre="__('historique.titre')" actif="historique">
    <div class="flex flex-col gap-6">
        @if (session('statut') === 'transcription-supprimee')
            <p role="status" class="m-0 flex items-center gap-2.5 rounded-bouton border border-succes/40 bg-succes/10 px-3.5 py-3 text-sm text-texte">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" class="shrink-0 text-succes">
                    <circle cx="12" cy="12" r="9"></circle><path d="m8 12.5 2.5 2.5L16 9.5"></path>
                </svg>
                {{ __('historique.statut.supprimee') }}
            </p>
        @endif

        <section aria-labelledby="titre-historique" class="grid gap-1.5">
            <h1 id="titre-historique" class="titre-h2 m-0">{{ __('historique.titre') }}</h1>
            <p class="m-0 text-[15px] text-texte-secondaire">{{ __('historique.sous_titre') }}</p>
        </section>

        <div class="grid gap-2.5">
            <form method="get" action="{{ route('historique') }}" role="search"
                  class="flex flex-wrap items-end gap-3 rounded-panneau border border-filet bg-panneau p-4 shadow-panneau">
                <div class="grid min-w-0 flex-[999_1_280px] gap-1.5">
                    <label for="recherche-historique" class="text-sm font-semibold">{{ __('historique.recherche.libelle') }}</label>
                    <div class="relative">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true" focusable="false"
                             class="absolute top-1/2 left-3.5 -translate-y-1/2 text-texte-discret">
                            <circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.5-3.5"></path>
                        </svg>
                        <input id="recherche-historique" name="q" type="search" value="{{ $terme }}" aria-describedby="aide-recherche"
                               class="min-h-11 w-full min-w-0 rounded-bouton border border-filet-contour bg-fond py-0 pr-3.5 pl-10 text-[15px] text-texte outline-none focus-visible:border-accent">
                    </div>
                </div>

                <div class="grid min-w-0 flex-[1_1_200px] gap-1.5">
                    <label for="tri-historique" class="text-sm font-semibold">{{ __('historique.tri.libelle') }}</label>
                    <select id="tri-historique" name="tri"
                            class="min-h-11 rounded-bouton border border-filet-contour bg-fond px-3 text-sm text-texte">
                        <option value="recent" @selected($tri === 'recent')>{{ __('historique.tri.recent') }}</option>
                        <option value="ancien" @selected($tri === 'ancien')>{{ __('historique.tri.ancien') }}</option>
                    </select>
                </div>

                <button type="submit"
                        class="min-h-11 flex-none cursor-pointer rounded-bouton border border-filet-contour bg-transparent px-5 text-sm font-semibold text-texte hover:border-accent-texte hover:text-accent-texte">
                    {{ __('historique.recherche.bouton') }}
                </button>

                <p id="aide-recherche" class="m-0 flex-[1_1_100%] text-[13px] text-texte-discret">{{ __('historique.recherche.aide') }}</p>
            </form>

            @unless ($peutRechercher)
                <p class="m-0 flex flex-wrap items-center gap-x-2.5 gap-y-1.5 rounded-bouton bg-panneau-eleve px-3.5 py-3 text-sm text-texte">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" class="shrink-0 text-texte-secondaire">
                        <rect x="4.5" y="10.5" width="15" height="10" rx="2"></rect><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"></path>
                    </svg>
                    <span class="flex-[1_1_260px]">{{ __('historique.recherche.gratuit') }}</span>
                    <button type="button" aria-haspopup="dialog" x-on:click="$dispatch('voxia-ouvrir', { id: 'upgrade-recherche' })"
                            class="inline-flex min-h-9 cursor-pointer items-center whitespace-nowrap rounded-[10px] bg-accent px-3.5 text-[13px] font-semibold text-sur-accent hover:opacity-90">{{ __('historique.recherche.passer_pro') }}</button>
                </p>
            @endunless
        </div>

        {{-- Déclencheur « recherche » (cahier §2) : s'ouvre dès qu'un terme est saisi en Gratuit. --}}
        @unless ($peutRechercher)
            <x-upgrade.fenetre id="upgrade-recherche" :titre="__('upgrade.recherche.titre')" :retour="route('historique', $terme !== '' ? ['q' => $terme] : [])"
                :ouvrir-au-chargement="$terme !== ''">
                <p class="text-texte">{{ __('upgrade.recherche.texte') }}</p>
            </x-upgrade.fenetre>
        @endunless

        @if ($resultats !== null)
            <section aria-labelledby="titre-resultats" class="grid gap-3">
                <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-2">
                    <h2 id="titre-resultats" class="m-0 font-titre text-[17px] font-semibold tracking-[-0.02em]">
                        {{ trans_choice('historique.recherche.resultats', $resultats->total(), ['terme' => $terme, 'nombre' => $resultats->total()]) }}
                    </h2>
                    <a href="{{ route('historique') }}" class="text-[13px] text-texte-secondaire no-underline hover:text-accent-texte">{{ __('historique.recherche.effacer') }}</a>
                </div>

                @if ($resultats->isNotEmpty())
                    <ol aria-label="{{ __('historique.recherche.liste_aria') }}"
                        class="m-0 list-none divide-y divide-filet overflow-hidden rounded-panneau border border-filet bg-panneau p-0 shadow-panneau">
                        @foreach ($resultats as $resultat)
                            <li class="grid gap-2 px-[18px] py-4">
                                <p class="m-0 flex flex-wrap items-center gap-x-3 gap-y-1">
                                    <a href="{{ route('transcriptions.show', ['transcription' => $resultat->transcription, 't' => $resultat->debutMs]) }}"
                                       aria-label="{{ __('historique.recherche.aller') }} : {{ $resultat->transcription->titre ?? __('historique.sans_titre') }}"
                                       class="inline-flex min-h-7 items-center gap-1.5 rounded-pilule bg-surlignage px-2.5 font-mono text-[13px] font-medium tabular-nums text-surlignage-temps no-underline">
                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M7 4.5v15l12-7.5z"></path></svg>{{ Horodatage::format($resultat->debutMs) }}
                                    </a>
                                    <span class="min-w-0 font-semibold [overflow-wrap:anywhere]">{{ $resultat->transcription->titre ?? __('historique.sans_titre') }}</span>
                                    @if ($resultat->transcription->scriptAnalysis?->statut === \App\Enums\StatutAnalyseScript::Termine)
                                        <span class="font-mono text-[11px] text-accent-texte">{{ __('analyse.pastille') }}</span>
                                    @endif
                                </p>
                                {{-- Extrait échappé côté serveur ; seules les balises <mark> du surlignage sont ajoutées. Le surlignage suit la maquette (vert citron). --}}
                                <p class="m-0 text-sm text-texte-secondaire [overflow-wrap:anywhere] [&_mark]:rounded-[4px] [&_mark]:bg-accent [&_mark]:px-[3px] [&_mark]:text-sur-accent">{!! $resultat->extraitHtml !!}</p>
                            </li>
                        @endforeach
                    </ol>

                    @include('app.partials.pagination', ['paginateur' => $resultats])
                @else
                    <div class="grid justify-items-center gap-1.5 rounded-panneau border border-dashed border-filet-contour p-6 text-center">
                        <p class="m-0 text-sm text-texte-secondaire">{{ __('historique.vide_recherche') }}</p>
                    </div>
                @endif
            </section>
        @else
            <section aria-labelledby="titre-liste" class="grid gap-4">
                <h2 id="titre-liste" class="sr-only">{{ __('historique.titre') }}</h2>

                @if ($transcriptions->isEmpty())
                    <div class="grid justify-items-center gap-3.5 rounded-panneau border border-dashed border-filet-contour px-6 py-12 text-center">
                        <span aria-hidden="true" class="grid size-12 place-items-center rounded-[14px] bg-panneau-eleve text-texte-secondaire">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                                <path d="M4 6h3"></path><path d="M10 6h10"></path><path d="M4 12h3"></path><path d="M10 12h8"></path><path d="M4 18h3"></path><path d="M10 18h10"></path>
                            </svg>
                        </span>
                        <p class="m-0 max-w-[30em] text-[15px] text-texte-secondaire">{{ __('historique.vide') }}</p>
                        <a href="{{ route('tableau-de-bord') }}"
                           class="inline-flex min-h-11 items-center rounded-bouton bg-accent px-[18px] text-sm font-semibold text-sur-accent no-underline hover:opacity-90">{{ __('interface.application.nouvelle') }}</a>
                    </div>
                @else
                    <ul class="m-0 list-none divide-y divide-filet overflow-hidden rounded-panneau border border-filet bg-panneau p-0 shadow-panneau">
                        @foreach ($transcriptions as $transcription)
                            @php
                                $plateforme = $transcription->source->value;
                                $titre = $transcription->titre ?? __('historique.sans_titre');
                                $duree = $transcription->duree_secondes !== null ? Horodatage::format($transcription->duree_secondes * 1000) : '—';
                            @endphp
                            <li class="flex flex-wrap items-center gap-x-4 gap-y-3 px-[18px] py-3.5 transition-colors duration-150 hover:bg-[color-mix(in_oklab,var(--vx-surface-2)_60%,transparent)]">
                                <span aria-hidden="true" class="relative block h-[54px] w-24 shrink-0 overflow-hidden rounded-[10px] border border-filet bg-panneau-eleve">
                                    @if ($transcription->miniature_url)
                                        <img src="{{ $transcription->miniature_url }}" alt="" width="96" height="54" loading="lazy" referrerpolicy="no-referrer"
                                             class="size-full object-cover">
                                    @else
                                        <span class="grid size-full place-items-center font-mono text-[11px] font-medium text-texte-discret">
                                            {{ $sigles[$plateforme] ?? strtoupper(substr($plateforme, 0, 2)) }}
                                        </span>
                                    @endif
                                    <span class="absolute right-1 bottom-1 rounded-[4px] bg-fond/85 px-1 font-mono text-[10px] text-texte">{{ $duree }}</span>
                                </span>

                                <div class="grid min-w-0 flex-[999_1_260px] gap-1">
                                    <a href="{{ route('transcriptions.show', $transcription) }}"
                                       class="block truncate text-[15px] font-semibold text-texte no-underline hover:text-accent-texte">{{ $titre }}</a>

                                    <p class="m-0 flex flex-wrap gap-x-3.5 gap-y-0.5 font-mono text-xs text-texte-discret">
                                        <span>{{ __('historique.plateformes.'.$plateforme) }}</span>
                                        <span>
                                            <span class="sr-only">{{ __('historique.colonnes.duree') }} : </span>{{ $duree }}
                                        </span>
                                        <time datetime="{{ $transcription->created_at->toIso8601String() }}">
                                            <span class="sr-only">{{ __('historique.colonnes.date') }} : </span>{{ \App\Support\Formats::dateHeure($transcription->created_at) }}
                                        </time>
                                        @if ($joursConservation !== null)
                                            <span class="text-avertissement">{{ __('historique.suppression_prevue', ['date' => \App\Support\Formats::date($transcription->created_at->copy()->addDays($joursConservation))]) }}</span>
                                        @endif
                                    </p>
                                </div>

                                <div class="flex flex-[1_0_auto] flex-wrap items-center justify-end gap-x-3.5 gap-y-2">
                                    @if ($transcription->scriptAnalysis?->statut === \App\Enums\StatutAnalyseScript::Termine)
                                        <span class="font-mono text-[11px] whitespace-nowrap text-accent-texte">{{ __('analyse.pastille') }}</span>
                                    @endif
                                    <x-statut-transcription :statut="$transcription->statut" />
                                    <a href="{{ route('transcriptions.supprimer', $transcription) }}"
                                       class="inline-flex min-h-9 items-center gap-1.5 rounded-[10px] px-2.5 text-[13px] text-texte-secondaire no-underline hover:bg-erreur/10 hover:text-erreur">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                            <path d="M4 7h16"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M6 7l1 13h10l1-13"></path><path d="M9 7V4h6v3"></path>
                                        </svg>
                                        {{ __('historique.supprimer') }}<span class="sr-only"> : {{ __('historique.supprimer_aria', ['titre' => $titre]) }}</span>
                                    </a>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    @include('app.partials.pagination', ['paginateur' => $transcriptions])
                @endif
            </section>
        @endif
    </div>
</x-layouts::app>
