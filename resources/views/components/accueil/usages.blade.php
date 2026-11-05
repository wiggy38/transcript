{{-- « Pour qui ? » (handoff, écran 1) : trois cartes, chacune avec une scène décorative animée. L'état de repos
     de chaque scène est lisible (sous-titre visible, ligne surlignée, fiche complète) : sous
     prefers-reduced-motion, rien ne bouge et le contenu reste compréhensible. Textes lus dans lang/*/accueil.php. --}}
@php
    $cartes = __('accueil.usages.cartes');
    $scene = __('accueil.usages.scene');
@endphp

<section id="usages" class="scroll-mt-24 border-t border-filet bg-fond py-16" aria-labelledby="titre-usages">
    <div class="mx-auto w-full max-w-[1120px] px-4">
    <div class="mb-10 flex flex-wrap items-end justify-between gap-4">
        <h2 id="titre-usages" class="titre-h2">{{ __('accueil.usages.titre') }}</h2>
        <p class="max-w-[30em] text-[15px] text-texte-secondaire">{{ __('accueil.usages.sous_titre') }}</p>
    </div>

    <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,250px),1fr))] gap-4">
        {{-- Créateurs de contenu : vignette, bouton lecture, trois sous-titres qui se succèdent --}}
        <article class="flex min-w-0 flex-col gap-3.5 rounded-panneau border border-filet bg-panneau p-5 shadow-panneau transition-colors hover:border-filet-contour">
            <div aria-hidden="true" class="relative flex h-[176px] flex-col justify-between overflow-hidden rounded-[14px] bg-panneau-eleve p-3">
                <div class="fond-heros absolute inset-0 opacity-70"></div>
                <div class="relative flex items-center justify-between">
                    <span class="flex size-9 items-center justify-center rounded-full bg-accent text-sur-accent"><x-icone nom="lecture" class="size-4 fill-current" /></span>
                    <span class="rounded-[6px] bg-fond px-1.5 py-0.5 font-mono text-[11px] text-texte-secondaire">12:03</span>
                </div>
                <div class="relative flex flex-col gap-1.5">
                    <span class="w-fit animate-[vx-cap_7.5s_ease-in-out_infinite] rounded-[6px] bg-fond/90 px-2 py-0.5 font-mono text-[11px] text-texte">00:13 · Le secret, c'est de centrer la terre.</span>
                    <span class="w-fit animate-[vx-cap_7.5s_ease-in-out_-5s_infinite] rounded-[6px] bg-fond/90 px-2 py-0.5 font-mono text-[11px] text-texte">00:19 · Si elle n'est pas centrée.</span>
                    <span class="w-fit animate-[vx-cap_7.5s_ease-in-out_-2.5s_infinite] rounded-[6px] bg-fond/90 px-2 py-0.5 font-mono text-[11px] text-texte">00:24 · Je mouille les mains.</span>
                </div>
                <span class="absolute top-3 right-3 rounded-pilule bg-accent px-2 py-0.5 font-mono text-[11px] font-medium text-sur-accent">.srt</span>
            </div>

            <p class="font-mono text-donnee font-medium text-accent-texte">{{ $cartes['createurs']['numero'] }}</p>
            <h3 class="text-[17px] leading-[1.25] font-semibold tracking-[-0.02em]">{{ $cartes['createurs']['titre'] }}</h3>
            <p class="text-petit text-texte-secondaire">{{ $cartes['createurs']['texte'] }}</p>
            <ul class="m-0 mt-auto flex list-none flex-wrap gap-1.5 p-0">
                @foreach ($cartes['createurs']['formats'] as $format)
                    <li class="rounded-pilule border border-filet-contour px-2.5 py-0.5 font-mono text-donnee text-texte-secondaire">{{ $format }}</li>
                @endforeach
            </ul>
        </article>

        {{-- Marketeurs : champ de recherche, surlignage qui descend, citation copiée --}}
        <article class="flex min-w-0 flex-col gap-3.5 rounded-panneau border border-filet bg-panneau p-5 shadow-panneau transition-colors hover:border-filet-contour">
            <div aria-hidden="true" class="relative flex h-[176px] flex-col gap-2 overflow-hidden rounded-[14px] bg-panneau-eleve p-3">
                <div class="flex items-center gap-2 rounded-champ border border-filet-contour bg-fond px-2.5 py-1.5 text-texte-secondaire">
                    <x-icone nom="recherche" class="size-3.5" />
                    <span class="animate-[vx-type_6s_steps(8)_infinite] overflow-hidden font-mono text-donnee whitespace-nowrap text-texte">{{ $scene['recherche'] }}</span>
                </div>
                <div class="relative flex flex-col gap-1.5 font-mono text-[11px] text-texte-secondaire">
                    <span class="px-1">00:06 · Aujourd'hui on tourne six bols…</span>
                    <span class="px-1">00:13 · Le secret, c'est de bien préparer…</span>
                    <span class="rounded-[4px] bg-surlignage px-1 text-sur-surlignage">00:19 · « Si elle n'est pas centrée, rien ne tiendra. »</span>
                    <span class="px-1">00:24 · Je mouille les mains…</span>
                    <span aria-hidden="true" class="absolute top-[18px] left-0 right-0 h-[2px] animate-[vx-scan_6s_ease-in-out_infinite] bg-accent/60"></span>
                </div>
                <span class="absolute right-3 bottom-3 animate-[vx-pop_6s_ease-in-out_infinite] rounded-pilule bg-accent px-2.5 py-1 font-mono text-[11px] font-medium text-sur-accent">{{ $scene['citation_copiee'] }}</span>
            </div>

            <p class="font-mono text-donnee font-medium text-accent-texte">{{ $cartes['marketeurs']['numero'] }}</p>
            <h3 class="text-[17px] leading-[1.25] font-semibold tracking-[-0.02em]">{{ $cartes['marketeurs']['titre'] }}</h3>
            <p class="text-petit text-texte-secondaire">{{ $cartes['marketeurs']['texte'] }}</p>
            <ul class="m-0 mt-auto flex list-none flex-wrap gap-1.5 p-0">
                @foreach ($cartes['marketeurs']['formats'] as $format)
                    <li class="rounded-pilule border border-filet-contour px-2.5 py-0.5 font-mono text-donnee text-texte-secondaire">{{ $format }}</li>
                @endforeach
            </ul>
        </article>

        {{-- E-commerçants : démo, flèche animée, fiche produit qui se dessine --}}
        <article class="flex min-w-0 flex-col gap-3.5 rounded-panneau border border-filet bg-panneau p-5 shadow-panneau transition-colors hover:border-filet-contour">
            <div aria-hidden="true" class="flex h-[176px] flex-col gap-2 overflow-hidden rounded-[14px] bg-panneau-eleve p-3">
                <div class="flex items-center justify-between font-mono text-donnee text-texte-discret">
                    <span>{{ $scene['demo'] }}</span>
                    <span class="animate-[vx-flow_2.4s_ease-in-out_infinite] text-accent-texte">→</span>
                </div>
                <div class="flex flex-col gap-1.5">
                    <span class="block h-2 w-full rounded bg-filet"></span>
                    <span class="block h-2 w-4/5 rounded bg-filet"></span>
                    <span class="block h-2 w-3/5 rounded bg-filet"></span>
                </div>
                <div class="mt-auto flex flex-col gap-1.5 rounded-[10px] border border-filet-contour bg-fond p-2.5">
                    <span class="font-mono text-[11px] text-texte-secondaire">{{ $scene['fiche'] }}</span>
                    <span class="block h-1.5 origin-left animate-[vx-grow_7.5s_ease-in-out_infinite] rounded bg-accent"></span>
                    <span class="block h-1.5 origin-left animate-[vx-grow_7.5s_ease-in-out_-5s_infinite] rounded bg-filet"></span>
                    <span class="flex items-center justify-between">
                        <span class="rounded-pilule bg-direct/20 px-2 py-0.5 font-mono text-[11px] text-direct">{{ $scene['faq'] }}</span>
                    </span>
                </div>
            </div>

            <p class="font-mono text-donnee font-medium text-accent-texte">{{ $cartes['ecommerce']['numero'] }}</p>
            <h3 class="text-[17px] leading-[1.25] font-semibold tracking-[-0.02em]">{{ $cartes['ecommerce']['titre'] }}</h3>
            <p class="text-petit text-texte-secondaire">{{ $cartes['ecommerce']['texte'] }}</p>
            <ul class="m-0 mt-auto flex list-none flex-wrap gap-1.5 p-0">
                @foreach ($cartes['ecommerce']['formats'] as $format)
                    <li class="rounded-pilule border border-filet-contour px-2.5 py-0.5 font-mono text-donnee text-texte-secondaire">{{ $format }}</li>
                @endforeach
            </ul>
        </article>
    </div>
    </div>
</section>
