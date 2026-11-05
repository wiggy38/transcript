{{-- Tarifs (handoff, écran 1) : Gratuit, Pro (recommandé, contour accent), Pack. Pas de tableau : sur mobile les
     cartes s'empilent. Pro bascule mensuel / annuel. Tous les montants et quotas viennent de config/voxia.php. --}}
@php
    $plans = config('voxia.plans');
    $formater = fn (float|int $valeur): string => floor($valeur) == $valeur
        ? (string) (int) $valeur
        : str_replace('.', __('accueil.separateur_decimal'), (string) $valeur);
    $montant = fn (float|int $valeur): string => __('accueil.montant', ['montant' => $formater($valeur)]);

    $connecte = auth()->check();
    $urlMois = $connecte ? route('paiement.show', 'pro_mensuel') : route('register');
    $urlAn = $connecte ? route('paiement.show', 'pro_annuel') : route('register');
    $urlPack = $connecte ? route('paiement.show', 'pack') : route('register');

    $minutesGratuit = intdiv($plans['free']['max_video_seconds'], 60);
    $heuresVideoPro = intdiv($plans['pro']['max_video_seconds'], 3600);
    $heuresPro = $plans['pro']['hours_per_month'];
    $heuresPack = $plans['pack']['hours'];
    $moisPack = $plans['pack']['validity_months'];

    $classeCarte = 'flex min-w-0 flex-[1_1_300px] flex-col gap-0 rounded-panneau border border-filet bg-panneau p-6 md:p-7';
    $classeLigne = 'border-t border-filet py-2.5 text-petit text-texte-secondaire';
@endphp

<section id="tarifs" class="scroll-mt-24 border-t border-filet bg-panneau py-16" aria-labelledby="titre-tarifs">
    <div class="mx-auto w-full max-w-[1120px] px-4">
    <div class="mb-10 flex flex-wrap items-end justify-between gap-4">
        <h2 id="titre-tarifs" class="titre-h2">{{ __('accueil.tarifs.titre') }}</h2>
        <p class="max-w-[26em] text-texte-secondaire">{{ __('accueil.tarifs.intro') }}</p>
    </div>

    <div class="flex flex-wrap items-stretch gap-4">
        {{-- Gratuit --}}
        <article class="{{ $classeCarte }} bg-fond" aria-labelledby="plan-gratuit">
            <h3 id="plan-gratuit" class="font-mono text-donnee font-medium text-texte-discret">{{ $plans['free']['label'] }}</h3>
            <p class="mt-3.5 font-titre text-[28px] leading-none font-semibold tracking-[-0.02em]">{{ $montant($plans['free']['price_usd']) }}</p>

            <ul class="m-0 mt-6 list-none p-0">
                <li class="{{ $classeLigne }}">{{ __('accueil.tarifs.gratuit.lignes.0', ['nombre' => $plans['free']['videos_per_month']]) }}</li>
                <li class="{{ $classeLigne }}">{{ __('accueil.tarifs.gratuit.lignes.1', ['minutes' => $minutesGratuit]) }}</li>
                <li class="{{ $classeLigne }}">{{ __('accueil.tarifs.gratuit.lignes.2') }}</li>
                <li class="{{ $classeLigne }} border-b">{{ __('accueil.tarifs.gratuit.lignes.3') }}</li>
            </ul>

            <a href="{{ $connecte ? route('tableau-de-bord') : route('register') }}" class="mt-7 inline-flex min-h-11 items-center self-start rounded-bouton border border-filet-contour px-5 font-semibold text-texte no-underline hover:border-accent-texte hover:text-accent-texte">{{ __('accueil.tarifs.gratuit.cta') }}</a>
        </article>

        {{-- Pro : contour accent, mention « Recommandé », bascule mensuel / annuel --}}
        <article x-data="{ periode: 'mois' }" class="{{ $classeCarte }} border-2 border-accent bg-fond" aria-labelledby="plan-pro">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 id="plan-pro" class="font-mono text-donnee font-medium text-accent-texte">{{ $plans['pro']['label'] }}</h3>
                <span class="font-mono text-donnee text-accent-texte">{{ __('accueil.tarifs.recommande') }}</span>
            </div>

            <div role="group" aria-label="{{ __('accueil.tarifs.pro.periode') }}" class="mt-3 inline-flex w-fit rounded-bouton border border-filet-contour p-0.5 font-mono text-donnee">
                <button type="button" class="min-h-9 cursor-pointer rounded-[9px] px-3 transition-colors"
                    :class="periode === 'mois' ? 'bg-texte text-fond' : 'text-texte-secondaire hover:text-texte'"
                    :aria-pressed="(periode === 'mois').toString()"
                    @click="periode = 'mois'">{{ __('accueil.tarifs.pro.mois') }}</button>
                <button type="button" class="min-h-9 cursor-pointer rounded-[9px] px-3 transition-colors"
                    :class="periode === 'an' ? 'bg-texte text-fond' : 'text-texte-secondaire hover:text-texte'"
                    :aria-pressed="(periode === 'an').toString()"
                    @click="periode = 'an'">{{ __('accueil.tarifs.pro.an') }}</button>
            </div>

            <p class="mt-3.5 font-titre text-[28px] leading-none font-semibold tracking-[-0.02em]">
                <span x-show="periode === 'mois'">
                    {{ $montant($plans['pro']['price_monthly_usd']) }}<span class="ml-1 font-sans text-[13px] font-normal tracking-normal text-texte-discret">{{ __('accueil.tarifs.pro.suffixe_mois') }}</span>
                </span>
                <span x-show="periode === 'an'" x-cloak>
                    {{ $montant($plans['pro']['price_yearly_usd']) }}<span class="ml-1 font-sans text-[13px] font-normal tracking-normal text-texte-discret">{{ __('accueil.tarifs.pro.suffixe_an') }}</span>
                </span>
            </p>

            <ul class="m-0 mt-6 list-none p-0">
                <li class="{{ $classeLigne }}">{{ __('accueil.tarifs.pro.lignes.0', ['heures' => $heuresPro]) }}</li>
                <li class="{{ $classeLigne }}">{{ __('accueil.tarifs.pro.lignes.1', ['heures' => $heuresVideoPro]) }}</li>
                <li class="{{ $classeLigne }}">{{ __('accueil.tarifs.pro.lignes.2') }}</li>
                <li class="{{ $classeLigne }}">{{ __('accueil.tarifs.pro.lignes.3') }}</li>
                <li class="{{ $classeLigne }}">{{ __('accueil.tarifs.pro.lignes.4') }}</li>
                <li class="{{ $classeLigne }} border-b">{{ __('accueil.tarifs.pro.lignes.5') }}</li>
            </ul>

            <a href="{{ $urlMois }}"
               x-bind:href="periode === 'an' ? '{{ $urlAn }}' : '{{ $urlMois }}'"
               class="mt-7 inline-flex min-h-11 items-center self-start rounded-bouton bg-accent px-5 font-semibold text-sur-accent no-underline hover:brightness-110">{{ __('accueil.tarifs.pro.cta') }}</a>
        </article>

        {{-- Pack : achat unique --}}
        <article class="{{ $classeCarte }} bg-fond" aria-labelledby="plan-pack">
            <h3 id="plan-pack" class="font-mono text-donnee font-medium text-texte-discret">{{ $plans['pack']['label'] }}</h3>
            <p class="mt-3.5 font-titre text-[28px] leading-none font-semibold tracking-[-0.02em]">
                {{ $montant($plans['pack']['price_usd']) }}<span class="ml-1 font-sans text-[13px] font-normal tracking-normal text-texte-discret">{{ __('accueil.tarifs.pack.suffixe') }}</span>
            </p>

            <ul class="m-0 mt-6 list-none p-0">
                <li class="{{ $classeLigne }}">{{ __('accueil.tarifs.pack.lignes.0', ['heures' => $heuresPack, 'mois' => $moisPack]) }}</li>
                <li class="{{ $classeLigne }}">{{ __('accueil.tarifs.pack.lignes.1') }}</li>
                <li class="{{ $classeLigne }}">{{ __('accueil.tarifs.pack.lignes.2') }}</li>
                <li class="{{ $classeLigne }} border-b">{{ __('accueil.tarifs.pack.lignes.3') }}</li>
            </ul>

            <a href="{{ $urlPack }}" class="mt-7 inline-flex min-h-11 items-center self-start rounded-bouton border border-filet-contour px-5 font-semibold text-texte no-underline hover:border-accent-texte hover:text-accent-texte">{{ __('accueil.tarifs.pack.cta') }}</a>
        </article>
    </div>

    <p class="mt-5 text-aide text-texte-discret">{{ __('accueil.tarifs.provisoire') }}</p>
    </div>
</section>
