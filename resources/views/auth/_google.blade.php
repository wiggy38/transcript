{{-- Connexion Google : affichée seulement si VOXIA_GOOGLE_LOGIN=true. --}}
@if (config('voxia.comptes.google_actif'))
    <div class="flex flex-col gap-5">
        <a href="{{ route('google.redirect') }}" class="inline-flex min-h-11 items-center justify-center rounded-bouton border border-filet-contour px-5 text-[15px] font-semibold text-texte no-underline hover:border-accent hover:text-accent-texte">
            {{ __('comptes.google.bouton') }}
        </a>
        <p class="text-center font-mono text-xs uppercase tracking-wide text-texte-discret">{{ __('comptes.google.ou') }}</p>
    </div>
@endif
