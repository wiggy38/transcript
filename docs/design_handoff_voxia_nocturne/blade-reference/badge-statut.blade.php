{{-- Badge de statut : point + libellé, fond teinté à 18 %. Ne jamais afficher la couleur seule.
     Classes écrites en entier pour que Tailwind les détecte. Adapter les clés à l'enum existant. --}}
@props(['statut'])
@php
  $map = [
    'en_attente'     => ['text-texte-secondaire bg-texte-secondaire/18', false],
    'telechargement' => ['text-direct bg-direct/18', true],
    'transcription'  => ['text-direct bg-direct/18', true],
    'termine'        => ['text-succes bg-succes/18', false],
    'echec'          => ['text-erreur bg-erreur/18', false],
  ];
  [$classes, $pulse] = $map[$statut] ?? $map['en_attente'];
@endphp
<span {{ $attributes->class("inline-flex items-center gap-2 whitespace-nowrap rounded-full px-2.5 py-1 font-mono text-donnee font-medium $classes") }}>
  <span aria-hidden="true" @class(['size-1.5 rounded-full bg-current', 'animate-pulse-direct' => $pulse])></span>
  {{ __("interface.statut.$statut") }}
</span>
