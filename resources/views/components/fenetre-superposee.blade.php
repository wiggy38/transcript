{{-- Fenêtre superposée (dialog natif : piège le focus, se ferme avec Échap, rend le focus à l'élément qui l'a ouverte).
     Ouverture : $dispatch('voxia-ouvrir', { id: '{{ $id }}' }) depuis n'importe quel bouton. --}}
@props(['id', 'titre'])

<dialog
    id="{{ $id }}"
    x-data
    x-ref="dialogue"
    x-on:voxia-ouvrir.window="$event.detail.id === '{{ $id }}' && $refs.dialogue.showModal()"
    aria-labelledby="{{ $id }}-titre"
    {{ $attributes->class(['m-auto w-[min(92vw,34rem)] rounded-panneau border border-filet bg-panneau-eleve p-0 text-texte']) }}
>
    <div class="flex flex-col gap-6 p-6 md:p-8">
        <div class="flex items-start justify-between gap-4">
            <h2 id="{{ $id }}-titre" class="text-xl font-semibold tracking-tight">{{ $titre }}</h2>
            <x-bouton variante="filet" x-on:click="$refs.dialogue.close()">{{ __('interface.fenetre.fermer') }}</x-bouton>
        </div>
        <div class="text-texte-secondaire">{{ $slot }}</div>
    </div>
</dialog>
