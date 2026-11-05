{{-- Suivi : trois étapes dérivées des statuts existants. Aucune donnée serveur nouvelle. --}}
@props(['statut'])
@php
  $etapes = ['telechargement', 'transcription', 'termine'];
  $index = ['en_attente' => -1, 'telechargement' => 0, 'transcription' => 1, 'termine' => 3, 'echec' => null][$statut] ?? -1;
@endphp
<ol aria-label="{{ __('interface.etapes_traitement') }}" class="grid grid-cols-3 gap-1.5">
  @foreach ($etapes as $n => $e)
    @php
      $fait = $index !== null && $n < $index;
      $encours = $index === $n;
      $echec = $statut === 'echec' && $n === 0;
    @endphp
    <li class="grid gap-1.5" @if ($encours) aria-current="step" @endif>
      <span @class(['relative h-1 overflow-hidden rounded-full',
        'bg-succes' => $fait || ($statut === 'termine'),
        'bg-erreur' => $echec,
        'bg-panneau-eleve' => ! $fait && ! $echec && $statut !== 'termine'])>
        @if ($encours)
          <span class="absolute inset-y-0 left-0 w-2/5 rounded-full bg-direct animate-indetermine"></span>
        @endif
      </span>
      <span @class(['font-mono text-[11px]', 'text-direct font-medium' => $encours, 'text-erreur' => $echec, 'text-texte-secondaire' => $fait, 'text-texte-discret' => ! $fait && ! $encours && ! $echec])>
        {{ __("interface.statut.$e") }}
      </span>
    </li>
  @endforeach
</ol>
