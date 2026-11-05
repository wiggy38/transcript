@props(["classe" => ""])

{{-- Pied de page commun : site public, connexion, inscription et back-office (/admin). --}}
<footer class="border-t border-filet bg-panneau {{ $classe }}">
    <div class="mx-auto flex w-full max-w-[1120px] flex-wrap items-center justify-between gap-4 px-4 py-7 text-aide text-texte-discret">
        <span>{{ __('interface.pied.droits', ['annee' => now()->year]) }}</span>
        <ul class="m-0 flex list-none flex-wrap gap-x-6 gap-y-2 p-0">
            <li><a href="{{ route('tarifs', ['locale' => app()->getLocale()]) }}" class="inline-flex min-h-11 items-center text-texte-secondaire no-underline hover:text-accent-texte">{{ __('interface.pied.tarifs') }}</a></li>
            <li><a href="{{ route('questions', ['locale' => app()->getLocale()]) }}" class="inline-flex min-h-11 items-center text-texte-secondaire no-underline hover:text-accent-texte">{{ __('interface.pied.questions') }}</a></li>
            <li><a href="{{ route('conditions', ['locale' => app()->getLocale()]) }}" class="inline-flex min-h-11 items-center text-texte-secondaire no-underline hover:text-accent-texte">{{ __('interface.pied.conditions') }}</a></li>
            <li><a href="{{ route('confidentialite', ['locale' => app()->getLocale()]) }}" class="inline-flex min-h-11 items-center text-texte-secondaire no-underline hover:text-accent-texte">{{ __('interface.pied.confidentialite') }}</a></li>
            <li><a href="{{ route('contact', ['locale' => app()->getLocale()]) }}" class="inline-flex min-h-11 items-center text-texte-secondaire no-underline hover:text-accent-texte">{{ __('interface.pied.contact') }}</a></li>
        </ul>
    </div>
</footer>
