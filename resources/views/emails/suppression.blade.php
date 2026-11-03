<x-mail::message>
# {{ __('courriels.suppression.titre') }}

{{ __('courriels.suppression.texte', ['nom' => $nom]) }}

<span class="signature-discrete">{{ __('courriels.suppression.ignorer') }}</span>

{{ __('courriels.signature') }}<br>
Voxia
</x-mail::message>
