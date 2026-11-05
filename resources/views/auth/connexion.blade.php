<x-layouts::auth :titre="__('comptes.connexion.titre')">
    <section aria-labelledby="titre-connexion" class="flex w-full flex-col gap-6">
        <div class="flex flex-col gap-2">
            <h1 id="titre-connexion" class="titre-h2">{{ __('comptes.connexion.titre') }}</h1>
            <p class="text-texte-secondaire">{{ __('comptes.connexion.sous_titre') }}</p>
        </div>

        @if (request()->session()->has('lien_en_attente'))
            <p role="status" class="border-l-2 border-accent py-1 pl-3 text-sm text-texte">{{ __('comptes.connexion.lien_garde') }}</p>
        @endif

        @include('auth._google')

        <form method="post" action="{{ route('login.store') }}" class="flex flex-col gap-5" novalidate>
            @csrf

            <x-champ id="email" name="email" type="email" autocomplete="email" :label="__('comptes.connexion.email')"
                :value="old('email')" required :erreur="$errors->first('email')" />

            {{-- Libellé et lien « Mot de passe oublié ? » sur la même ligne (maquette). --}}
            <div class="flex min-w-0 flex-col gap-2">
                <div class="flex items-baseline justify-between gap-4">
                    <label for="password" class="font-mono text-xs text-texte-discret">{{ __('comptes.connexion.mot_de_passe') }}</label>
                    <a href="{{ route('password.request') }}" class="text-sm whitespace-nowrap text-accent-texte hover:underline">{{ __('comptes.connexion.oubli') }}</a>
                </div>
                <input id="password" name="password" type="password" autocomplete="current-password" required
                       @if ($errors->has('password')) aria-invalid="true" aria-describedby="password-erreur" @endif
                       class="min-h-11 w-full min-w-0 rounded-champ border border-filet-contour bg-panneau px-3.5 text-[15px] text-texte outline-none focus-visible:border-accent">
                @if ($errors->has('password'))
                    <x-message-erreur id="password-erreur">{{ $errors->first('password') }}</x-message-erreur>
                @endif
            </div>

            <label class="inline-flex min-h-10 items-center gap-3 text-sm text-texte-secondaire">
                <input type="checkbox" name="remember" value="1" class="size-4 accent-[var(--color-accent)]">
                {{ __('comptes.connexion.souvenir') }}
            </label>

            <x-bouton type="submit" class="min-h-12 w-full text-[15px]">{{ __('comptes.connexion.bouton') }}</x-bouton>
        </form>

        <p class="text-sm text-texte-discret">
            {{ __('comptes.connexion.pas_de_compte') }}
            <a href="{{ route('register') }}" class="font-medium text-accent-texte hover:underline">{{ __('comptes.connexion.creer') }}</a>
        </p>
    </section>

    @include('auth._apercu')
</x-layouts::auth>
