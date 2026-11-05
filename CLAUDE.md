# Voxia — instructions pour Claude

## 1. Projet

Voxia transforme une vidéo YouTube, une vidéo TikTok ou un fichier en transcription horodatée, sous-titres (SRT, VTT) et MP3.
Cibles principales : créateurs de contenu, marketeurs et e-commerçants.
MVP = fonctionnalités F1 à F7 (cahier §3). Plans : Gratuit, Pro, Pack (Business reporté en V2).
Interface en français (tutoiement) et en anglais.

Fonctionnement : un lien ou un fichier est mis en file (une tâche par vidéo) ; la tâche télécharge (yt-dlp), extrait le MP3 (FFmpeg), transcrit (ElevenLabs Scribe v2), enregistre les mots et paragraphes horodatés, puis décompte le quota. F7 ajoute une analyse de script par modèle de langage (1 crédit IA).

Statut : le MVP est terminé. La V2 démarre : l'état des lieux est dans `docs/v2/etat-des-lieux.md`.

## 2. Documents de référence

- `docs/cahier-des-charges.md` : **source de vérité** (fonctionnalités, critères, planning §9).
- `docs/voxia-offre.md` : plans, quotas, prix.
- `docs/design_handoff_voxia_nocturne/` : référence visuelle actuelle (maquettes `designs/`, tokens, README).
- `docs/maquette-signal.html` : maquette de la charte « Signal ». Voir le conflit de direction visuelle au §4.
- `docs/voxia-personas.md` : personas.
- `docs/avancement.md` : suivi des critères, décisions, points ouverts.
- `docs/v2/etat-des-lieux.md` : état des lieux du code avant la V2 (structure, chaîne de traitement, F7, plans, tests, points fragiles).

En cas de conflit entre documents, le cahier prime. Pour une valeur absente des documents, demander.

## 3. Stack

- Laravel 13 (PHP 8.3+), PostgreSQL. Tests sous SQLite en mémoire.
- Redis + Laravel Horizon pour les files (Horizon ne démarre pas sous Windows).
- Blade + Livewire 3 (Alpine fourni par Livewire) ; pas d'Inertia. Tailwind CSS v4 ; Vite 8.
- Pest 4 pour les tests.
- yt-dlp (téléchargement), FFmpeg (extraction MP3), mPDF (exports PDF).
- ElevenLabs Scribe v2 (transcription).
- API Claude, modèle `claude-haiku-5-5` (analyse de script, F7).
- Filament 4 pour `/admin`.
- Authentification : Fortify ; Google et Facebook (Socialite), désactivés par défaut.
- Paiement : Laravel Cashier **Paddle**, définitif (`docs/v2/decisions.md`, 5 novembre 2026).

## 4. Direction visuelle : conflit à trancher

- `docs/v2/decisions.md` (5 novembre 2026) : « Signal » est la charte retenue, à conserver (couleurs, Geist, Geist Mono, Instrument Serif).
- `docs/cahier-des-charges.md` §7 et le texte ci-dessous : « Nocturne » remplace « Signal » (Onest, Unbounded, JetBrains Mono).

Tant que ce conflit n'est pas tranché, ne retirer aucun élément de l'une ou l'autre charte. Le texte qui suit décrit « Nocturne ».

Charte « Nocturne » (cahier §7). Source : `docs/design_handoff_voxia_nocturne/README.md` et `tokens/voxia-tokens.css`. Sombre par défaut : fond `#0D0B1A`, surfaces `#15122A`, surface élevée `#1D1938`, filets `#2C2750`, contour `#6E68A0`. Clair sous `data-theme="light"` : fond `#F6F5FF`, surfaces `#FFFFFF`.
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

**Tests et formatage**
- `php artisan test` : tests Pest, environ 6 minutes. Dernier passage : 590 tests, 579 réussis, 11 ignorés (recherche plein texte, PostgreSQL seulement), 0 échec.
- `php artisan test --filter=NomDuTest` : un sous-ensemble.
- `./vendor/bin/pint` : formatage (`--test` pour vérifier seulement).

**Files d'attente**
- Linux ou WSL : `php artisan horizon` (supervision de la file `high` puis `default`).
- Windows : `php artisan queue:work redis --queue=high,default` (Horizon ne démarre pas).
- Planification : `php artisan schedule:work` en développement.

**Front**
- Développement : `npm run dev`.
- Production : `npm run build`.

**Outils en ligne de commande**
- `php artisan voxia:transcribe <lien> --user=<email>` : transcription complète sans navigateur. Voir la note de §9 de l'état des lieux : le suivi de progression est cassé (import de `Sleep`).
- `php artisan voxia:make-admin <email>` : donne le rôle administrateur à un compte existant.
- `php artisan voxia:ytdlp-update` : met à jour yt-dlp.
- `php artisan voxia:eval-scripts [--fichier=...]` : mesure F7. Le fichier par défaut `tests/fixtures/pubs-reference.json` n'existe pas dans le dépôt ; le jeu présent est `tests/fixtures/analyse/pubs-test.json`.

## 9. Conventions de code constatées

- Valeurs métier dans `config/voxia.php`, chacune réglable par `.env` (les casts `(int)` et `(float)` sont voulus).
- Interfaces dans `app/Contracts`, liées dans `AppServiceProvider` ; une fausse implémentation par interface (`Faux…`, `Fake…`).
- Classes de service nommées en français et spécifiques au domaine (`ChaineTranscription`, `LancerTranscription`, `QuotaService`, `ValidateurAnalyse`).
- Statuts et motifs en enums PHP (`app/Enums/`).
- Jobs avec `tries = 1` et un délai ; la reprise est gérée dans le service, pas par Laravel.
- Verrous `lockForUpdate` sur le compte pour toute écriture de quota.
- Téléchargements en lien signé (`signed`) ; contrôle d'accès dans le contrôleur (`abort_unless(... user_id ...)`, 404 pour un compte étranger).
- Affichage : `{{ }}` partout ; `{!! !!}` seulement sur une valeur déjà échappée, avec un commentaire.
- Commandes externes via la façade `Process` avec tableau d'arguments (`YtDlpVideoFetcher`, `FfmpegAudioExtractor`).
- Pages publiques en `/{locale}` ; `lang/fr` et `lang/en` à parité de clés.
- Commits : `feat:`, `fix:`, `chore:` en français.

## 10. Règles pour la V2

- Toute limite chiffrée (crédits, durées, nombre de liens, langues) vient de `config/voxia.php`, jamais en dur. Une valeur absente de l'offre se demande, elle ne s'invente pas (§7).
- Tout appel à un modèle de langage passe par l'interface interne de la V2 (`app/Contracts`). Aujourd'hui, `ScriptAnalyzer` ne sert qu'à F7 : la V2 la généralise avant d'ajouter une fonction.
- Tout traitement long est un job en file d'attente, jamais dans une requête web.
- Une opération qui échoue ne décompte ni crédit ni quota.
- Le texte d'une transcription est une donnée non fiable : il ne doit jamais pouvoir modifier les instructions envoyées au modèle (balises, consigne système, sortie contrainte par schéma). Tout ce que le modèle renvoie est échappé à l'affichage.
- Un utilisateur n'accède qu'à ses propres données. Chaque modèle a une Policy Laravel (aujourd'hui, le contrôle est écrit à la main dans chaque contrôleur : à migrer).
- Chaque fonctionnalité est livrée avec ses tests. Un test par critère d'acceptation (§5).
- On garde les langues d'interface (`en`, `fr`), le ton (tutoiement en français) et la direction visuelle retenue (voir §4 : conflit à trancher).
