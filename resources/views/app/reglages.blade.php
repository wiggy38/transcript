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

        $dateCourte = fn ($date) => $date ? \App\Support\Formats::date($date) : '';
    @endphp

    @if ($messageStatut && $messageStatut !== 'comptes.statut.'.$cleStatut)
        <p role="status" class="mb-8 flex items-center gap-2.5 rounded-champ border border-succes/40 bg-succes/10 px-3.5 py-3 text-sm text-texte">
            <span aria-hidden="true" class="text-succes">✓</span>{{ $messageStatut }}
        </p>
    @endif

    <h1 class="titre-h2">{{ __('comptes.reglages.titre') }}</h1>

    <div class="mt-8 grid gap-8 md:grid-cols-[240px_minmax(0,1fr)] md:items-start">
        <nav aria-label="{{ __('comptes.reglages.sections.navigation') }}" class="md:sticky md:top-4">
            <ul class="flex flex-wrap gap-1 md:flex-col">
                @foreach ($sections as $id => $libelle)
                    <li>
                        <a href="#{{ $id }}" class="inline-flex min-h-10 items-center rounded-bouton px-3 text-sm text-texte-secondaire no-underline transition-colors hover:bg-panneau-eleve hover:text-texte">{{ $libelle }}</a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="flex min-w-0 max-w-[820px] flex-col gap-4">
            {{-- Ta formule : plan en pastille, détail, bouton vers l'abonnement, quotas en cartes (maquette « Compte »). --}}
            <section id="abonnement" aria-labelledby="t-abonnement" class="scroll-mt-4 flex flex-col gap-4 rounded-panneau border border-filet bg-panneau p-5.5 shadow-panneau sm:p-[22px]">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex flex-col gap-1">
                        <h2 id="t-abonnement" class="font-titre text-[17px] font-semibold tracking-[-0.02em]">{{ __('comptes.reglages.sections.formule') }}</h2>
                        <p class="flex flex-wrap items-center gap-2 text-sm text-texte-secondaire">
                            <span class="rounded-full bg-accent px-2.5 py-0.5 font-mono text-xs font-semibold text-sur-accent">{{ $formule['nom'] }}</span>
                            <span>{{ $formule['detail'] }}</span>
                        </p>
                    </div>
                    <x-bouton :href="route('abonnement')" variante="filet" class="whitespace-nowrap">{{ __('abonnement.titre') }}</x-bouton>
                </div>

                @if ($formule['quotas'] !== [])
                    <dl class="grid grid-cols-[repeat(auto-fit,minmax(150px,1fr))] gap-3">
                        @foreach ($formule['quotas'] as $quota)
                            <div class="grid gap-2 rounded-champ bg-fond p-3.5">
                                <dt class="text-[13px] text-texte-secondaire">{{ $quota['libelle'] }}</dt>
                                <dd class="font-mono text-[15px] font-medium">{{ $quota['valeur'] }}</dd>
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
            <section id="profil" aria-labelledby="t-profil" class="scroll-mt-4 flex flex-col gap-4 rounded-panneau border border-filet bg-panneau p-5.5 shadow-panneau sm:p-[22px]">
                <h2 id="t-profil" class="font-titre text-[17px] font-semibold tracking-[-0.02em]">{{ __('comptes.reglages.profil') }}</h2>
                <form method="post" action="{{ route('user-profile-information.update') }}" class="grid grid-cols-[repeat(auto-fit,minmax(240px,1fr))] gap-4" novalidate>
                    @csrf
                    @method('PUT')
                    <x-champ id="profil-nom" name="name" variante="formulaire" autocomplete="name" :label="__('comptes.reglages.nom')" :value="old('name', $utilisateur->name)"
                        required :erreur="$errors->updateProfileInformation->first('name')" />
                    <x-champ id="profil-email" name="email" type="email" variante="formulaire" autocomplete="email" :label="__('comptes.reglages.email')" :value="old('email', $utilisateur->email)"
                        required :erreur="$errors->updateProfileInformation->first('email')" />
                    <div class="sm:col-span-2">
                        <x-bouton type="submit" class="min-h-11">{{ __('comptes.reglages.enregistrer') }}</x-bouton>
                    </div>
                </form>
            </section>

            {{-- Mot de passe : Fortify (user-password.update). --}}
            <section id="mot-de-passe" aria-labelledby="t-mot-de-passe" class="scroll-mt-4 flex flex-col gap-4 rounded-panneau border border-filet bg-panneau p-5.5 shadow-panneau sm:p-[22px]">
                <h2 id="t-mot-de-passe" class="font-titre text-[17px] font-semibold tracking-[-0.02em]">{{ __('comptes.reglages.mot_de_passe') }}</h2>
                <form method="post" action="{{ route('user-password.update') }}" class="grid grid-cols-[repeat(auto-fit,minmax(240px,1fr))] gap-4" novalidate>
                    @csrf
                    @method('PUT')
                    <div class="max-w-[400px] sm:col-span-full">
                        <x-champ id="mdp-actuel" name="current_password" type="password" variante="formulaire" autocomplete="current-password" :label="__('comptes.reglages.mot_de_passe_actuel')"
                            required :erreur="$errors->updatePassword->first('current_password')" />
                    </div>
                    <x-champ id="mdp-nouveau" name="password" type="password" variante="formulaire" autocomplete="new-password" :label="__('comptes.reglages.nouveau')"
                        :aide="__('comptes.inscription.mot_de_passe_aide')" required :erreur="$errors->updatePassword->first('password')" />
                    <x-champ id="mdp-confirmation" name="password_confirmation" type="password" variante="formulaire" autocomplete="new-password" :label="__('comptes.reglages.confirmation')" required />
                    <div class="sm:col-span-full">
                        <x-bouton type="submit" class="min-h-11">{{ __('comptes.reglages.enregistrer') }}</x-bouton>
                    </div>
                </form>
            </section>

            {{-- Langue de l'interface, stockée sur le compte. --}}
            <section id="langue" aria-labelledby="t-langue" class="scroll-mt-4 flex flex-col gap-4 rounded-panneau border border-filet bg-panneau p-5.5 shadow-panneau sm:p-[22px]">
                <h2 id="t-langue" class="font-titre text-[17px] font-semibold tracking-[-0.02em]">{{ __('comptes.reglages.langue') }}</h2>
                <form method="post" action="{{ route('reglages.langue') }}" class="flex flex-wrap items-end gap-3">
                    @csrf
                    @method('PUT')
                    <div class="flex min-w-0 flex-col gap-2 sm:w-[280px]">
                        <label for="langue-interface" class="text-sm font-semibold">{{ __('comptes.reglages.langue') }}</label>
                        <select id="langue-interface" name="locale" class="min-h-11 rounded-champ border border-filet-contour bg-fond px-3 text-sm text-texte">
                            <option value="auto" @selected($utilisateur->locale === null)>{{ __('comptes.reglages.langues.auto') }}</option>
                            @foreach ($langues as $langue)
                                <option value="{{ $langue }}" @selected($utilisateur->locale === $langue)>{{ __('comptes.reglages.langues.'.$langue) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-bouton type="submit" variante="filet" class="min-h-11">{{ __('comptes.reglages.enregistrer') }}</x-bouton>
                </form>
            </section>

            {{-- Packs et factures : liste des factures du fournisseur de paiement (même source que la page Abonnement). --}}
            <section id="factures" aria-labelledby="t-factures" class="scroll-mt-4 flex flex-col gap-3.5 rounded-panneau border border-filet bg-panneau p-5.5 shadow-panneau sm:p-[22px]">
                <div class="flex flex-col gap-1">
                    <h2 id="t-factures" class="font-titre text-[17px] font-semibold tracking-[-0.02em]">{{ __('comptes.reglages.sections.factures') }}</h2>
                    <p class="text-[13px] text-texte-discret">{{ __('abonnement.factures.aide') }}</p>
                </div>

                @if ($factures === [])
                    <p class="text-sm text-texte-secondaire">{{ __('abonnement.factures.aucune') }}</p>
                @else
                    <ul class="grid">
                        @foreach ($factures as $facture)
                            <li class="flex flex-wrap items-center gap-x-4 gap-y-1.5 border-t border-filet py-3 text-sm">
                                <span class="min-w-0 flex-1 basis-52">
                                    {{ $dateCourte($facture['date']) }} · <span class="font-mono text-[13px] text-texte-secondaire">{{ $facture['numero'] ?? __('abonnement.factures.sans_numero') }}</span>
                                </span>
                                <span class="font-mono text-[13px]">{{ $facture['montant'] }}</span>
                                <span class="rounded-full bg-succes/20 px-2 py-0.5 font-mono text-[11px] text-succes">{{ __('abonnement.factures.statuts.'.$facture['statut']) }}</span>
                                @if ($facture['numero'])
                                    <a href="{{ route('abonnement.facture', ['facture' => $facture['id']]) }}" class="whitespace-nowrap text-[13px] text-accent-texte hover:underline">{{ __('abonnement.factures.telecharger') }}</a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            {{-- Export de toutes les données : préparé par la file, lien reçu par email (et ici quand il est prêt). --}}
            <section id="donnees" aria-labelledby="t-donnees" class="scroll-mt-4 flex flex-col gap-3 rounded-panneau border border-filet bg-panneau p-5.5 shadow-panneau sm:p-[22px]">
                <h2 id="t-donnees" class="font-titre text-[17px] font-semibold tracking-[-0.02em]">{{ __('comptes.reglages.donnees.titre') }}</h2>
                <p class="max-w-[46em] text-sm text-texte-secondaire">{{ __('comptes.reglages.donnees.texte') }}</p>
                <div class="flex flex-wrap items-center gap-x-4.5 gap-y-2.5">
                    <form method="post" action="{{ route('reglages.donnees.demander') }}">
                        @csrf
                        <x-bouton type="submit" variante="filet" class="min-h-11">{{ __('comptes.reglages.donnees.bouton') }}</x-bouton>
                    </form>
                    @if ($exportDisponible)
                        <p class="text-sm text-texte-secondaire">{{ __('comptes.reglages.donnees.pret') }}
                            <a href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('reglages.donnees.telecharger', now()->addMinutes(config('voxia.donnees.duree_lien_minutes')), ['utilisateur' => $utilisateur->getKey()]) }}" class="text-accent-texte underline-offset-4 hover:underline">{{ __('comptes.reglages.donnees.telecharger') }}</a>
                        </p>
                    @endif
                </div>
            </section>

            {{-- Suppression : zone de danger teintée, confirmation par la saisie de l'adresse email. --}}
            <section id="suppression" aria-labelledby="t-suppression" class="scroll-mt-4 flex flex-col gap-3.5 rounded-panneau border border-erreur/45 bg-erreur/5 p-5.5 shadow-panneau sm:p-[22px]">
                <h2 id="t-suppression" class="font-titre text-[17px] font-semibold tracking-[-0.02em] text-erreur">{{ __('comptes.suppression.titre') }}</h2>
                <p class="max-w-[46em] text-sm text-texte-secondaire">{{ __('comptes.suppression.texte') }}</p>
                <form method="post" action="{{ route('reglages.supprimer') }}" class="flex flex-wrap items-end gap-3" novalidate>
                    @csrf
                    @method('DELETE')
                    <div class="w-full max-w-[400px] min-w-0 flex-[1_1_280px]">
                        <x-champ id="confirmation-email" name="confirmation_email" type="email" variante="formulaire" autocomplete="off" :label="__('comptes.suppression.confirmation')"
                            required :erreur="$errors->first('confirmation_email')" />
                    </div>
                    <button type="submit" class="inline-flex min-h-11 cursor-pointer items-center rounded-bouton border border-erreur bg-transparent px-4.5 text-sm font-semibold text-erreur transition-colors hover:bg-erreur hover:text-fond">
                        {{ __('comptes.suppression.bouton') }}
                    </button>
                </form>
            </section>
        </div>
    </div>
</x-layouts::app>
