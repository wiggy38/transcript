{{-- Statut d'une transcription en pastille, comme la maquette « Transcription - Nocturne » : couleur et libellé,
     point qui pulse pendant le traitement. Accepte un StatutTranscription ou sa valeur texte. --}}
@props(['statut'])
@php
    $valeur = $statut instanceof \BackedEnum ? $statut->value : (string) $statut;
    $encours = ['classe' => 'text-direct bg-[color-mix(in_oklab,var(--vx-live)_18%,transparent)]', 'pulse' => true];
    $styles = [
        'en_attente' => ['classe' => 'text-texte-secondaire bg-[color-mix(in_oklab,var(--vx-text-2)_18%,transparent)]', 'pulse' => false],
        'telechargement' => $encours,
        'transcription' => $encours,
        'termine' => ['classe' => 'text-succes bg-[color-mix(in_oklab,var(--vx-success)_18%,transparent)]', 'pulse' => false],
        'echec' => ['classe' => 'text-erreur bg-[color-mix(in_oklab,var(--vx-error)_18%,transparent)]', 'pulse' => false],
    ];
    $style = $styles[$valeur] ?? $styles['en_attente'];
@endphp

<span {{ $attributes->class(['inline-flex w-fit items-center gap-1.5 rounded-pilule px-2.5 py-[3px] font-mono text-xs leading-normal font-medium whitespace-nowrap', $style['classe']]) }}>
    <span aria-hidden="true" class="size-1.5 rounded-full bg-current {{ $style['pulse'] ? 'vx-pulsation' : '' }}"></span>
    {{ __('interface.statuts.'.$valeur) }}
</span>
