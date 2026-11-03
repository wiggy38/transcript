<x-mail::message>
# {{ __('courriels.reinitialisation.titre') }}

{{ __('courriels.reinitialisation.texte', ['nom' => $nom]) }}

<x-mail::button :url="$url" color="primary">
{{ __('courriels.reinitialisation.bouton') }}
</x-mail::button>

<span class="signature-discrete">{{ __('courriels.reinitialisation.ignorer') }}</span>

{{ __('courriels.signature') }}<br>
Voxia
</x-mail::message>
