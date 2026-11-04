{{-- Confirmation avant suppression (cahier F5). Rien n'est supprimé tant que le bouton n'est pas activé. --}}
<x-layouts::app :titre="__('historique.confirmation.titre')" actif="historique">
    <section class="max-w-[640px]">
        <p class="mb-6 text-sm">
            <a href="{{ route('historique') }}" class="text-texte-secondaire no-underline hover:text-accent-texte">← {{ __('historique.confirmation.retour') }}</a>
        </p>

        <x-panneau class="p-6">
            <h1 class="text-2xl font-semibold tracking-tight">{{ __('historique.confirmation.titre') }}</h1>
            <p class="mt-3 text-texte-secondaire [overflow-wrap:anywhere]">
                {{ __('historique.confirmation.texte', ['titre' => $transcription->titre ?? __('historique.sans_titre')]) }}
            </p>

            <form method="post" action="{{ route('transcriptions.destroy', $transcription) }}" class="mt-6 flex flex-wrap gap-3">
                @csrf
                @method('DELETE')

                <button type="submit"
                        class="inline-flex min-h-11 cursor-pointer items-center justify-center rounded-bouton border border-erreur px-5 text-[15px] font-semibold text-erreur transition-colors duration-150 hover:bg-erreur hover:text-sur-accent">
                    {{ __('historique.confirmation.bouton') }}
                </button>

                <x-bouton :href="route('historique')" variante="filet">{{ __('historique.confirmation.annuler') }}</x-bouton>
            </form>
        </x-panneau>
    </section>
</x-layouts::app>
