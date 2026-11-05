{{-- Questions fréquentes (handoff, écran 1) : <details> natifs, sans JavaScript. Langues, durées de conservation
     et quotas lus dans config/voxia.php. --}}
@php
    $langues = collect(config('voxia.langues_forcables'))
        ->map(fn ($code) => strtoupper($code))
        ->implode(' · ');
    $historique = config('voxia.plans.free.history_days');
    $medias = config('voxia.retention.media_days');
    $liste = __('accueil.questions.liste');
    $remplacements = [
        4 => ['langues' => $langues],
        5 => ['historique' => $historique, 'media' => $medias],
    ];
@endphp

<section id="questions" class="scroll-mt-24 border-t border-filet bg-fond py-16" aria-labelledby="titre-questions">
    <div class="mx-auto w-full max-w-[1120px] px-4">
    <h2 id="titre-questions" class="titre-h2 mb-8">{{ __('accueil.questions.titre') }}</h2>

    <div class="mx-auto max-w-[760px] divide-y divide-filet border-y border-filet">
        @foreach ($liste as $indice => $question)
            <details class="group">
                <summary class="flex min-h-12 cursor-pointer list-none items-center justify-between gap-4 py-4 text-[15px] font-semibold text-texte">
                    {{ $question['question'] }}
                    <span aria-hidden="true" class="font-mono text-[18px] text-texte-discret transition-transform group-open:rotate-45">+</span>
                </summary>
                <p class="pb-5 text-petit text-texte-secondaire">
                    {{ __('accueil.questions.liste.'.$indice.'.reponse', $remplacements[$indice] ?? []) }}
                </p>
            </details>
        @endforeach
    </div>
    </div>
</section>
