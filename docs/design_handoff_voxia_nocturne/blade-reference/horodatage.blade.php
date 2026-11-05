{{-- Ligne de transcription. L'horodatage est un vrai bouton. $actif = ligne en cours de lecture. --}}
@props(['secondes', 'texte', 'actif' => false, 'lu' => false])
@php $t = gmdate($secondes >= 3600 ? 'H:i:s' : 'i:s', (int) $secondes); @endphp
<li @class(['flex gap-3 rounded-champ px-2.5 py-2', 'bg-surlignage' => $actif])>
  <button type="button"
    x-on:click="$dispatch('lecteur-aller', { t: {{ (int) $secondes }} })"
    aria-label="{{ __('transcriptions.lire_a_partir_de', ['t' => $t]) }}"
    @class(['min-h-7 w-11 shrink-0 text-left font-mono text-donnee tabular-nums', 'text-surlignage-temps font-medium' => $actif, 'text-texte-discret' => ! $actif])>{{ $t }}</button>
  <p @class(['text-petit', 'text-surlignage-texte' => $actif, 'text-texte-discret' => $lu && ! $actif, 'text-texte-secondaire' => ! $lu && ! $actif])>{{ $texte }}</p>
</li>
