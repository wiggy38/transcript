{{-- Écran « Nouvelle transcription » (maquette « Tableau de bord ») : lien (aperçu puis lancement) ou fichier (envoi découpé).
     L'aperçu est interrogé tant qu'il est en cours ; rien ne se lance sans aperçu prêt. --}}
@php
    $motif = $apercu['motif'] ?? null;
    $erreurAperçu = $etat === \App\Services\Video\ApercuLien::ERREUR;
@endphp

<div class="flex flex-col gap-6" @if ($apercuEnCours) wire:poll.1500ms="verifierApercu" @endif>

    @if ($repriseActive)
        <p role="status" class="border-l-2 border-accent py-1 pl-3 text-sm text-texte">{{ __('upgrade.reprise') }}</p>
    @endif

    {{-- Lien : carte, champ de 54 px avec icône, bouton « Transcrire → » --}}
    <section aria-labelledby="titre-lien" class="flex flex-col gap-4 rounded-panneau border border-filet bg-panneau p-5 shadow-panneau">
        <h2 id="titre-lien" class="sr-only">{{ __('transcriptions.lien.titre') }}</h2>

        <form wire:submit="soumettreLien" class="flex flex-col gap-2.5" novalidate>
            <label for="lien-transcription" class="text-[15px] font-semibold text-texte">{{ __('transcriptions.lien.champ') }}</label>

            <div class="flex flex-wrap gap-2">
                <div class="relative min-w-0 flex-1 basis-64">
                    <svg aria-hidden="true" focusable="false" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"
                         class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-texte-discret">
                        <path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/>
                        <path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/>
                    </svg>
                    <input id="lien-transcription" type="url" inputmode="url" autocomplete="off" placeholder="https://"
                           wire:model.live.debounce.500ms="lien" required
                           aria-describedby="aide-lien" @if ($erreurLien && ! $erreurAperçu) aria-invalid="true" @endif
                           class="min-h-[54px] w-full min-w-0 rounded-bouton border border-filet-contour bg-fond py-0 pr-3.5 pl-[42px] font-mono text-sm text-texte outline-none placeholder:text-texte-discret focus-visible:border-accent">
                </div>
                <x-bouton type="submit" class="min-h-[54px] px-6 text-[16px]">{{ __('transcriptions.lien.bouton') }}</x-bouton>
            </div>

            <p id="aide-lien" class="text-[13px] text-texte-discret">{{ __('transcriptions.lien.aide') }}</p>
        </form>

        @if ($erreurLien && ! $erreurAperçu)
            <x-message-erreur>{{ $erreurLien }}</x-message-erreur>
        @endif

        {{-- Aperçu : miniature, titre, durée, chaîne, puis lancement --}}
        <div aria-live="polite">
            @if ($apercuEnCours)
                <p role="status" class="flex items-center gap-3 rounded-champ bg-panneau-eleve p-3.5 text-sm text-texte-secondaire">
                    <span aria-hidden="true" class="vx-pulsation size-2 rounded-full bg-direct"></span>
                    {{ __('transcriptions.lien.lecture') }}
                </p>
            @elseif ($erreurAperçu)
                <x-message-erreur>{{ __('videos.motifs.'.($motif ?? 'erreur_technique')) }}</x-message-erreur>
            @elseif ($apercuPret)
                <div class="flex flex-col gap-5 rounded-champ border border-filet bg-fond p-3.5 sm:flex-row">
                    @if (! empty($donnees['miniature_url']))
                        <img src="{{ $donnees['miniature_url'] }}" alt="{{ __('transcriptions.apercu.miniature') }}"
                             loading="lazy" width="168" height="94"
                             class="aspect-video w-full rounded-champ border border-filet object-cover sm:w-42 sm:shrink-0">
                    @endif

                    <div class="flex min-w-0 flex-1 flex-col gap-3">
                        <p class="text-[16px] leading-snug font-semibold [overflow-wrap:anywhere]">{{ $donnees['titre'] ?? '—' }}</p>

                        <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-0.5 text-[13px]">
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
                                <a href="{{ route('transcriptions.show', $transcriptionExistante) }}" class="text-accent-texte hover:underline">{{ __('transcriptions.apercu.voir') }}</a>
                            </p>
                        @endif

                        @if ($trop_longue)
                            <x-message-erreur>{{ __('videos.motifs.trop_longue') }}</x-message-erreur>
                        @endif

                        @if ($apercu_limite)
                            <p class="rounded-champ bg-avertissement/10 px-3 py-2.5 text-[13px] text-texte">
                                {{ __('transcriptions.apercu.limite', ['minutes' => $minutes_plan]) }}
                            </p>
                        @endif

                        @if ($erreurLien)
                            <x-message-erreur>{{ $erreurLien }}</x-message-erreur>
                        @endif

                        <div class="flex flex-col gap-4 pt-1 sm:flex-row sm:items-end">
                            @include('livewire.partials.langue', ['identifiant' => 'langue-lien'])
                            <x-bouton wire:click="lancerLien" wire:loading.attr="disabled" :disabled="$trop_longue" class="min-h-11">
                                {{ __('transcriptions.apercu.lancer') }}
                            </x-bouton>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>

    {{-- Séparateur « ou » --}}
    <div aria-hidden="true" class="flex items-center gap-3 font-mono text-xs text-texte-discret">
        <span class="h-px flex-1 bg-filet"></span>{{ __('transcriptions.fichier.ou') }}<span class="h-px flex-1 bg-filet"></span>
    </div>

    {{-- Fichier --}}
    <section aria-labelledby="titre-fichier" class="flex flex-col gap-3">
        <h2 id="titre-fichier" class="text-[15px] font-semibold">{{ __('transcriptions.fichier.titre') }}</h2>

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
            @dragover.prevent="survol = true"
            @dragleave.prevent="survol = false"
            @drop.prevent="survol = false; choisir($event.dataTransfer.files[0])">

            <div class="flex flex-wrap items-center gap-x-5 gap-y-3.5 rounded-panneau border-[1.5px] border-dashed p-5 transition-colors motion-reduce:transition-none"
                 :class="survol ? 'border-accent bg-panneau-eleve' : 'border-filet-contour bg-panneau'">
                <span aria-hidden="true" class="grid size-11 shrink-0 place-items-center rounded-bouton bg-panneau-eleve text-texte-secondaire">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 15V3"/><path d="m7 8 5-5 5 5"/><path d="M4 15v4a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-4"/>
                    </svg>
                </span>

                <p class="min-w-0 flex-1 basis-64 text-sm text-texte-secondaire">
                    <strong class="block text-[15px] font-semibold text-texte">{{ __('transcriptions.fichier.glisser') }}</strong>
                    {{ __('transcriptions.fichier.aide', ['formats' => strtoupper(implode(', ', $extensions)), 'max' => round($tailleMaxMo / 1024, 1)]) }}
                </p>

                <x-bouton variante="filet" type="button" @click="$refs.fichier.click()" x-bind:disabled="etat === 'envoi'">
                    {{ __('transcriptions.fichier.parcourir') }}
                </x-bouton>
                <input type="file" hidden x-ref="fichier" id="fichier-transcription"
                       accept="{{ collect($extensions)->map(fn ($e) => '.'.$e)->implode(',') }}"
                       @change="choisir($event.target.files[0]); $event.target.value = ''">

                <div x-show="etat === 'envoi'" x-cloak class="flex w-full flex-col gap-2">
                    <div role="progressbar" aria-label="{{ __('transcriptions.fichier.envoi') }}"
                         aria-valuemin="0" aria-valuemax="100" :aria-valuenow="progression"
                         class="h-1 w-full overflow-hidden rounded-full bg-filet">
                        <div class="h-full bg-accent transition-[width] duration-200 motion-reduce:transition-none" :style="'width: ' + progression + '%'"></div>
                    </div>
                    <p class="font-mono text-xs text-texte-secondaire" x-text="libelleProgression()"></p>
                </div>

                <template x-if="etat === 'erreur'">
                    <x-message-erreur class="w-full">
                        <span x-text="erreur"></span>
                    </x-message-erreur>
                </template>
            </div>
        </div>

        @if ($erreurFichier)
            <x-message-erreur>{{ $erreurFichier }}</x-message-erreur>
        @endif

        @if ($envoi)
            <div class="flex flex-col gap-4 rounded-panneau border border-filet bg-panneau p-5 sm:flex-row sm:items-end sm:justify-between">
                <p class="flex min-w-0 items-center gap-2.5 [overflow-wrap:anywhere]">
                    <span aria-hidden="true" class="text-succes">✓</span>
                    {{ __('transcriptions.fichier.pret', ['nom' => $nomAffiche ?? $nomFichier]) }}
                </p>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
                    @include('livewire.partials.langue', ['identifiant' => 'langue-fichier'])
                    <div class="flex flex-wrap gap-3">
                        <x-bouton wire:click="lancerFichier" wire:loading.attr="disabled">{{ __('transcriptions.fichier.lancer') }}</x-bouton>
                        <x-bouton variante="filet" wire:click="retirerFichier">{{ __('transcriptions.fichier.retirer') }}</x-bouton>
                    </div>
                </div>
            </div>
        @endif
    </section>

    {{-- Déclencheur principal : la 6e vidéo du mois en Gratuit. Ouverte par proposerUpgradeQuota (NouvelleTranscription). --}}
    <x-upgrade.fenetre id="upgrade-quota" :titre="__('upgrade.quota.titre')" :retour="route('tableau-de-bord')">
        <p class="text-texte">{{ __('upgrade.quota.texte', ['limite' => (int) config('voxia.plans.free.videos_per_month')]) }}</p>
        <x-compteur-plan class="mt-3" />
        <p class="mt-3 text-sm">{{ __('upgrade.quota.reprise') }}</p>
    </x-upgrade.fenetre>
</div>
