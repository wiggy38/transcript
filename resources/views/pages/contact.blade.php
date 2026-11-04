{{-- Formulaire de contact : protégé par un champ piège (« site_web », caché), un délai minimal (jeton horodaté) et une limite par adresse IP. --}}
@php
    $remplacements = \App\Support\PagesLegales::remplacements();
    $sujets = ['question', 'bug', 'facturation', 'autre'];
@endphp

<x-layouts::marketing :titre="__('pages.contact.titre').' — '.__('interface.marque')" :description="__('pages.contact.intro')">
    <section class="max-w-[640px] pt-20 pb-16">
        <h1 class="text-[clamp(40px,5vw,64px)] leading-none font-semibold tracking-[-0.04em]">{{ __('pages.contact.titre') }}</h1>
        <p class="mt-6 text-lg text-texte-secondaire">{{ __('pages.contact.intro') }}</p>

        @if (session('statut') === 'envoye')
            <p role="status" class="mt-8 border-l-2 border-accent py-1 pl-3 text-texte">{{ __('pages.contact.envoye') }}</p>
        @endif

        @if ($formulaire)
            @error('formulaire')
                <p role="alert" class="mt-8 border-l-2 border-accent py-1 pl-3 text-texte">{{ $message }}</p>
            @enderror

            <form method="post" action="{{ route('contact.envoyer') }}" class="mt-10 grid gap-6" novalidate>
                @csrf

                <x-champ id="contact-nom" name="nom" autocomplete="name" :label="__('pages.contact.champs.nom')" :value="old('nom')"
                    required :erreur="$errors->first('nom')" />

                <x-champ id="contact-email" name="email" type="email" autocomplete="email" :label="__('pages.contact.champs.email')" :value="old('email')"
                    required :erreur="$errors->first('email')" />

                <div class="flex flex-col gap-2">
                    <label for="contact-sujet" class="font-mono text-xs uppercase tracking-wide text-texte-discret">{{ __('pages.contact.champs.sujet') }}</label>
                    <select id="contact-sujet" name="sujet" required class="min-h-11 w-full rounded-champ border border-filet-contour bg-panneau px-3.5 text-[15px] text-texte">
                        @foreach ($sujets as $sujet)
                            <option value="{{ $sujet }}" @selected(old('sujet') === $sujet)>{{ __('pages.contact.sujets.'.$sujet) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-col gap-2">
                    <label for="contact-message" class="font-mono text-xs uppercase tracking-wide text-texte-discret">{{ __('pages.contact.champs.message') }}</label>
                    <textarea id="contact-message" name="message" rows="6" required minlength="10" maxlength="5000"
                        aria-describedby="contact-message-aide{{ $errors->has('message') ? ' contact-message-erreur' : '' }}"
                        @if ($errors->has('message')) aria-invalid="true" @endif
                        class="w-full min-w-0 rounded-champ border border-filet-contour bg-panneau px-3.5 py-3 text-[15px] text-texte outline-none focus-visible:border-accent">{{ old('message') }}</textarea>
                    <p id="contact-message-aide" class="text-sm text-texte-discret">{{ __('pages.contact.champs.message_aide') }}</p>
                    @error('message')
                        <x-message-erreur id="contact-message-erreur">{{ $message }}</x-message-erreur>
                    @enderror
                </div>

                {{-- Champ piège : invisible pour les visiteurs, rempli par les robots. --}}
                <div class="sr-only" aria-hidden="true">
                    <label for="contact-site-web">{{ __('pages.contact.miel') }}</label>
                    <input id="contact-site-web" name="site_web" type="text" tabindex="-1" autocomplete="off" value="">
                </div>

                <input type="hidden" name="ouvert" value="{{ $jeton }}">

                <p class="text-sm text-texte-discret">{{ __('pages.contact.confidentialite') }}</p>

                <div>
                    <x-bouton type="submit">{{ __('pages.contact.bouton') }}</x-bouton>
                </div>
            </form>
        @else
            <p class="mt-10 text-texte-secondaire">{{ __('pages.contact.indisponible', ['email' => $remplacements['email']]) }}</p>
        @endif
    </section>
</x-layouts::marketing>
