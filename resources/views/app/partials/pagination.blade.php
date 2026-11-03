{{-- Pagination simple (précédente, suivante, position). Pas de liens de page : le texte reste lisible au téléphone. --}}
@if ($paginateur->hasPages())
    <nav aria-label="{{ __('historique.pagination.aria') }}" class="mt-8 flex items-center justify-between gap-4 text-sm">
        @if ($paginateur->onFirstPage())
            <span class="text-texte-discret">← {{ __('historique.pagination.precedente') }}</span>
        @else
            <a href="{{ $paginateur->previousPageUrl() }}" rel="prev" class="text-texte-secondaire no-underline hover:text-accent">← {{ __('historique.pagination.precedente') }}</a>
        @endif

        <span class="font-mono text-xs text-texte-discret">{{ __('historique.pagination.page', ['page' => $paginateur->currentPage(), 'total' => $paginateur->lastPage()]) }}</span>

        @if ($paginateur->hasMorePages())
            <a href="{{ $paginateur->nextPageUrl() }}" rel="next" class="text-texte-secondaire no-underline hover:text-accent">{{ __('historique.pagination.suivante') }} →</a>
        @else
            <span class="text-texte-discret">{{ __('historique.pagination.suivante') }} →</span>
        @endif
    </nav>
@endif
