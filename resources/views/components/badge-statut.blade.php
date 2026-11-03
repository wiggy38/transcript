{{-- Badge de statut d'une transcription. Accepte un StatutTranscription ou sa valeur texte.
     Le libellé est toujours affiché : la couleur n'est jamais le seul indice. --}}
@props(['statut'])
@php
    $valeur = $statut instanceof \BackedEnum ? $statut->value : (string) $statut;
    $encours = ['classe' => 'border-accent text-accent', 'point' => 'bg-accent', 'pulse' => true];
    $styles = [
        'en_attente' => ['classe' => 'border-filet-contour text-texte-secondaire', 'point' => 'bg-texte-discret', 'pulse' => false],
        'telechargement' => $encours,
        'transcription' => $encours,
        'termine' => ['classe' => 'border-filet-contour text-texte', 'point' => 'bg-texte', 'pulse' => false],
        'echec' => ['classe' => 'border-erreur text-erreur', 'point' => 'bg-erreur', 'pulse' => false],
    ];
    $style = $styles[$valeur] ?? $styles['en_attente'];
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-2 rounded-bouton border px-2.5 py-1 font-mono text-xs', $style['classe']]) }}>
    <span aria-hidden="true" class="size-2 rounded-full {{ $style['point'] }} {{ $style['pulse'] ? 'vx-pulsation' : '' }}"></span>
    {{ __('interface.statuts.'.$valeur) }}
</span>
