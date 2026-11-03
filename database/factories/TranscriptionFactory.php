<?php

namespace Database\Factories;

use App\Enums\MotifEchec;
use App\Enums\SourceTranscription;
use App\Enums\StatutTranscription;
use App\Models\Transcription;
use App\Models\TranscriptionParagraph;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transcription>
 */
class TranscriptionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $idVideo = fake()->regexify('[A-Za-z0-9_-]{11}');

        return [
            'user_id' => User::factory(),
            'source' => SourceTranscription::Youtube,
            'url_origine' => 'https://www.youtube.com/watch?v='.$idVideo,
            'id_video' => $idVideo,
            'titre' => fake()->sentence(4),
            'chaine' => fake()->company(),
            'miniature_url' => 'https://i.ytimg.com/vi/'.$idVideo.'/hqdefault.jpg',
            'duree_secondes' => 600,
            'langue_forcee' => null,
            'langue_detectee' => null,
            'statut' => StatutTranscription::EnAttente,
            'motif_echec' => null,
            'est_apercu' => false,
            'mp3_chemin' => null,
            'mp3_duree_secondes' => null,
            'nombre_mots' => 0,
            'cout_estime_usd' => 0,
        ];
    }

    public function tiktok(): static
    {
        return $this->state(fn () => [
            'source' => SourceTranscription::Tiktok,
            'url_origine' => 'https://www.tiktok.com/@marque/video/'.fake()->numerify('###################'),
            'id_video' => fake()->numerify('###################'),
            'miniature_url' => null,
        ]);
    }

    /** Fichier importé : pas d'URL, pas d'identifiant de vidéo. */
    public function fichier(): static
    {
        return $this->state(fn () => [
            'source' => SourceTranscription::Fichier,
            'url_origine' => null,
            'id_video' => null,
            'miniature_url' => null,
        ]);
    }

    public function termine(): static
    {
        return $this->state(fn () => [
            'statut' => StatutTranscription::Termine,
            'langue_detectee' => 'fr',
            'nombre_mots' => 1500,
            'mp3_chemin' => 'mp3/'.fake()->uuid().'.mp3',
            'mp3_duree_secondes' => 600,
            'cout_estime_usd' => 0.0367,
            'date_termine' => now(),
        ]);
    }

    public function echec(MotifEchec $motif = MotifEchec::VideoPrivee): static
    {
        return $this->state(fn () => [
            'statut' => StatutTranscription::Echec,
            'motif_echec' => $motif,
            'date_echec' => now(),
        ]);
    }

    /**
     * Transcription terminée de la durée donnée, avec ses paragraphes horodatés
     * (3 heures par défaut). Les paragraphes se suivent sans trou, comme le regroupement réel.
     */
    public function longue(int $dureeSecondes = 3 * 3600): static
    {
        return $this->termine()
            ->state(fn () => [
                'duree_secondes' => $dureeSecondes,
                'mp3_duree_secondes' => $dureeSecondes,
            ])
            ->afterCreating(function (Transcription $transcription) use ($dureeSecondes) {
                $lignes = [];
                $debut = 0;
                $fin = $dureeSecondes * 1000;
                $position = 1;
                $mots = 0;

                while ($debut < $fin) {
                    $texte = fake()->sentences(fake()->numberBetween(2, 5), true);
                    $finParagraphe = min($fin, $debut + fake()->numberBetween(8_000, 22_000));

                    $lignes[] = [
                        'transcription_id' => $transcription->getKey(),
                        'position' => $position++,
                        'debut_ms' => $debut,
                        'fin_ms' => $finParagraphe,
                        'texte' => $texte,
                    ];
                    $mots += str_word_count($texte);
                    $debut = $finParagraphe;
                }

                foreach (array_chunk($lignes, 500) as $morceau) {
                    TranscriptionParagraph::insert($morceau);
                }

                $transcription->update(['nombre_mots' => $mots]);
            });
    }

    /** Aperçu gratuit : seules les 10 premières minutes sont transcrites. */
    public function apercu(): static
    {
        return $this->state(fn () => [
            'est_apercu' => true,
        ]);
    }
}
