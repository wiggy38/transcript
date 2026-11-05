{{-- Questions fréquentes (maquette Signal). Langues et durées lues dans config/voxia.php. --}}
@php
    $langues = collect(config('voxia.langues_forcables'))
        ->map(fn ($code) => __('accueil.langues.'.$code))
        ->implode(', ');
    $historique = config('voxia.plans.free.history_days');
    $medias = config('voxia.retention.media_days');
@endphp

<section id="questions" class="pt-2.5" aria-labelledby="titre-questions">
    <div class="flex flex-wrap gap-8 border-t border-filet pt-8">
        <h2 id="titre-questions" class="flex-[1_1_280px] font-mono text-xs font-medium text-texte-discret">{{ __('accueil.questions.titre') }}</h2>

        <dl class="m-0 min-w-0 flex-[3_1_640px]">
            <dt class="text-xl font-semibold tracking-[-0.015em]">{{ __('accueil.questions.liste.0.question') }}</dt>
            <dd class="m-0 mt-1.5 border-b border-filet pb-6 text-texte-secondaire">{{ __('accueil.questions.liste.0.reponse', ['langues' => $langues]) }}</dd>

            <dt class="pt-6 text-xl font-semibold tracking-[-0.015em]">{{ __('accueil.questions.liste.1.question') }}</dt>
            <dd class="m-0 mt-1.5 border-b border-filet pb-6 text-texte-secondaire">{{ __('accueil.questions.liste.1.reponse') }}</dd>

            <dt class="pt-6 text-xl font-semibold tracking-[-0.015em]">{{ __('accueil.questions.liste.2.question') }}</dt>
            <dd class="m-0 mt-1.5 text-texte-secondaire">{{ __('accueil.questions.liste.2.reponse', ['historique' => $historique, 'media' => $medias]) }}</dd>
        </dl>
    </div>
</section>
