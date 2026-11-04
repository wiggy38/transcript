{{-- Questions fréquentes. Quotas, durées, formats et langues viennent de config/voxia.php (PagesLegales). --}}
@php
    $remplacements = \App\Support\PagesLegales::remplacements();
    $categories = __('pages.questions.categories');
@endphp

<x-layouts::marketing :titre="__('pages.questions.titre').' — '.__('interface.marque')" :description="__('pages.questions.intro')">
    <section class="pt-20 pb-10">
        <p class="font-mono text-xs font-medium text-accent-texte uppercase">{{ __('interface.pied.questions') }}</p>
        <h1 class="mt-4 text-[clamp(40px,5vw,64px)] leading-none font-semibold tracking-[-0.04em]">{{ __('pages.questions.titre') }}</h1>
        <p class="mt-6 max-w-[40em] text-lg text-texte-secondaire">{{ __('pages.questions.intro') }}</p>
    </section>

    @foreach ($categories as $indice => $categorie)
        <section class="pt-16" aria-labelledby="categorie-{{ $indice }}">
            <div class="flex flex-wrap gap-8 border-t border-filet pt-8">
                <h2 id="categorie-{{ $indice }}" class="flex-[1_1_280px] font-mono text-xs font-medium text-texte-discret uppercase">{{ __('pages.questions.categories.'.$indice.'.titre') }}</h2>

                <dl class="m-0 min-w-0 flex-[3_1_640px]">
                    @foreach ($categorie['liste'] as $numero => $entree)
                        <dt class="{{ $numero > 0 ? 'pt-6' : '' }} text-xl font-semibold tracking-[-0.015em]">{{ __('pages.questions.categories.'.$indice.'.liste.'.$numero.'.question') }}</dt>
                        <dd class="m-0 mt-1.5 border-b border-filet pb-6 text-texte-secondaire">{{ __('pages.questions.categories.'.$indice.'.liste.'.$numero.'.reponse', $remplacements) }}</dd>
                    @endforeach
                </dl>
            </div>
        </section>
    @endforeach

    <section class="pt-16 pb-10">
        <p class="text-texte-secondaire">
            {{ __('pages.contact.intro') }}
            <a href="{{ route('contact') }}" class="text-accent-texte underline-offset-4 hover:underline">{{ __('interface.pied.contact') }}</a>
        </p>
    </section>
</x-layouts::marketing>
