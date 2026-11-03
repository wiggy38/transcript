{{-- Logo Voxia : cinq barres, la première en accent. --}}
<a href="/" {{ $attributes->class(['inline-flex items-center gap-2.5 text-[19px] font-semibold tracking-tight text-texte no-underline']) }}>
    <svg width="22" height="18" viewBox="0 0 22 18" aria-hidden="true" focusable="false">
        <rect x="0" y="6" width="3" height="6" class="fill-accent"/>
        <rect x="5" y="2" width="3" height="14" class="fill-texte"/>
        <rect x="10" y="0" width="3" height="18" class="fill-texte"/>
        <rect x="15" y="4" width="3" height="10" class="fill-texte"/>
        <rect x="20" y="7" width="2" height="4" class="fill-texte"/>
    </svg>
    {{ __('interface.marque') }}
</a>
