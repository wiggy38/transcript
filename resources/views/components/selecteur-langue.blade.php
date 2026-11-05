{{-- Sélecteur de langue (maquette « Nocturne ») : un bouton avec le code de la langue courante
     ouvre la liste des langues. Chaque choix passe par /langue : mémorisé dans le navigateur
     et enregistré sur le compte s'il y en a un. Le retour rouvre la même page dans la nouvelle langue.
     Le menu demande Alpine (fourni avec Livewire, présent dans les layouts). --}}
@php
    $courante = app()->getLocale();
@endphp
<div class="relative" x-data="{ ouvert: false }" @click.outside="ouvert = false" @keydown.escape="ouvert = false">
    <button type="button" x-on:click="ouvert = ! ouvert" :aria-expanded="ouvert ? 'true' : 'false'" aria-haspopup="true" aria-controls="menu-langue"
        aria-label="{{ __('interface.langue.courante', ['langue' => __('interface.langues.'.$courante)]) }}"
        class="inline-flex min-h-9 cursor-pointer items-center rounded-[10px] border border-filet px-2.5 font-mono text-xs text-texte-secondaire transition-colors hover:text-texte">{{ strtoupper($courante) }}</button>

    <form id="menu-langue" method="post" action="{{ route('langue.changer') }}" x-show="ouvert" x-cloak role="group" aria-label="{{ __('interface.langue.selecteur') }}"
        class="absolute top-full right-0 z-30 mt-2 grid min-w-36 gap-1 rounded-champ border border-filet bg-panneau p-1.5 shadow-panneau">
        @csrf
        <input type="hidden" name="retour" value="{{ request()->getRequestUri() }}">
        @foreach (\App\Support\Langue::disponibles() as $langue)
            <button type="submit" name="locale" value="{{ $langue }}" lang="{{ $langue }}"
                @if ($langue === $courante) aria-current="true" class="flex min-h-9 cursor-default items-center rounded-[10px] bg-panneau-eleve px-3 text-left text-sm font-medium text-texte"
                @else class="flex min-h-9 cursor-pointer items-center rounded-[10px] px-3 text-left text-sm text-texte-secondaire transition-colors hover:bg-panneau-eleve hover:text-texte" @endif>
                {{ __('interface.langues.'.$langue) }}
            </button>
        @endforeach
    </form>
</div>
