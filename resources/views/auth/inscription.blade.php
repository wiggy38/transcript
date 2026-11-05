<x-layouts::auth :titre="__('comptes.inscription.titre')">
    <section aria-labelledby="titre-inscription" class="flex w-full flex-col gap-6">
        <div class="flex flex-col gap-2">
            <h1 id="titre-inscription" class="titre-h2">{{ __('comptes.inscription.titre') }}</h1>
            <p class="text-texte-secondaire">{{ __('comptes.inscription.sous_titre') }}</p>
        </div>

        @if (request()->session()->has('lien_en_attente'))
            <p role="status" class="border-l-2 border-accent py-1 pl-3 text-sm text-texte">{{ __('comptes.inscription.lien_garde') }}</p>
        @endif

        @include('auth._google')

        <form method="post" action="{{ route('register.store') }}" class="flex flex-col gap-5" novalidate>
            @csrf

            <x-champ id="name" name="name" autocomplete="name" :label="__('comptes.inscription.nom')"
                :value="old('name')" required :erreur="$errors->first('name')" />

            <x-champ id="email" name="email" type="email" autocomplete="email" :label="__('comptes.inscription.email')"
                :value="old('email')" required :erreur="$errors->first('email')" />

            <x-champ id="password" name="password" type="password" autocomplete="new-password" :label="__('comptes.inscription.mot_de_passe')"
                :aide="__('comptes.inscription.mot_de_passe_aide')" required :erreur="$errors->first('password')" />

            <x-champ id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                :label="__('comptes.inscription.confirmation')" required />

            <x-bouton type="submit" class="min-h-12 w-full text-[15px]">{{ __('comptes.inscription.bouton') }}</x-bouton>

            <p class="text-sm text-texte-discret">
                {{ __('comptes.inscription.cgu') }}
                <a href="{{ route('conditions') }}" class="text-accent-texte hover:underline">{{ __('comptes.inscription.cgu_lien') }}</a>
                {{ __('comptes.inscription.et') }}
                <a href="{{ route('confidentialite') }}" class="text-accent-texte hover:underline">{{ __('comptes.inscription.confidentialite_lien') }}</a>.
            </p>
        </form>

        <p class="text-sm text-texte-discret">
            {{ __('comptes.inscription.deja') }}
            <a href="{{ route('login') }}" class="font-medium text-accent-texte hover:underline">{{ __('comptes.connexion.bouton') }}</a>
        </p>
    </section>

    @include('auth._apercu')
</x-layouts::auth>
