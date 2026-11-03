{{-- Message d'erreur : texte clair, filet d'accent d'erreur à gauche, annoncé aux lecteurs d'écran. --}}
@props(['id' => null])

<p @if ($id) id="{{ $id }}" @endif role="alert" {{ $attributes->class(['border-l-2 border-erreur py-1 pl-3 text-sm text-texte']) }}>
    {{ $slot }}
</p>
