<x-mail::message>
# {{ __('courriels.verification.titre') }}

{{ __('courriels.verification.texte', ['nom' => $nom]) }}

<x-mail::button :url="$url" color="primary">
{{ __('courriels.verification.bouton') }}
</x-mail::button>

<span class="signature-discrete">{{ __('courriels.verification.ignorer') }}</span>

{{ __('courriels.signature') }}<br>
Voxia
</x-mail::message>
