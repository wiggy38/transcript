<x-layouts::marketing :titre="__('comptes.mot_de_passe.oublie_titre')">
    <section class="mx-auto max-w-md py-16" aria-labelledby="titre-oubli">
        <x-panneau class="p-8">
            <h1 id="titre-oubli" class="titre-h2">{{ __('comptes.mot_de_passe.oublie_titre') }}</h1>
            <p class="mt-2 text-texte-secondaire">{{ __('comptes.mot_de_passe.oublie_texte') }}</p>

            @if (session('status'))
                <p role="status" class="mt-6 border-l-2 border-accent py-1 pl-3 text-sm text-texte">{{ session('status') }}</p>
            @endif

            <form method="post" action="{{ route('password.email') }}" class="mt-8 flex flex-col gap-5" novalidate>
                @csrf

                <x-champ id="email" name="email" type="email" autocomplete="email" :label="__('comptes.connexion.email')"
                    :value="old('email')" required :erreur="$errors->first('email')" />

                <x-bouton type="submit">{{ __('comptes.mot_de_passe.envoyer') }}</x-bouton>
            </form>

            <p class="mt-8 text-sm">
                <a href="{{ route('login') }}" class="text-texte-secondaire hover:text-accent-texte">{{ __('comptes.mot_de_passe.retour') }}</a>
            </p>
        </x-panneau>
    </section>
</x-layouts::marketing>
