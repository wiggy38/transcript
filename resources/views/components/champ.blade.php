{{-- Champ avec libellé visible. Le libellé, l'aide et l'erreur sont reliés au champ (label, aria-describedby).
     variante : « mono » (libellé en mono, champ standard) ou « formulaire » (libellé en Onest semi-gras 14 px, champ de 44 px sur fond, comme les réglages du compte). --}}
@props(['id', 'label', 'name' => null, 'type' => 'text', 'value' => null, 'placeholder' => null, 'mono' => false, 'required' => false, 'aide' => null, 'erreur' => null, 'variante' => 'mono'])
@php
    $idAide = $id.'-aide';
    $idErreur = $id.'-erreur';
    $decrit = collect([$aide ? $idAide : null, $erreur ? $idErreur : null])->filter()->implode(' ');
    $formulaire = $variante === 'formulaire';
@endphp

<div @class(['flex min-w-0 flex-col', 'gap-2' => ! $formulaire, 'gap-1.5' => $formulaire])>
    <label for="{{ $id }}" @class(['font-mono text-xs text-texte-discret' => ! $formulaire, 'text-sm font-semibold text-texte' => $formulaire])>{{ $label }}</label>

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
            'w-full min-w-0 rounded-champ border border-filet-contour px-3.5 text-texte outline-none focus-visible:border-accent',
            'min-h-10 bg-panneau' => ! $formulaire,
            'min-h-11 bg-fond text-[15px]' => $formulaire,
            'font-mono text-sm' => $mono,
            'text-[14px]' => ! $mono && ! $formulaire,
        ]) }}
    >

    @if ($aide)
        <p id="{{ $idAide }}" @class(['text-sm' => ! $formulaire, 'text-aide' => $formulaire, 'text-texte-discret'])>{{ $aide }}</p>
    @endif

    @if ($erreur)
        <x-message-erreur :id="$idErreur">{{ $erreur }}</x-message-erreur>
    @endif
</div>
