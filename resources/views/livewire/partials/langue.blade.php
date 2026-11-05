{{-- Choix facultatif de la langue : détection automatique par défaut. Les deux écrans de lancement
     (lien et fichier) partagent la même propriété ; $identifiant évite les doublons d'id.
     $classe et $fond : placement dans la ligne et fond du champ (maquette « Tableau de bord »). --}}
@php
    $classe = $classe ?? 'flex-[1_1_180px]';
    $fond = $fond ?? 'bg-fond';
@endphp
<div class="flex min-w-0 flex-col gap-1.5 {{ $classe }}">
    <label for="{{ $identifiant }}" class="text-[13px] font-semibold text-texte">{{ __('transcriptions.langue.libelle') }}</label>
    <select id="{{ $identifiant }}" wire:model="langue"
            class="min-h-11 w-full rounded-champ border border-filet-contour {{ $fond }} px-3 text-sm text-texte outline-none focus-visible:border-accent">
        <option value="">{{ __('transcriptions.langue.auto') }}</option>
        @foreach ($langues as $code)
            <option value="{{ $code }}">{{ __('transcriptions.langues.'.$code) }}</option>
        @endforeach
    </select>
</div>
