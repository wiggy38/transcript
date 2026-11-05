# Voxia V2 — Décisions

5 novembre 2026. Toutes les valeurs chiffrées ci-dessous sont reportées dans `config/voxia.php` ; on les ajuste là, jamais en dur dans le code.

## Offre et plans

| Décision | Valeur retenue |
| --- | --- |
| Plan Business | Validé (offre du 3 novembre 2026) : 29 $/mois ou 276 $/an, 150 h/mois, 5 h max par vidéo, file prioritaire, historique illimité + recherche |
| Exports du Business | TXT, PDF, SRT, VTT + DOCX et JSON (DOCX et JSON réservés au Business) |
| Traitement par lots | Business uniquement, 25 liens maximum par lot |
| Bibliothèque classée | Pro et Business ; Gratuit et Pack gardent la liste du MVP |
| Passage Pro → Business | Immédiat, heures et crédits du Business ouverts tout de suite |
| Passage Business → Pro | En fin de période payée |
| Prestataire de paiement | Paddle (définitif) |

## Crédits IA

| Décision | Valeur retenue |
| --- | --- |
| Crédits par mois | Gratuit 0, Pack 0, Pro 30, Business 300 |
| Crédits non utilisés | Perdus à la date de renouvellement |
| Achat de crédits supplémentaires | Hors V2 |
| Analyse de script (F7) | 1 crédit par analyse |
| Résumé et points clés | 1 crédit par heure de vidéo entamée |
| Réécriture | 1 crédit par format (article, newsletter, post LinkedIn, thread X) |
| Traduction | 1 crédit par heure entamée et par langue |

## IA et langues

| Décision | Valeur retenue |
| --- | --- |
| Fournisseur du modèle de langage | Celui déjà utilisé par F7 |
| Langues de traduction | Anglais, français, espagnol, portugais, allemand, italien, arabe, néerlandais, turc, japonais |

## Technique

| Décision | Valeur retenue |
| --- | --- |
| Accès au modèle de langage | Une interface (contrat) pour le fournisseur ; ScriptAnalyzer devient un service qui l'utilise |
| Policies | Étape 1 : Transcription et nouveaux modèles V2 ; les autres modèles au fil des étapes qui les touchent |
| Direction visuelle | « Signal » est la charte retenue, à conserver |
| Tests de recherche | Exécutés sur la même base que la production |
