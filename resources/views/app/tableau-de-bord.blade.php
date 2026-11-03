<x-layouts::app :titre="__('comptes.tableau.titre')" actif="nouvelle">
    @if (session('statut') && __('comptes.statut.'.session('statut')) !== 'comptes.statut.'.session('statut'))
        <p role="status" class="mb-8 border-l-2 border-accent py-1 pl-3 text-sm text-texte">{{ __('comptes.statut.'.session('statut')) }}</p>
    @endif

    <section aria-labelledby="titre-nouvelle" class="max-w-[720px]">
        <h1 id="titre-nouvelle" class="text-3xl font-semibold tracking-tight">{{ __('comptes.tableau.titre') }}</h1>
        <p class="mt-2 text-texte-secondaire">{{ __('comptes.tableau.sous_titre') }}</p>

        @unless ($verifie)
            <p class="mt-6 border-l-2 border-accent py-1 pl-3 text-sm text-texte">
                {{ __('comptes.tableau.non_verifie') }}
                <a href="{{ route('verification.notice') }}" class="text-accent hover:underline">{{ __('comptes.verification.renvoyer') }}</a>
            </p>
        @endunless

        <form method="post" action="{{ route('transcriptions.store') }}" class="mt-8 flex flex-col gap-4 sm:flex-row sm:items-end" novalidate>
            @csrf
            <div class="min-w-0 flex-1">
                <x-champ id="lien-transcription" name="lien" type="url" :label="__('comptes.tableau.champ')" :placeholder="'https://'"
                    :value="old('lien')" mono required :erreur="$errors->first('lien')" />
            </div>
            <x-bouton type="submit" class="sm:mb-[26px]">{{ __('comptes.tableau.bouton') }}</x-bouton>
        </form>
    </section>

    <section aria-labelledby="titre-historique" class="mt-16">
        <h2 id="titre-historique" class="font-mono text-xs uppercase tracking-wide text-texte-discret">{{ __('comptes.tableau.historique') }}</h2>

        @if ($transcriptions->isEmpty())
            <p class="mt-4 text-texte-secondaire">{{ __('comptes.tableau.vide') }}</p>
        @else
            <ul class="mt-4 flex flex-col divide-y divide-filet rounded-panneau border border-filet bg-panneau">
                @foreach ($transcriptions as $transcription)
                    <li class="flex flex-wrap items-center justify-between gap-4 px-5 py-4">
                        <span class="min-w-0 truncate">{{ $transcription->titre ?? $transcription->url_origine ?? '—' }}</span>
                        <x-badge-statut :statut="$transcription->statut" />
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</x-layouts::app>
