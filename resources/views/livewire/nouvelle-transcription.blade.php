{{-- Écran « Nouvelle transcription » : lien (aperçu puis lancement) ou fichier (envoi découpé).
     L'aperçu est interrogé tant qu'il est en cours ; rien ne se lance sans aperçu prêt. --}}
@php
    $motif = $apercu['motif'] ?? null;
    $erreurAperçu = $etat === \App\Services\Video\ApercuLien::ERREUR;
@endphp

<div class="flex flex-col gap-12" @if ($apercuEnCours) wire:poll.1500ms="verifierApercu" @endif>

    {{-- Lien --}}
    <section aria-labelledby="titre-lien" class="max-w-[720px]">
        <h2 id="titre-lien" class="font-mono text-xs uppercase tracking-wide text-texte-discret">{{ __('transcriptions.lien.titre') }}</h2>

        <form wire:submit="soumettreLien" class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-end" novalidate>
            <div class="min-w-0 flex-1">
                <x-champ id="lien-transcription" wire:model.live.debounce.500ms="lien" type="url" :label="__('transcriptions.lien.champ')"
                    :placeholder="'https://'" mono required :aide="__('transcriptions.lien.aide')" />
            </div>
            <x-bouton type="submit" class="sm:mb-[26px]">{{ __('transcriptions.lien.bouton') }}</x-bouton>
        </form>

        @if ($erreurLien && ! $erreurAperçu)
            <div class="mt-4">
                <x-message-erreur>{{ $erreurLien }}</x-message-erreur>
            </div>
        @endif

        {{-- Aperçu : titre, durée, miniature, chaîne, puis lancement --}}
        <div class="mt-6" aria-live="polite">
            @if ($apercuEnCours)
                <p role="status" class="flex items-center gap-3 text-sm text-texte-secondaire">
                    <span aria-hidden="true" class="vx-pulsation size-2 rounded-full bg-accent"></span>
                    {{ __('transcriptions.lien.lecture') }}
                </p>
            @elseif ($erreurAperçu)
                <x-message-erreur>{{ __('videos.motifs.'.($motif ?? 'erreur_technique')) }}</x-message-erreur>
            @elseif ($apercuPret)
                <x-panneau class="flex flex-col gap-5 p-5 sm:flex-row">
                    @if (! empty($donnees['miniature_url']))
                        <img src="{{ $donnees['miniature_url'] }}" alt="{{ __('transcriptions.apercu.miniature') }}"
                             loading="lazy" width="160" height="90"
                             class="aspect-video w-full rounded-champ object-cover sm:w-40 sm:shrink-0">
                    @endif

                    <div class="flex min-w-0 flex-1 flex-col gap-3">
                        <p class="text-lg font-semibold leading-snug [overflow-wrap:anywhere]">{{ $donnees['titre'] ?? '—' }}</p>

                        <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 text-sm">
                            <dt class="text-texte-discret">{{ __('transcriptions.apercu.duree') }}</dt>
                            <dd class="font-mono">{{ \App\Support\Horodatage::format(((int) ($donnees['duree_secondes'] ?? 0)) * 1000) }}</dd>
                            @if (! empty($donnees['chaine']))
                                <dt class="text-texte-discret">{{ __('transcriptions.apercu.chaine') }}</dt>
                                <dd class="min-w-0 [overflow-wrap:anywhere]">{{ $donnees['chaine'] }}</dd>
                            @endif
                        </dl>

                        @if ($transcriptionExistante)
                            <p class="text-sm text-texte-secondaire">
                                {{ __('transcriptions.apercu.deja') }}
                                <a href="{{ route('transcriptions.show', $transcriptionExistante) }}" class="text-accent hover:underline">{{ __('transcriptions.apercu.voir') }}</a>
                            </p>
                        @endif

                        @if ($trop_longue)
                            <x-message-erreur>{{ __('videos.motifs.trop_longue') }}</x-message-erreur>
                        @endif

                        @if ($apercu_limite)
                            <p class="border-l-2 border-accent py-1 pl-3 text-sm text-texte">
                                {{ __('transcriptions.apercu.limite', ['minutes' => $minutes_plan]) }}
                            </p>
                        @endif

                        @if ($erreurLien)
                            <x-message-erreur>{{ $erreurLien }}</x-message-erreur>
                        @endif

                        <div class="flex flex-col gap-4 pt-1 sm:flex-row sm:items-end">
                            @include('livewire.partials.langue', ['identifiant' => 'langue-lien'])
                            <x-bouton wire:click="lancerLien" wire:loading.attr="disabled" :disabled="$trop_longue">
                                {{ __('transcriptions.apercu.lancer') }}
                            </x-bouton>
                        </div>
                    </div>
                </x-panneau>
            @endif
        </div>
    </section>

    {{-- Fichier --}}
    <section aria-labelledby="titre-fichier" class="max-w-[720px]">
        <h2 id="titre-fichier" class="font-mono text-xs uppercase tracking-wide text-texte-discret">{{ __('transcriptions.fichier.titre') }}</h2>

        <div x-data="televersement($wire, @js([
                'url' => route('televersements.morceau'),
                'morceauOctets' => $morceauOctets,
                'tailleMaxOctets' => $tailleMaxOctets,
                'extensions' => $extensions,
                'messages' => [
                    'format' => __('transcriptions.televersement.format'),
                    'vide' => __('transcriptions.televersement.vide'),
                    'trop_gros' => __('transcriptions.televersement.trop_gros', ['max' => $tailleMaxMo]),
                    'echec' => __('transcriptions.televersement.echec'),
                    'progression' => __('transcriptions.fichier.progression'),
                ],
            ]))"
            class="mt-4"
            @dragover.prevent="survol = true"
            @dragleave.prevent="survol = false"
            @drop.prevent="survol = false; choisir($event.dataTransfer.files[0])">

            <div class="flex flex-col gap-4 rounded-panneau border border-dashed p-6 transition-colors motion-reduce:transition-none"
                 :class="survol ? 'border-accent bg-panneau-eleve' : 'border-filet-contour bg-panneau'">
                <p class="text-texte-secondaire">
                    <span class="text-texte">{{ __('transcriptions.fichier.glisser') }}</span>
                    {{ __('transcriptions.fichier.aide', ['formats' => strtoupper(implode(', ', $extensions)), 'max' => round($tailleMaxMo / 1024, 1)]) }}
                </p>

                <div>
                    <x-bouton variante="filet" type="button" @click="$refs.fichier.click()" x-bind:disabled="etat === 'envoi'">
                        {{ __('transcriptions.fichier.parcourir') }}
                    </x-bouton>
                    <input type="file" hidden x-ref="fichier" id="fichier-transcription"
                           accept="{{ collect($extensions)->map(fn ($e) => '.'.$e)->implode(',') }}"
                           @change="choisir($event.target.files[0]); $event.target.value = ''">
                </div>

                <div x-show="etat === 'envoi'" x-cloak class="flex flex-col gap-2">
                    <div role="progressbar" aria-label="{{ __('transcriptions.fichier.envoi') }}"
                         aria-valuemin="0" aria-valuemax="100" :aria-valuenow="progression"
                         class="h-1 w-full overflow-hidden rounded-bouton bg-filet">
                        <div class="h-full bg-accent transition-[width] duration-200 motion-reduce:transition-none" :style="'width: ' + progression + '%'"></div>
                    </div>
                    <p class="font-mono text-xs text-texte-secondaire" x-text="libelleProgression()"></p>
                </div>

                <template x-if="etat === 'erreur'">
                    <x-message-erreur>
                        <span x-text="erreur"></span>
                    </x-message-erreur>
                </template>
            </div>
        </div>

        @if ($erreurFichier)
            <div class="mt-4">
                <x-message-erreur>{{ $erreurFichier }}</x-message-erreur>
            </div>
        @endif

        @if ($envoi)
            <x-panneau class="mt-4 flex flex-col gap-4 p-5 sm:flex-row sm:items-end sm:justify-between">
                <p class="min-w-0 [overflow-wrap:anywhere]">
                    {{ __('transcriptions.fichier.pret', ['nom' => $nomAffiche ?? $nomFichier]) }}
                </p>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
                    @include('livewire.partials.langue', ['identifiant' => 'langue-fichier'])
                    <div class="flex gap-3">
                        <x-bouton wire:click="lancerFichier" wire:loading.attr="disabled">{{ __('transcriptions.fichier.lancer') }}</x-bouton>
                        <x-bouton variante="filet" wire:click="retirerFichier">{{ __('transcriptions.fichier.retirer') }}</x-bouton>
                    </div>
                </div>
            </x-panneau>
        @endif
    </section>
</div>
