{{-- Bandeau défilant d'extraits horodatés (décoratif : masqué aux lecteurs d'écran). Coupé par prefers-reduced-motion. --}}
<div class="overflow-hidden border-b border-filet py-[18px] font-mono text-sm whitespace-nowrap text-texte-discret" aria-hidden="true">
    <div class="vx-defilement inline-flex gap-12 pl-12">
        @foreach ([1, 2] as $copie)
            @foreach (trans('accueil.bandeau.extraits') as $extrait)
                <span><span class="text-accent">{{ $extrait['temps'] }}</span>&nbsp;&nbsp;{{ $extrait['texte'] }}</span>
            @endforeach
        @endforeach
    </div>
</div>
