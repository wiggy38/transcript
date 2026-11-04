<x-layouts::marketing :titre="__('comptes.connexion.titre')">
    <section class="mx-auto max-w-md py-16" aria-labelledby="titre-connexion">
        <x-panneau class="p-8">
            <h1 id="titre-connexion" class="text-2xl font-semibold tracking-tight">{{ __('comptes.connexion.titre') }}</h1>
            <p class="mt-2 text-texte-secondaire">{{ __('comptes.connexion.sous_titre') }}</p>

            @if (request()->session()->has('lien_en_attente'))
                <p class="mt-4 text-sm text-texte-secondaire">{{ __('comptes.connexion.lien_garde') }}</p>
            @endif

            <div class="mt-8">
                @include('auth._google')
            </div>

            <form method="post" action="{{ route('login.store') }}" class="flex flex-col gap-5" novalidate>
                @csrf

                <x-champ id="email" name="email" type="email" autocomplete="email" :label="__('comptes.connexion.email')"
                    :value="old('email')" required :erreur="$errors->first('email')" />

                <x-champ id="password" name="password" type="password" autocomplete="current-password" :label="__('comptes.connexion.mot_de_passe')"
                    required :erreur="$errors->first('password')" />

                <label class="flex items-center gap-3 text-sm text-texte-secondaire">
                    <input type="checkbox" name="remember" value="1" class="size-4 accent-[var(--color-accent)]">
                    {{ __('comptes.connexion.souvenir') }}
                </label>

                <x-bouton type="submit">{{ __('comptes.connexion.bouton') }}</x-bouton>
            </form>

            <ul class="mt-8 flex flex-col gap-2 text-sm">
                <li><a href="{{ route('password.request') }}" class="text-texte-secondaire hover:text-accent-texte">{{ __('comptes.connexion.oubli') }}</a></li>
                <li class="text-texte-discret">
                    {{ __('comptes.connexion.pas_de_compte') }}
                    <a href="{{ route('register') }}" class="text-texte-secondaire hover:text-accent-texte">{{ __('comptes.connexion.creer') }}</a>
                </li>
            </ul>
        </x-panneau>
    </section>
</x-layouts::marketing>
