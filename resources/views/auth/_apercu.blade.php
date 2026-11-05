{{-- Colonne d'accompagnement des écrans de connexion et d'inscription (maquettes). Décorative : masquée sur petit écran. --}}
<aside aria-hidden="true" class="hidden flex-col gap-7 lg:flex">
    <p class="text-[28px] leading-tight font-semibold tracking-tight">
        {{ __('comptes.apercu.titre') }} <span class="vx-surlignage">{{ __('comptes.apercu.mot') }}</span>
    </p>

    <ul class="flex flex-col overflow-hidden rounded-panneau border border-filet bg-panneau">
        @foreach (__('comptes.apercu.lignes') as $indice => $ligne)
            <li class="flex items-baseline gap-4 px-5 py-3.5 {{ $indice === 1 ? 'bg-surlignage' : '' }}">
                <span class="shrink-0 font-mono text-xs tabular-nums {{ $indice === 1 ? 'text-surlignage-temps' : 'text-texte-discret' }}">{{ $ligne[0] }}</span>
                <span class="text-[15px] {{ $indice === 1 ? 'text-sur-surlignage' : 'text-texte-secondaire' }}">{{ $ligne[1] }}</span>
            </li>
        @endforeach
    </ul>

    <ul class="flex flex-wrap gap-2">
        @foreach (__('comptes.apercu.formats') as $format)
            <li class="rounded-bouton border border-filet-contour px-2.5 py-1 font-mono text-xs text-texte-secondaire">{{ $format }}</li>
        @endforeach
    </ul>
</aside>
