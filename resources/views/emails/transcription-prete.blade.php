<x-mail::message>
# {{ __('courriels.transcription_prete.titre') }}

{{ __('courriels.transcription_prete.texte', ['nom' => $nom, 'titre' => $titre, 'duree' => $duree]) }}

<x-mail::button :url="$url">
{{ __('courriels.transcription_prete.bouton') }}
</x-mail::button>

<span class="signature-discrete">{{ __('courriels.transcription_prete.ignorer') }}</span>

{{ __('courriels.signature') }}<br>
Voxia
</x-mail::message>
