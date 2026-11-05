{{-- Fonctionnement en quatre étapes (maquette Signal). Le paquet d'accent parcourt la ligne ; coupé par prefers-reduced-motion. --}}
<section id="fonctionnement" class="pt-2.5" aria-labelledby="titre-fonctionnement">
    <h2 id="titre-fonctionnement" class="mb-14 text-[clamp(36px,4.6vw,64px)] leading-none font-semibold tracking-[-0.04em]">
        {{ __('accueil.fonctionnement.titre') }}
    </h2>

    <div class="relative">
        <span aria-hidden="true" class="absolute inset-x-0 top-1 h-px bg-filet"></span>
        <span aria-hidden="true" class="vx-paquet absolute top-0 left-0 size-[9px] rounded-[2px] bg-accent"></span>

        <ol class="m-0 flex list-none flex-wrap gap-8 p-0">
            @foreach (trans('accueil.fonctionnement.etapes') as $indice => $etape)
                <li class="min-w-0 flex-[1_1_220px] pt-9">
                    <p class="font-mono text-xs {{ $indice === 3 ? 'text-accent-texte' : 'text-texte-discret' }}">0{{ $indice + 1 }}</p>
                    <h3 class="mt-2 text-[19px] font-semibold tracking-[-0.02em]">{{ $etape['titre'] }}</h3>
                    <p class="mt-2 text-texte-secondaire">{{ $etape['texte'] }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</section>
