{{-- Bouton de la charte Signal. variante : principal (accent), clair, filet. Devient un lien si href est renseigné. --}}
@props(['variante' => 'principal', 'href' => null, 'type' => 'button'])
@php
    $base = 'inline-flex min-h-10 cursor-pointer items-center justify-center gap-2 rounded-bouton px-5 text-[14px] font-semibold no-underline transition-colors duration-150 motion-reduce:transition-none disabled:cursor-not-allowed disabled:opacity-50';
    $variantes = [
        'principal' => 'bg-accent text-sur-accent hover:opacity-90',
        'clair' => 'bg-texte text-fond hover:bg-texte-secondaire',
        'filet' => 'border border-filet-contour bg-transparent text-texte hover:border-accent hover:text-accent-texte',
    ];
    $classes = $base.' '.($variantes[$variante] ?? $variantes['principal']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
