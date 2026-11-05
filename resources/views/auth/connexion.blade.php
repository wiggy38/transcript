@php
    // Erreur de formulaire (identifiants, limitation) : affichée sous le mot de passe, comme dans la maquette.
    $erreurFormulaire = $errors->first('email') ?: $errors->first('password') ?: null;
    $champInvalide = $errors->has('email') || $errors->has('password');
    $classeChamp = 'min-h-12 w-full min-w-0 rounded-bouton bg-fond px-3.5 text-[15px] text-texte outline-none focus-visible:border-accent '
        .($champInvalide ? 'border-[1.5px] border-erreur' : 'border border-filet-contour');
@endphp

{{-- Maquette « Connexion - Nocturne » : carte du formulaire (480 px max, panneau 20 px, ombre héros),
     puis colonne d'accompagnement. Les blocs suivent l'ordre de la maquette. --}}
<x-layouts::auth :titre="__('comptes.connexion.titre')" entete=""
    principal="mx-auto grid w-full max-w-[1120px] items-stretch gap-8 px-4 pt-4 pb-16 min-[792px]:grid-cols-[minmax(0,480px)_minmax(0,1fr)]">

    <section aria-labelledby="titre-connexion" class="mx-auto flex w-full max-w-[480px] flex-col gap-5 rounded-panneau border border-filet bg-panneau p-7 shadow-heros">
        <div class="flex flex-col gap-1.5">
            <h1 id="titre-connexion" class="font-titre text-[26px] leading-[1.15] font-semibold tracking-[-0.03em]">{{ __('comptes.connexion.titre') }}</h1>
            <p class="text-[15px] text-texte-secondaire">{{ __('comptes.connexion.sous_titre') }}</p>
        </div>

        @if (request()->session()->has('lien_en_attente'))
            <p role="status" class="flex items-start gap-2.5 rounded-champ bg-surlignage px-3.5 py-3 text-sm text-sur-surlignage">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true" focusable="false" class="mt-[3px] shrink-0">
                    <path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"></path>
                    <path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"></path>
                </svg>
                <span>{{ __('comptes.connexion.lien_garde') }}</span>
            </p>
        @endif

        @if ($errors->has('google'))
            <p role="alert" class="flex items-start gap-2.5 rounded-champ px-3.5 py-3 text-sm text-texte"
               style="background-color: color-mix(in oklab, var(--color-erreur) 12%, transparent);">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true" focusable="false" class="mt-0.5 shrink-0 text-erreur">
                    <circle cx="12" cy="12" r="9"></circle>
                    <path d="M12 7.5v5.5"></path>
                    <path d="M12 16.5h.01"></path>
                </svg>
                <span>{{ $errors->first('google') }}</span>
            </p>
        @endif

        @if (config('voxia.comptes.google_actif') || config('voxia.comptes.facebook_actif'))
        <div class="grid gap-2.5">
            <div class="grid grid-cols-2 gap-2.5">
            @if (config('voxia.comptes.google_actif'))
                <a href="{{ route('google.redirect') }}"
                   class="inline-flex min-h-12 items-center justify-center gap-2.5 rounded-bouton border border-filet-contour bg-fond px-2 text-sm font-semibold whitespace-nowrap text-texte no-underline transition-colors hover:border-texte-secondaire">
                    <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true" focusable="false"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.4-.4-3.5z"/><path fill="#FF3D00" d="m6.3 14.7 6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-8l-6.5 5C9.5 39.6 16.2 44 24 44z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C36.9 39.2 44 34 44 24c0-1.3-.1-2.4-.4-3.5z"/></svg>
                    {{ __('comptes.google.bouton') }}
                </a>
            @endif
            {{-- Maquette : bouton Facebook, affiché seulement si VOXIA_FACEBOOK_LOGIN=true. Le lien reste « # » tant que la connexion Facebook n'existe pas côté serveur. --}}
            @if (config('voxia.comptes.facebook_actif'))
            <a href="#"
               class="inline-flex min-h-12 items-center justify-center gap-2.5 rounded-bouton border border-filet-contour bg-fond px-2 text-sm font-semibold whitespace-nowrap text-texte no-underline transition-colors hover:border-texte-secondaire">
                <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="12" fill="#1877F2"/><path d="M16.7 15.5 17.2 12h-3.4V9.8c0-1 .5-1.9 2-1.9h1.5v-3s-1.4-.2-2.7-.2c-2.8 0-4.6 1.7-4.6 4.7V12H7v3.5h3V24h3.8v-8.5z" fill="#FFFFFF"/></svg>
                {{ __('comptes.connexion.facebook_bouton') }}
            </a>
            @endif
            </div>
        </div>

        <p class="flex items-center gap-3 font-mono text-donnee text-texte-discret" aria-hidden="true">
            <span class="h-px flex-1 bg-filet"></span>{{ __('comptes.connexion.ou_email') }}<span class="h-px flex-1 bg-filet"></span>
        </p>
        @endif

        <form method="post" action="{{ route('login.store') }}" class="grid gap-4" novalidate>
            @csrf

            <div class="grid gap-1.5">
                <label for="email" class="text-sm font-semibold text-texte">{{ __('comptes.connexion.email') }}</label>
                <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}"
                       @if ($champInvalide) aria-invalid="true" @endif
                       @if ($erreurFormulaire) aria-describedby="erreur-form" @endif
                       class="{{ $classeChamp }}">
            </div>

            <div class="grid gap-1.5">
                <div class="flex items-baseline justify-between gap-3">
                    <label for="password" class="text-sm font-semibold text-texte">{{ __('comptes.connexion.mot_de_passe') }}</label>
                    <a href="{{ route('password.request') }}" class="text-aide whitespace-nowrap text-accent-texte hover:underline">{{ __('comptes.connexion.oubli') }}</a>
                </div>
                <input id="password" name="password" type="password" autocomplete="current-password" required
                       @if ($champInvalide) aria-invalid="true" @endif
                       @if ($erreurFormulaire) aria-describedby="erreur-form" @endif
                       class="{{ $classeChamp }}">
            </div>

            @if ($erreurFormulaire)
                <p id="erreur-form" role="alert" class="flex items-start gap-2 text-sm text-erreur">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true" focusable="false" class="mt-[3px] shrink-0">
                        <circle cx="12" cy="12" r="9"></circle>
                        <path d="M12 7.5v5.5"></path>
                        <path d="M12 16.5h.01"></path>
                    </svg>
                    <span>{{ $erreurFormulaire }}</span>
                </p>
            @endif

            <label class="inline-flex min-h-8 cursor-pointer items-center gap-2.5 text-sm text-texte-secondaire">
                <input type="checkbox" name="remember" value="1" class="size-[18px] accent-[var(--color-accent)]">
                {{ __('comptes.connexion.souvenir') }}
            </label>

            <button type="submit" class="min-h-12 w-full cursor-pointer rounded-bouton bg-accent text-[16px] font-semibold text-sur-accent transition-[filter] hover:brightness-110">{{ __('comptes.connexion.bouton') }}</button>
        </form>

        <p class="border-t border-filet pt-4 text-sm text-texte-discret">
            {{ __('comptes.connexion.pas_de_compte') }}
            <a href="{{ route('register') }}" class="font-semibold">{{ __('comptes.connexion.creer') }}</a>
        </p>
    </section>

    {{-- Colonne d'accompagnement (maquette) : décorative, visible sur toutes les tailles. --}}
    <aside aria-hidden="true" class="grid min-w-0 content-start gap-6 rounded-panneau border border-filet p-8"
        style="background-color: var(--color-panneau); background-image: radial-gradient(60% 50% at 30% 0%, rgb(120 100 255 / 0.18), transparent 70%), radial-gradient(color-mix(in oklab, var(--color-texte) 10%, transparent) 1px, transparent 1.5px); background-size: auto, 20px 20px;">
        <p class="font-titre text-[clamp(22px,2.6vw,30px)] leading-[1.15] font-semibold tracking-[-0.035em]">
            {{ __('comptes.apercu.titre') }} <span class="text-accent-texte">{{ __('comptes.apercu.mot') }}</span>
        </p>

        <div class="grid gap-0.5 rounded-[16px] border border-filet bg-fond p-3">
            @foreach (__('comptes.apercu.lignes') as $indice => $ligne)
                <div class="flex gap-3 rounded-[10px] px-2.5 py-2 {{ $indice === 1 ? 'bg-surlignage' : '' }}">
                    <span class="shrink-0 pt-px font-mono text-donnee {{ $indice === 1 ? 'text-surlignage-temps' : 'text-texte-discret' }}">{{ $ligne[0] }}</span>
                    <span class="text-sm {{ $indice === 1 ? 'text-sur-surlignage' : 'text-texte-secondaire' }}">{{ $ligne[1] }}</span>
                </div>
            @endforeach
        </div>

        <ul class="flex flex-wrap gap-1.5">
            @foreach (__('comptes.apercu.formats') as $format)
                <li class="rounded-pilule border border-filet-contour px-2.5 py-0.5 font-mono text-donnee text-texte-secondaire">{{ $format }}</li>
            @endforeach
        </ul>
    </aside>
</x-layouts::auth>
