{{-- Compteur de quota du compte connecté, dans la mise en page. Les valeurs viennent de QuotaService (config/voxia.php) : rien n'est écrit en dur ici. --}}
@php($resume = app(\App\Services\Quota\QuotaService::class)->resume(auth()->user()))

<div {{ $attributes->class(['flex flex-col gap-1 font-mono text-xs text-texte-secondaire']) }}>
    @if (isset($resume['videos_limite']))
        <p>{{ __('interface.quota.videos_mois', ['utilisees' => $resume['videos_utilisees'], 'limite' => $resume['videos_limite']]) }}</p>
    @else
        @if ($resume['secondes_mois'] !== null)
            <p>{{ __('interface.quota.heures_mois', ['duree' => \App\Support\Duree::formater($resume['secondes_mois'])]) }}</p>
        @endif

        @if ($resume['secondes_mois'] === null || $resume['secondes_packs'] > 0)
            <p>{{ __('interface.quota.packs', ['duree' => \App\Support\Duree::formater($resume['secondes_packs'])]) }}</p>
        @endif

        @if ($resume['credits_ia'] !== null)
            <p>{{ __('interface.quota.credits_ia', ['credits' => $resume['credits_ia']]) }}</p>
        @endif
    @endif
</div>
