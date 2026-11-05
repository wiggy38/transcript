<x-layouts::marketing :titre="__('comptes.inscription.titre')">
    <section class="mx-auto max-w-md py-16" aria-labelledby="titre-inscription">
        <x-panneau class="p-8">
            <h1 id="titre-inscription" class="titre-h2">{{ __('comptes.inscription.titre') }}</h1>
            <p class="mt-2 text-texte-secondaire">{{ __('comptes.inscription.sous_titre') }}</p>

            @if (request()->session()->has('lien_en_attente'))
                <p class="mt-4 border-l-2 border-accent py-1 pl-3 text-sm text-texte">{{ __('comptes.inscription.lien_garde') }}</p>
            @endif

            <div class="mt-8">
                @include('auth._google')
            </div>

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

                <x-bouton type="submit">{{ __('comptes.inscription.bouton') }}</x-bouton>
            </form>

            <p class="mt-8 text-sm text-texte-discret">
                {{ __('comptes.inscription.deja') }}
                <a href="{{ route('login') }}" class="text-texte-secondaire hover:text-accent-texte">{{ __('comptes.connexion.bouton') }}</a>
            </p>
        </x-panneau>
    </section>
</x-layouts::marketing>
