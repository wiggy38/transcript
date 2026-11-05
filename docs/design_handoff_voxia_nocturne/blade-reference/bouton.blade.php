{{-- Bouton. Variantes : principal | secondaire | discret. Tailles : md (44 px) | lg (54 px, héros). --}}
@props(['variante' => 'principal', 'taille' => 'md', 'href' => null, 'type' => 'button'])
@php
  $base = 'inline-flex items-center justify-center gap-2 rounded-bouton font-semibold whitespace-nowrap transition-[filter,border-color,color] duration-150 disabled:cursor-not-allowed';
  $tailles = ['md' => 'min-h-11 px-4 text-[14px]', 'lg' => 'min-h-[54px] px-6 text-[16px]'];
  $variantes = [
    'principal'  => 'bg-accent text-sur-accent hover:brightness-110',
    'secondaire' => 'border border-filet-contour text-texte hover:border-accent-texte hover:text-accent-texte',
    'discret'    => 'text-texte-secondaire hover:text-texte',
  ];
  $classes = "$base {$tailles[$taille]} {$variantes[$variante]} disabled:border-dashed disabled:bg-transparent disabled:text-texte-discret";
@endphp
@if ($href)
  <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
  <button type="{{ $type }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
