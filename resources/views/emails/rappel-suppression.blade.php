<x-mail::message>
# {{ __('courriels.rappel_suppression.titre') }}

{{ __('courriels.rappel_suppression.texte', ['nom' => $nom, 'nombre' => $nombre, 'jours' => $jours, 'avant' => $avant]) }}

@foreach ($lignes as $ligne)
- {{ $ligne['titre'] }} : {{ __('courriels.rappel_suppression.le') }} {{ $ligne['date'] }}
@endforeach

<x-mail::button :url="$url">
{{ __('courriels.rappel_suppression.bouton') }}
</x-mail::button>

<span class="signature-discrete">{{ __('courriels.rappel_suppression.ignorer', ['avant' => $avant]) }}</span>

{{ __('courriels.signature') }}<br>
Voxia
</x-mail::message>
