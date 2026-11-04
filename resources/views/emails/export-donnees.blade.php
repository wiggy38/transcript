<x-mail::message>
# {{ __('pages.export.titre') }}

{{ __('pages.export.texte', ['nom' => $nom]) }}

<x-mail::button :url="$lien">
{{ __('pages.export.bouton') }}
</x-mail::button>

{{ __('pages.export.expiration', ['minutes' => $minutes, 'heures' => $heures]) }}

<span class="signature-discrete">{{ __('pages.export.ignorer') }}</span>

{{ __('courriels.signature') }}<br>
Voxia
</x-mail::message>
