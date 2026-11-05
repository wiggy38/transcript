{{-- Barre déterminée pour l'envoi de fichier (Livewire upload). --}}
<div x-data="{ p: 0 }"
     x-on:livewire-upload-progress="p = $event.detail.progress"
     class="grid gap-1.5">
  <div class="flex justify-between font-mono text-donnee text-texte-secondaire">
    <span>{{ __('transcriptions.envoi') }} : <span x-text="p"></span> %</span>
    <span class="truncate text-texte-discret">{{ $nom }}</span>
  </div>
  <div role="progressbar" aria-label="{{ __('transcriptions.envoi_fichier') }}" aria-valuemin="0" aria-valuemax="100" :aria-valuenow="p"
       class="h-1.5 overflow-hidden rounded-full border border-filet bg-panneau-eleve">
    <div class="h-full rounded-full bg-accent transition-[width] duration-150" :style="'width:' + p + '%'"></div>
  </div>
</div>
