<x-mail::message>
# {{ __('administration.courriel.alerte_echecs.titre') }}

{{ __('administration.courriel.alerte_echecs.texte', ['nom' => $nom, 'fenetre' => $fenetre, 'taux' => $taux, 'seuil' => $seuil]) }}

{{ __('administration.courriel.alerte_echecs.chiffres', ['echecs' => $echecs, 'tentatives' => $tentatives]) }}

<x-mail::button :url="$url">
{{ __('administration.courriel.alerte_echecs.bouton') }}
</x-mail::button>

<span class="signature-discrete">{{ __('administration.courriel.alerte_echecs.ignorer') }}</span>

{{ __('courriels.signature') }}<br>
Voxia
</x-mail::message>
