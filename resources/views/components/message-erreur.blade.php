{{-- Message d'erreur : texte clair, annoncé aux lecteurs d'écran.
     Par défaut : filet d'accent d'erreur à gauche. alerte : encadré teinté avec icône (maquette « Tableau de bord »). --}}
@props(['id' => null, 'alerte' => false])

<p @if ($id) id="{{ $id }}" @endif role="alert" {{ $attributes->class([$alerte ? 'flex items-start gap-2.5 rounded-champ bg-erreur/[12%] px-3.5 py-3 text-sm text-texte' : 'border-l-2 border-erreur py-1 pl-3 text-sm text-texte']) }}>
    @if ($alerte)
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" class="mt-0.5 shrink-0 text-erreur">
            <circle cx="12" cy="12" r="9"></circle>
            <path d="M12 7.5v5.5"></path>
            <path d="M12 16.5h.01"></path>
        </svg>
    @endif
    {{ $slot }}
</p>
