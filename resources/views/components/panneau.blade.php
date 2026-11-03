{{-- Panneau de la charte : fond #121311, filet fin, rayon 10 px. eleve : fond #161715. --}}
@props(['eleve' => false])

<div {{ $attributes->class(['rounded-panneau border border-filet', $eleve ? 'bg-panneau-eleve' : 'bg-panneau']) }}>
    {{ $slot }}
</div>
