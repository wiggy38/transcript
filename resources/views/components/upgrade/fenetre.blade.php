{{-- Fenêtre d'upgrade : une fenêtre superposée, l'écran d'origine reste en place (cahier §2, parcours d'upgrade).
     Le message du déclencheur est passé en slot ; les plans viennent de PresentationTarifs.
     « retour » : l'adresse de l'écran d'origine, rejointe après le paiement. --}}
@props(['id', 'titre', 'retour', 'plans' => ['pro', 'pack'], 'transcriptionId' => null, 'ouvrirAuChargement' => false])

@php
    $cartes = app(\App\Services\Plans\PresentationTarifs::class)->cartes($plans, $retour, $transcriptionId);
@endphp

<x-fenetre-superposee :id="$id" :titre="$titre" grande :ouvrir-au-chargement="$ouvrirAuChargement">
    <div class="flex flex-col gap-8">
        <div class="text-texte-secondaire">{{ $slot }}</div>

        <section aria-label="{{ __('upgrade.tarifs.titre') }}" class="flex flex-col gap-4">
            <h3 class="font-mono text-xs uppercase tracking-wide text-texte-discret">{{ __('upgrade.tarifs.titre') }}</h3>

            <div class="grid gap-4 md:grid-cols-2 {{ count($cartes) > 2 ? 'lg:grid-cols-3' : '' }}">
                @foreach ($cartes as $carte)
                    <x-upgrade.carte-plan :carte="$carte" />
                @endforeach
            </div>

            <p class="font-mono text-[11px] text-texte-discret">{{ __('upgrade.tarifs.pied') }}</p>
        </section>
    </div>
</x-fenetre-superposee>
