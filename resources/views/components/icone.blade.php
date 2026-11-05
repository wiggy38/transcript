{{-- Icône en SVG en ligne, trait currentColor (style Lucide, ISC). Décorative par défaut : le texte à côté porte le sens.
     nom : lien | envoi | lecture | copier | telecharger | alerte | cadenas | coche | recherche | fichier-audio --}}
@props(['nom'])
@php
    $chemins = [
        'lien' => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
        'envoi' => '<path d="M12 3v12"/><path d="m17 8-5-5-5 5"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>',
        'lecture' => '<polygon points="6 4 20 12 6 20 6 4"/>',
        'copier' => '<rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
        'telecharger' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>',
        'alerte' => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>',
        'cadenas' => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'coche' => '<circle cx="12" cy="12" r="10"/><polyline points="8 12 11 15 16 9"/>',
        'recherche' => '<circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
        'fichier-audio' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M10 17v-4l4-1v4"/><circle cx="8.5" cy="17" r="1.5"/><circle cx="14.5" cy="16" r="1.5"/>',
    ];
@endphp
<svg {{ $attributes->class(['size-[22px] shrink-0']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    {!! $chemins[$nom] ?? '' !!}
</svg>
