# Test manuel : résumé sur la fixture de 28 minutes

Objectif : vérifier avec le vrai modèle (Claude Haiku 5.5, clé d'API réelle) la qualité du résumé, des points
clés et des chapitres sur une transcription longue, en français. Les tests automatiques (`tests/Feature/Resume`)
utilisent un faux modèle ; ce test-ci ne peut pas être automatisé dans la suite, car il appelle l'API.

**Fixture :** `tests/fixtures/transcription-fr-28min.txt`. Elle est **synthétique** (fictive, rédigée pour le
test) : 84 paragraphes, un toutes les 20 secondes, format `[mm:ss] texte`, durée 28 min 00. Elle ne contient
aucun chiffre en toutes lettres ; les nombres y sont écrits en mots, ce qui évite de tester le contrôle des
chiffres par accident.

## Prérequis

- `ANTHROPIC_API_KEY` renseignée dans `.env` (jamais committée).
- Une file de traitement active : `php artisan queue:work redis --queue=high,default` (Windows), ou tâche
  exécutée en synchrone comme ci-dessous.
- Coût attendu : un crédit IA (28 min = une heure entamée) et quelques centimes de jetons au plus.

## Procédure

Lancer `php artisan tinker`, puis coller le script suivant. Il crée un compte Pro de test, une transcription
terminée avec les 84 paragraphes de la fixture, puis lance le résumé dans le processus courant.

```php
use App\Enums\Plan;
use App\Enums\StatutTranscription;
use App\Jobs\GenererResume;
use App\Models\Transcription;
use App\Models\TranscriptionParagraph;
use App\Models\User;
use App\Services\Resume\LancerResume;

$utilisateur = User::factory()->create(['email' => 'test-resume-'.uniqid().'@example.test']);
$utilisateur->forceFill(['plan' => Plan::Pro])->save();

$transcription = Transcription::factory()->termine()->for($utilisateur)->create([
    'titre' => 'Fixture 28 min (synthétique)',
    'duree_secondes' => 1680,
    'langue_detectee' => 'fr',
]);

$lignes = file(base_path('tests/fixtures/transcription-fr-28min.txt'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($lignes as $index => $ligne) {
    preg_match('/^\[(\d{2}):(\d{2})\] (.*)$/u', $ligne, $m);
    TranscriptionParagraph::create([
        'transcription_id' => $transcription->getKey(),
        'position' => $index + 1,
        'debut_ms' => ((int) $m[1] * 60 + (int) $m[2]) * 1000,
        'fin_ms' => ((int) $m[1] * 60 + (int) $m[2]) * 1000 + 19000,
        'texte' => $m[3],
        'config_recherche' => 'simple',
    ]);
}

$sortie = app(LancerResume::class)->lancer($utilisateur, $transcription, 'fr');
(new GenererResume($sortie->getKey()))->handle(app(\App\Services\Resume\ServiceResume::class));

$sortie->refresh();
dump($sortie->statut->value, $sortie->cout_estime_usd, $sortie->contenu);
```

Le décompte se vérifie ensuite dans `usage_records` : une ligne `credit_ia` de quantité 1 pour ce compte si la
sortie est terminée, et aucune ligne si elle est en échec.

## Ce qu'il faut vérifier à la main

1. **Statut** : `termine`. Si `echec`, lire `appels_ia` (colonne `statut`) et les journaux : le motif est écrit.
2. **Résumé** : 3 à 5 phrases, en français, qui ne parle que de ce qui figure dans la fixture (balcon, bacs,
   terreau, arrosage, maladies, récolte, questions de la salle).
3. **Points clés** : entre 5 et 10. Pour chaque point, relire le passage cité : l'horodatage `debut_ms` doit
   correspondre au paragraphe qui justifie le point, à 20 secondes près (un paragraphe).
4. **Chapitres** : présents (vidéo de plus de 20 minutes), 2 phrases chacun, dans l'ordre de la vidéo. Le
   premier commence à 0, les suivants à peu près à 10 min et 20 min.
5. **Fidélité** : aucun fait, nom, conseil ou chiffre absent de la fixture. Le contrôle serveur couvre les
   chiffres seulement ; les affirmations sont à relire à la main.
6. **Langue** : relancer avec `'en'` à la place de `'fr'` et vérifier que la sortie est en anglais (noms propres
   conservés).

## Points qui peuvent échouer, et ce qu'ils disent

| Symptôme | Cause probable | Action |
| --- | --- | --- |
| `echec`, motif `chiffre_absent` dans les journaux | Le modèle a écrit un chiffre absent du texte (par exemple « 3 » pour « trois ») | Vérifier la sortie. Si la règle est trop stricte, l'assouplir dans `ValidateurResume::verifierNombres`, et le noter dans `avancement.md`. |
| `echec`, motif `points` | Trop de points cités à des paragraphes inexistants | Relire les numéros de paragraphe dans la sortie brute. |
| `echec`, motif `chapitres` | Aucun chapitre renvoyé pour une vidéo longue | Vérifier le prompt de synthèse. |
| Horodatage décalé de plusieurs paragraphes | Le modèle a cité le mauvais numéro | Noter le cas dans `avancement.md` : c'est la limite de l'option B (numéro de paragraphe). |
