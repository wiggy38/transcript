# Voxia — instructions pour Claude

## 1. Projet

Voxia transforme une vidéo YouTube, une vidéo TikTok ou un fichier en transcription horodatée, sous-titres (SRT, VTT) et MP3.
Cibles principales : créateurs de contenu, marketeurs et e-commerçants.
MVP = fonctionnalités F1 à F7 (cahier §3). Plans : Gratuit, Pro, Pack (Business reporté en V2).
Interface en français (tutoiement) et en anglais.

## 2. Documents de référence

- `docs/cahier-des-charges.md` : **source de vérité** (fonctionnalités, critères, planning §9).
- `docs/voxia-offre.md` : plans, quotas, prix.
- `docs/maquette-signal.html` : référence visuelle (page d'accueil, puis écrans de l'app).
- `docs/voxia-personas.md` : personas.
- `docs/avancement.md` : suivi des critères, décisions, points ouverts.

En cas de conflit entre documents, le cahier prime. Pour une valeur absente des documents, demander.

## 3. Stack

- Laravel (PHP), PostgreSQL.
- Redis + Laravel Horizon pour les files.
- Livewire 3 + Alpine.js ; Tailwind CSS v4 ; Vite.
- Pest pour les tests.
- yt-dlp (téléchargement), FFmpeg (extraction MP3).
- ElevenLabs Scribe v2 (transcription).
- API Claude (analyse de script, F7).
- Filament pour `/admin`.
- Paiement : Stripe ou Paddle, à décider (cahier §8).

## 4. Charte « Surligneur » (cahier §7, remplace Signal)

- Clair par défaut : fond `#F4F1EA`, surfaces `#FFFDF8`, surface élevée `#EAE6DB`, filets `#DDD7CA`, contour `#817A6B`. Sombre : fond `#13120F`, surfaces `#1B1A16`.
- Texte `#1A1814` ; secondaire `#4B463D` ; discret `#655F53` (sombre : `#F2EEE4`, `#C4BEB0`, `#9C9686`).
- Accent d'action `#E4521F` (sombre `#FF7A4D`), texte encre sur bouton. Surligneur `#DDF37A` (sombre `#3A4419`), mot d'accent `#D7F46A`.
- Tokens dans `resources/css/voxia-tokens.css`, mappés dans `app.css` (`@theme inline`) ; les classes `bg-fond`, `text-texte`, `border-filet`, `rounded-bouton` restent valides.
- Typographies : Bricolage Grotesque 700 (titres, mots d'accent surlignés) ; Geist (texte) ; Geist Mono (horodatages, données, casse normale).
- Rayons : pilule boutons et badges, 12 px champs, 20 px panneaux.
- Animations : onde audio, progression, apparition mot à mot ; toutes coupées sous `prefers-reduced-motion`.

## 5. Règles de code

- Toute valeur métier (quotas, durées, prix, tailles, limites) vient de `config/voxia.php`, jamais en dur.
- Chaque service externe (téléchargement, transcription, analyse de script, paiement) est appelé derrière une interface dans `app/Contracts`, avec une fausse implémentation pour les tests. Aucun test n'appelle une API réelle.
- Aucune requête web n'attend un traitement : tout passe par la file.
- Un échec technique ne décompte jamais de quota.
- Commandes externes via le composant `Process` de Laravel, arguments échappés. Jamais `shell_exec`.
- Textes visibles dans `lang/fr` et `lang/en`. Tutoiement en français.
- Accessibilité : vrais boutons et labels, contraste 4,5:1, animations coupées sous `prefers-reduced-motion`.
- Un test Pest par critère d'acceptation.
- Avant chaque commit : `php artisan test` et `./vendor/bin/pint`.
- Commits en français, préfixes `feat:`, `fix:` ou `chore:`.

## 6. Fin de tâche

1. Mettre à jour `docs/avancement.md` : fait, critères cochés, décisions, points ouverts.
2. Commit, avec `docs/avancement.md` inclus.

## 7. Interdits

- Committer `.env` ou une clé d'API.
- Désactiver ou supprimer un test pour le faire passer.
- Inventer une valeur d'offre (prix, quota) ou un texte juridique définitif (CGU, confidentialité) : demander.

## 8. Commandes utiles

- `php artisan test` : tests Pest.
- `./vendor/bin/pint` : formatage (`--test` pour vérifier seulement).
- `php artisan horizon` : worker de la file.
