{{-- Horodatage en Geist Mono. ms : position en millisecondes. --}}
@props(['ms'])

<time datetime="{{ \App\Support\Horodatage::iso($ms) }}" {{ $attributes->class(['font-mono text-sm tabular-nums text-texte-discret']) }}>{{ \App\Support\Horodatage::format($ms) }}</time>
