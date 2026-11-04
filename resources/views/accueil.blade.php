<x-layouts::marketing :titre="__('accueil.titre_page')">
    <x-slot:tete>
        <x-accueil.hero />
    </x-slot:tete>

    <x-slot:bandeau>
        <x-accueil.bandeau />
    </x-slot:bandeau>

    <x-accueil.usages />
    <x-accueil.fonctionnement />
    <x-accueil.fiche-technique />
    <x-accueil.tarifs />
    <x-accueil.questions />
</x-layouts::marketing>
