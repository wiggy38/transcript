<?php

namespace App\Livewire;

use App\Contracts\VideoFetcher;
use App\Enums\MotifEchec;
use App\Enums\Plan;
use App\Exceptions\ErreurVideo;
use App\Services\Plans\PlanFeatures;
use App\Services\Televersement\Televersement;
use App\Services\Traitement\LancerTranscription;
use App\Services\Video\ApercuLien;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Livewire\Component;

/**
 * Écran « Nouvelle transcription » : un lien (aperçu avant lancement) ou un fichier envoyé
 * en morceaux par le navigateur (resources/js/televersement.js).
 *
 * Aucune attente web : l'aperçu est lu par la file (RecupererApercu) et l'écran l'interroge.
 * Les erreurs de lien (privé, supprimé, région, live, invalide) s'affichent avant tout
 * lancement, donc avant tout décompte.
 */
class NouvelleTranscription extends Component
{
    public string $lien = '';

    public string $langue = '';

    /** Identifiant de l'envoi du fichier, une fois assemblé côté serveur. */
    public ?string $envoi = null;

    public ?string $nomFichier = null;

    public ?string $nomAffiche = null;

    /** @var array{etat: string, donnees?: array<string, mixed>, motif?: string}|null */
    public ?array $apercu = null;

    /** Transcription déjà faite par cet utilisateur pour la même vidéo. */
    public ?int $transcriptionExistante = null;

    public ?string $erreurLien = null;

    public ?string $erreurFichier = null;

    /** Affiche l'aperçu du lien collé (bouton « Transcrire → »). */
    public function soumettreLien(): void
    {
        $this->erreurLien = null;
        $this->chargerApercu();
    }

    /** Collage ou saisie : aperçu immédiat, sans attendre le bouton. */
    public function updatedLien(): void
    {
        $this->erreurLien = null;
        $this->chargerApercu();
    }

    /** Interrogation (wire:poll) tant que l'aperçu n'est pas prêt. */
    public function verifierApercu(): void
    {
        if (($this->apercu['etat'] ?? null) !== ApercuLien::EN_COURS) {
            return;
        }

        $url = trim($this->lien);
        $etat = app(ApercuLien::class)->lire($url);

        if ($etat === null) {
            // Cache expiré avant la fin de la tâche : on relance la lecture.
            $etat = $this->demarrerApercu(app(ApercuLien::class), $url);
        }

        $this->apercu = $etat;
    }

    /** Lance la transcription du lien aperçu. Refusé tant que l'aperçu n'est pas prêt. */
    public function lancerLien(LancerTranscription $lancement): void
    {
        if ($this->redirigeSiNonVerifie()) {
            return;
        }

        $this->erreurLien = null;
        $this->validate();
        $this->validerLangue();

        $url = trim($this->lien);

        if (($this->apercu['etat'] ?? null) === ApercuLien::ERREUR) {
            $this->erreurLien = $this->messageMotif($this->apercu['motif'] ?? null);

            return;
        }

        if (($this->apercu['etat'] ?? null) !== ApercuLien::PRET) {
            $this->erreurLien = __('transcriptions.lien.apercu_pas_pret');

            return;
        }

        if ($this->depasseLeDroitDuPlan()) {
            $this->erreurLien = $this->messageMotif(MotifEchec::TropLongue->value);

            return;
        }

        try {
            $transcription = $lancement->lancer(Auth::user(), $url, $this->langueChoisie(), $this->dureeApercue() ?: null);
        } catch (ErreurVideo $erreur) {
            $this->erreurLien = $erreur->messageUtilisateur();

            return;
        } catch (InvalidArgumentException) {
            $this->erreurLien = $this->messageMotif(MotifEchec::LienInvalide->value);

            return;
        }

        $this->redirect(route('transcriptions.show', $transcription), navigate: false);
    }

    /**
     * Appelé par le navigateur quand le fichier est assemblé. Le chemin est recalculé côté
     * serveur à partir de l'identifiant, du nom et de l'utilisateur : rien de plus n'est lu.
     */
    public function fichierTermine(string $envoi, string $nom, string $nomAffiche): void
    {
        if (Televersement::estIdentifiantEnvoi($envoi)
            && app(Televersement::class)->cheminFichier(Auth::user(), $envoi, $nom) !== null) {
            $this->envoi = $envoi;
            $this->nomFichier = $nom;
            $this->nomAffiche = mb_substr(trim($nomAffiche), 0, 200);
            $this->erreurFichier = null;

            return;
        }

        $this->erreurFichier = __('transcriptions.televersement.echec');
    }

    public function retirerFichier(): void
    {
        $this->envoi = null;
        $this->nomFichier = null;
        $this->nomAffiche = null;
        $this->erreurFichier = null;
    }

    /** Lance la transcription du fichier reçu. */
    public function lancerFichier(LancerTranscription $lancement): void
    {
        if ($this->redirigeSiNonVerifie()) {
            return;
        }

        $this->erreurFichier = null;
        $this->validerLangue();

        $chemin = $this->envoi !== null && $this->nomFichier !== null
            ? app(Televersement::class)->cheminFichier(Auth::user(), $this->envoi, $this->nomFichier)
            : null;

        if ($chemin === null) {
            $this->erreurFichier = __('transcriptions.televersement.echec');

            return;
        }

        try {
            $transcription = $lancement->lancer(Auth::user(), $chemin, $this->langueChoisie());
        } catch (ErreurVideo $erreur) {
            $this->erreurFichier = $erreur->messageUtilisateur();

            return;
        } catch (InvalidArgumentException) {
            $this->erreurFichier = __('transcriptions.televersement.echec');

            return;
        }

        $this->redirect(route('transcriptions.show', $transcription), navigate: false);
    }

    public function render()
    {
        $plan = $this->planDeCompte()->value;
        $etat = $this->apercu['etat'] ?? null;
        $donnees = $this->apercu['donnees'] ?? [];

        return view('livewire.nouvelle-transcription', [
            'langues' => (array) config('voxia.langues_forcables'),
            'etat' => $etat,
            'apercuEnCours' => $etat === ApercuLien::EN_COURS,
            'apercuPret' => $etat === ApercuLien::PRET,
            'donnees' => $donnees,
            'trop_longue' => $etat === ApercuLien::PRET && $this->depasseLeDroitDuPlan(),
            'apercu_limite' => $etat === ApercuLien::PRET && $this->limiteAuPlan(),
            'minutes_plan' => intdiv($this->dureeMaxDuPlan(), 60),
            'tailleMaxMo' => (int) config('voxia.uploads.max_size_mb'),
            'extensions' => (array) config('voxia.uploads.extensions'),
            'morceauOctets' => (int) config('voxia.uploads.morceau_octets'),
            'tailleMaxOctets' => Televersement::tailleMaxOctets(),
            'plan' => $plan,
        ]);
    }

    private function chargerApercu(): void
    {
        $url = trim($this->lien);

        $this->apercu = null;
        $this->transcriptionExistante = null;

        if ($url === '') {
            return;
        }

        try {
            $lien = app(VideoFetcher::class)->reconnaitre($url);
        } catch (ErreurVideo) {
            $this->apercu = ['etat' => ApercuLien::ERREUR, 'motif' => MotifEchec::LienInvalide->value];

            return;
        }

        $apercus = app(ApercuLien::class);
        $etat = $apercus->lire($url);

        if ($etat === null) {
            $etat = $this->demarrerApercu($apercus, $url);
        }

        $this->apercu = $etat;

        if ($lien->idVideo !== null) {
            $this->transcriptionExistante = Auth::user()->transcriptions()
                ->where('source', $lien->source)
                ->where('id_video', $lien->idVideo)
                ->value('id');
        }
    }

    /**
     * Un lancement exige un email vérifié (cahier §5). L'écran reste visible sans lui :
     * seul le lancement est refusé, vers la page de vérification.
     */
    private function redirigeSiNonVerifie(): bool
    {
        if (Auth::user()->hasVerifiedEmail()) {
            return false;
        }

        $this->redirect(route('verification.notice'), navigate: false);

        return true;
    }

    /**
     * Confie la lecture à la file. Avec une file synchrone, l'aperçu peut être prêt tout de
     * suite : on relit donc le cache au lieu de supposer « en cours ».
     *
     * @return array{etat: string, donnees?: array<string, mixed>, motif?: string}
     */
    private function demarrerApercu(ApercuLien $apercus, string $url): array
    {
        $apercus->demarrer($url);

        return $apercus->lire($url) ?? ['etat' => ApercuLien::EN_COURS];
    }

    /**
     * Vrai si la vidéo aperçue dépasse la durée max d'un plan payant : refus.
     * Le Gratuit n'est jamais refusé pour la durée : il reçoit l'aperçu (voir limiteAuPlan).
     */
    private function depasseLeDroitDuPlan(): bool
    {
        if ($this->planDeCompte() === Plan::Free) {
            return false;
        }

        return $this->dureeApercue() > $this->dureeMaxDuPlan();
    }

    /** Vrai pour le Gratuit si la vidéo dépasse 10 minutes : seules les premières seront transcrites. */
    private function limiteAuPlan(): bool
    {
        return $this->planDeCompte() === Plan::Free && $this->dureeApercue() > $this->dureeMaxDuPlan();
    }

    private function dureeApercue(): int
    {
        return (int) ($this->apercu['donnees']['duree_secondes'] ?? 0);
    }

    private function dureeMaxDuPlan(): int
    {
        return app(PlanFeatures::class)->dureeMaxSecondes($this->planDeCompte());
    }

    private function planDeCompte(): Plan
    {
        return app(PlanFeatures::class)->planDe(Auth::user());
    }

    private function messageMotif(?string $motif): string
    {
        return __('videos.motifs.'.($motif ?? MotifEchec::ErreurTechnique->value));
    }

    private function langueChoisie(): ?string
    {
        return $this->langue !== '' ? $this->langue : null;
    }

    private function validerLangue(): void
    {
        $this->validate([
            'langue' => ['nullable', Rule::in(['', ...(array) config('voxia.langues_forcables')])],
        ], [
            'langue.in' => __('transcriptions.langue.invalide'),
        ]);
    }

    protected function rules(): array
    {
        return [
            'lien' => ['required', 'string', 'max:2048'],
        ];
    }

    protected function messages(): array
    {
        return [
            'lien.required' => __('comptes.lien.vide'),
            'lien.max' => __('comptes.lien.invalide'),
        ];
    }
}
