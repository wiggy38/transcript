<x-layouts::app :titre="__('interface.styleguide.titre')">
    <div x-data class="flex flex-col gap-20">
        <header class="flex flex-col gap-3">
            <p class="font-mono text-xs uppercase tracking-wide text-texte-discret">{{ __('interface.styleguide.avertissement') }}</p>
            <h1 class="text-4xl font-semibold tracking-tight">{{ __('interface.styleguide.titre') }}</h1>
        </header>

        {{-- Couleurs : jetons de app.css --}}
        <section aria-labelledby="s-couleurs" class="flex flex-col gap-6">
            <h2 id="s-couleurs" class="font-mono text-xs uppercase tracking-wide text-texte-discret">{{ __('interface.styleguide.couleurs') }}</h2>
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                <x-panneau class="flex flex-col gap-2 p-4"><span class="h-12 rounded-champ bg-fond border border-filet"></span><span class="font-mono text-xs">fond · #F4F1EA</span></x-panneau>
                <x-panneau class="flex flex-col gap-2 p-4"><span class="h-12 rounded-champ bg-panneau border border-filet"></span><span class="font-mono text-xs">panneau · #FFFDF8</span></x-panneau>
                <x-panneau eleve class="flex flex-col gap-2 p-4"><span class="h-12 rounded-champ bg-panneau-eleve border border-filet"></span><span class="font-mono text-xs">panneau-eleve · #EAE6DB</span></x-panneau>
                <x-panneau class="flex flex-col gap-2 p-4"><span class="h-12 rounded-champ bg-accent"></span><span class="font-mono text-xs">accent · #E4521F</span></x-panneau>
                <x-panneau class="flex flex-col gap-2 p-4"><span class="h-12 rounded-champ bg-surlignage"></span><span class="font-mono text-xs">surlignage · #DDF37A</span></x-panneau>
                <x-panneau class="flex flex-col gap-2 p-4"><span class="h-12 rounded-champ bg-texte"></span><span class="font-mono text-xs">texte · #1A1814</span></x-panneau>
                <x-panneau class="flex flex-col gap-2 p-4"><span class="h-12 rounded-champ bg-texte-secondaire"></span><span class="font-mono text-xs">texte-secondaire · #4B463D</span></x-panneau>
                <x-panneau class="flex flex-col gap-2 p-4"><span class="h-12 rounded-champ bg-texte-discret"></span><span class="font-mono text-xs">texte-discret · #655F53</span></x-panneau>
                <x-panneau class="flex flex-col gap-2 p-4"><span class="h-12 rounded-champ bg-erreur"></span><span class="font-mono text-xs">erreur · #BA2530</span></x-panneau>
            </div>
        </section>

        {{-- Boutons --}}
        <section aria-labelledby="s-boutons" class="flex flex-col gap-6">
            <h2 id="s-boutons" class="font-mono text-xs uppercase tracking-wide text-texte-discret">{{ __('interface.styleguide.boutons') }}</h2>
            <div class="flex flex-wrap items-center gap-4">
                <x-bouton>{{ __('interface.styleguide.bouton_principal') }} →</x-bouton>
                <x-bouton variante="clair">{{ __('interface.styleguide.bouton_clair') }}</x-bouton>
                <x-bouton variante="filet">{{ __('interface.styleguide.bouton_filet') }}</x-bouton>
                <x-bouton variante="filet" disabled>{{ __('interface.styleguide.bouton_filet') }}</x-bouton>
            </div>
        </section>

        {{-- Champs --}}
        <section aria-labelledby="s-champs" class="flex flex-col gap-6">
            <h2 id="s-champs" class="font-mono text-xs uppercase tracking-wide text-texte-discret">{{ __('interface.styleguide.champs') }}</h2>
            <div class="grid gap-8 md:grid-cols-2">
                <x-champ id="champ-lien" type="url" mono :label="__('interface.styleguide.champ_lien')" placeholder="https://youtube.com/watch?v=…" :aide="__('interface.styleguide.champ_lien_aide')" />
                <x-champ id="champ-erreur" type="url" mono :label="__('interface.styleguide.champ_lien')" value="https://exemple.invalide" :erreur="__('interface.styleguide.champ_erreur')" />
            </div>
        </section>

        {{-- Panneaux --}}
        <section aria-labelledby="s-panneaux" class="flex flex-col gap-6">
            <h2 id="s-panneaux" class="font-mono text-xs uppercase tracking-wide text-texte-discret">{{ __('interface.styleguide.panneaux') }}</h2>
            <div class="grid gap-4 md:grid-cols-2">
                <x-panneau class="p-6"><p class="text-texte-secondaire">Panneau · bg-panneau</p></x-panneau>
                <x-panneau eleve class="p-6"><p class="text-texte-secondaire">Panneau élevé · bg-panneau-eleve</p></x-panneau>
            </div>
        </section>

        {{-- Badges de statut --}}
        <section aria-labelledby="s-statuts" class="flex flex-col gap-6">
            <h2 id="s-statuts" class="font-mono text-xs uppercase tracking-wide text-texte-discret">{{ __('interface.styleguide.statuts') }}</h2>
            <div class="flex flex-wrap gap-3">
                @foreach (\App\Enums\StatutTranscription::cases() as $statut)
                    <x-badge-statut :statut="$statut" />
                @endforeach
            </div>
        </section>

        {{-- Horodatages --}}
        <section aria-labelledby="s-horodatages" class="flex flex-col gap-6">
            <h2 id="s-horodatages" class="font-mono text-xs uppercase tracking-wide text-texte-discret">{{ __('interface.styleguide.horodatages') }}</h2>
            <div class="flex flex-wrap gap-6">
                <x-horodatage :ms="0" />
                <x-horodatage :ms="13000" />
                <x-horodatage :ms="754000" />
                <x-horodatage :ms="10805000" />
            </div>
        </section>

        {{-- Erreurs --}}
        <section aria-labelledby="s-erreurs" class="flex flex-col gap-6">
            <h2 id="s-erreurs" class="font-mono text-xs uppercase tracking-wide text-texte-discret">{{ __('interface.styleguide.erreurs') }}</h2>
            <x-message-erreur>{{ __('interface.styleguide.exemple_erreur') }}</x-message-erreur>
        </section>

        {{-- Quota : valeurs lues dans config/voxia.php --}}
        <section aria-labelledby="s-quota" class="flex flex-col gap-6">
            <h2 id="s-quota" class="font-mono text-xs uppercase tracking-wide text-texte-discret">{{ __('interface.styleguide.quota') }}</h2>
            <div class="grid gap-6 md:grid-cols-2">
                <x-compteur-quota :utilise="2" :limite="(int) config('voxia.plans.free.videos_per_month')" />
                <x-compteur-quota :utilise="7" />
            </div>
        </section>

        {{-- Onde audio --}}
        <section aria-labelledby="s-onde" class="flex flex-col gap-6">
            <h2 id="s-onde" class="font-mono text-xs uppercase tracking-wide text-texte-discret">{{ __('interface.styleguide.onde') }}</h2>
            <x-panneau class="p-6"><x-onde-audio /></x-panneau>
        </section>

        {{-- Fenêtre superposée --}}
        <section aria-labelledby="s-fenetre" class="flex flex-col gap-6">
            <h2 id="s-fenetre" class="font-mono text-xs uppercase tracking-wide text-texte-discret">{{ __('interface.styleguide.fenetre') }}</h2>
            <div>
                <x-bouton variante="filet" x-on:click="$dispatch('voxia-ouvrir', { id: 'fenetre-demo' })">{{ __('interface.styleguide.ouvrir_fenetre') }}</x-bouton>
            </div>
            <x-fenetre-superposee id="fenetre-demo" :titre="__('interface.styleguide.fenetre_titre')">
                <p>{{ __('interface.styleguide.fenetre_texte') }}</p>
            </x-fenetre-superposee>
        </section>
    </div>
</x-layouts::app>
