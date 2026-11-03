# Voxia — Personas et fonctionnalités prioritaires

29 octobre 2026

## Positionnement

Voxia est l'outil des créateurs et marketeurs pour transformer n'importe quelle vidéo en texte exploitable. La cible est internationale. Les créateurs et marketeurs sont la cible principale ; les autres profils viennent en soutien, via l'offre gratuite ou des fonctions avancées plus tard.

| Persona | Priorité | Rôle commercial |
| --- | --- | --- |
| 1. Créateur de contenu | Principale | Abonnement, usage fréquent |
| 2. Marketeur / e-commerçant | Principale | Abonnement, forte disposition à payer |
| 3. Étudiant / autodidacte | Secondaire | Offre gratuite, volume et bouche-à-oreille |
| 4. Journaliste / chercheur | Plus tard | Niche exigeante, offre pro |
| 5. Traduction et accessibilité | Plus tard | Niche exigeante, offre pro |
| 6. Apprenant en langues | Secondaire | Offre gratuite, volume |

## 1. Le créateur de contenu

YouTubeur, coach ou formateur en ligne qui publie des vidéos chaque semaine et veut en tirer plusieurs contenus sans tout réécrire.

**Frustrations**

- Réécrire à la main un article ou une newsletter à partir d'une vidéo prend des heures
- Les sous-titres automatiques de YouTube sont imprécis et pénibles à exporter
- Les outils de transcription existants sont chers ou facturés à la minute
- Il jongle entre plusieurs outils : téléchargement, transcription, rédaction

**Fonctionnalités prioritaires**

1. Transcription fidèle avec ponctuation propre et paragraphes
2. Export des sous-titres SRT et VTT
3. Conversion de la transcription en article, newsletter ou post social
4. Extraction de l'audio en MP3 pour une version podcast
5. Historique de ses transcriptions, classées par chaîne ou par projet

## 2. Le marketeur ou e-commerçant

Social media manager, media buyer ou dropshipper qui regarde des dizaines de publicités TikTok et de vidéos YouTube chaque semaine pour trouver ce qui fonctionne.

**Frustrations**

- Il doit regarder une vidéo entière pour récupérer une accroche ou un argument
- Recopier le script d'une pub concurrente à la main est lent et source d'erreurs
- Ses analyses sont éparpillées entre notes, captures d'écran et onglets ouverts
- Une pub intéressante peut disparaître avant qu'il ait pu l'analyser

**Fonctionnalités prioritaires**

1. Transcription rapide à partir d'un lien TikTok ou YouTube
2. Mise en évidence de la structure d'un script : accroche, problème, preuve, appel à l'action
3. Bibliothèque de scripts classés par niche, produit ou concurrent
4. Traitement de plusieurs liens à la fois
5. Résumé et points clés d'une longue vidéo (webinaire, live, vidéo d'expert)

## 3. L'étudiant ou l'autodidacte

Il apprend en grande partie via YouTube : cours universitaires, conférences, tutoriels techniques. Il a peu de budget.

**Frustrations**

- Revenir en arrière dans une vidéo de 2 heures pour retrouver une explication
- Prendre des notes en même temps qu'il écoute lui fait perdre le fil
- Il ne peut pas toujours écouter (transports, bibliothèque, pas de casque)
- Les outils payants dépassent son budget

**Fonctionnalités prioritaires**

1. Transcription horodatée, avec clic pour revenir au passage dans la vidéo
2. Recherche dans le texte
3. Résumé et fiche de révision générés à partir de la transcription
4. Export en PDF ou en texte
5. Offre gratuite limitée en nombre de vidéos ou en durée

## 4. Le journaliste, chercheur ou analyste

Il travaille sur des interviews, des déclarations publiques, des conférences et des débats. Une citation inexacte peut lui coûter sa crédibilité.

**Frustrations**

- Vérifier une citation mot pour mot prend du temps
- Il ne sait pas toujours qui parle dans une vidéo à plusieurs intervenants
- Les vidéos peuvent être supprimées avant qu'il ait archivé leur contenu
- Analyser un corpus de dizaines de vidéos à la main est impossible

**Fonctionnalités prioritaires**

1. Transcription très précise avec horodatage de chaque phrase
2. Identification des intervenants (qui parle quand)
3. Lien de la source et date de la transcription conservés pour l'archivage
4. Recherche dans l'ensemble de ses transcriptions
5. Export en DOCX avec horodatages

## 5. Le professionnel de la traduction et de l'accessibilité

Sous-titreur, traducteur ou responsable accessibilité d'une organisation qui doit rendre des vidéos lisibles par tous, y compris les personnes sourdes ou malentendantes.

**Frustrations**

- Taper une transcription de zéro avant de pouvoir traduire
- Recaler à la main des sous-titres mal synchronisés
- Gérer plusieurs langues pour une même vidéo
- Livrer dans les formats exigés par les clients et les plateformes

**Fonctionnalités prioritaires**

1. Éditeur de transcription synchronisé avec l'audio pour corriger rapidement
2. Export SRT et VTT avec un découpage propre des lignes
3. Traduction de la transcription dans plusieurs langues
4. Détection automatique de la langue parlée
5. Traitement par lots de plusieurs fichiers

## 6. L'apprenant en langues

Il regarde des vidéos en anglais, espagnol ou autre pour progresser, et veut comprendre chaque mot.

**Frustrations**

- Il ne saisit pas tout à l'oral, surtout quand on parle vite ou avec un accent
- Les sous-titres intégrés sont absents ou approximatifs
- Il veut réécouter hors ligne, dans les transports
- Noter le vocabulaire nouveau en cours de visionnage est fastidieux

**Fonctionnalités prioritaires**

1. Texte affiché en synchronisation avec l'audio
2. Téléchargement de l'audio pour l'écoute hors ligne
3. Traduction côte à côte avec la transcription originale
4. Sélection de mots pour créer une liste de vocabulaire
5. Offre gratuite limitée

## Synthèse : quoi construire en premier

Le MVP couvre les besoins communs aux deux personas principaux ; le reste suit en V2 ou plus tard selon la demande.

| Fonctionnalité | Personas concernés | Version |
| --- | --- | --- |
| Transcription depuis un lien YouTube / TikTok ou un fichier | Tous | MVP |
| Transcription horodatée avec ponctuation propre | 1, 2, 3, 4 | MVP |
| Extraction de l'audio en MP3 | 1, 6 | MVP |
| Export TXT, PDF, SRT, VTT | 1, 3, 5 | MVP |
| Historique et recherche dans ses transcriptions | 1, 2, 3, 4 | MVP |
| Offre gratuite limitée | 3, 6 | MVP |
| Résumé et points clés | 2, 3 | V2 |
| Réécriture en article, newsletter ou post | 1 | V2 |
| Analyse de la structure d'un script publicitaire | 2 | V2 |
| Bibliothèque classée par niche ou projet | 1, 2 | V2 |
| Traitement de plusieurs liens à la fois | 2, 5 | V2 |
| Traduction de la transcription | 5, 6 | V2 |
| Identification des intervenants | 4 | Plus tard |
| Éditeur synchronisé avec l'audio | 5, 6 | Plus tard |
| Liste de vocabulaire | 6 | Plus tard |
