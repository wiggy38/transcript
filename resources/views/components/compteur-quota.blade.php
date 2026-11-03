{{-- Compteur de quota. Les valeurs viennent de l'appelant (plan de l'utilisateur, config/voxia.php) : rien n'est écrit en dur ici. --}}
@props(['utilise' => 0, 'limite' => null])

<div {{ $attributes->class(['flex min-w-44 flex-col gap-2 font-mono text-xs text-texte-secondaire']) }}>
    @if ($limite !== null)
        <p>{{ __('interface.quota.sur', ['utilise' => $utilise, 'limite' => $limite]) }}</p>
        @if ($limite > 0)
            <div
                role="progressbar"
                aria-label="{{ __('interface.quota.libelle') }}"
                aria-valuemin="0"
                aria-valuemax="{{ $limite }}"
                aria-valuenow="{{ min($utilise, $limite) }}"
                class="h-1.5 w-full overflow-hidden rounded-full bg-filet"
            >
                <div class="h-full rounded-full bg-accent" style="width: {{ min(100, (int) round($utilise / $limite * 100)) }}%"></div>
            </div>
        @endif
    @else
        <p>{{ __('interface.quota.sans_limite', ['utilise' => $utilise]) }}</p>
    @endif
</div>
