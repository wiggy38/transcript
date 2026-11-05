{{-- Choix facultatif de la langue : détection automatique par défaut. Les deux écrans de lancement
     (lien et fichier) partagent la même propriété ; $identifiant évite les doublons d'id. --}}
<div class="flex min-w-0 flex-col gap-2 sm:w-56">
    <label for="{{ $identifiant }}" class="font-mono text-xs text-texte-discret">{{ __('transcriptions.langue.libelle') }}</label>
    <select id="{{ $identifiant }}" wire:model="langue"
            class="min-h-10 w-full rounded-champ border border-filet-contour bg-panneau px-3.5 text-[14px] text-texte outline-none focus-visible:border-accent">
        <option value="">{{ __('transcriptions.langue.auto') }}</option>
        @foreach ($langues as $code)
            <option value="{{ $code }}">{{ __('transcriptions.langues.'.$code) }}</option>
        @endforeach
    </select>
</div>
