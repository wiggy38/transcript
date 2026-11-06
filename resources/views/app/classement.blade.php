{{-- Classement d'une transcription (V2) : un dossier au plus, plusieurs étiquettes, une étiquette nouvelle possible.
     L'autocomplétion est un datalist des noms déjà créés ; le type reste à choisir. --}}
@php
    use Illuminate\Support\Facades\Lang;

    $titre = $transcription->titre ?? __('historique.sans_titre');
    $statut = session('statut');
    $message = $statut && Lang::has('bibliotheque.statuts.'.$statut) ? __('bibliotheque.statuts.'.$statut) : null;
    $champ = 'min-h-11 w-full min-w-0 rounded-bouton border border-filet-contour bg-fond px-3 text-[15px] text-texte';
@endphp

<x-layouts::app :titre="__('bibliotheque.classer.titre', ['titre' => $titre])" actif="historique">
    <div class="grid max-w-[760px] gap-6">
        <a href="{{ route('transcriptions.show', $transcription) }}" class="justify-self-start text-sm text-texte-secondaire no-underline hover:text-accent-texte">{{ __('bibliotheque.classer.retour') }}</a>

        <section aria-labelledby="titre-classer" class="grid gap-1.5">
            <h1 id="titre-classer" class="titre-h2 m-0">{{ __('bibliotheque.classer.titre', ['titre' => $titre]) }}</h1>
        </section>

        @if ($message)
            <p role="status" class="m-0 rounded-bouton border border-succes/40 bg-succes/10 px-3.5 py-3 text-sm text-texte">{{ $message }}</p>
        @endif

        <form method="post" action="{{ route('transcriptions.classement.update', $transcription) }}"
              class="grid gap-6 rounded-panneau border border-filet bg-panneau p-5 shadow-panneau">
            @csrf
            @method('PUT')

            <div class="grid gap-1.5">
                <label for="classement-dossier" class="text-sm font-semibold">{{ __('bibliotheque.classer.dossier') }}</label>
                <select id="classement-dossier" name="dossier_id" aria-describedby="aide-dossier" class="{{ $champ }}">
                    <option value="">{{ __('bibliotheque.non_classe') }}</option>
                    @foreach ($dossiers as $dossier)
                        <option value="{{ $dossier->id }}" @selected($transcription->dossier_id === $dossier->id)>{{ $dossier->nom }}</option>
                    @endforeach
                </select>
                <p id="aide-dossier" class="m-0 text-[13px] text-texte-discret">{{ __('bibliotheque.classer.dossier_aide') }}</p>
            </div>

            <fieldset class="m-0 grid gap-4 border-0 p-0">
                <legend class="text-sm font-semibold">{{ __('bibliotheque.classer.etiquettes') }}</legend>
                <p class="m-0 -mt-2 text-[13px] text-texte-discret">{{ __('bibliotheque.classer.etiquettes_aide') }}</p>

                @foreach ($types as $type)
                    <fieldset class="m-0 grid gap-2 border-0 p-0">
                        <legend class="mb-1 font-mono text-xs text-texte-discret">{{ $type->libelle() }}</legend>
                        @forelse ($etiquettes[$type->value] as $etiquette)
                            <label class="inline-flex min-h-11 items-center gap-2.5 text-sm text-texte">
                                <input type="checkbox" name="etiquettes[]" value="{{ $etiquette->id }}" @checked(in_array($etiquette->id, $etiquettesChoisies, true))
                                       class="size-4 accent-accent">
                                {{ $etiquette->nom }}
                            </label>
                        @empty
                            <p class="m-0 text-[13px] text-texte-discret">{{ __('bibliotheque.navigation.aucune_etiquette') }}</p>
                        @endforelse
                    </fieldset>
                @endforeach
            </fieldset>

            <div class="grid gap-3 rounded-bouton bg-panneau-eleve p-4">
                <div class="grid gap-1.5">
                    <label for="nouvelle-etiquette-classement" class="text-sm font-semibold">{{ __('bibliotheque.classer.nouvelle') }}</label>
                    <input id="nouvelle-etiquette-classement" name="nouvelle_etiquette" type="text" maxlength="80" list="noms-etiquettes" aria-describedby="aide-nouvelle" class="{{ $champ }}">
                    <datalist id="noms-etiquettes">
                        @foreach ($nomsConnus as $nom)
                            <option value="{{ $nom }}"></option>
                        @endforeach
                    </datalist>
                    <p id="aide-nouvelle" class="m-0 text-[13px] text-texte-discret">{{ __('bibliotheque.classer.nouvelle_aide') }}</p>
                </div>
                <div class="grid gap-1.5">
                    <label for="nouvelle-etiquette-type" class="text-sm font-semibold">{{ __('bibliotheque.classer.nouvelle_type') }}</label>
                    <select id="nouvelle-etiquette-type" name="nouvelle_etiquette_type" class="{{ $champ }}">
                        @foreach ($types as $choix)
                            <option value="{{ $choix->value }}" @selected($choix->value === 'libre')>{{ $choix->libelle() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <button type="submit"
                        class="inline-flex min-h-11 cursor-pointer items-center rounded-bouton bg-accent px-[18px] text-sm font-semibold text-sur-accent hover:opacity-90">{{ __('bibliotheque.classer.enregistrer') }}</button>
            </div>
        </form>
    </div>
</x-layouts::app>
