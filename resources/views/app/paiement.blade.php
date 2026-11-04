<x-layouts::app :titre="__('abonnement.paiement.titre')" actif="compte">
    <h1 class="text-3xl font-semibold tracking-tight">{{ __('abonnement.paiement.titre_'.$produit) }}</h1>

    <div class="mt-10 grid max-w-[880px] gap-8">
        <x-panneau class="p-6">
            <p class="text-texte">{{ __('abonnement.paiement.montant_'.$produit, $valeurs) }}</p>

            @if ($apercu)
                <p class="mt-3 text-sm text-texte-secondaire">{{ __('abonnement.paiement.apercu', ['titre' => $apercu->titre ?? __('abonnement.paiement.video_sans_titre')]) }}</p>
            @endif

            <p class="mt-3 text-sm text-texte-secondaire">{{ __('abonnement.paiement.taxes') }}</p>

            <p id="paiement-erreur" role="alert" class="mt-4 hidden border-l-2 border-accent py-1 pl-3 text-sm text-texte"></p>

            <div class="mt-6">
                <x-bouton id="ouvrir-paiement" type="button">{{ __('abonnement.paiement.payer') }}</x-bouton>
            </div>
        </x-panneau>

        <p class="text-sm text-texte-secondaire">
            <a href="{{ route('abonnement') }}" class="text-accent-texte">{{ __('abonnement.paiement.retour') }}</a>
        </p>
    </div>

    {{-- Paddle.js : l'overlay de paiement est ouvert par le prestataire ; aucune donnée de carte ne passe par Voxia. --}}
    <script src="https://cdn.paddle.com/paddle/v2/paddle.js"></script>
    <script>
        (function () {
            const ouverture = @json($ouverture);
            const bouton = document.getElementById('ouvrir-paiement');
            const erreur = document.getElementById('paiement-erreur');

            function signaler(message) {
                erreur.textContent = message;
                erreur.classList.remove('hidden');
            }

            if (typeof Paddle === 'undefined') {
                bouton.disabled = true;
                signaler(@json(__('abonnement.paiement.indisponible')));
                return;
            }

            if (ouverture.environnement === 'sandbox') {
                Paddle.Environment.set('sandbox');
            }

            Paddle.Initialize({ token: ouverture.jeton });

            bouton.addEventListener('click', function () {
                Paddle.Checkout.open(ouverture.options);
            });
        })();
    </script>
</x-layouts::app>
