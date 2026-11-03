<x-layouts::marketing :titre="__('interface.marque')">
    <section class="pt-20 pb-16" aria-labelledby="titre-accueil">
        <div class="max-w-[820px]">
            <h1 id="titre-accueil" class="text-[clamp(40px,6vw,72px)] leading-[1.02] font-semibold tracking-[-0.03em]">
                {{ __('comptes.accueil.titre') }}
                <span class="font-serif italic font-normal text-accent">{{ __('comptes.accueil.titre_accent') }}</span>
            </h1>
            <p class="mt-6 max-w-[620px] text-lg text-texte-secondaire">{{ __('comptes.accueil.sous_titre') }}</p>

            @if (session('statut') === 'compte-supprime')
                <p role="status" class="mt-8 border-l-2 border-accent py-1 pl-3 text-sm text-texte">
                    {{ __('comptes.accueil.compte_supprime') }}
                </p>
            @endif

            <form method="post" action="{{ route('lien.store') }}" class="mt-10 flex max-w-[720px] flex-col gap-4 sm:flex-row sm:items-end" novalidate>
                @csrf
                <div class="min-w-0 flex-1">
                    <x-champ
                        id="lien"
                        name="lien"
                        type="url"
                        :label="__('comptes.accueil.champ')"
                        :placeholder="'https://'"
                        :value="old('lien')"
                        mono
                        required
                        :aide="__('comptes.accueil.aide')"
                        :erreur="$errors->first('lien')"
                    />
                </div>
                <x-bouton type="submit" class="sm:mb-[26px]">{{ __('comptes.accueil.bouton') }} →</x-bouton>
            </form>

            <p class="mt-6 text-sm text-texte-discret">
                {{ __('comptes.accueil.deja_compte') }}
                <a href="{{ route('login') }}" class="text-texte-secondaire hover:text-accent">{{ __('comptes.accueil.connexion') }}</a>
            </p>
        </div>
    </section>
</x-layouts::marketing>
