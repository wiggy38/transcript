<x-layouts::marketing :titre="__('comptes.verification.titre')">
    <section class="mx-auto max-w-md py-16" aria-labelledby="titre-verification">
        <x-panneau class="p-8">
            <h1 id="titre-verification" class="text-2xl font-semibold tracking-tight">{{ __('comptes.verification.titre') }}</h1>
            <p class="mt-2 text-texte-secondaire">{{ __('comptes.verification.texte') }}</p>

            @if (session('status') === 'verification-link-sent')
                <p role="status" class="mt-6 border-l-2 border-accent py-1 pl-3 text-sm text-texte">{{ __('comptes.verification.lien_renvoye') }}</p>
            @endif

            <div class="mt-8 flex flex-col gap-4">
                <form method="post" action="{{ route('verification.send') }}">
                    @csrf
                    <x-bouton type="submit" variante="filet" class="w-full">{{ __('comptes.verification.renvoyer') }}</x-bouton>
                </form>

                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <x-bouton type="submit" variante="filet" class="w-full">{{ __('comptes.verification.deconnexion') }}</x-bouton>
                </form>
            </div>
        </x-panneau>
    </section>
</x-layouts::marketing>
