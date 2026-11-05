{{-- Onde décorative (aria-hidden). $progression 0–1 = part lue. $animee = vrai pendant la lecture uniquement. --}}
@props(['barres' => 48, 'progression' => 0, 'animee' => false, 'hauteur' => 'h-18'])
<div aria-hidden="true" {{ $attributes->class("flex items-center gap-0.5 $hauteur") }}>
  @for ($i = 0; $i < $barres; $i++)
    @php $h = round(22 + 78 * abs(sin($i * 1.7 + 0.6) * cos($i * 0.45))); @endphp
    <span
      @class(['min-w-px flex-1 rounded-[1px]', 'bg-accent' => $i / $barres < $progression, 'bg-onde-repos' => $i / $barres >= $progression, 'animate-onde' => $animee])
      style="height: {{ $h }}%; animation-delay: -{{ ($i % 16) / 10 }}s"></span>
  @endfor
</div>
