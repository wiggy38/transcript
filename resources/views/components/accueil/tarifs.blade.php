{{-- Tarifs (maquette Signal) : Gratuit, Pro (bascule mensuel / annuel), Pack. Tous les montants et quotas viennent de config/voxia.php. --}}
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
@endphp

<section id="tarifs" class="pt-2.5" aria-labelledby="titre-tarifs">
    <div class="mb-12 flex flex-wrap items-end justify-between gap-6">
        <h2 id="titre-tarifs" class="text-[clamp(36px,4.6vw,64px)] leading-none font-semibold tracking-[-0.04em]">{{ __('accueil.tarifs.titre') }}</h2>
        <p class="max-w-[26em] text-texte-secondaire">{{ __('accueil.tarifs.intro') }}</p>
    </div>

    <div class="flex flex-wrap gap-px overflow-hidden rounded-panneau border border-filet bg-filet">
        {{-- Gratuit --}}
        <article class="flex min-w-0 flex-[1_1_300px] flex-col bg-panneau p-6 md:p-10" aria-labelledby="plan-gratuit">
            <h3 id="plan-gratuit" class="font-mono text-[13px] font-medium text-texte-discret uppercase">{{ $plans['free']['label'] }}</h3>
            <p class="mt-3.5 text-[52px] leading-none font-semibold tracking-[-0.04em]">{{ $montant($plans['free']['price_usd']) }}</p>

            <ul class="m-0 mt-7 list-none p-0 text-texte-secondaire">
                <li class="border-t border-filet py-2.5">{{ __('accueil.tarifs.gratuit.lignes.0', ['nombre' => $plans['free']['videos_per_month']]) }}</li>
                <li class="border-t border-filet py-2.5">{{ __('accueil.tarifs.gratuit.lignes.1', ['minutes' => $minutesGratuit]) }}</li>
                <li class="border-t border-filet py-2.5">{{ __('accueil.tarifs.gratuit.lignes.2') }}</li>
                <li class="border-y border-filet py-2.5">{{ __('accueil.tarifs.gratuit.lignes.3') }}</li>
            </ul>

            <a href="{{ $connecte ? route('tableau-de-bord') : route('register') }}" class="mt-8 inline-flex min-h-[46px] items-center self-start rounded-bouton border border-filet-contour px-5 font-semibold text-texte no-underline hover:border-accent hover:text-accent-texte">{{ __('accueil.tarifs.gratuit.cta') }}</a>
        </article>

        {{-- Pro : bascule mensuel / annuel --}}
        <article x-data="{ periode: 'mois' }" class="relative flex min-w-0 flex-[1_1_300px] flex-col bg-panneau-eleve p-6 md:p-10" aria-labelledby="plan-pro">
            <span aria-hidden="true" class="absolute inset-x-0 top-0 h-[3px] bg-accent"></span>

            <div class="flex flex-wrap items-center justify-between gap-4">
                <h3 id="plan-pro" class="font-mono text-[13px] font-medium text-accent-texte uppercase">{{ $plans['pro']['label'] }}</h3>

                <div role="group" aria-label="{{ __('accueil.tarifs.pro.periode') }}" class="inline-flex rounded-bouton border border-filet-contour p-0.5 font-mono text-xs">
                    <button type="button" class="min-h-9 cursor-pointer rounded-[3px] px-3 transition-colors"
                        :class="periode === 'mois' ? 'bg-texte text-fond' : 'text-texte-secondaire hover:text-texte'"
                        :aria-pressed="(periode === 'mois').toString()"
                        @click="periode = 'mois'">{{ __('accueil.tarifs.pro.mois') }}</button>
                    <button type="button" class="min-h-9 cursor-pointer rounded-[3px] px-3 transition-colors"
                        :class="periode === 'an' ? 'bg-texte text-fond' : 'text-texte-secondaire hover:text-texte'"
                        :aria-pressed="(periode === 'an').toString()"
                        @click="periode = 'an'">{{ __('accueil.tarifs.pro.an') }}</button>
                </div>
            </div>

            <p class="mt-3.5 text-[52px] leading-none font-semibold tracking-[-0.04em]">
                <span x-show="periode === 'mois'">
                    {{ $montant($plans['pro']['price_monthly_usd']) }}<span class="ml-1 text-lg font-normal tracking-normal text-texte-discret">{{ __('accueil.tarifs.pro.suffixe_mois') }}</span>
                </span>
                <span x-show="periode === 'an'" x-cloak>
                    {{ $montant($plans['pro']['price_yearly_usd']) }}<span class="ml-1 text-lg font-normal tracking-normal text-texte-discret">{{ __('accueil.tarifs.pro.suffixe_an') }}</span>
                </span>
            </p>

            <ul class="m-0 mt-7 list-none p-0 text-texte-secondaire">
                <li class="border-t border-filet py-2.5">{{ __('accueil.tarifs.pro.lignes.0', ['heures' => $heuresPro]) }}</li>
                <li class="border-t border-filet py-2.5">{{ __('accueil.tarifs.pro.lignes.1', ['heures' => $heuresVideoPro]) }}</li>
                <li class="border-t border-filet py-2.5">{{ __('accueil.tarifs.pro.lignes.2') }}</li>
                <li class="border-t border-filet py-2.5">{{ __('accueil.tarifs.pro.lignes.3') }}</li>
                <li class="border-t border-filet py-2.5">{{ __('accueil.tarifs.pro.lignes.4') }}</li>
                <li class="border-y border-filet py-2.5">{{ __('accueil.tarifs.pro.lignes.5') }}</li>
            </ul>

            <a href="{{ $urlMois }}"
               x-bind:href="periode === 'an' ? '{{ $urlAn }}' : '{{ $urlMois }}'"
               class="mt-8 inline-flex min-h-[46px] items-center self-start rounded-bouton bg-accent px-5 font-semibold text-sur-accent no-underline hover:opacity-90">{{ __('accueil.tarifs.pro.cta') }}</a>
        </article>

        {{-- Pack : achat unique --}}
        <article class="flex min-w-0 flex-[1_1_300px] flex-col bg-panneau p-6 md:p-10" aria-labelledby="plan-pack">
            <h3 id="plan-pack" class="font-mono text-[13px] font-medium text-texte-discret uppercase">{{ $plans['pack']['label'] }}</h3>
            <p class="mt-3.5 text-[52px] leading-none font-semibold tracking-[-0.04em]">
                {{ $montant($plans['pack']['price_usd']) }}<span class="ml-1 text-lg font-normal tracking-normal text-texte-discret">{{ __('accueil.tarifs.pack.suffixe') }}</span>
            </p>

            <ul class="m-0 mt-7 list-none p-0 text-texte-secondaire">
                <li class="border-t border-filet py-2.5">{{ __('accueil.tarifs.pack.lignes.0', ['heures' => $heuresPack, 'mois' => $moisPack]) }}</li>
                <li class="border-t border-filet py-2.5">{{ __('accueil.tarifs.pack.lignes.1') }}</li>
                <li class="border-t border-filet py-2.5">{{ __('accueil.tarifs.pack.lignes.2') }}</li>
                <li class="border-y border-filet py-2.5">{{ __('accueil.tarifs.pack.lignes.3') }}</li>
            </ul>

            <a href="{{ $urlPack }}" class="mt-8 inline-flex min-h-[46px] items-center self-start rounded-bouton border border-filet-contour px-5 font-semibold text-texte no-underline hover:border-accent hover:text-accent-texte">{{ __('accueil.tarifs.pack.cta') }}</a>
        </article>
    </div>
</section>
