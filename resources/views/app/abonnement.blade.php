<x-layouts::app :titre="__('abonnement.titre')" actif="compte">
    @php
        $cleStatut = session('statut');
        $messageStatut = $cleStatut ? __('abonnement.statut.'.$cleStatut) : null;
        $dateCourte = fn ($date) => $date ? \App\Support\Formats::date($date) : '';
    @endphp

    @if ($messageStatut && $messageStatut !== 'abonnement.statut.'.$cleStatut)
        <p role="status" class="mb-8 border-l-2 border-accent py-1 pl-3 text-sm text-texte">{{ $messageStatut }}</p>
    @endif

    <h1 class="text-2xl font-semibold tracking-tight">{{ __('abonnement.titre') }}</h1>

    @if ($impayee)
        <p role="alert" class="mt-6 border-l-2 border-accent py-1 pl-3 text-sm text-texte">{{ __('abonnement.impayee') }}</p>
    @endif

    <div class="mt-10 grid max-w-[880px] gap-8">
        {{-- Formule actuelle --}}
        <x-panneau class="p-6">
            <h2 class="text-lg font-semibold">{{ __('abonnement.formule.titre') }}</h2>

            @if ($pro)
                <p class="mt-2 text-texte">
                    {{ __('abonnement.formule.pro') }} —
                    {{ $formule === 'pro_annuel' ? __('abonnement.formule.annuel') : __('abonnement.formule.mensuel') }}
                </p>

                @if ($resiliee)
                    <p class="mt-2 text-texte-secondaire">{{ __('abonnement.formule.fin_acces', ['date' => $dateCourte($finAcces)]) }}</p>
                @elseif ($prochaineFacturation)
                    <p class="mt-2 text-texte-secondaire">{{ __('abonnement.formule.prochaine', ['date' => $dateCourte($prochaineFacturation)]) }}</p>
                @endif

                <div class="mt-6 flex flex-wrap gap-3">
                    @if (! $resiliee)
                        @if ($formule === 'pro_annuel')
                            <form method="post" action="{{ route('abonnement.formule') }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="formule" value="pro_mensuel">
                                <x-bouton type="submit" variante="filet">{{ __('abonnement.formule.passer_mensuel') }}</x-bouton>
                            </form>
                        @else
                            <form method="post" action="{{ route('abonnement.formule') }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="formule" value="pro_annuel">
                                <x-bouton type="submit" variante="filet">{{ __('abonnement.formule.passer_annuel') }}</x-bouton>
                            </form>
                        @endif

                        <form method="post" action="{{ route('abonnement.resilier') }}">
                            @csrf
                            <x-bouton type="submit" variante="filet">{{ __('abonnement.formule.resilier') }}</x-bouton>
                        </form>
                    @else
                        <form method="post" action="{{ route('abonnement.reprendre') }}">
                            @csrf
                            <x-bouton type="submit">{{ __('abonnement.formule.annuler_resiliation') }}</x-bouton>
                        </form>
                    @endif
                </div>
            @else
                <p class="mt-2 text-texte">{{ __('abonnement.formule.'.($plan?->value ?? 'free')) }}</p>
                <p class="mt-2 text-texte-secondaire">{{ __('abonnement.formule.aucun_pro') }}</p>

                <div class="mt-6 flex flex-wrap gap-3">
                    <x-bouton :href="route('paiement.show', ['produit' => 'pro_mensuel'])" variante="principal">{{ __('abonnement.formule.souscrire_mensuel', ['montant' => (int) config('voxia.plans.pro.price_monthly_usd')]) }}</x-bouton>
                    <x-bouton :href="route('paiement.show', ['produit' => 'pro_annuel'])" variante="filet">{{ __('abonnement.formule.souscrire_annuel', ['montant' => (int) config('voxia.plans.pro.price_yearly_usd')]) }}</x-bouton>
                    <x-bouton :href="route('paiement.show', ['produit' => 'pack'])" variante="filet">{{ __('abonnement.formule.acheter_pack', ['montant' => (int) config('voxia.plans.pack.price_usd')]) }}</x-bouton>
                </div>
            @endif
        </x-panneau>

        {{-- Packs achetés et leur expiration --}}
        <x-panneau class="p-6">
            <h2 class="text-lg font-semibold">{{ __('abonnement.packs.titre') }}</h2>

            @if ($packs->isEmpty())
                <p class="mt-2 text-texte-secondaire">{{ __('abonnement.packs.aucun') }}</p>
            @else
                <ul class="mt-4 grid gap-3">
                    @foreach ($packs as $pack)
                        <li class="flex flex-wrap justify-between gap-2 border-t border-filet pt-3 text-sm">
                            <span>{{ __('abonnement.packs.ligne', ['heures' => \App\Support\Formats::nombre($pack->secondes / 3600)]) }}</span>
                            <span class="text-texte-secondaire">{{ __('abonnement.packs.expire', ['date' => $dateCourte($pack->expire_at)]) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-panneau>

        {{-- Factures --}}
        <x-panneau class="p-6">
            <h2 class="text-lg font-semibold">{{ __('abonnement.factures.titre') }}</h2>
            <p class="mt-2 text-sm text-texte-secondaire">{{ __('abonnement.factures.aide') }}</p>

            @if ($factures === [])
                <p class="mt-4 text-texte-secondaire">{{ __('abonnement.factures.aucune') }}</p>
            @else
                <ul class="mt-4 grid gap-3">
                    @foreach ($factures as $facture)
                        <li class="flex flex-wrap items-center justify-between gap-2 border-t border-filet pt-3 text-sm">
                            <span>{{ $dateCourte($facture['date']) }} · {{ $facture['numero'] ?? __('abonnement.factures.sans_numero') }}</span>
                            <span class="font-mono">{{ $facture['montant'] }}</span>
                            <span class="text-texte-discret">{{ __('abonnement.factures.statuts.'.$facture['statut']) }}</span>
                            @if ($facture['numero'])
                                <a href="{{ route('abonnement.facture', ['facture' => $facture['id']]) }}" class="text-accent-texte">{{ __('abonnement.factures.telecharger') }}</a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-panneau>
    </div>
</x-layouts::app>
