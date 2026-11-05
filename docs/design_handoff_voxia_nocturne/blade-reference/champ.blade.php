{{-- Champ avec vrai <label>, aide et erreur reliées par aria-describedby. --}}
@props(['id', 'label', 'aide' => null, 'erreur' => null, 'icone' => null, 'taille' => 'md'])
@php $h = $taille === 'lg' ? 'min-h-[54px] text-[15px]' : 'min-h-11 text-[14px]'; @endphp
<div class="grid gap-2">
  <label for="{{ $id }}" class="text-[15px] font-semibold text-texte">{{ $label }}</label>
  <div class="relative">
    @if ($icone)
      <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-texte-discret" aria-hidden="true">{{ $icone }}</span>
    @endif
    <input id="{{ $id }}"
      @if ($aide || $erreur) aria-describedby="{{ $id }}-desc" @endif
      @if ($erreur) aria-invalid="true" @endif
      {{ $attributes->class([
        "w-full min-w-0 rounded-champ bg-fond px-3.5 font-mono text-texte placeholder:text-texte-discret $h",
        'pl-[42px]' => $icone,
        'border border-filet-contour' => ! $erreur,
        'border-[1.5px] border-erreur' => $erreur,
      ]) }}>
  </div>
  @if ($erreur)
    <p id="{{ $id }}-desc" role="alert" class="flex items-start gap-2 text-[14px] text-erreur">
      <x-icone nom="alerte" class="mt-0.5 size-4 shrink-0" />{{ $erreur }}
    </p>
  @elseif ($aide)
    <p id="{{ $id }}-desc" class="text-aide text-texte-discret">{{ $aide }}</p>
  @endif
</div>
