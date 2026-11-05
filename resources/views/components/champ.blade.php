{{-- Champ avec libellé visible. Le libellé, l'aide et l'erreur sont reliés au champ (label, aria-describedby).
     variante : « mono » (libellé en mono, champ standard), « formulaire » (libellé en Onest semi-gras 14 px, champ de 44 px sur fond, comme les réglages du compte)
     ou « auth » (maquette « Inscription » : champ de 48 px, bordure d'erreur 1,5 px, erreur avec icône). --}}
@props(['id', 'label', 'name' => null, 'type' => 'text', 'value' => null, 'placeholder' => null, 'mono' => false, 'required' => false, 'aide' => null, 'erreur' => null, 'variante' => 'mono'])
@php
    $idAide = $id.'-aide';
    $idErreur = $id.'-erreur';
    $decrit = collect([$aide ? $idAide : null, $erreur ? $idErreur : null])->filter()->implode(' ');
    $auth = $variante === 'auth';
    $formulaire = $variante === 'formulaire' || $auth;
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
            'w-full min-w-0 rounded-champ px-3.5 text-texte outline-none focus-visible:border-accent',
            'border border-filet-contour' => ! ($auth && $erreur),
            'border-[1.5px] border-erreur' => $auth && $erreur,
            'min-h-10 border border-filet-contour bg-panneau' => ! $formulaire,
            'min-h-11 border border-filet-contour bg-fond text-[15px]' => $variante === 'formulaire',
            'min-h-12 bg-fond text-[15px]' => $auth,
            'font-mono text-sm' => $mono,
            'text-[14px]' => ! $mono && ! $formulaire,
        ]) }}
    >

    @if ($aide)
        <p id="{{ $idAide }}" @class(['text-sm' => ! $formulaire, 'text-aide' => $formulaire, 'text-texte-discret'])>{{ $aide }}</p>
    @endif

    @if ($erreur && $auth)
        <p id="{{ $idErreur }}" class="flex items-start gap-2 text-sm text-erreur">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true" focusable="false" class="mt-[3px] shrink-0">
                <circle cx="12" cy="12" r="9"></circle>
                <path d="M12 7.5v5.5"></path>
                <path d="M12 16.5h.01"></path>
            </svg>
            <span>{{ $erreur }}</span>
        </p>
    @elseif ($erreur)
        <x-message-erreur :id="$idErreur">{{ $erreur }}</x-message-erreur>
    @endif
</div>
