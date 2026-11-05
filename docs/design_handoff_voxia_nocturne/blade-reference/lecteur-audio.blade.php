{{-- Lecteur habillé (Alpine) remplaçant <audio controls>. L'élément <audio> reste dans le DOM. --}}
@props(['src', 'duree'])
<div x-data="{
      enLecture: false, t: 0,
      fmt(s) { s = Math.floor(s); return String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0') },
      basculer() { this.$refs.a.paused ? this.$refs.a.play() : this.$refs.a.pause() }
    }"
    x-on:lecteur-aller.window="$refs.a.currentTime = $event.detail.t; $refs.a.play()"
    class="sticky top-0 z-10 grid grid-cols-[44px_minmax(0,1fr)] items-center gap-3 rounded-panneau border border-filet bg-panneau p-3">
  <audio x-ref="a" src="{{ $src }}" preload="metadata"
         x-on:play="enLecture = true" x-on:pause="enLecture = false" x-on:timeupdate="t = $refs.a.currentTime"></audio>
  <button type="button" x-on:click="basculer()" :aria-label="enLecture ? '{{ __('transcriptions.pause') }}' : '{{ __('transcriptions.lecture') }}'"
          class="grid size-11 place-items-center rounded-bouton bg-accent text-sur-accent">
    <x-icone nom="lecture" x-show="!enLecture" class="size-4" />
    <x-icone nom="pause" x-show="enLecture" class="size-4" />
  </button>
  <div class="grid min-w-0 gap-1">
    <input type="range" min="0" :max="{{ (int) $duree }}" step="1" :value="t" x-on:input="$refs.a.currentTime = $event.target.value"
           aria-label="{{ __('transcriptions.position') }}" class="w-full accent-[var(--vx-accent)]">
    <span class="font-mono text-[11px] tabular-nums text-texte-secondaire"><span x-text="fmt(t)">00:00</span> / {{ gmdate('i:s', (int) $duree) }}</span>
  </div>
</div>
