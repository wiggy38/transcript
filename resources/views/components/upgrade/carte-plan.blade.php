{{-- Une carte de plan, reprise de la carte « Tarifs » de la maquette Signal. Les valeurs viennent de PresentationTarifs. --}}
@props(['carte'])

<article aria-labelledby="carte-{{ $carte['cle'] }}" class="relative flex flex-col gap-4 rounded-panneau border border-filet bg-panneau p-6">
    @if ($carte['vedette'])
        <span aria-hidden="true" class="absolute inset-x-0 top-0 h-[3px] rounded-t-panneau bg-accent"></span>
    @endif

    <div class="flex items-center justify-between gap-3">
        <h3 id="carte-{{ $carte['cle'] }}" class="font-mono text-xs uppercase tracking-wide {{ $carte['vedette'] ? 'text-accent-texte' : 'text-texte-discret' }}">{{ $carte['nom'] }}</h3>
        @if ($carte['actuel'])
            <span class="font-mono text-[11px] text-texte-secondaire">{{ __('upgrade.actions.ton_plan') }}</span>
        @endif
    </div>

    <p class="text-3xl font-semibold leading-none tracking-tight">
        {{ $carte['prix'] }}
        @if ($carte['periode'])
            <span class="text-base font-normal tracking-normal text-texte-discret">{{ $carte['periode'] }}</span>
        @endif
    </p>

    @if ($carte['annuel'])
        <p class="-mt-2 text-sm text-texte-secondaire">{{ $carte['annuel'] }}</p>
    @endif

    <ul class="flex flex-col text-sm text-texte-secondaire">
        @foreach ($carte['lignes'] as $ligne)
            <li class="border-t border-filet py-2">{{ $ligne }}</li>
        @endforeach
    </ul>

    @if ($carte['actions'] !== [])
        <div class="mt-auto flex flex-col gap-2 pt-2">
            @foreach ($carte['actions'] as $action)
                <x-bouton :href="$action['url']" :variante="$loop->first ? 'principal' : 'filet'">{{ $action['libelle'] }}</x-bouton>
            @endforeach
        </div>
    @endif
</article>
