<x-layouts::marketing :titre="__('comptes.mot_de_passe.reinitialiser_titre')">
    <section class="mx-auto max-w-md py-16" aria-labelledby="titre-reinitialisation">
        <x-panneau class="p-8">
            <h1 id="titre-reinitialisation" class="titre-h2">{{ __('comptes.mot_de_passe.reinitialiser_titre') }}</h1>

            <form method="post" action="{{ route('password.update') }}" class="mt-8 flex flex-col gap-5" novalidate>
                @csrf
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <x-champ id="email" name="email" type="email" autocomplete="email" :label="__('comptes.connexion.email')"
                    :value="old('email', $request->email)" required :erreur="$errors->first('email')" />

                <x-champ id="password" name="password" type="password" autocomplete="new-password" :label="__('comptes.mot_de_passe.nouveau')"
                    :aide="__('comptes.inscription.mot_de_passe_aide')" required :erreur="$errors->first('password')" />

                <x-champ id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                    :label="__('comptes.reglages.confirmation')" required />

                <x-bouton type="submit">{{ __('comptes.mot_de_passe.bouton') }}</x-bouton>
            </form>
        </x-panneau>
    </section>
</x-layouts::marketing>
