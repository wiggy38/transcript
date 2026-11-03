# Mesures de l'étape 1 : chaîne réelle sur des vidéos publiques

Date : 3 novembre 2026. Machine de développement Windows 11, PHP 8.4, PostgreSQL local, Redis local, yt-dlp 2026.10.26 (winget), FFmpeg 9.0.1, ElevenLabs Scribe v2 (vraie clé, `.env`). Aucun proxy (`VOXIA_PROXY_URL` vide).

Méthode : commande `php artisan voxia:transcribe <lien> --user=…` sur les liens fournis, traitement exécuté par `queue:work` sur les files `high` et `default` (Horizon ne démarre pas sous Windows, voir plus bas). Durées lues dans `processing_logs` (`duree_ms`), coût dans `cout_usd`, taille des MP3 sur le disque `local`.

Comptes : deux comptes de test locaux (`mesures-free@voxia.test`, plan gratuit ; `mesures-pro@voxia.test`, plan Pro). Le compte `m.miguellao@gmail.com` n'existe pas encore dans la base locale.

## 1. Résultats par vidéo

Durées par étape en secondes. Coût en dollars US (`cout_usd`, un seul poste : Scribe). MP3 en Mo (1 Mo = 1 048 576 octets).

| # | Lien | Source | Statut | Motif d'échec | Durée vidéo | Métadonnées | Téléchargement | Extraction MP3 | Transcription | Paragraphes | Enregistrement | **Total** | Coût estimé | Taille MP3 |
|---|------|--------|--------|---------------|-------------|-------------|----------------|----------------|---------------|-------------|----------------|-----------|-------------|-----------|
| 1 | `youtube.com/shorts/T0e-banlh_A` | YouTube | Terminé | — | 2 min 8 s (128 s) | 13,0 | 15,0 | 3,8 | 9,0 | 0,0 | 0,2 | **41,1 s** | 0,0078 $ | 2,93 Mo (3 067 926 o) |
| 2 | `youtube.com/watch?v=I827M8JW2eg` | YouTube | Terminé | — | 9 min 51 s (591 s) | 6,3 | 8,2 | 15,3 | 23,7 | 0,0 | 0,7 | **54,2 s** | 0,0361 $ | 13,53 Mo (14 186 422 o) |
| 3 | TikTok `…/7619058999111011598` | TikTok | Terminé | — | 1 min 28 s (88 s) | 5,8 | 8,4 | 5,4 | 8,3 | 0,0 | 0,2 | **28,1 s** | 0,0054 $ | 2,04 Mo (2 136 117 o) |
| 4 | TikTok `…/7680612500500532500` | TikTok | Terminé | — | 59 s | 8,0 | 7,5 | 2,8 | 6,0 | 0,0 | 0,2 | **24,4 s** | 0,0037 $ | 1,37 Mo (1 433 904 o) |
| 5 | `youtube.com/watch?v=orQKfIXMiA8` (anglais) | YouTube | Terminé | — | 5 min 49 s (349 s) | 8,4 | 10,0 | 10,6 | 18,1 | 0,0 | 0,3 | **47,4 s** | 0,0213 $ | 7,99 Mo (8 382 041 o) |
| 6 | `youtube.com/watch?v=0O5SzbiihM` | — | Refusé | « Ce lien n'est pas reconnu » | — | — | — | — | — | — | — | — | — | — |
| 7 | `youtube.com/watch?v=Unzc731iCUY` (compte Pro) | YouTube | Terminé | — | 1 h 3 min 42 s (3 822 s) | 6,8 | 15,3 | 82,9 | 96,3 | 0,0 | 3,4 | **204,7 s** | 0,2336 $ | 87,49 Mo (91 735 556 o) |

Notes :
- Les durées sont celles des journaux (`duree_ms`). Elles ne comptent pas le délai entre l'envoi dans la file et le démarrage du worker, ni le temps de la commande qui suit l'avancement.
- Vidéo 6 : l'identifiant YouTube fait 10 caractères au lieu de 11. Le lien est refusé par `ReconnaissanceLien` avant tout appel réseau : aucune transcription créée, aucun coût. C'est un refus de forme, pas un essai de lien cassé en ligne.
- Langues détectées : fr pour 1 à 4, en pour 5 (`langue_detectee`).
- Mots : 293, 1 789, 255, 241, 1 082. Paragraphes : 10, 57, 11, 9, 43.

## 2. Indicateurs

| Indicateur | Cible | Mesure | Verdict |
|------------|-------|--------|---------|
| Taux de réussite sur les liens valides | 95 % | 6 réussites sur 6 liens valides, soit 100 % | Cible atteinte, mais échantillon trop petit (voir §3) |
| Délai médian pour 10 min de vidéo | moins de 2 min | Vidéo de 9 min 51 s : 54,2 s, soit environ 55 s pour 10 min. Médiane des 6 runs, toutes durées : 44,3 s | Cible atteinte (une seule vidéo proche de 10 min) |
| Coût par heure de vidéo, hors proxys | (aucune cible chiffrée) | 0,3079 $ pour 5 037 s, soit 1,399 h, soit **0,22 $ par heure** | Conforme au tarif retenu (`voxia.costs.transcription_usd_per_hour` = 0,22) |

Calculs :
- Réussite : 6 / 6 liens valides = 100 %. Intervalle de confiance à 95 % avec 6 cas : de 54 % à 100 %.
- Délai médian : valeurs triées 24,4 ; 28,1 ; 41,1 ; 47,4 ; 54,2 ; 204,7 s, médiane (41,1 + 47,4) / 2 = 44,3 s.
- Coût horaire : somme des coûts (0,0078 + 0,0361 + 0,0054 + 0,0037 + 0,0213 + 0,2336 = 0,3079 $) divisée par la durée totale (5 037 s = 1,399 h).
- Vidéo 7 : 1 h 3 min 42 s, traitée en 3 min 25 s au total. Le téléchargement et l'extraction MP3 (82,9 s) pèsent le plus, devant la transcription (96,3 s). Compte Pro, nécessaire au-delà de 600 s.

## 3. Limites de cette mesure

- **Échantillon** : 6 liens valides. Pour démontrer 95 % de réussite avec 95 % de confiance, il faut une réussite sans échec sur environ 60 liens valides (règle de trois : 3 / 0,05).
- **Vidéo d'une heure** : une seule vidéo (lien 7, 1 h 3 min 42 s), sur le compte Pro. Le plan gratuit refuse plus de 600 s (`VOXIA_FREE_MAX_VIDEO_SECONDS`) après les métadonnées : aucun téléchargement, aucun coût.
- **Vidéo privée** : aucune vidéo privée dans la liste reçue. Le motif est prévu par le code mais n'a jamais été vu sur une vraie sortie yt-dlp (P16, P17).
- **Lien cassé** : le lien 6 est refusé par la reconnaissance locale, pas par YouTube. Un vrai lien cassé (identifiant de 11 caractères, vidéo supprimée) n'a pas été testé.
- **Deux publicités TikTok** : les liens 3 et 4 sont supposés être les deux publicités. Aucune vérification n'a été faite sur le contenu.

## 4. Anomalies rencontrées

1. **`voxia:transcribe` plante au premier suivi** : `app/Console/Commands/TranscrireVideo.php:102` utilise `Illuminate\Support\Facades\Sleep`, classe absente de Laravel 13 (la bonne est `Illuminate\Support\Sleep`). La commande sort en erreur, mais la transcription continue dans la file. Les cinq runs ont abouti. Aucun paragraphe n'a été affiché par la commande.
2. **Horizon ne démarre pas sous Windows** : `pcntl_async_signals()` est absente. Les mesures ont utilisé `queue:work redis --queue=high,default`. Horizon doit être testé sous Linux ou WSL (P29).
3. **Erreur unique non expliquée** : au premier passage du worker, `Object of class App\Enums\SourceTranscription could not be converted to string` est apparue dans la console du worker. La transcription 1 a quand même abouti, et aucune ligne d'erreur ne figure dans `storage/logs/laravel.log`. Cause non trouvée. À surveiller sur les prochains runs.
4. **Le compte `m.miguellao@gmail.com` n'existe pas** dans la base locale : `--user` exige un compte existant. Les mesures utilisent des comptes de test.

## 5. Corrections proposées (non appliquées)

- **Anomalie 1** : remplacer l'import par `use Illuminate\Support\Sleep;` dans `TranscrireVideo.php`, puis ajouter un test Pest qui exécute la commande sur une transcription déjà terminée (critère « un test par critère »).
- **Anomalie 2** : tester Horizon sous WSL, ou documenter `queue:work` comme méthode de développement sous Windows.
- **Anomalie 3** : relancer les mêmes liens avec `--verbose` côté worker et garder la trace complète si l'erreur revient ; ajouter un test de sérialisation de la source si elle se confirme.
- **Cible 95 %** : constituer un jeu de 60 liens publics valides (YouTube et TikTok, courts et longs, anglais et français) et le rejouer. Ajouter la vidéo d'une heure avec le compte Pro, une vidéo privée et une vidéo supprimée réelles.
- **Coût** : 0,22 $ par heure est cohérent avec la mesure. Rien à corriger ; le coût réel de yt-dlp est nul hors proxy.

## 6. Ce qui n'a pas été fait

- Pas de correction du code (anomalies 1 à 3 seulement signalées).
- Pas de test de vidéo privée ni de vrai lien supprimé : les liens n'étaient pas dans la liste reçue. À fournir.
- Pas de passage de la chaîne par Horizon.
