{{-- Pied de page du back-office (/admin) : classes Filament, car le thème admin ne charge pas les tokens Voxia. --}}
<footer class="border-t border-gray-700 bg-gray-900">
    <div class="mx-auto flex w-full max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-6 text-sm text-gray-400">
        <span>{{ __('interface.pied.droits', ['annee' => now()->year]) }}</span>
        <ul class="m-0 flex list-none flex-wrap gap-x-6 gap-y-2 p-0">
            <li><a href="{{ route('tarifs', ['locale' => app()->getLocale()]) }}" class="inline-flex min-h-11 items-center text-gray-300 no-underline hover:text-primary-400">{{ __('interface.pied.tarifs') }}</a></li>
            <li><a href="{{ route('questions', ['locale' => app()->getLocale()]) }}" class="inline-flex min-h-11 items-center text-gray-300 no-underline hover:text-primary-400">{{ __('interface.pied.questions') }}</a></li>
            <li><a href="{{ route('conditions', ['locale' => app()->getLocale()]) }}" class="inline-flex min-h-11 items-center text-gray-300 no-underline hover:text-primary-400">{{ __('interface.pied.conditions') }}</a></li>
            <li><a href="{{ route('confidentialite', ['locale' => app()->getLocale()]) }}" class="inline-flex min-h-11 items-center text-gray-300 no-underline hover:text-primary-400">{{ __('interface.pied.confidentialite') }}</a></li>
            <li><a href="{{ route('contact', ['locale' => app()->getLocale()]) }}" class="inline-flex min-h-11 items-center text-gray-300 no-underline hover:text-primary-400">{{ __('interface.pied.contact') }}</a></li>
        </ul>
    </div>
</footer>
