<x-layouts::marketing :titre="__('accueil.titre_page')">
    <x-slot:tete>
        <x-accueil.hero />
    </x-slot:tete>

    <x-accueil.fonctionnement />
    <x-accueil.livrables />
    <x-accueil.usages />
    <x-accueil.tarifs />
    <x-accueil.questions />

    <section class="border-t border-filet bg-panneau py-16 text-center" aria-labelledby="titre-cta">
        <div class="mx-auto w-full max-w-[1120px] px-4">
        <h2 id="titre-cta" class="titre-h2 mx-auto max-w-[20ch]">{{ __('accueil.cta.titre') }}</h2>
        <a href="#hero-lien" class="mt-7 inline-flex min-h-11 items-center rounded-bouton bg-accent px-6 font-semibold text-sur-accent no-underline hover:brightness-110">{{ __('accueil.cta.bouton') }}</a>
        </div>
    </section>
</x-layouts::marketing>
