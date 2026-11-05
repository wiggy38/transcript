{{-- Champ avec libellé visible. Le libellé, l'aide et l'erreur sont reliés au champ (label, aria-describedby). --}}
@props(['id', 'label', 'name' => null, 'type' => 'text', 'value' => null, 'placeholder' => null, 'mono' => false, 'required' => false, 'aide' => null, 'erreur' => null])
@php
    $idAide = $id.'-aide';
    $idErreur = $id.'-erreur';
    $decrit = collect([$aide ? $idAide : null, $erreur ? $idErreur : null])->filter()->implode(' ');
@endphp

<div class="flex min-w-0 flex-col gap-2">
    <label for="{{ $id }}" class="font-mono text-xs uppercase tracking-wide text-texte-discret">{{ $label }}</label>

    <input
        id="{{ $id }}"
        name="{{ $name ?? $id }}"
        type="{{ $type }}"
        @if ($value !== null) value="{{ $value }}" @endif
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($decrit !== '') aria-describedby="{{ $decrit }}" @endif
        @if ($erreur) aria-invalid="true" @endif
        @required($required)
        {{ $attributes->class([
            'min-h-10 w-full min-w-0 rounded-champ border border-filet-contour bg-panneau px-3.5 text-texte outline-none focus-visible:border-accent',
            'font-mono text-sm' => $mono,
            'text-[14px]' => ! $mono,
        ]) }}
    >

    @if ($aide)
        <p id="{{ $idAide }}" class="text-sm text-texte-discret">{{ $aide }}</p>
    @endif

    @if ($erreur)
        <x-message-erreur :id="$idErreur">{{ $erreur }}</x-message-erreur>
    @endif
</div>
