{{-- Écran « Nouvelle transcription » (maquette « Tableau de bord ») : lien (aperçu puis lancement) ou fichier (envoi découpé).
     L'aperçu est interrogé tant qu'il est en cours ; rien ne se lance sans aperçu prêt. --}}
@php
    $motif = $apercu['motif'] ?? null;
    $erreurAperçu = $etat === \App\Services\Video\ApercuLien::ERREUR;
@endphp

<div class="flex flex-col gap-5" @if ($apercuEnCours) wire:poll.1500ms="verifierApercu" @endif>

    @if ($repriseActive)
        <p role="status" class="border-l-2 border-accent py-1 pl-3 text-sm text-texte">{{ __('upgrade.reprise') }}</p>
    @endif

    {{-- Lien : carte, champ de 54 px avec icône, bouton « Transcrire → » --}}
    <section aria-labelledby="titre-lien" class="grid gap-2.5 rounded-panneau border border-filet bg-panneau p-5 shadow-heros">
        <h2 id="titre-lien" class="sr-only">{{ __('transcriptions.lien.titre') }}</h2>

        <form wire:submit="soumettreLien" class="grid gap-2.5" novalidate>
            <label for="lien-transcription" class="text-[15px] font-semibold text-texte">{{ __('transcriptions.lien.champ') }}</label>

            <div class="flex flex-wrap gap-2">
                <div class="relative min-w-0 flex-[1_1_260px]">
                    <svg aria-hidden="true" focusable="false" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"
                         class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-texte-discret">
                        <path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/>
                        <path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/>
                    </svg>
                    <input id="lien-transcription" type="url" inputmode="url" autocomplete="off" placeholder="https://"
                           wire:model.live.debounce.500ms="lien" required
                           aria-describedby="aide-lien" @if ($erreurLien && ! $erreurAperçu) aria-invalid="true" @endif
                           class="min-h-[54px] w-full min-w-0 rounded-bouton border border-filet-contour bg-fond py-0 pr-3.5 pl-[42px] font-mono text-[15px] text-texte outline-none placeholder:text-texte-discret focus-visible:border-accent">
                </div>
                <button type="submit" class="inline-flex min-h-[54px] cursor-pointer items-center justify-center rounded-bouton bg-accent px-6 text-[16px] font-semibold whitespace-nowrap text-sur-accent transition-[filter] hover:brightness-110 motion-reduce:transition-none">{{ __('transcriptions.lien.bouton') }}</button>
            </div>

            <p id="aide-lien" class="m-0 text-[13px] text-texte-discret">{{ __('transcriptions.lien.aide') }}</p>
        </form>

        @if ($erreurLien && ! $erreurAperçu)
            <x-message-erreur alerte>{{ $erreurLien }}</x-message-erreur>
        @endif

        {{-- Aperçu : miniature, titre, durée, chaîne, puis lancement --}}
        <div aria-live="polite" class="grid">
            @if ($apercuEnCours)
                <p role="status" class="mt-1.5 flex items-center gap-2.5 rounded-[14px] bg-panneau-eleve p-3.5 text-sm text-texte-secondaire">
                    <span aria-hidden="true" class="vx-pulsation size-2 shrink-0 rounded-full bg-direct"></span>
                    {{ __('transcriptions.lien.lecture') }}
                </p>
            @elseif ($erreurAperçu)
                <x-message-erreur alerte class="mt-1.5">{{ __('videos.motifs.'.($motif ?? 'erreur_technique')) }}</x-message-erreur>
            @elseif ($apercuPret)
                <div class="mt-1.5 flex flex-wrap gap-4 rounded-[14px] border border-filet bg-fond p-3.5">
                    @if (! empty($donnees['miniature_url']))
                        <img src="{{ $donnees['miniature_url'] }}" alt="{{ __('transcriptions.apercu.miniature') }}"
                             loading="lazy" width="168" height="94"
                             class="aspect-video w-[168px] max-w-full shrink-0 self-start rounded-[10px] border border-filet object-cover">
                    @else
                        <div role="img" aria-label="{{ __('transcriptions.apercu.miniature') }}"
                             class="aspect-video w-[168px] max-w-full shrink-0 self-start rounded-[10px] border border-filet bg-panneau-eleve"></div>
                    @endif

                    <div class="grid min-w-0 flex-[1_1_260px] content-start gap-2.5">
                        <p class="m-0 text-[16px] leading-[1.35] font-semibold [overflow-wrap:anywhere]">{{ $donnees['titre'] ?? '—' }}</p>

                        <dl class="m-0 grid grid-cols-[auto_minmax(0,1fr)] gap-x-3.5 gap-y-0.5 text-[13px]">
                            <dt class="text-texte-discret">{{ __('transcriptions.apercu.duree') }}</dt>
                            <dd class="m-0 font-mono">{{ \App\Support\Horodatage::format(((int) ($donnees['duree_secondes'] ?? 0)) * 1000) }}</dd>
                            @if (! empty($donnees['chaine']))
                                <dt class="text-texte-discret">{{ __('transcriptions.apercu.chaine') }}</dt>
                                <dd class="m-0 min-w-0 [overflow-wrap:anywhere]">{{ $donnees['chaine'] }}</dd>
                            @endif
                        </dl>

                        @if ($transcriptionExistante)
                            <p class="m-0 text-sm text-texte-secondaire">
                                {{ __('transcriptions.apercu.deja') }}
                                <a href="{{ route('transcriptions.show', $transcriptionExistante) }}" class="text-accent-texte hover:underline">{{ __('transcriptions.apercu.voir') }}</a>
                            </p>
                        @endif

                        @if ($trop_longue)
                            <x-message-erreur alerte>{{ __('videos.motifs.trop_longue') }}</x-message-erreur>
                        @endif

                        @if ($apercu_limite)
                            <p class="m-0 rounded-[10px] bg-avertissement/[12%] px-3 py-2.5 text-[13px] text-texte">
                                {{ __('transcriptions.apercu.limite', ['minutes' => $minutes_plan]) }}
                            </p>
                        @endif

                        @if ($erreurLien)
                            <x-message-erreur alerte>{{ $erreurLien }}</x-message-erreur>
                        @endif

                        <div class="flex flex-wrap items-end gap-2.5">
                            @include('livewire.partials.langue', ['identifiant' => 'langue-lien', 'classe' => 'flex-[1_1_180px]', 'fond' => 'bg-panneau'])
                            <button type="button" wire:click="lancerLien" wire:loading.attr="disabled" @disabled($trop_longue)
                                    class="inline-flex min-h-11 cursor-pointer items-center justify-center rounded-bouton bg-accent px-[18px] text-sm font-semibold whitespace-nowrap text-sur-accent transition-opacity hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50">
                                {{ __('transcriptions.apercu.lancer') }}
                            </button>
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

    {{-- Fichier : la zone de dépôt disparaît quand le fichier est prêt (maquette « fichier prêt ») --}}
    <section aria-labelledby="titre-fichier" class="grid gap-2.5">
        <h2 id="titre-fichier" class="m-0 text-[15px] font-semibold">{{ __('transcriptions.fichier.titre') }}</h2>

        @unless ($envoi)
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
                            <path d="M12 15V3"/><path d="m7 8 5-5 5 5"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        </svg>
                    </span>

                    <p class="m-0 min-w-0 flex-[1_1_260px] text-sm text-texte-secondaire">
                        <strong class="block text-[15px] font-semibold text-texte">{{ __('transcriptions.fichier.glisser') }}</strong>
                        {{ __('transcriptions.fichier.aide', ['formats' => strtoupper(implode(', ', $extensions)), 'max' => round($tailleMaxMo / 1024, 1)]) }}
                    </p>

                    <button type="button" @click="$refs.fichier.click()" x-bind:disabled="etat === 'envoi'"
                            class="inline-flex min-h-11 cursor-pointer items-center rounded-bouton border border-filet-contour bg-transparent px-[18px] text-sm font-semibold text-texte transition-colors hover:border-accent-texte hover:text-accent-texte disabled:cursor-not-allowed disabled:opacity-50">
                        {{ __('transcriptions.fichier.parcourir') }}
                    </button>
                    <input type="file" hidden x-ref="fichier" id="fichier-transcription"
                           accept="{{ collect($extensions)->map(fn ($e) => '.'.$e)->implode(',') }}"
                           @change="choisir($event.target.files[0]); $event.target.value = ''">

                    <div x-show="etat === 'envoi'" x-cloak class="grid w-full gap-1.5">
                        <div class="flex justify-between gap-3 font-mono text-xs text-texte-secondaire">
                            <span x-text="libelleProgression()"></span>
                            <span class="min-w-0 truncate text-texte-discret" x-text="nom"></span>
                        </div>
                        <div role="progressbar" aria-label="{{ __('transcriptions.fichier.envoi') }}"
                             aria-valuemin="0" aria-valuemax="100" :aria-valuenow="progression"
                             class="h-1.5 w-full overflow-hidden rounded-full bg-panneau-eleve">
                            <div class="h-full bg-accent transition-[width] duration-200 motion-reduce:transition-none" :style="'width: ' + progression + '%'"></div>
                        </div>
                    </div>

                    <template x-if="etat === 'erreur'">
                        <p role="alert" class="flex w-full items-start gap-2 text-sm text-erreur">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true" class="mt-[3px] shrink-0">
                                <circle cx="12" cy="12" r="9"></circle><path d="M12 7.5v5.5"></path><path d="M12 16.5h.01"></path>
                            </svg>
                            <span x-text="erreur"></span>
                        </p>
                    </template>
                </div>
            </div>
        @endunless

        @if ($erreurFichier)
            <x-message-erreur alerte>{{ $erreurFichier }}</x-message-erreur>
        @endif

        @if ($envoi)
            <div class="flex flex-wrap items-end gap-x-4 gap-y-3.5 rounded-panneau border border-filet bg-panneau px-5 py-[18px]">
                <p class="m-0 flex min-w-0 flex-[1_1_220px] items-center gap-2.5 [overflow-wrap:anywhere]">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="shrink-0 text-succes">
                        <circle cx="12" cy="12" r="9"></circle><path d="m8 12.5 2.5 2.5L16 9.5"></path>
                    </svg>
                    <span>{{ __('transcriptions.fichier.pret_titre') }} <span class="font-mono text-sm">{{ $nomAffiche ?? $nomFichier }}</span></span>
                </p>
                @include('livewire.partials.langue', ['identifiant' => 'langue-fichier', 'classe' => 'flex-[0_1_200px]', 'fond' => 'bg-fond'])
                <div class="flex flex-wrap gap-2">
                    <button type="button" wire:click="lancerFichier" wire:loading.attr="disabled"
                            class="inline-flex min-h-11 cursor-pointer items-center rounded-bouton bg-accent px-[18px] text-sm font-semibold whitespace-nowrap text-sur-accent transition-opacity hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50">{{ __('transcriptions.fichier.lancer') }}</button>
                    <button type="button" wire:click="retirerFichier"
                            class="inline-flex min-h-11 cursor-pointer items-center rounded-bouton border border-filet-contour bg-transparent px-4 text-sm font-semibold text-texte transition-colors hover:border-accent-texte hover:text-accent-texte">{{ __('transcriptions.fichier.retirer') }}</button>
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
