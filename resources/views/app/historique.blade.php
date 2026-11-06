{{-- Historique (cahier F5), maquette « Historique - Nocturne » : liste triée par date et paginée, recherche plein texte
     (Pro et Pack). En Gratuit, le champ reste visible et un bandeau propose le passage au Pro.
     Bibliothèque (V2) : colonne latérale, filtres dans l'URL et actions groupées, pour les comptes qui classent. --}}
@php
    use App\Enums\SourceTranscription;
    use App\Enums\TypeEtiquette;
    use App\Support\Horodatage;
    use Illuminate\Support\Facades\Lang;

    // Sigles de la miniature (maquette : YT, TT, FI) et couleurs du statut, comme sur le tableau de bord.
    $sigles = ['youtube' => 'YT', 'tiktok' => 'TT', 'fichier' => 'FI'];

    // Message après une action de la bibliothèque (clés dans lang/*/bibliotheque.php, « statuts »).
    $messageBibliotheque = session('statut') && Lang::has('bibliotheque.statuts.'.session('statut'))
        ? __('bibliotheque.statuts.'.session('statut'))
        : null;
@endphp

<x-layouts::app :titre="__('historique.titre')" actif="historique">
    <div class="grid gap-6 {{ $bibliotheque ? 'lg:grid-cols-[240px_minmax(0,1fr)] lg:items-start' : '' }}">
        @if ($bibliotheque)
            <aside aria-labelledby="titre-bibliotheque"
                   class="grid content-start gap-5 rounded-panneau border border-filet bg-panneau p-4 shadow-panneau">
                <h2 id="titre-bibliotheque" class="m-0 font-titre text-[15px] font-semibold tracking-[-0.02em]">{{ __('bibliotheque.navigation.titre') }}</h2>

                <nav aria-label="{{ __('bibliotheque.navigation.aria') }}" class="grid gap-5 text-sm">
                    <ul class="m-0 grid list-none gap-0.5 p-0">
                        <li>
                            <a href="{{ route('historique') }}" class="flex items-center justify-between rounded-bouton px-2.5 py-2 text-texte no-underline hover:bg-panneau-eleve">
                                <span>{{ __('bibliotheque.navigation.toutes') }}</span>
                                <span class="font-mono text-xs text-texte-discret tabular-nums">{{ $navigation['total'] }}</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('historique', ['dossier' => \App\Services\Bibliotheque\FiltresBibliotheque::SANS_DOSSIER]) }}"
                               class="flex items-center justify-between rounded-bouton px-2.5 py-2 text-texte no-underline hover:bg-panneau-eleve">
                                <span>{{ __('bibliotheque.navigation.non_classees') }}</span>
                                <span class="font-mono text-xs text-texte-discret tabular-nums">{{ $navigation['sans_dossier'] }}</span>
                            </a>
                        </li>
                    </ul>

                    <section class="grid gap-2">
                        <h3 class="m-0 font-mono text-xs text-texte-discret">{{ __('bibliotheque.navigation.dossiers') }}</h3>
                        @forelse ($navigation['dossiers'] as $dossier)
                            <ul class="m-0 grid list-none gap-0.5 p-0">
                                <li>
                                    <a href="{{ route('historique', ['dossier' => $dossier->id]) }}" class="flex items-center justify-between gap-2 rounded-bouton px-2.5 py-2 text-texte no-underline hover:bg-panneau-eleve">
                                        <span class="min-w-0 truncate">{{ $dossier->nom }}</span>
                                        <span class="font-mono text-xs text-texte-discret tabular-nums">{{ $dossier->transcriptions_count }}</span>
                                    </a>
                                </li>
                            </ul>
                        @empty
                            <p class="m-0 text-[13px] text-texte-discret">{{ __('bibliotheque.navigation.aucun_dossier') }}</p>
                        @endforelse
                    </section>

                    <section class="grid gap-2">
                        <h3 class="m-0 font-mono text-xs text-texte-discret">{{ __('bibliotheque.navigation.chaines') }}</h3>
                        @forelse ($navigation['chaines'] as $chaine)
                            <ul class="m-0 grid list-none gap-0.5 p-0">
                                <li>
                                    <a href="{{ route('historique', ['chaine' => $chaine->chaine]) }}" class="flex items-center justify-between gap-2 rounded-bouton px-2.5 py-2 text-texte no-underline hover:bg-panneau-eleve">
                                        <span class="min-w-0 truncate">{{ $chaine->chaine }}</span>
                                        <span class="font-mono text-xs text-texte-discret tabular-nums">{{ $chaine->nombre }}</span>
                                    </a>
                                </li>
                            </ul>
                        @empty
                            <p class="m-0 text-[13px] text-texte-discret">{{ __('bibliotheque.navigation.aucune_chaine') }}</p>
                        @endforelse
                    </section>

                    @foreach ($etiquettesTypes as $type)
                        <section class="grid gap-2">
                            <h3 class="m-0 font-mono text-xs text-texte-discret">{{ $type->libelle() }}</h3>
                            @forelse ($navigation['etiquettes'][$type->value] as $etiquette)
                                <ul class="m-0 grid list-none gap-0.5 p-0">
                                    <li>
                                        <a href="{{ route('historique', ['etiquettes' => [$etiquette->id]]) }}" class="flex items-center justify-between gap-2 rounded-bouton px-2.5 py-2 text-texte no-underline hover:bg-panneau-eleve">
                                            <span class="min-w-0 truncate">{{ $etiquette->nom }}</span>
                                            <span class="font-mono text-xs text-texte-discret tabular-nums">{{ $etiquette->transcriptions_count }}</span>
                                        </a>
                                    </li>
                                </ul>
                            @empty
                                <p class="m-0 text-[13px] text-texte-discret">{{ __('bibliotheque.navigation.aucune_etiquette') }}</p>
                            @endforelse
                        </section>
                    @endforeach
                </nav>

                @if ($peutClasser)
                    <a href="{{ route('bibliotheque.gerer') }}"
                       class="inline-flex min-h-11 items-center justify-center rounded-bouton border border-filet-contour px-3 text-sm font-semibold text-texte no-underline hover:border-accent-texte hover:text-accent-texte">{{ __('bibliotheque.navigation.gerer') }}</a>
                @endif
            </aside>
        @endif

        <div class="flex min-w-0 flex-col gap-6">
            @if (session('statut') === 'transcription-supprimee')
                <p role="status" class="m-0 flex items-center gap-2.5 rounded-bouton border border-succes/40 bg-succes/10 px-3.5 py-3 text-sm text-texte">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" class="shrink-0 text-succes">
                        <circle cx="12" cy="12" r="9"></circle><path d="m8 12.5 2.5 2.5L16 9.5"></path>
                    </svg>
                    {{ __('historique.statut.supprimee') }}
                </p>
            @elseif ($messageBibliotheque)
                <p role="status" class="m-0 flex items-center gap-2.5 rounded-bouton border border-succes/40 bg-succes/10 px-3.5 py-3 text-sm text-texte">{{ $messageBibliotheque }}</p>
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
                            <input id="recherche-historique" name="q" type="search" value="{{ $terme }}" placeholder="{{ __('historique.recherche.aide') }}"
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
                            class="min-h-11 flex-none cursor-pointer rounded-bouton bg-accent px-6 text-sm font-semibold text-sur-accent transition-opacity hover:opacity-90 focus-visible:outline-2 focus-visible:outline-accent">
                        {{ __('historique.recherche.bouton') }}
                    </button>

                    @if ($bibliotheque)
                        @php
                            // Nombre de filtres actifs, affiché sur le résumé replié.
                            $nombreFiltres = count($filtre['etiquettes']) + ($filtre['produit'] !== null ? 1 : 0) + ($filtre['plateforme'] !== null ? 1 : 0);
                        @endphp
                        <details class="group w-full min-w-0" @if ($filtresActifs) open @endif>
                            <summary class="flex min-h-10 w-full cursor-pointer list-none items-center justify-between gap-3 rounded-bouton border border-filet-contour bg-fond px-3.5 text-sm font-semibold text-texte transition-colors hover:border-accent-texte focus-visible:outline-2 focus-visible:outline-accent [&::-webkit-details-marker]:hidden">
                                <span>{{ __('bibliotheque.filtres.legende') }}</span>
                                <span class="flex items-center gap-2">
                                    @if ($nombreFiltres > 0)
                                        <span class="grid min-w-5 place-items-center rounded-pilule bg-accent px-1.5 font-mono text-[11px] font-semibold text-sur-accent">{{ $nombreFiltres }}</span>
                                    @endif
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" focusable="false" class="text-texte-discret transition-transform group-open:rotate-180"><path d="m6 9 6 6 6-6"></path></svg>
                                </span>
                            </summary>
                            <fieldset class="m-0 mt-3 grid w-full min-w-0 gap-3 border-0 p-0 sm:grid-cols-2 lg:grid-cols-3">
                            <legend class="sr-only">{{ __('bibliotheque.filtres.legende') }}</legend>

                            @php
                                // Étiquettes de niche, concurrent et libre ; les produits ont leur propre filtre.
                                $etiquettesHorsProduit = collect($etiquettesTypes)
                                    ->reject(fn ($type) => $type === TypeEtiquette::Produit)
                                    ->filter(fn ($type) => $navigation['etiquettes'][$type->value]->isNotEmpty());
                                $produits = $navigation['etiquettes'][TypeEtiquette::Produit->value];
                                $produitChoisi = $filtre['produit'] !== null ? $produits->firstWhere('id', $filtre['produit']) : null;
                                $nombreEtiquettes = count($filtre['etiquettes']);

                                // Un menu déroulant : résumé cliquable, panneau de choix. Sans JavaScript, « details » suffit.
                                $menu = 'flex min-h-9 w-full cursor-pointer list-none items-center justify-between gap-3 rounded-bouton border border-filet-contour bg-fond px-3 text-[13px] text-texte transition-colors hover:border-accent-texte focus-visible:outline-2 focus-visible:outline-accent [&::-webkit-details-marker]:hidden';
                                $panneau = 'absolute top-full left-0 z-20 mt-1.5 grid max-h-72 w-[min(18rem,calc(100vw-2.5rem))] gap-3 overflow-y-auto rounded-panneau border border-filet bg-panneau-eleve p-2.5 shadow-panneau';
                                $choix = 'flex min-h-9 cursor-pointer items-center gap-2.5 rounded-[10px] px-2 text-[13px] text-texte transition-colors hover:bg-panneau';
                                $titreTypes = 'm-0 px-2 font-mono text-[10px] tracking-wide text-texte-discret uppercase';
                            @endphp

                            <div class="grid min-w-0 gap-1.5 lg:col-span-3">
                                <div class="flex flex-wrap items-start gap-x-5 gap-y-4">
                                    {{-- Étiquettes --}}
                                    <div class="grid w-44 gap-1.5">
                                        <p class="m-0 font-mono text-[11px] text-texte-discret">{{ __('bibliotheque.filtres.etiquettes') }}</p>
                                        <details name="filtres-bibliotheque" class="group relative">
                                            <summary class="{{ $menu }}">
                                                <span class="truncate">{{ $nombreEtiquettes === 0 ? __('bibliotheque.filtres.etiquettes_toutes') : __('bibliotheque.filtres.etiquettes_nombre', ['nombre' => $nombreEtiquettes]) }}</span>
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" focusable="false" class="shrink-0 text-texte-discret transition-transform group-open:rotate-180"><path d="m6 9 6 6 6-6"></path></svg>
                                            </summary>
                                            <div class="{{ $panneau }}">
                                                <p class="m-0 px-2 text-[12px] text-texte-discret">{{ __('bibliotheque.filtres.etiquettes_aide') }}</p>
                                                @foreach ($etiquettesHorsProduit as $type)
                                                    <fieldset class="m-0 grid gap-0.5 border-0 p-0">
                                                        <legend class="{{ $titreTypes }}">{{ $type->libelle() }}</legend>
                                                        @foreach ($navigation['etiquettes'][$type->value] as $etiquette)
                                                            <label class="{{ $choix }}">
                                                                <input type="checkbox" name="etiquettes[]" value="{{ $etiquette->id }}" @checked(in_array($etiquette->id, $filtre['etiquettes'], true))
                                                                       class="size-4 shrink-0 accent-accent">
                                                                <span class="min-w-0 truncate">{{ $etiquette->nom }}</span>
                                                            </label>
                                                        @endforeach
                                                    </fieldset>
                                                @endforeach
                                            </div>
                                        </details>
                                    </div>

                                    {{-- Produit : un seul choix --}}
                                    <div class="grid w-44 gap-1.5">
                                        <p class="m-0 font-mono text-[11px] text-texte-discret">{{ __('bibliotheque.filtres.produit') }}</p>
                                        <details name="filtres-bibliotheque" class="group relative">
                                            <summary class="{{ $menu }}">
                                                <span class="truncate">{{ $produitChoisi?->nom ?? __('bibliotheque.filtres.produit_tous') }}</span>
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" focusable="false" class="shrink-0 text-texte-discret transition-transform group-open:rotate-180"><path d="m6 9 6 6 6-6"></path></svg>
                                            </summary>
                                            <div class="{{ $panneau }}">
                                                <fieldset class="m-0 grid gap-0.5 border-0 p-0">
                                                    <legend class="{{ $titreTypes }}">{{ TypeEtiquette::Produit->libelle() }}</legend>
                                                    <label class="{{ $choix }}">
                                                        <input type="radio" name="produit" value="" @checked($filtre['produit'] === null) class="size-4 shrink-0 accent-accent">
                                                        <span class="min-w-0 truncate">{{ __('bibliotheque.filtres.produit_tous') }}</span>
                                                    </label>
                                                    @foreach ($produits as $produit)
                                                        <label class="{{ $choix }}">
                                                            <input type="radio" name="produit" value="{{ $produit->id }}" @checked($filtre['produit'] === $produit->id) class="size-4 shrink-0 accent-accent">
                                                            <span class="min-w-0 truncate">{{ $produit->nom }}</span>
                                                        </label>
                                                    @endforeach
                                                </fieldset>
                                            </div>
                                        </details>
                                    </div>

                                    {{-- Plateforme : sélecteur segmenté --}}
                                    <fieldset class="m-0 grid min-w-0 gap-1.5 border-0 p-0">
                                        <legend class="mb-1.5 font-mono text-[11px] text-texte-discret">{{ __('bibliotheque.filtres.plateforme') }}</legend>
                                        <div class="inline-flex flex-wrap gap-0.5 rounded-pilule border border-filet-contour bg-fond p-0.5">
                                            <label class="relative">
                                                <input type="radio" name="plateforme" value="" @checked($filtre['plateforme'] === null) class="peer sr-only">
                                                <span class="inline-flex min-h-8 cursor-pointer items-center rounded-pilule px-3 text-[13px] text-texte-secondaire transition-colors hover:text-texte peer-checked:bg-accent peer-checked:font-semibold peer-checked:text-sur-accent peer-focus-visible:outline-2 peer-focus-visible:outline-accent">{{ __('bibliotheque.filtres.plateforme_toutes') }}</span>
                                            </label>
                                            @foreach (SourceTranscription::cases() as $source)
                                                <label class="relative">
                                                    <input type="radio" name="plateforme" value="{{ $source->value }}" @checked($filtre['plateforme'] === $source->value) class="peer sr-only">
                                                    <span class="inline-flex min-h-8 cursor-pointer items-center rounded-pilule px-3 text-[13px] text-texte-secondaire transition-colors hover:text-texte peer-checked:bg-accent peer-checked:font-semibold peer-checked:text-sur-accent peer-focus-visible:outline-2 peer-focus-visible:outline-accent">{{ __('historique.plateformes.'.$source->value) }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </fieldset>
                                </div>
                            </div>

                            <fieldset class="m-0 grid min-w-0 gap-1.5 border-0 p-0 sm:col-span-2 lg:col-span-2">
                                <legend class="text-sm font-semibold">{{ __('bibliotheque.filtres.portee') }}</legend>
                                <div class="flex flex-wrap gap-x-5 gap-y-2 text-sm text-texte">
                                    <label class="inline-flex min-h-11 items-center gap-2">
                                        <input type="radio" name="portee" value="tout" @checked($filtre['portee'] === 'tout') class="size-4 accent-accent">
                                        {{ __('bibliotheque.filtres.portee_tout') }}
                                    </label>
                                    <label class="inline-flex min-h-11 items-center gap-2">
                                        <input type="radio" name="portee" value="filtre" @checked($filtre['portee'] === 'filtre') class="size-4 accent-accent">
                                        {{ __('bibliotheque.filtres.portee_filtre') }}
                                    </label>
                                </div>
                            </fieldset>

                            <div class="flex items-end">
                                <a href="{{ route('historique') }}" class="inline-flex min-h-11 items-center text-sm text-texte-secondaire no-underline hover:text-accent-texte">{{ __('bibliotheque.filtres.reinitialiser') }}</a>
                            </div>
                        </fieldset>
                        </details>
                    @endif
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

            {{-- Déclencheur « classer » : le bouton Classer des listes ouvre cette fenêtre hors Pro et Business. --}}
            @unless ($peutClasser)
                <x-upgrade.fenetre id="upgrade-classement" :titre="__('bibliotheque.upgrade.titre')" :retour="route('historique')" :plans="['pro', 'business']">
                    <p class="text-texte">{{ __('bibliotheque.upgrade.texte') }}</p>
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
                                        @if ($resultat->estDuResume() || $resultat->estUneTraduction())
                                            {{-- Trouvé dans le résumé ou une traduction : pas de passage précis, la transcription s'ouvre au début. --}}
                                            <a href="{{ route('transcriptions.show', ['transcription' => $resultat->transcription]) }}"
                                               aria-label="{{ __('historique.recherche.aller_resume') }} : {{ $resultat->transcription->titre ?? __('historique.sans_titre') }}"
                                               class="inline-flex min-h-7 items-center gap-1.5 rounded-pilule border border-filet-contour px-2.5 font-mono text-[13px] font-medium text-texte no-underline">
                                                {{ $resultat->estUneTraduction()
                                                    ? __('historique.recherche.dans_traduction', ['langue' => __('traduction.langues.'.$resultat->langueTraduction)])
                                                    : __('historique.recherche.dans_resume') }}
                                            </a>
                                        @else
                                            <a href="{{ route('transcriptions.show', ['transcription' => $resultat->transcription, 't' => $resultat->debutMs]) }}"
                                               aria-label="{{ __('historique.recherche.aller') }} : {{ $resultat->transcription->titre ?? __('historique.sans_titre') }}"
                                               class="inline-flex min-h-7 items-center gap-1.5 rounded-pilule bg-surlignage px-2.5 font-mono text-[13px] font-medium tabular-nums text-surlignage-temps no-underline">
                                                <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M7 4.5v15l12-7.5z"></path></svg>{{ Horodatage::format($resultat->debutMs) }}
                                            </a>
                                        @endif
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
                            @if ($filtresActifs)
                                <p class="m-0 max-w-[30em] text-[15px] text-texte-secondaire">{{ __('historique.vide_recherche') }}</p>
                                <a href="{{ route('historique') }}" class="text-sm text-accent-texte no-underline">{{ __('bibliotheque.filtres.reinitialiser') }}</a>
                            @else
                                <p class="m-0 max-w-[30em] text-[15px] text-texte-secondaire">{{ __('historique.vide') }}</p>
                                <a href="{{ route('tableau-de-bord') }}"
                                   class="inline-flex min-h-11 items-center rounded-bouton bg-accent px-[18px] text-sm font-semibold text-sur-accent no-underline hover:opacity-90">{{ __('interface.application.nouvelle') }}</a>
                            @endif
                        </div>
                    @else
                        <form method="post" action="{{ route('bibliotheque.selection') }}" class="grid gap-3">
                            @csrf
                            <ul class="m-0 list-none divide-y divide-filet overflow-hidden rounded-panneau border border-filet bg-panneau p-0 shadow-panneau">
                                @foreach ($transcriptions as $transcription)
                                    @php
                                        $plateforme = $transcription->source->value;
                                        $titre = $transcription->titre ?? __('historique.sans_titre');
                                        $duree = $transcription->duree_secondes !== null ? Horodatage::format($transcription->duree_secondes * 1000) : '—';
                                    @endphp
                                    <li class="flex flex-wrap items-center gap-x-4 gap-y-3 px-[18px] py-3.5 transition-colors duration-150 hover:bg-[color-mix(in_oklab,var(--vx-surface-2)_60%,transparent)]">
                                        @if ($bibliotheque)
                                            <input type="checkbox" name="selection[]" value="{{ $transcription->id }}" id="selection-{{ $transcription->id }}"
                                                   class="size-5 shrink-0 accent-accent">
                                            <label for="selection-{{ $transcription->id }}" class="sr-only">{{ __('bibliotheque.selection.legende') }} : {{ $titre }}</label>
                                        @endif

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

                                            @if ($bibliotheque)
                                                <p class="m-0 flex flex-wrap gap-1.5 text-[12px]">
                                                    <span class="inline-flex min-h-6 items-center rounded-pilule border border-filet-contour px-2 text-texte-secondaire">
                                                        {{ $transcription->dossier?->nom ?? __('bibliotheque.non_classe') }}
                                                    </span>
                                                    @foreach ($transcription->etiquettes as $etiquette)
                                                        <span class="inline-flex min-h-6 items-center rounded-pilule bg-panneau-eleve px-2 text-texte-secondaire">
                                                            <span class="sr-only">{{ $etiquette->type->libelle() }} : </span>{{ $etiquette->nom }}
                                                        </span>
                                                    @endforeach
                                                </p>
                                            @endif
                                        </div>

                                        <div class="flex flex-[1_0_auto] flex-wrap items-center justify-end gap-x-3.5 gap-y-2">
                                            @if ($transcription->scriptAnalysis?->statut === \App\Enums\StatutAnalyseScript::Termine)
                                                <span class="font-mono text-[11px] whitespace-nowrap text-accent-texte">{{ __('analyse.pastille') }}</span>
                                            @endif
                                            <x-statut-transcription :statut="$transcription->statut" />
                                            @if ($peutClasser)
                                                <a href="{{ route('transcriptions.classement', $transcription) }}"
                                                   class="inline-flex min-h-9 items-center rounded-[10px] px-2.5 text-[13px] text-texte-secondaire no-underline hover:bg-panneau-eleve hover:text-accent-texte">
                                                    {{ __('bibliotheque.classer.bouton') }}<span class="sr-only"> : {{ $titre }}</span>
                                                </a>
                                            @else
                                                {{-- Hors Pro et Business, « Classer » propose le passage au Pro (fenêtre d'upgrade). --}}
                                                <button type="button" aria-haspopup="dialog" x-on:click="$dispatch('voxia-ouvrir', { id: 'upgrade-classement' })"
                                                        class="inline-flex min-h-9 cursor-pointer items-center rounded-[10px] px-2.5 text-[13px] text-texte-secondaire hover:bg-panneau-eleve hover:text-accent-texte">
                                                    {{ __('bibliotheque.classer.bouton') }}<span class="sr-only"> : {{ $titre }}</span>
                                                </button>
                                            @endif
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

                            @if ($bibliotheque)
                                <div class="flex flex-wrap items-end gap-3 rounded-panneau border border-filet bg-panneau p-4">
                                    <p class="m-0 flex-[1_1_100%] text-[13px] text-texte-discret">{{ __('bibliotheque.selection.aide') }}</p>

                                    @if ($peutClasser)
                                        <div class="grid min-w-0 gap-1.5">
                                            <label for="selection-dossier" class="text-sm font-semibold">{{ __('bibliotheque.selection.dossier') }}</label>
                                            <select id="selection-dossier" name="dossier_id" class="min-h-11 rounded-bouton border border-filet-contour bg-fond px-3 text-sm text-texte">
                                                <option value="">{{ __('bibliotheque.selection.choisir_dossier') }}</option>
                                                <option value="aucun">{{ __('bibliotheque.filtres.dossier_aucun') }}</option>
                                                @foreach ($navigation['dossiers'] as $dossier)
                                                    <option value="{{ $dossier->id }}">{{ $dossier->nom }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <button type="submit" name="action" value="deplacer"
                                                class="min-h-11 cursor-pointer rounded-bouton border border-filet-contour px-4 text-sm font-semibold text-texte hover:border-accent-texte hover:text-accent-texte">
                                            {{ __('bibliotheque.selection.deplacer') }}
                                        </button>

                                        <div class="grid min-w-0 gap-1.5">
                                            <label for="selection-etiquette" class="text-sm font-semibold">{{ __('bibliotheque.selection.etiquette') }}</label>
                                            <select id="selection-etiquette" name="etiquettes[]" class="min-h-11 rounded-bouton border border-filet-contour bg-fond px-3 text-sm text-texte">
                                                <option value="">{{ __('bibliotheque.selection.choisir_etiquette') }}</option>
                                                @foreach ($etiquettesTypes as $type)
                                                    @if ($navigation['etiquettes'][$type->value]->isNotEmpty())
                                                        <optgroup label="{{ $type->libelle() }}">
                                                            @foreach ($navigation['etiquettes'][$type->value] as $etiquette)
                                                                <option value="{{ $etiquette->id }}">{{ $etiquette->nom }}</option>
                                                            @endforeach
                                                        </optgroup>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>
                                        <button type="submit" name="action" value="etiqueter"
                                                class="min-h-11 cursor-pointer rounded-bouton border border-filet-contour px-4 text-sm font-semibold text-texte hover:border-accent-texte hover:text-accent-texte">
                                            {{ __('bibliotheque.selection.etiqueter') }}
                                        </button>
                                    @endif

                                    <button type="submit" name="action" value="supprimer"
                                            class="min-h-11 cursor-pointer rounded-bouton border border-erreur/40 px-4 text-sm font-semibold text-erreur hover:bg-erreur/10">
                                        {{ __('bibliotheque.selection.supprimer') }}
                                    </button>
                                </div>
                            @endif
                        </form>

                        @include('app.partials.pagination', ['paginateur' => $transcriptions])
                    @endif
                </section>
            @endif
        </div>
    </div>
</x-layouts::app>
