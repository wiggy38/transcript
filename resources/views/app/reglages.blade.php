<x-layouts::app :titre="__('comptes.reglages.titre')" actif="compte">
    @php
        $cleStatut = session('status') ?? session('statut');
        $messageStatut = $cleStatut ? __('comptes.statut.'.$cleStatut) : null;
    @endphp

    @if ($messageStatut && $messageStatut !== 'comptes.statut.'.$cleStatut)
        <p role="status" class="mb-8 border-l-2 border-accent py-1 pl-3 text-sm text-texte">{{ $messageStatut }}</p>
    @endif

    <h1 class="text-3xl font-semibold tracking-tight">{{ __('comptes.reglages.titre') }}</h1>

    <div class="mt-10 grid max-w-[880px] gap-8">
        {{-- Profil : nom et email, traités par Fortify (user-profile-information.update). --}}
        <x-panneau class="p-6">
            <h2 class="text-lg font-semibold">{{ __('comptes.reglages.profil') }}</h2>
            <form method="post" action="{{ route('user-profile-information.update') }}" class="mt-6 grid gap-5 sm:grid-cols-2" novalidate>
                @csrf
                @method('PUT')
                <x-champ id="profil-nom" name="name" autocomplete="name" :label="__('comptes.reglages.nom')" :value="old('name', $utilisateur->name)"
                    required :erreur="$errors->updateProfileInformation->first('name')" />
                <x-champ id="profil-email" name="email" type="email" autocomplete="email" :label="__('comptes.reglages.email')" :value="old('email', $utilisateur->email)"
                    required :erreur="$errors->updateProfileInformation->first('email')" />
                <div class="sm:col-span-2">
                    <x-bouton type="submit">{{ __('comptes.reglages.enregistrer') }}</x-bouton>
                </div>
            </form>
        </x-panneau>

        {{-- Mot de passe : Fortify (user-password.update). --}}
        <x-panneau class="p-6">
            <h2 class="text-lg font-semibold">{{ __('comptes.reglages.mot_de_passe') }}</h2>
            <form method="post" action="{{ route('user-password.update') }}" class="mt-6 grid gap-5 sm:grid-cols-2" novalidate>
                @csrf
                @method('PUT')
                <x-champ id="mdp-actuel" name="current_password" type="password" autocomplete="current-password" :label="__('comptes.reglages.mot_de_passe_actuel')"
                    required :erreur="$errors->updatePassword->first('current_password')" />
                <span></span>
                <x-champ id="mdp-nouveau" name="password" type="password" autocomplete="new-password" :label="__('comptes.reglages.nouveau')"
                    :aide="__('comptes.inscription.mot_de_passe_aide')" required :erreur="$errors->updatePassword->first('password')" />
                <x-champ id="mdp-confirmation" name="password_confirmation" type="password" autocomplete="new-password" :label="__('comptes.reglages.confirmation')" required />
                <div class="sm:col-span-2">
                    <x-bouton type="submit">{{ __('comptes.reglages.enregistrer') }}</x-bouton>
                </div>
            </form>
        </x-panneau>

        {{-- Langue de l'interface, stockée sur le compte. --}}
        <x-panneau class="p-6">
            <h2 class="text-lg font-semibold">{{ __('comptes.reglages.langue') }}</h2>
            <form method="post" action="{{ route('reglages.langue') }}" class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-end">
                @csrf
                @method('PUT')
                <div class="flex flex-col gap-2">
                    <label for="langue-interface" class="font-mono text-xs uppercase tracking-wide text-texte-discret">{{ __('comptes.reglages.langue') }}</label>
                    <select id="langue-interface" name="locale" class="min-h-11 rounded-champ border border-filet-contour bg-panneau px-3.5 text-texte">
                        <option value="auto" @selected($utilisateur->locale === null)>{{ __('comptes.reglages.langues.auto') }}</option>
                        @foreach ($langues as $langue)
                            <option value="{{ $langue }}" @selected($utilisateur->locale === $langue)>{{ __('comptes.reglages.langues.'.$langue) }}</option>
                        @endforeach
                    </select>
                </div>
                <x-bouton type="submit" variante="filet">{{ __('comptes.reglages.enregistrer') }}</x-bouton>
            </form>
        </x-panneau>

        {{-- Abonnement : formule, résiliation, factures (BillingProvider). --}}
        <x-panneau class="p-6">
            <h2 class="text-lg font-semibold">{{ __('abonnement.titre') }}</h2>
            <div class="mt-6">
                <x-bouton :href="route('abonnement')" variante="filet">{{ __('abonnement.titre') }}</x-bouton>
            </div>
        </x-panneau>

        {{-- Export de toutes les données : préparé par la file, lien reçu par email (et ici quand il est prêt). --}}
        <x-panneau class="p-6">
            <h2 class="text-lg font-semibold">{{ __('comptes.reglages.donnees.titre') }}</h2>
            <p class="mt-2 text-texte-secondaire">{{ __('comptes.reglages.donnees.texte') }}</p>
            @if ($exportDisponible)
                <p class="mt-4 text-sm text-texte">{{ __('comptes.reglages.donnees.pret') }}
                    <a href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('reglages.donnees.telecharger', now()->addMinutes(config('voxia.donnees.duree_lien_minutes')), ['utilisateur' => $utilisateur->getKey()]) }}" class="text-accent-texte underline-offset-4 hover:underline">{{ __('comptes.reglages.donnees.telecharger') }}</a>
                </p>
            @endif
            <form method="post" action="{{ route('reglages.donnees.demander') }}" class="mt-6">
                @csrf
                <x-bouton type="submit" variante="filet">{{ __('comptes.reglages.donnees.bouton') }}</x-bouton>
            </form>
        </x-panneau>

        {{-- Suppression : confirmation par la saisie de l'adresse email. --}}
        <x-panneau class="p-6">
            <h2 class="text-lg font-semibold">{{ __('comptes.suppression.titre') }}</h2>
            <p class="mt-2 text-texte-secondaire">{{ __('comptes.suppression.texte') }}</p>
            <form method="post" action="{{ route('reglages.supprimer') }}" class="mt-6 grid gap-5 sm:max-w-md" novalidate>
                @csrf
                @method('DELETE')
                <x-champ id="confirmation-email" name="confirmation_email" type="email" autocomplete="off" :label="__('comptes.suppression.confirmation')"
                    required :erreur="$errors->first('confirmation_email')" />
                <div>
                    <x-bouton type="submit" variante="filet">{{ __('comptes.suppression.bouton') }}</x-bouton>
                </div>
            </form>
        </x-panneau>
    </div>
</x-layouts::app>
