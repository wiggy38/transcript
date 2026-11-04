{{-- Panneau de la charte « Surligneur » : fond surface, filet fin, rayon 20 px. eleve : surface élevée. --}}
@props(['eleve' => false])

<div {{ $attributes->class(['rounded-panneau border border-filet', $eleve ? 'bg-panneau-eleve' : 'bg-panneau']) }}>
    {{ $slot }}
</div>
