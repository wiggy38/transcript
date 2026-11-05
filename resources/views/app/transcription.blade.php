<x-layouts::app :titre="$transcription->titre ?? __('transcriptions.page.titre')" actif="nouvelle">
    <p class="mb-[18px]">
        <a href="{{ route('tableau-de-bord') }}" class="inline-flex min-h-9 items-center text-sm text-texte-secondaire no-underline hover:text-accent-texte">← {{ __('transcriptions.page.retour') }}</a>
    </p>

    <livewire:suivi-transcription :transcription="$transcription" :position-initiale="$positionInitiale" />
</x-layouts::app>
