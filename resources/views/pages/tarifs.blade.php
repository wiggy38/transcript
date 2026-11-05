{{-- Page Tarifs : comparatif détaillé et FAQ. Tous les montants, quotas et durées viennent de config/voxia.php. --}}
@php
    $plans = config('voxia.plans');
    $remplacements = \App\Support\PagesLegales::remplacements();

    $formater = fn (float|int $valeur): string => floor($valeur) == $valeur
        ? (string) (int) $valeur
        : str_replace('.', __('accueil.separateur_decimal'), (string) $valeur);
    $montant = fn (float|int $valeur): string => __('accueil.montant', ['montant' => $formater($valeur)]);
    $liste = fn (array $formats): string => strtoupper(implode(', ', $formats));
    $oui = __('pages.tarifs.oui');
    $non = __('pages.tarifs.non');

    $connecte = auth()->check();
    $ctaGratuit = $connecte ? route('tableau-de-bord') : route('register');
    $ctaPro = $connecte ? route('paiement.show', 'pro_mensuel') : route('register');
    $ctaPack = $connecte ? route('paiement.show', 'pack') : route('register');

    $heuresVideoPayant = intdiv((int) $plans['pro']['max_video_seconds'], 3600);
    $creditsPro = $plans['pro']['ai_credits_per_month'] > 0 ? __('pages.tarifs.valeurs.credits_pro') : $non;
@endphp

<x-layouts::marketing :titre="__('pages.tarifs.titre').' — '.__('interface.marque')" :description="__('pages.tarifs.intro')">
    <section class="pt-2.5" aria-labelledby="titre-page-tarifs">
        <p class="font-mono text-xs font-medium text-accent-texte">{{ __('interface.pied.tarifs') }}</p>
        <h1 id="titre-page-tarifs" class="mt-4 text-[clamp(40px,5vw,64px)] leading-none font-semibold tracking-[-0.04em]">{{ __('pages.tarifs.titre') }}</h1>
        <p class="mt-6 max-w-[40em] text-lg text-texte-secondaire">{{ __('pages.tarifs.intro') }}</p>

        <div class="mt-12 overflow-x-auto rounded-panneau border border-filet">
            <table class="w-full min-w-[640px] border-collapse text-left text-[14px]">
                <caption class="sr-only">{{ __('pages.tarifs.titre') }}</caption>
                <thead>
                    <tr class="border-b border-filet bg-panneau">
                        <th scope="col" class="p-5 font-mono text-xs font-medium text-texte-discret">{{ __('pages.tarifs.colonnes.fonctionnalite') }}</th>
                        <th scope="col" class="p-5 font-mono text-xs font-medium text-texte-discret">{{ $plans['free']['label'] }}</th>
                        <th scope="col" class="p-5 font-mono text-xs font-medium text-accent-texte">{{ $plans['pro']['label'] }}</th>
                        <th scope="col" class="p-5 font-mono text-xs font-medium text-texte-discret">{{ $plans['pack']['label'] }}</th>
                    </tr>
                </thead>
                <tbody class="text-texte-secondaire">
                    <tr class="border-b border-filet">
                        <th scope="row" class="p-5 font-medium text-texte">{{ __('pages.tarifs.lignes.prix') }}</th>
                        <td class="p-5 font-semibold text-texte">{{ __('pages.tarifs.valeurs.prix_gratuit', ['montant' => $montant($plans['free']['price_usd'])]) }}</td>
                        <td class="p-5 font-semibold text-texte">{{ __('pages.tarifs.valeurs.prix_mois', ['montant' => $montant($plans['pro']['price_monthly_usd']), 'an' => $montant($plans['pro']['price_yearly_usd'])]) }}</td>
                        <td class="p-5 font-semibold text-texte">{{ __('pages.tarifs.valeurs.prix_pack', ['montant' => $montant($plans['pack']['price_usd'])]) }}</td>
                    </tr>
                    <tr class="border-b border-filet">
                        <th scope="row" class="p-5 font-medium text-texte">{{ __('pages.tarifs.lignes.volume') }}</th>
                        <td class="p-5">{{ __('pages.tarifs.valeurs.volume_gratuit', ['nombre' => $plans['free']['videos_per_month']]) }}</td>
                        <td class="p-5">{{ __('pages.tarifs.valeurs.volume_pro', ['heures' => $plans['pro']['hours_per_month']]) }}</td>
                        <td class="p-5">{{ __('pages.tarifs.valeurs.volume_pack', ['heures' => $plans['pack']['hours'], 'mois' => $plans['pack']['validity_months']]) }}</td>
                    </tr>
                    <tr class="border-b border-filet">
                        <th scope="row" class="p-5 font-medium text-texte">{{ __('pages.tarifs.lignes.duree') }}</th>
                        <td class="p-5">{{ __('pages.tarifs.valeurs.duree_gratuit', ['minutes' => $remplacements['minutes']]) }}</td>
                        <td class="p-5">{{ __('pages.tarifs.valeurs.duree_payant', ['heures' => $heuresVideoPayant]) }}</td>
                        <td class="p-5">{{ __('pages.tarifs.valeurs.duree_payant', ['heures' => intdiv((int) $plans['pack']['max_video_seconds'], 3600)]) }}</td>
                    </tr>
                    <tr class="border-b border-filet">
                        <th scope="row" class="p-5 font-medium text-texte">{{ __('pages.tarifs.lignes.exports') }}</th>
                        <td class="p-5">{{ $liste($plans['free']['exports']) }}</td>
                        <td class="p-5">{{ $liste($plans['pro']['exports']) }}</td>
                        <td class="p-5">{{ $liste($plans['pack']['exports']) }}</td>
                    </tr>
                    <tr class="border-b border-filet">
                        <th scope="row" class="p-5 font-medium text-texte">{{ __('pages.tarifs.lignes.mp3') }}</th>
                        <td class="p-5">{{ $oui }}</td>
                        <td class="p-5">{{ $oui }}</td>
                        <td class="p-5">{{ $oui }}</td>
                    </tr>
                    <tr class="border-b border-filet">
                        <th scope="row" class="p-5 font-medium text-texte">{{ __('pages.tarifs.lignes.historique') }}</th>
                        <td class="p-5">{{ __('pages.tarifs.valeurs.historique_gratuit', ['jours' => $plans['free']['history_days']]) }}</td>
                        <td class="p-5">{{ $plans['pro']['history_days'] === null ? __('pages.tarifs.valeurs.historique_illimite') : $non }}</td>
                        <td class="p-5">{{ $plans['pack']['history_days'] === null ? __('pages.tarifs.valeurs.historique_illimite') : $non }}</td>
                    </tr>
                    <tr class="border-b border-filet">
                        <th scope="row" class="p-5 font-medium text-texte">{{ __('pages.tarifs.lignes.recherche') }}</th>
                        <td class="p-5">{{ $plans['free']['recherche'] ? $oui : $non }}</td>
                        <td class="p-5">{{ $plans['pro']['recherche'] ? $oui : $non }}</td>
                        <td class="p-5">{{ $plans['pack']['recherche'] ? $oui : $non }}</td>
                    </tr>
                    <tr class="border-b border-filet">
                        <th scope="row" class="p-5 font-medium text-texte">{{ __('pages.tarifs.lignes.attente') }}</th>
                        <td class="p-5">{{ __('pages.tarifs.valeurs.attente_standard') }}</td>
                        <td class="p-5">{{ __('pages.tarifs.valeurs.attente_prioritaire') }}</td>
                        <td class="p-5">{{ __('pages.tarifs.valeurs.attente_standard') }}</td>
                    </tr>
                    <tr class="border-b border-filet">
                        <th scope="row" class="p-5 font-medium text-texte">{{ __('pages.tarifs.lignes.credits') }}</th>
                        <td class="p-5">{{ $plans['free']['ai_credits_per_month'] > 0 ? $oui : $non }}</td>
                        <td class="p-5">{{ $creditsPro }}</td>
                        <td class="p-5">{{ $plans['pack']['ai_credits_per_month'] > 0 ? $oui : $non }}</td>
                    </tr>
                    <tr>
                        <th scope="row" class="p-5"><span class="sr-only">{{ __('pages.tarifs.colonnes.fonctionnalite') }}</span></th>
                        <td class="p-5"><x-bouton :href="$ctaGratuit" variante="filet">{{ __('pages.tarifs.cta.gratuit') }}</x-bouton></td>
                        <td class="p-5"><x-bouton :href="$ctaPro">{{ __('pages.tarifs.cta.pro') }}</x-bouton></td>
                        <td class="p-5"><x-bouton :href="$ctaPack" variante="filet">{{ __('pages.tarifs.cta.pack') }}</x-bouton></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="pt-2.5 pb-10" aria-labelledby="titre-faq-tarifs">
        <div class="flex flex-wrap gap-8 border-t border-filet pt-8">
            <h2 id="titre-faq-tarifs" class="flex-[1_1_280px] font-mono text-xs font-medium text-texte-discret">{{ __('pages.tarifs.faq.titre') }}</h2>

            <dl class="m-0 min-w-0 flex-[3_1_640px]">
                @foreach (__('pages.tarifs.faq.liste') as $numero => $entree)
                    <dt class="{{ $numero > 0 ? 'pt-6' : '' }} text-xl font-semibold tracking-[-0.015em]">{{ __('pages.tarifs.faq.liste.'.$numero.'.question') }}</dt>
                    <dd class="m-0 mt-1.5 border-b border-filet pb-6 text-texte-secondaire">{{ __('pages.tarifs.faq.liste.'.$numero.'.reponse', $remplacements) }}</dd>
                @endforeach
            </dl>
        </div>
    </section>
</x-layouts::marketing>
