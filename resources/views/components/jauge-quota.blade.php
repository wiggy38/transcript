{{-- Jauge de quota de l'en-tête (maquettes « Nocturne ») : une ligne et une barre fine, lien vers l'abonnement.
     Gratuit : vidéos du mois avec barre. Pro et Pack : heures restantes, sans barre. Valeurs de QuotaService (config/voxia.php). --}}
@php
    $resume = app(\App\Services\Quota\QuotaService::class)->resume(auth()->user());
    $pourcentage = isset($resume['videos_limite']) && $resume['videos_limite'] > 0
        ? min(100, (int) round($resume['videos_utilisees'] / $resume['videos_limite'] * 100))
        : null;
@endphp

<a href="{{ route('abonnement') }}" title="{{ __('interface.quota.voir_abonnement') }}"
   {{ $attributes->class(['grid min-w-44 gap-1 no-underline']) }}>
    <span class="flex justify-between gap-3 font-mono text-[11px] text-texte-secondaire">
        @if (isset($resume['videos_limite']))
            <span>{{ __('interface.quota.videos_mois', ['utilisees' => $resume['videos_utilisees'], 'limite' => $resume['videos_limite']]) }}</span>
        @elseif ($resume['secondes_mois'] !== null)
            <span>{{ __('interface.quota.heures_mois', ['duree' => \App\Support\Duree::formater($resume['secondes_mois'])]) }}</span>
        @else
            <span>{{ __('interface.quota.packs', ['duree' => \App\Support\Duree::formater($resume['secondes_packs'])]) }}</span>
        @endif
    </span>

    @if ($pourcentage !== null)
        <span aria-hidden="true" class="block h-1 overflow-hidden rounded-full bg-panneau-eleve">
            <span class="block h-full rounded-full bg-accent" style="width: {{ $pourcentage }}%"></span>
        </span>
    @endif
</a>
