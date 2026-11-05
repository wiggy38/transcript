{{-- Bouton « Copier » d'un bloc de réécriture. $texte est échappé par @js ; $libelle décrit le bloc pour les lecteurs d'écran. --}}
<button type="button" x-data="{ copie: false }"
    x-on:click="navigator.clipboard.writeText(@js($texte)); copie = true"
    aria-label="{{ __('reecriture.copier_bloc', ['bloc' => $libelle]) }}"
    class="min-h-9 shrink-0 cursor-pointer rounded-bouton border border-filet-contour px-3 font-mono text-[12px] text-texte hover:border-accent">
    <span x-show="! copie">{{ __('reecriture.copier') }}</span>
    <span x-show="copie" x-cloak>{{ __('reecriture.copie') }}</span>
</button>
