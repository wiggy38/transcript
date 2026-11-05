{{-- Pagination simple, maquette « Historique - Nocturne » : précédente, position, suivante. Les extrémités désactivées
     restent visibles en pointillés. Pas de liens de page : le texte reste lisible au téléphone. --}}
<nav aria-label="{{ __('historique.pagination.aria') }}" class="flex items-center justify-between gap-3">
    @if ($paginateur->onFirstPage())
        <span class="inline-flex min-h-10 shrink-0 items-center rounded-bouton border border-dashed border-filet px-3.5 text-sm whitespace-nowrap text-texte-discret">← {{ __('historique.pagination.precedente') }}</span>
    @else
        <a href="{{ $paginateur->previousPageUrl() }}" rel="prev" class="inline-flex min-h-10 shrink-0 items-center rounded-bouton border border-filet-contour px-3.5 text-sm whitespace-nowrap text-texte no-underline hover:border-accent-texte hover:text-accent-texte">← {{ __('historique.pagination.precedente') }}</a>
    @endif

    <span class="text-center font-mono text-xs text-texte-discret">{{ __('historique.pagination.page', ['page' => $paginateur->currentPage(), 'total' => $paginateur->lastPage()]) }}</span>

    @if ($paginateur->hasMorePages())
        <a href="{{ $paginateur->nextPageUrl() }}" rel="next" class="inline-flex min-h-10 shrink-0 items-center rounded-bouton border border-filet-contour px-3.5 text-sm whitespace-nowrap text-texte no-underline hover:border-accent-texte hover:text-accent-texte">{{ __('historique.pagination.suivante') }} →</a>
    @else
        <span class="inline-flex min-h-10 shrink-0 items-center rounded-bouton border border-dashed border-filet px-3.5 text-sm whitespace-nowrap text-texte-discret">{{ __('historique.pagination.suivante') }} →</span>
    @endif
</nav>
