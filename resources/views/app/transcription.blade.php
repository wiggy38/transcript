<x-layouts::app :titre="$transcription->titre ?? __('transcriptions.page.titre')" actif="nouvelle">
    <p class="mb-6 text-sm">
        <a href="{{ route('tableau-de-bord') }}" class="text-texte-secondaire hover:text-accent-texte">← {{ __('transcriptions.page.retour') }}</a>
    </p>

    <livewire:suivi-transcription :transcription="$transcription" :position-initiale="$positionInitiale" />
</x-layouts::app>
