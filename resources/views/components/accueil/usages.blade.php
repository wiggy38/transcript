{{-- Usages (maquette Signal) : carte Créateurs (quatre formats réels) et carte Marketeurs (F7, gardée telle quelle). --}}
@php
    $createurs = trans('accueil.usages.createurs');
    $marketeurs = trans('accueil.usages.marketeurs');
    $couleursParties = ['var(--vx-accent)', 'var(--vx-line-strong)', 'var(--vx-wave-idle)', 'var(--vx-line)', 'var(--vx-text)'];
@endphp

<section id="usages" class="pt-2.5" aria-labelledby="titre-usages">
    <div class="mb-12 flex flex-wrap items-end justify-between gap-6">
        <h2 id="titre-usages" class="max-w-[14ch] text-[clamp(36px,4.6vw,64px)] leading-none font-semibold tracking-[-0.04em]">
            {{ __('accueil.usages.titre') }}
            <span class="vx-surlignage">{{ __('accueil.usages.titre_accent') }}</span>
        </h2>
        <p class="max-w-[26em] text-texte-secondaire">{{ __('accueil.usages.intro') }}</p>
    </div>

    <div class="flex flex-wrap gap-px overflow-hidden rounded-panneau border border-filet bg-filet">
        {{-- Créateurs --}}
        <article class="min-w-0 flex-[1_1_480px] bg-panneau p-6 md:p-10" aria-labelledby="usage-createurs">
            <p class="font-mono text-xs text-accent-texte">{{ $createurs['numero'] }}</p>
            <h3 id="usage-createurs" class="mt-3 text-[24px] leading-[1.15] font-semibold tracking-[-0.025em]">{{ $createurs['titre'] }}</h3>
            <p class="mt-3 max-w-[30em] text-texte-secondaire">{{ $createurs['texte'] }}</p>

            <div class="mt-9 flex items-center gap-5">
                <div class="flex h-[92px] w-[132px] shrink-0 items-center justify-center rounded-champ border border-filet-contour bg-fond" aria-hidden="true">
                    <svg width="26" height="28" viewBox="0 0 26 28"><path d="M2 2 L24 14 L2 26 Z" fill="none" style="stroke: var(--vx-text)" stroke-width="2" stroke-linejoin="round"></path></svg>
                </div>
                <svg width="56" height="160" viewBox="0 0 56 160" class="shrink-0" aria-hidden="true">
                    <g fill="none" style="stroke: var(--vx-line)" stroke-width="1">
                        <path d="M0 80 C 28 80, 28 20, 56 20"></path>
                        <path d="M0 80 C 28 80, 28 60, 56 60"></path>
                        <path d="M0 80 C 28 80, 28 100, 56 100"></path>
                        <path d="M0 80 C 28 80, 28 140, 56 140"></path>
                    </g>
                    <circle cx="0" cy="80" r="3" style="fill: var(--vx-accent)"></circle>
                </svg>
                <ul class="m-0 flex min-w-0 flex-1 list-none flex-col gap-2 p-0 font-mono text-[13px]">
                    @foreach ($createurs['formats'] as $format)
                        <li class="flex justify-between gap-2 rounded-bouton border px-3 py-2 {{ ($format['accent'] ?? false) ? 'border-accent' : 'border-filet' }}">
                            <span>{{ $format['libelle'] }}</span>
                            <span class="{{ ($format['accent'] ?? false) ? 'text-accent-texte' : 'text-texte-discret' }}">{{ $format['extension'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </article>

        {{-- Marketeurs : analyse de script (F7) --}}
        <article class="min-w-0 flex-[1_1_480px] bg-panneau p-6 md:p-10" aria-labelledby="usage-marketeurs">
            <p class="font-mono text-xs text-accent-texte">{{ $marketeurs['numero'] }}</p>
            <h3 id="usage-marketeurs" class="mt-3 text-[24px] leading-[1.15] font-semibold tracking-[-0.025em]">{{ $marketeurs['titre'] }}</h3>
            <p class="mt-3 max-w-[30em] text-texte-secondaire">{{ $marketeurs['texte'] }}</p>

            <div class="mt-9">
                <div class="flex justify-between font-mono text-[11px] text-texte-discret" aria-hidden="true">
                    <span>{{ $marketeurs['pub'] }}</span>
                    <span>{{ $marketeurs['duree'] }}</span>
                </div>

                <div role="img" aria-label="{{ $marketeurs['description_parties'] }}" class="mt-2.5 flex h-10 gap-[3px]">
                    @foreach ($marketeurs['parties'] as $indice => $partie)
                        <span class="rounded-[3px]" style="flex: {{ $partie['secondes'] }} 1 0; background: {{ $couleursParties[$indice] }}"></span>
                    @endforeach
                </div>

                <div class="mt-2.5 flex gap-[3px] font-mono text-[11px] text-texte-discret" aria-hidden="true">
                    @foreach ($marketeurs['parties'] as $indice => $partie)
                        <span class="overflow-hidden whitespace-nowrap {{ $indice === 0 ? 'text-accent-texte' : ($indice === 4 ? 'text-texte' : '') }}" style="flex: {{ $partie['secondes'] }} 1 0; min-width: 0">{{ $partie['libelle'] }}</span>
                    @endforeach
                </div>

                <blockquote class="mt-7 rounded-champ border border-filet bg-fond p-[18px_20px]">
                    <p class="font-mono text-[11px] text-accent-texte">{{ $marketeurs['citation_repere'] }}</p>
                    <p class="mt-1.5 font-titre text-[19px] leading-[1.25] font-bold tracking-[-0.02em]">{{ $marketeurs['citation'] }}</p>
                </blockquote>
            </div>
        </article>
    </div>
</section>
