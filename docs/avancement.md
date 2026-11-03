# Avancement Voxia

Source : `docs/cahier-des-charges.md` (3 novembre 2026).
Mise à jour à la fin de chaque tâche (voir `CLAUDE.md` §6).

## 1. Planning (cahier §9)

- [ ] Étape 1 — Chaîne de traitement : lien, téléchargement, MP3, transcription, en ligne de commande (2 à 3 semaines)
- [ ] Étape 2 — Comptes, historique et affichage de la transcription (F2, F5) (2 semaines)
- [ ] Étape 3 — Exports (F4), lecteur synchronisé et analyse de script (F7) (2 à 3 semaines)
- [ ] Étape 4 — Plans, quotas, paiement et back-office (F6) (2 semaines)
- [ ] Étape 5 — Page d'accueil, page des tarifs, CGU, puis bêta privée avec 20 à 50 créateurs et marketeurs (2 semaines)

## 2. Critères d'acceptation

### F1 — Transcription depuis un lien ou un fichier

- [ ] Un lien public valide de 10 minutes donne une transcription sans autre action que le lancement.
- [ ] Une vidéo privée, supprimée, bloquée dans la région ou un live en cours affiche un message clair et ne décompte rien.
- [ ] Un échec technique ne décompte rien du quota.
- [ ] Un lien déjà transcrit par le même utilisateur renvoie vers la transcription existante.

### F2 — Transcription horodatée avec ponctuation propre

- [ ] Le clic sur un horodatage positionne la lecture à 1 seconde près.
- [ ] Une transcription de 3 heures s'affiche et reste fluide au défilement.

### F3 — Extraction de l'audio en MP3

- [ ] Le MP3 téléchargé a la même durée que la vidéo source, à 1 seconde près.
- [ ] Le nom du fichier ne contient que des caractères valides sous Windows, macOS et Linux.

### F4 — Export TXT, PDF, SRT, VTT

- [ ] Les fichiers SRT et VTT s'importent sans erreur dans YouTube Studio et CapCut.
- [ ] Les accents et caractères non latins s'affichent correctement dans chaque format.

### F5 — Historique et recherche

- [ ] Une recherche sur 500 transcriptions répond en moins d'une seconde.
- [ ] Une transcription supprimée disparaît de la liste et de la recherche.

### F6 — Offre gratuite limitée

- [ ] Aucun critère d'acceptation défini dans le cahier. Voir point ouvert P0.

### F7 — Analyse de la structure d'un script publicitaire

- [ ] Sur 20 pubs découpées à la main, les limites des parties tombent à 2 secondes près dans 80 % des cas (cible à valider).
- [ ] Les parties ne se chevauchent pas et suivent l'ordre de la vidéo.
- [ ] L'analyse d'une pub de 60 secondes s'affiche en moins de 15 secondes.
- [ ] Un clic sur une partie lance la lecture à son début.

## 3. Décisions

- Laravel pour l'application web (validé, cahier §8).
- Transcription : ElevenLabs Scribe v2, environ 0,22 $ par heure d'audio (validé).
- Téléchargement derrière une interface, moteur de transcription derrière une interface (cahier §9).
- Devise : dollars US (cahier §7).
- Carte Marketeurs de la maquette gardée telle quelle (cahier §7).
- MP3 inclus dans tous les plans ; mention Pro reformulée « tous les exports, dont PDF et VTT » (cahier §7).
- Base de données : PostgreSQL (demande du projet ; le cahier indique « MySQL ou PostgreSQL »).
- File : Redis + Laravel Horizon, une tâche par vidéo (cahier §8).

## 4. Points ouverts

- [ ] P0 — Critères d'acceptation de F6 (offre gratuite) : à rédiger, le cahier n'en fournit pas.
- [ ] P1 — L'extraction MP3 seule, sans transcription, décompte-t-elle une vidéo en gratuit ?
- [ ] P2 — Durée de conservation des MP3 et des fichiers importés (proposition : 30 jours).
- [ ] P3 — Taille maximale d'un fichier importé (proposition : 2 Go).
- [ ] P4 — Prestataire de paiement et structure juridique qui encaisse (Stripe ou Paddle ; Stripe semble fermé aux entreprises du Burkina Faso, à vérifier).
- [ ] P5 — Langues de l'interface au lancement (proposition : anglais et français).
- [ ] P6 — Autoriser un premier essai sans compte depuis la page d'accueil ?
- [ ] P7 — Nombre de crédits IA inclus chaque mois dans le Pro ; une analyse d'essai en Gratuit pour déclencher l'upgrade ?
- [ ] P8 — Fournisseur du modèle de langage utilisé pour F7.
- [ ] P9 — Durée maximale d'une vidéo analysable (proposition : 3 minutes).
- [ ] P10 — Avis juridique sur la récupération des vidéos YouTube et TikTok, et rédaction des CGU et de la politique de confidentialité (texte définitif à demander, ne pas inventer).
