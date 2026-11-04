{{ __('pages.contact_message.titre') }}

{{ __('pages.contact_message.de', ['nom' => $nom, 'email' => $adresse]) }}
{{ __('pages.contact_message.sujet', ['sujet' => __('pages.contact.sujets.'.$sujet)]) }}

{!! $contenu !!}
