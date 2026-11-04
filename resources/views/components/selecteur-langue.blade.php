{{-- Sélecteur de langue (en-tête et réglages). Chaque choix passe par /langue : mémorisé dans le navigateur
     et enregistré sur le compte s'il y en a un. Le retour rouvre la même page dans la nouvelle langue. --}}
@php
    $courante = app()->getLocale();
@endphp
<form method="post" action="{{ route('langue.changer') }}" class="flex items-center gap-3 text-sm" role="group" aria-label="{{ __('interface.langue.selecteur') }}">
    @csrf
    <input type="hidden" name="retour" value="{{ request()->getRequestUri() }}">
    @foreach (\App\Support\Langue::disponibles() as $langue)
        <button type="submit" name="locale" value="{{ $langue }}" lang="{{ $langue }}"
            @if ($langue === $courante) aria-current="true" class="cursor-default font-medium text-texte"
            @else class="cursor-pointer text-texte-secondaire hover:text-accent" @endif>
            {{ __('interface.langues.'.$langue) }}
        </button>
    @endforeach
</form>
