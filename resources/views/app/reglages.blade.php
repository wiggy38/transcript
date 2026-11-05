<x-layouts::app :titre="__('comptes.reglages.titre')" actif="compte">
    @php
        $cleStatut = session('status') ?? session('statut');
        $messageStatut = $cleStatut ? __('comptes.statut.'.$cleStatut) : null;

        $sections = [
            'abonnement' => __('comptes.reglages.sections.abonnement'),
            'profil' => __('comptes.reglages.profil'),
            'mot-de-passe' => __('comptes.reglages.mot_de_passe'),
            'langue' => __('comptes.reglages.langue'),
            'factures' => __('comptes.reglages.sections.factures'),
            'donnees' => __('comptes.reglages.donnees.titre'),
            'suppression' => __('comptes.suppression.titre'),
        ];
    @endphp

    @if ($messageStatut && $messageStatut !== 'comptes.statut.'.$cleStatut)
        <p role="status" class="mb-6 border-l-2 border-accent py-1 pl-3 text-sm text-texte">{{ $messageStatut }}</p>
    @endif

    <h1 class="titre-h2">{{ __('comptes.reglages.titre') }}</h1>

    <div class="mt-8 grid gap-8 lg:grid-cols-[200px_minmax(0,1fr)] lg:items-start">
        <nav aria-label="{{ __('comptes.reglages.sections.navigation') }}" class="lg:sticky lg:top-6">
            <ul class="flex flex-wrap gap-1 lg:flex-col">
                @foreach ($sections as $id => $libelle)
                    <li>
                        <a href="#{{ $id }}" class="inline-flex min-h-9 items-center rounded-bouton px-3 text-sm text-texte-secondaire no-underline hover:bg-panneau-eleve hover:text-texte">{{ $libelle }}</a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="flex min-w-0 max-w-[720px] flex-col gap-6">
            {{-- Ta formule : nom du plan, détail et quotas (maquette « Compte »). --}}
            <section id="abonnement" aria-labelledby="t-abonnement" class="scroll-mt-6 rounded-panneau border border-filet bg-panneau p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex flex-col gap-1">
                        <h2 id="t-abonnement" class="text-lg font-semibold">{{ __('comptes.reglages.sections.formule') }}</h2>
                        <p class="text-sm text-texte-secondaire">
                            <span class="font-semibold text-texte">{{ $formule['nom'] }}</span> · {{ $formule['detail'] }}
                        </p>
                    </div>
                    <x-bouton :href="route('abonnement')" variante="filet">{{ __('abonnement.titre') }}</x-bouton>
                </div>

                @if ($formule['quotas'] !== [])
                    <dl class="mt-6 grid gap-5">
                        @foreach ($formule['quotas'] as $quota)
                            <div class="grid gap-2">
                                <div class="flex items-baseline justify-between gap-4 font-mono text-xs">
                                    <dt class="text-texte-discret">{{ $quota['libelle'] }}</dt>
                                    <dd class="text-texte">{{ $quota['valeur'] }}</dd>
                                </div>
                                @if ($quota['pourcentage'] !== null)
                                    <div aria-hidden="true" class="h-1 overflow-hidden rounded-full bg-panneau-eleve">
                                        <div class="h-full rounded-full bg-accent" style="width: {{ $quota['pourcentage'] }}%"></div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </dl>
                @endif
            </section>

            {{-- Profil : nom et email, traités par Fortify (user-profile-information.update). --}}
            <section id="profil" aria-labelledby="t-profil" class="scroll-mt-6 rounded-panneau border border-filet bg-panneau p-6">
                <h2 id="t-profil" class="text-lg font-semibold">{{ __('comptes.reglages.profil') }}</h2>
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
            </section>

            {{-- Mot de passe : Fortify (user-password.update). --}}
            <section id="mot-de-passe" aria-labelledby="t-mot-de-passe" class="scroll-mt-6 rounded-panneau border border-filet bg-panneau p-6">
                <h2 id="t-mot-de-passe" class="text-lg font-semibold">{{ __('comptes.reglages.mot_de_passe') }}</h2>
                <form method="post" action="{{ route('user-password.update') }}" class="mt-6 grid gap-5 sm:grid-cols-2" novalidate>
                    @csrf
                    @method('PUT')
                    <x-champ id="mdp-actuel" name="current_password" type="password" autocomplete="current-password" :label="__('comptes.reglages.mot_de_passe_actuel')"
                        required :erreur="$errors->updatePassword->first('current_password')" />
                    <span class="hidden sm:block"></span>
                    <x-champ id="mdp-nouveau" name="password" type="password" autocomplete="new-password" :label="__('comptes.reglages.nouveau')"
                        :aide="__('comptes.inscription.mot_de_passe_aide')" required :erreur="$errors->updatePassword->first('password')" />
                    <x-champ id="mdp-confirmation" name="password_confirmation" type="password" autocomplete="new-password" :label="__('comptes.reglages.confirmation')" required />
                    <div class="sm:col-span-2">
                        <x-bouton type="submit">{{ __('comptes.reglages.enregistrer') }}</x-bouton>
                    </div>
                </form>
            </section>

            {{-- Langue de l'interface, stockée sur le compte. --}}
            <section id="langue" aria-labelledby="t-langue" class="scroll-mt-6 rounded-panneau border border-filet bg-panneau p-6">
                <h2 id="t-langue" class="text-lg font-semibold">{{ __('comptes.reglages.langue') }}</h2>
                <form method="post" action="{{ route('reglages.langue') }}" class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-end">
                    @csrf
                    @method('PUT')
                    <div class="flex flex-col gap-2">
                        <label for="langue-interface" class="font-mono text-xs text-texte-discret">{{ __('comptes.reglages.langue') }}</label>
                        <select id="langue-interface" name="locale" class="min-h-10 rounded-champ border border-filet-contour bg-panneau px-3.5 text-texte">
                            <option value="auto" @selected($utilisateur->locale === null)>{{ __('comptes.reglages.langues.auto') }}</option>
                            @foreach ($langues as $langue)
                                <option value="{{ $langue }}" @selected($utilisateur->locale === $langue)>{{ __('comptes.reglages.langues.'.$langue) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-bouton type="submit" variante="filet">{{ __('comptes.reglages.enregistrer') }}</x-bouton>
                </form>
            </section>

            {{-- Packs et factures : gérés sur la page Abonnement (BillingProvider). --}}
            <section id="factures" aria-labelledby="t-factures" class="scroll-mt-6 rounded-panneau border border-filet bg-panneau p-6">
                <h2 id="t-factures" class="text-lg font-semibold">{{ __('comptes.reglages.sections.factures') }}</h2>
                <p class="mt-2 text-texte-secondaire">{{ __('comptes.reglages.sections.factures_texte') }}</p>
                <div class="mt-6">
                    <x-bouton :href="route('abonnement')" variante="filet">{{ __('comptes.reglages.sections.factures_bouton') }}</x-bouton>
                </div>
            </section>

            {{-- Export de toutes les données : préparé par la file, lien reçu par email (et ici quand il est prêt). --}}
            <section id="donnees" aria-labelledby="t-donnees" class="scroll-mt-6 rounded-panneau border border-filet bg-panneau p-6">
                <h2 id="t-donnees" class="text-lg font-semibold">{{ __('comptes.reglages.donnees.titre') }}</h2>
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
            </section>

            {{-- Suppression : zone de danger, confirmation par la saisie de l'adresse email. --}}
            <section id="suppression" aria-labelledby="t-suppression" class="scroll-mt-6 rounded-panneau border border-erreur/50 bg-panneau p-6">
                <h2 id="t-suppression" class="text-lg font-semibold text-erreur">{{ __('comptes.suppression.titre') }}</h2>
                <p class="mt-2 text-texte-secondaire">{{ __('comptes.suppression.texte') }}</p>
                <form method="post" action="{{ route('reglages.supprimer') }}" class="mt-6 grid gap-5 sm:max-w-md" novalidate>
                    @csrf
                    @method('DELETE')
                    <x-champ id="confirmation-email" name="confirmation_email" type="email" autocomplete="off" :label="__('comptes.suppression.confirmation')"
                        required :erreur="$errors->first('confirmation_email')" />
                    <div>
                        <x-bouton type="submit" variante="filet" class="border-erreur text-erreur hover:border-erreur hover:text-erreur">{{ __('comptes.suppression.bouton') }}</x-bouton>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-layouts::app>
