{{-- Gabarit des textes juridiques (CGU, confidentialité). Brouillons : le bandeau en tête reste tant qu'un juriste ne les a pas relus. --}}
@php
    $remplacements = \App\Support\PagesLegales::remplacements();
    $titre = __('pages.'.$cle.'.titre');
    $sections = __('pages.'.$cle.'.sections');
@endphp

<x-layouts::marketing :titre="$titre.' — '.__('interface.marque')">
    <article class="max-w-[760px] pt-20 pb-10">
        <p role="note" class="rounded-panneau border border-accent bg-panneau px-5 py-4 font-mono text-sm font-medium text-accent-texte">{{ __('pages.brouillon') }}</p>

        <h1 class="mt-10 text-4xl font-semibold tracking-[-0.03em]">{{ $titre }}</h1>
        <p class="mt-3 text-sm text-texte-discret">{{ __('pages.maj', ['date' => $remplacements['date']]) }}</p>

        @foreach ($sections as $indice => $section)
            <section class="mt-12" aria-labelledby="section-{{ $cle }}-{{ $indice }}">
                <h2 id="section-{{ $cle }}-{{ $indice }}" class="text-xl font-semibold tracking-[-0.015em]">{{ __('pages.'.$cle.'.sections.'.$indice.'.titre') }}</h2>

                @foreach ($section['paragraphes'] as $numero => $paragraphe)
                    <p class="mt-3 text-texte-secondaire">{{ __('pages.'.$cle.'.sections.'.$indice.'.paragraphes.'.$numero, $remplacements) }}</p>
                @endforeach
            </section>
        @endforeach
    </article>
</x-layouts::marketing>
