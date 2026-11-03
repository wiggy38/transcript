{{-- Onde audio décorative. anime : false pour une image fixe. Les barres s'arrêtent sous prefers-reduced-motion (app.css). --}}
@props(['barres' => 48, 'anime' => true])
@php
    $hauteurs = [];
    for ($i = 0; $i < $barres; $i++) {
        $hauteurs[] = (int) round(25 + 75 * abs(sin($i * 1.7 + 0.6) * cos($i * 0.45)));
    }
@endphp

<div aria-hidden="true" {{ $attributes->class(['flex h-40 items-center gap-[3px]']) }}>
    @foreach ($hauteurs as $i => $hauteur)
        <span
            class="h-full min-w-0.5 flex-1 rounded-sm bg-accent {{ $anime ? 'vx-onde-barre' : '' }}"
            style="height: {{ $hauteur }}%; animation-delay: -{{ ($i % 16) / 10 }}s"
        ></span>
    @endforeach
</div>
