{{-- Fiche technique (maquette Signal) : entrées, sorties, horodatage, langues. Valeurs lues dans config/voxia.php. --}}
@php
    $fichiers = collect(config('voxia.uploads.extensions'))->map(fn ($extension) => strtoupper($extension))->implode(' · ');
    $sousTitres = '<span class="text-accent">SRT · VTT</span>';
    $codesLangues = collect(config('voxia.langues_forcables'))->map(fn ($code) => strtoupper($code))->implode(' · ');
@endphp

<section class="pt-32" aria-labelledby="titre-fiche">
    <div class="flex flex-wrap border-t border-filet">
        <div class="min-w-0 flex-[1_1_280px] py-8 pr-8">
            <h2 id="titre-fiche" class="font-mono text-xs font-medium text-texte-discret">{{ __('accueil.fiche.titre') }}</h2>
        </div>

        <dl class="m-0 min-w-0 flex-[3_1_640px]">
            <div class="flex flex-wrap items-baseline gap-2 border-b border-filet py-7 sm:gap-x-8">
                <dt class="w-40 shrink-0 text-texte-discret">{{ __('accueil.fiche.entrees') }}</dt>
                <dd class="m-0 min-w-0 flex-[1_1_360px] font-mono text-[clamp(22px,2.6vw,34px)] tracking-[-0.02em]">{{ __('accueil.fiche.entrees_valeur', ['fichiers' => $fichiers]) }}</dd>
            </div>
            <div class="flex flex-wrap items-baseline gap-2 border-b border-filet py-7 sm:gap-x-8">
                <dt class="w-40 shrink-0 text-texte-discret">{{ __('accueil.fiche.sorties') }}</dt>
                <dd class="m-0 min-w-0 flex-[1_1_360px] font-mono text-[clamp(22px,2.6vw,34px)] tracking-[-0.02em]">{!! __('accueil.fiche.sorties_valeur', ['sous_titres' => $sousTitres]) !!}</dd>
            </div>
            <div class="flex flex-wrap items-baseline gap-2 border-b border-filet py-7 sm:gap-x-8">
                <dt class="w-40 shrink-0 text-texte-discret">{{ __('accueil.fiche.horodatage') }}</dt>
                <dd class="m-0 min-w-0 flex-[1_1_360px] font-mono text-[clamp(22px,2.6vw,34px)] tracking-[-0.02em]">{{ __('accueil.fiche.horodatage_valeur') }}</dd>
            </div>
            <div class="flex flex-wrap items-baseline gap-2 border-b border-filet py-7 sm:gap-x-8">
                <dt class="w-40 shrink-0 text-texte-discret">{{ __('accueil.fiche.langues') }}</dt>
                <dd class="m-0 min-w-0 flex-[1_1_360px] font-mono text-[clamp(22px,2.6vw,34px)] tracking-[-0.02em]">{{ __('accueil.fiche.langues_valeur', ['codes' => $codesLangues]) }}</dd>
            </div>
        </dl>
    </div>
</section>
