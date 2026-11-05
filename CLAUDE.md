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

## 4. Charte « Nocturne » (cahier §7)

Source : `docs/design_handoff_voxia_nocturne/README.md` et `tokens/voxia-tokens.css`. Sombre par défaut : fond `#0D0B1A`, surfaces `#15122A`, surface élevée `#1D1938`, filets `#2C2750`, contour `#6E68A0`. Clair sous `data-theme="light"` : fond `#F6F5FF`, surfaces `#FFFFFF`.
- Texte sombre `#EEEBFF` ; secondaire `#C0BAE6` ; discret `#9690C4`. Texte clair `#14112B` ; secondaire `#423D66` ; discret `#5C5785`.
- Accent d'action (vert citron) `#C8F25A` en sombre, `#4A6A00` en clair, texte encre sur bouton. Cyan « en direct » et focus `#4FD1E0` en sombre.
- Typographies (fontsource) : Onest (texte), Unbounded 600 (titres seulement), JetBrains Mono (horodatages, données). Rayons : boutons et champs 12 px, panneaux 20 px, badges en pilule.
- Tokens dans `resources/css/voxia-tokens.css`, mappés dans `app.css` (`@theme inline`) ; les classes `bg-fond`, `text-texte`, `border-filet`, `rounded-bouton` restent valides.
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
