{{-- Historique (cahier F5) : liste triée par date et paginée, recherche plein texte (Pro et Pack).
     En Gratuit, le champ reste visible et renvoie vers le passage au Pro. --}}
@php
    use App\Support\Horodatage;
@endphp

<x-layouts::app :titre="__('historique.titre')" actif="historique">
    @if (session('statut') === 'transcription-supprimee')
        <p role="status" class="mb-8 border-l-2 border-accent py-1 pl-3 text-sm text-texte">{{ __('historique.statut.supprimee') }}</p>
    @endif

    <section aria-labelledby="titre-historique">
        <h1 id="titre-historique" class="text-3xl font-semibold tracking-tight">{{ __('historique.titre') }}</h1>
        <p class="mt-2 text-texte-secondaire">{{ __('historique.sous_titre') }}</p>
    </section>

    <form method="get" action="{{ route('historique') }}" role="search" class="mt-8 grid gap-5 lg:grid-cols-[minmax(0,1fr)_auto_auto] lg:items-end">
        <x-champ id="recherche-historique" name="q" type="search" :label="__('historique.recherche.libelle')"
            :value="$terme" :aide="__('historique.recherche.aide')" />

        <div class="flex min-w-0 flex-col gap-2">
            <label for="tri-historique" class="font-mono text-xs uppercase tracking-wide text-texte-discret">{{ __('historique.tri.libelle') }}</label>
            <select id="tri-historique" name="tri" class="min-h-11 rounded-champ border border-filet-contour bg-panneau px-3 text-texte">
                <option value="recent" @selected($tri === 'recent')>{{ __('historique.tri.recent') }}</option>
                <option value="ancien" @selected($tri === 'ancien')>{{ __('historique.tri.ancien') }}</option>
            </select>
        </div>

        <x-bouton type="submit" variante="filet">{{ __('historique.recherche.bouton') }}</x-bouton>
    </form>

    @unless ($peutRechercher)
        <p class="mt-3 border-l-2 border-accent py-1 pl-3 text-sm text-texte">
            {{ __('historique.recherche.gratuit') }}
            <button type="button" aria-haspopup="dialog" x-on:click="$dispatch('voxia-ouvrir', { id: 'upgrade-recherche' })"
                class="ml-1 cursor-pointer text-accent hover:underline">{{ __('upgrade.recherche.titre') }}</button>
        </p>

        {{-- Déclencheur « recherche » (cahier §2) : s'ouvre dès qu'un terme est saisi en Gratuit. --}}
        <x-upgrade.fenetre id="upgrade-recherche" :titre="__('upgrade.recherche.titre')" :retour="route('historique', $terme !== '' ? ['q' => $terme] : [])"
            :ouvrir-au-chargement="$terme !== ''">
            <p class="text-texte">{{ __('upgrade.recherche.texte') }}</p>
        </x-upgrade.fenetre>
    @endunless

    @if ($resultats !== null)
        <section aria-labelledby="titre-resultats" class="mt-10">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h2 id="titre-resultats" class="text-lg font-semibold">
                    {{ trans_choice('historique.recherche.resultats', $resultats->total(), ['terme' => $terme, 'nombre' => $resultats->total()]) }}
                </h2>
                <a href="{{ route('historique') }}" class="text-sm text-texte-secondaire hover:text-accent">{{ __('historique.recherche.effacer') }}</a>
            </div>

            @if ($resultats->isNotEmpty())
                <ol aria-label="{{ __('historique.recherche.liste_aria') }}" class="mt-4 flex flex-col divide-y divide-filet rounded-panneau border border-filet bg-panneau">
                    @foreach ($resultats as $resultat)
                        <li class="px-5 py-4">
                            <p class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                                <a href="{{ route('transcriptions.show', ['transcription' => $resultat->transcription, 't' => $resultat->debutMs]) }}"
                                   aria-label="{{ __('historique.recherche.aller') }} : {{ $resultat->transcription->titre ?? __('historique.sans_titre') }}"
                                   class="font-mono text-sm tabular-nums text-accent no-underline hover:underline">{{ Horodatage::format($resultat->debutMs) }}</a>
                                <span class="min-w-0 font-medium [overflow-wrap:anywhere]">{{ $resultat->transcription->titre ?? __('historique.sans_titre') }}</span>
                                @if ($resultat->transcription->scriptAnalysis?->statut === \App\Enums\StatutAnalyseScript::Termine)
                                    <span class="font-mono text-xs text-accent">{{ __('analyse.pastille') }}</span>
                                @endif
                            </p>
                            {{-- Extrait échappé côté serveur ; seules les balises <mark> du surlignage sont ajoutées. --}}
                            <p class="mt-2 text-texte-secondaire [overflow-wrap:anywhere]">{!! $resultat->extraitHtml !!}</p>
                        </li>
                    @endforeach
                </ol>

                @include('app.partials.pagination', ['paginateur' => $resultats])
            @endif
        </section>
    @else
        <section aria-labelledby="titre-liste" class="mt-10">
            <h2 id="titre-liste" class="sr-only">{{ __('historique.titre') }}</h2>

            @if ($transcriptions->isEmpty())
                <p class="text-texte-secondaire">{{ __('historique.vide') }}</p>
            @else
                <ul class="flex flex-col divide-y divide-filet rounded-panneau border border-filet bg-panneau">
                    @foreach ($transcriptions as $transcription)
                        @php
                            $plateforme = $transcription->source->value;
                            $titre = $transcription->titre ?? __('historique.sans_titre');
                        @endphp
                        <li class="grid gap-4 px-5 py-4 sm:grid-cols-[96px_minmax(0,1fr)_auto] sm:items-center">
                            @if ($transcription->miniature_url)
                                <img src="{{ $transcription->miniature_url }}" alt="" width="96" height="54" loading="lazy" referrerpolicy="no-referrer"
                                     class="h-[54px] w-24 rounded-champ border border-filet object-cover">
                            @else
                                <span aria-hidden="true" class="flex h-[54px] w-24 items-center justify-center rounded-champ border border-filet bg-panneau-eleve font-mono text-xs text-texte-discret">
                                    {{ strtoupper(substr($plateforme, 0, 2)) }}
                                </span>
                            @endif

                            <div class="min-w-0">
                                <a href="{{ route('transcriptions.show', $transcription) }}"
                                   class="block truncate font-medium text-texte no-underline hover:text-accent">{{ $titre }}</a>

                                <p class="mt-1 flex flex-wrap gap-x-4 gap-y-1 font-mono text-xs text-texte-discret">
                                    <span>{{ __('historique.plateformes.'.$plateforme) }}</span>
                                    <span>
                                        <span class="sr-only">{{ __('historique.colonnes.duree') }} : </span>
                                        {{ $transcription->duree_secondes !== null ? Horodatage::format($transcription->duree_secondes * 1000) : '—' }}
                                    </span>
                                    <time datetime="{{ $transcription->created_at->toIso8601String() }}">
                                        <span class="sr-only">{{ __('historique.colonnes.date') }} : </span>{{ \App\Support\Formats::dateHeure($transcription->created_at) }}
                                    </time>
                                    @if ($joursConservation !== null)
                                        <span>{{ __('historique.suppression_prevue', ['date' => \App\Support\Formats::date($transcription->created_at->copy()->addDays($joursConservation))]) }}</span>
                                    @endif
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-4 sm:justify-end">
                                <x-badge-statut :statut="$transcription->statut" />
                                @if ($transcription->scriptAnalysis?->statut === \App\Enums\StatutAnalyseScript::Termine)
                                    <span class="font-mono text-xs text-accent">{{ __('analyse.pastille') }}</span>
                                @endif
                                <a href="{{ route('transcriptions.supprimer', $transcription) }}"
                                   class="text-sm text-texte-secondaire no-underline hover:text-erreur">
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
</x-layouts::app>
