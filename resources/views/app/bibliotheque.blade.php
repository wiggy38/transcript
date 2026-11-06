{{-- Gestion des dossiers et des étiquettes (V2). Hors Pro et Business, les listes restent visibles sans formulaire
     (lecture seule, bandeau). Les droits viennent des Policies : @can, pas le plan lu à la main. --}}
@php
    use Illuminate\Support\Facades\Lang;

    $statut = session('statut');
    $message = $statut && Lang::has('bibliotheque.statuts.'.$statut) ? __('bibliotheque.statuts.'.$statut) : null;

    $champ = 'w-full min-h-11 min-w-0 rounded-bouton border border-filet-contour bg-fond px-3 text-[15px] text-texte';
    $bouton = 'inline-flex min-h-11 cursor-pointer items-center rounded-bouton bg-accent px-[18px] text-sm font-semibold text-sur-accent hover:opacity-90';
    $bouton2 = 'inline-flex min-h-11 cursor-pointer items-center rounded-bouton border border-filet-contour px-4 text-sm font-semibold text-texte hover:border-accent-texte hover:text-accent-texte';
@endphp

<x-layouts::app :titre="__('bibliotheque.gerer.titre')" actif="historique">
    <div class="grid gap-6">
        <a href="{{ route('historique') }}" class="justify-self-start text-sm text-texte-secondaire no-underline hover:text-accent-texte">{{ __('bibliotheque.gerer.retour') }}</a>

        <section aria-labelledby="titre-gerer" class="grid gap-1.5">
            <h1 id="titre-gerer" class="titre-h2 m-0">{{ __('bibliotheque.gerer.titre') }}</h1>
            <p class="m-0 text-[15px] text-texte-secondaire">{{ __('bibliotheque.gerer.sous_titre') }}</p>
        </section>

        @if ($message)
            <p role="status" class="m-0 rounded-bouton border border-succes/40 bg-succes/10 px-3.5 py-3 text-sm text-texte">{{ $message }}</p>
        @endif

        @unless ($peutClasser)
            <p class="m-0 rounded-bouton bg-panneau-eleve px-3.5 py-3 text-sm text-texte">{{ __('bibliotheque.gerer.lecture_seule') }}</p>
        @endunless

        @error('nom')
            <p role="alert" class="m-0 rounded-bouton border border-erreur/40 bg-erreur/10 px-3.5 py-3 text-sm text-texte">{{ $errors->first('nom') }}</p>
        @enderror

        {{-- Dossiers --}}
        <section aria-labelledby="titre-dossiers" class="grid gap-4 rounded-panneau border border-filet bg-panneau p-5 shadow-panneau">
            <div class="grid gap-1">
                <h2 id="titre-dossiers" class="m-0 font-titre text-[17px] font-semibold tracking-[-0.02em]">{{ __('bibliotheque.gerer.dossiers') }}</h2>
                <p class="m-0 text-sm text-texte-secondaire">{{ __('bibliotheque.gerer.dossiers_aide') }}</p>
            </div>

            @can('create', \App\Models\Dossier::class)
                <form method="post" action="{{ route('bibliotheque.dossiers.store') }}" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <div class="grid min-w-0 flex-[1_1_260px] gap-1.5">
                        <label for="nouveau-dossier" class="text-sm font-semibold">{{ __('bibliotheque.gerer.nouveau_dossier') }}</label>
                        <input id="nouveau-dossier" name="nom" type="text" maxlength="80" required class="{{ $champ }}">
                    </div>
                    <button type="submit" class="{{ $bouton }}">{{ __('bibliotheque.gerer.creer') }}</button>
                </form>
            @endcan

            <ul class="m-0 grid list-none gap-3 p-0">
                @forelse ($navigation['dossiers'] as $dossier)
                    <li class="grid gap-3 rounded-bouton border border-filet p-3.5">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <span class="font-semibold text-texte">{{ $dossier->nom }}</span>
                            <span class="font-mono text-xs text-texte-discret">{{ __('bibliotheque.gerer.nombre', ['nombre' => $dossier->transcriptions_count]) }}</span>
                        </div>

                        @can('update', $dossier)
                            <div class="flex flex-wrap items-end gap-2">
                                <form method="post" action="{{ route('bibliotheque.dossiers.update', $dossier) }}" class="flex flex-wrap items-end gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <label for="dossier-{{ $dossier->id }}" class="sr-only">{{ __('bibliotheque.gerer.nom') }}</label>
                                    <input id="dossier-{{ $dossier->id }}" name="nom" type="text" value="{{ $dossier->nom }}" maxlength="80" required class="{{ $champ }} sm:w-72">
                                    <button type="submit" class="{{ $bouton2 }}">{{ __('bibliotheque.gerer.renommer') }}</button>
                                </form>
                                <form method="post" action="{{ route('bibliotheque.dossiers.destroy', $dossier) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="{{ $bouton2 }} hover:border-erreur hover:text-erreur"
                                            aria-label="{{ __('bibliotheque.gerer.supprimer_aria', ['nom' => $dossier->nom]) }}">{{ __('bibliotheque.gerer.supprimer') }}</button>
                                </form>
                            </div>
                        @endcan
                    </li>
                @empty
                    <li class="text-sm text-texte-discret">{{ __('bibliotheque.gerer.vide') }}</li>
                @endforelse
            </ul>
        </section>

        {{-- Étiquettes --}}
        <section aria-labelledby="titre-etiquettes" class="grid gap-4 rounded-panneau border border-filet bg-panneau p-5 shadow-panneau">
            <div class="grid gap-1">
                <h2 id="titre-etiquettes" class="m-0 font-titre text-[17px] font-semibold tracking-[-0.02em]">{{ __('bibliotheque.gerer.etiquettes') }}</h2>
                <p class="m-0 text-sm text-texte-secondaire">{{ __('bibliotheque.gerer.etiquettes_aide') }}</p>
            </div>

            @can('create', \App\Models\Etiquette::class)
                <form method="post" action="{{ route('bibliotheque.etiquettes.store') }}" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <div class="grid min-w-0 flex-[1_1_240px] gap-1.5">
                        <label for="nouvelle-etiquette" class="text-sm font-semibold">{{ __('bibliotheque.gerer.nouvelle_etiquette') }}</label>
                        <input id="nouvelle-etiquette" name="nom" type="text" maxlength="80" required class="{{ $champ }}">
                    </div>
                    <div class="grid min-w-0 gap-1.5">
                        <label for="nouvelle-etiquette-type" class="text-sm font-semibold">{{ __('bibliotheque.gerer.type') }}</label>
                        <select id="nouvelle-etiquette-type" name="type" class="min-h-11 rounded-bouton border border-filet-contour bg-fond px-3 text-sm text-texte">
                            @foreach ($types as $type)
                                <option value="{{ $type->value }}">{{ $type->libelle() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="{{ $bouton }}">{{ __('bibliotheque.gerer.creer') }}</button>
                </form>
            @endcan

            @foreach ($types as $type)
                <div class="grid gap-2">
                    <h3 class="m-0 font-mono text-xs text-texte-discret">{{ $type->libelle() }}</h3>
                    <ul class="m-0 grid list-none gap-3 p-0">
                        @forelse ($navigation['etiquettes'][$type->value] as $etiquette)
                            <li class="grid gap-3 rounded-bouton border border-filet p-3.5">
                                <div class="flex flex-wrap items-baseline justify-between gap-2">
                                    <span class="font-semibold text-texte">{{ $etiquette->nom }}</span>
                                    <span class="font-mono text-xs text-texte-discret">{{ __('bibliotheque.gerer.nombre', ['nombre' => $etiquette->transcriptions_count]) }}</span>
                                </div>

                                @can('update', $etiquette)
                                    <div class="flex flex-wrap items-end gap-2">
                                        <form method="post" action="{{ route('bibliotheque.etiquettes.update', $etiquette) }}" class="flex flex-wrap items-end gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <label for="etiquette-{{ $etiquette->id }}" class="sr-only">{{ __('bibliotheque.gerer.nom') }}</label>
                                            <input id="etiquette-{{ $etiquette->id }}" name="nom" type="text" value="{{ $etiquette->nom }}" maxlength="80" required class="{{ $champ }} sm:w-60">
                                            <label for="etiquette-type-{{ $etiquette->id }}" class="sr-only">{{ __('bibliotheque.gerer.type') }}</label>
                                            <select id="etiquette-type-{{ $etiquette->id }}" name="type" class="min-h-11 rounded-bouton border border-filet-contour bg-fond px-3 text-sm text-texte">
                                                @foreach ($types as $choix)
                                                    <option value="{{ $choix->value }}" @selected($choix === $etiquette->type)>{{ $choix->libelle() }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="{{ $bouton2 }}">{{ __('bibliotheque.gerer.renommer') }}</button>
                                        </form>
                                        <form method="post" action="{{ route('bibliotheque.etiquettes.destroy', $etiquette) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="{{ $bouton2 }} hover:border-erreur hover:text-erreur"
                                                    aria-label="{{ __('bibliotheque.gerer.supprimer_etiquette_aria', ['nom' => $etiquette->nom]) }}">{{ __('bibliotheque.gerer.supprimer') }}</button>
                                        </form>
                                    </div>
                                @endcan
                            </li>
                        @empty
                            <li class="text-sm text-texte-discret">{{ __('bibliotheque.navigation.aucune_etiquette') }}</li>
                        @endforelse
                    </ul>
                </div>
            @endforeach
        </section>
    </div>
</x-layouts::app>
