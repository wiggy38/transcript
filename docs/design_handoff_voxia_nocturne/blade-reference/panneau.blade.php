@props(['ombre' => true])
<div {{ $attributes->class(['rounded-panneau border border-filet bg-panneau', 'shadow-panneau' => $ombre]) }}>
  {{ $slot }}
</div>
