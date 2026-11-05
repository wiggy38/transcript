# Handoff : refonte visuelle Voxia, direction « Nocturne »

## Vue d'ensemble
Refonte de l'apparence de Voxia (Laravel, Livewire 3, Alpine.js, Tailwind CSS v4, back-office Filament 4). Voxia transforme une vidéo YouTube, une vidéo TikTok ou un fichier en transcription horodatée, en sous-titres (SRT, VTT) et en fichier MP3.

La direction retenue s'appelle **Nocturne**. Le thème sombre est le thème par défaut : nuit indigo, vert citron pour les actions, cyan pour ce qui est en direct (lecture, traitement en cours). Un thème clair complet utilise les mêmes noms de jetons.

**Périmètre : apparence et hiérarchie visuelle uniquement.** Aucune fonctionnalité, route, donnée ou règle métier ne change.

Dépôt source : `wiggy38/transcript`, branche `feat/recuperation-videos`.

## À propos des fichiers de design
Les fichiers de `designs/` sont des **références de design en HTML** : des prototypes qui montrent l'apparence et le comportement attendus. Ce n'est pas du code de production à copier. La tâche consiste à **recréer ces designs dans le code existant de Voxia**, avec ses composants Blade, Livewire, Alpine et Tailwind v4.

Pour ouvrir les maquettes, ouvrir les fichiers `.dc.html` dans un navigateur, avec `support.js` dans le même dossier. Les styles y sont en ligne : il faut les traduire en classes Tailwind, sans reproduire les attributs `style`.

`tokens/` et `blade-reference/` sont plus proches de la cible :
- `tokens/voxia-tokens.css` et `tokens/app.css` peuvent être intégrés presque tels quels.
- `blade-reference/*.blade.php` sont des **implémentations de référence**. Il faut aligner leurs props, leurs clés de statut et leurs clés de traduction sur les composants existants (`resources/views/components/*`) avant de remplacer quoi que ce soit.

## Fidélité
**Haute fidélité** pour les jetons (couleurs, typographie, rayons, ombres), pour la landing page et pour les composants du design system. Les valeurs sont définitives, à l'exception des **prix et quotas, qui sont des valeurs provisoires** (`[prix]`, `[n]`) : ne pas les figer.

Les écrans applicatifs ont désormais leurs maquettes dédiées (écran 4) : tableau de bord, transcription, historique, compte, connexion, inscription. Elles sont **haute fidélité** et font foi sur le design system pour ces écrans.

Les écrans qui n'ont toujours pas de maquette dédiée (abonnement, pages légales, contact, FAQ, back-office) se construisent **avec les composants et les jetons** de ce kit, sans changer leur structure fonctionnelle actuelle. Les cinq états de traitement et l'écran de résultat sont montrés dans le design system.

## Contraintes non négociables
- Contraste du texte d'au moins 4,5:1 dans les deux thèmes (voir le tableau ci-dessous). Contours d'éléments d'interface d'au moins 3:1.
- Vrais `<button>` et vrais `<label for>`. Focus visible partout : `outline: 2px solid var(--vx-focus); outline-offset: 2px`.
- Toutes les animations sont coupées sous `prefers-reduced-motion: reduce` (règle globale dans `voxia-tokens.css`).
- Chaque écran qui traite un fichier montre cinq états : vide, en file d'attente, en cours (avec progression), en erreur, terminé.
- Pas de défilement horizontal sur mobile. Marge latérale de 16 px. Cibles tactiles d'au moins 44 px.
- Libellés FR (tutoiement) et EN : prévoir des libellés plus longs que ceux des maquettes. Ne jamais utiliser `nowrap` sur un texte de plus de deux mots, sauf pour les boutons et les badges.
- Pas de bibliothèque d'icônes payante.

## Écrans

### 1. Landing page (`designs/Voxia Landing - B Nocturne.dc.html`)
Fichiers du dépôt concernés : `resources/views/layouts/marketing.blade.php` et `resources/views/components/accueil/*`.

**Conteneurs.** Largeur maximale de 1120 px pour l'en-tête, les sections et le pied de page. Bloc texte du héros : 1040 px. Formulaire du héros : 680 px. Aperçu : 920 px. FAQ : 760 px. Toujours `padding-inline: 16px`.

**En-tête (sticky).**
- Fond `color-mix(in oklab, var(--vx-bg) 88%, transparent)`, `backdrop-filter: blur(10px)`, filet bas `--vx-line`. Padding `10px 16px`.
- Logo à gauche : 5 barres arrondies de 18 × 16 px (la première en `--vx-accent`), puis « Voxia » en Onest 600 17 px, `letter-spacing: -0.01em`.
- Navigation : Onest 14 px, `--vx-text-2`, `gap: 4px 20px`.
- À droite : bouton de langue (36 px de haut, filet `--vx-line`, rayon 12, JetBrains Mono 12 px), puis le lien « Se connecter » en 14 px 500.
- Mobile : la navigation passe dans un menu (bouton « Menu » de 44 px avec `aria-expanded`, panneau Alpine). Les maquettes ne montrent pas ce menu : c'est une évolution d'affichage à prévoir.

**Héros.**
- Padding `32px 16px 48px`. Fond : utilitaire `fond-heros` (lueur indigo et trame de points de 20 px à 12 %).
- Contenu centré en grille, `gap: 18px` :
  1. **Pastille des sources.** Filet `--vx-line-strong`, **rayon 18 px**, `max-width: 100%`, fond `--vx-surface`, padding `6px 14px`. Chaque élément est en `white-space: nowrap`, et la pastille peut passer sur deux lignes sans que le contenu sorte du contour. Onest 500 13 px en `--vx-text`, `gap: 6px 14px`, retour à la ligne autorisé. Contenu :
     - point « direct » de 6 px, pulsé ;
     - logo YouTube (18 × 13, rouge #FF0000) + « YouTube » ;
     - logo TikTok (14 × 16) + « TikTok » ;
     - icône fichier avec symbole lecture (16 px) + « Fichiers audio et vidéo ».
  2. **H1** (utilitaire `titre-h1`), deux blocs :
     - « Chaque vidéo contient un texte. » ;
     - « Voxia te l'écrit. » en `--vx-accent-text`.

     Deux lignes sur desktop. Sur mobile, le titre passe sur 3 ou 4 lignes : c'est accepté.
  3. **Texte d'appui** : 16 px, `--vx-text-2`, max 34em, `text-wrap: pretty`. « Transcription horodatée, sous-titres SRT et VTT, fichier MP3. Tout est prêt en quelques minutes et rangé dans ta bibliothèque. »
- **Formulaire du lien. C'est l'élément le plus important du héros.**
  - Carte : `margin-top: 32px`, padding 20 px, filet `--vx-line` (pas de contour d'accent), rayon 20 px, fond `--vx-surface`, ombre `--vx-shadow-hero`, `gap: 10px`.
  - Label « Lien de la vidéo » : Onest 600 15 px.
  - Rangée en `flex-wrap` avec `gap: 8px` :
    - champ `type="url"`, `inputmode="url"`, 54 px de haut, icône lien à 14 px du bord gauche, `padding-left: 42px`, fond `--vx-bg`, filet `--vx-line-strong` (ou `--vx-error` en erreur), rayon 12, JetBrains Mono 15 px, `flex: 1 1 260px` ;
    - bouton « Transcrire » : 54 px de haut, padding 0 24 px, `--vx-accent` / `--vx-on-accent`, Onest 600 16 px, `hover: brightness(1.1)`.
  - Erreur sous la rangée : `role="alert"`, icône et texte 14 px en `--vx-error`. Le champ porte `aria-invalid="true"`.
  - Aide (`aria-describedby`), 13 px en `--vx-text-3`, `space-between` : « Ou [dépose un fichier] · MP3, M4A, WAV, MP4, MOV, WEBM » et « Gratuit pour commencer ».

**Aperçu du résultat.** Panneau de 920 px, rayon 20, ombre `--vx-shadow`.
- En-tête : titre de la vidéo et badge « Terminé ».
- Corps sur deux colonnes en `flex-wrap` :
  - onde (72 px de haut, 48 barres, 2 px d'écart) et temps « 00:13 / 12:03 » ;
  - quatre lignes horodatées, la troisième surlignée (`--vx-mark`, horodatage en `--vx-mark-time`).
- Pied en `--vx-surface-2` avec les pastilles de format TXT, PDF, SRT, VTT, MP3 (pilules, mono 12 px).

**Sections suivantes.**
- Communs : padding `64px 16px`, filet haut `--vx-line`, surfaces qui alternent entre `--vx-bg` et `--vx-surface`. H2 avec l'utilitaire `titre-h2`, `margin-bottom: 28px`.
- **Fonctionnement** (`#fonctionnement`) : en-tête de section sur une ligne, en `flex-wrap` avec `space-between` : à gauche le H2 « Trois étapes, aucun réglage. » (« aucun réglage. » en accent), à droite le sous-titre 15 px en `--vx-text-2`, max 30em.
  - Corps : stepper dans un `<ol>` en grille `repeat(auto-fit, minmax(min(100%, 250px), 1fr))`, avec `gap: 32px 0` (aucun écart horizontal, pour que les rails se touchent).
  - En tête de chaque `<li>`, une rangée de 40 px de haut :
    - un rail horizontal de 2 px en `--vx-line`, en position absolue, `left: 0; right: 0; top: 19px`, sur toute la largeur de la colonne ;
    - à l'intérieur, un remplissage `--vx-accent` animé par `vx-line` (4,8 s, linéaire, délais de 0 / 0,8 / 1,6 s, `backwards`) ;
    - un cercle numéroté **centré** dans la colonne : 40 px, mono 500 14 px, `box-shadow: 0 0 0 6px var(--vx-bg)` pour couper le rail. Les étapes 1 et 2 ont un fond `--vx-surface`, un filet `--vx-line-strong` et un chiffre en `--vx-accent-text`. L'étape 3 est pleine : fond `--vx-accent`, chiffre en `--vx-on-accent`.
    - Sous 560 px, les rails sont masqués (`[data-connecteur] { display: none }`) : il ne reste que les cercles centrés.
  - Sous la rangée, avec `padding: 0 12px` :
    - un titre H3 en Unbounded 600 19 px, interligne 1,25, `letter-spacing: -0.02em` ;
    - une description 14 px ;
    - une mini-illustration décorative (`aria-hidden`) : panneau `--vx-surface`, rayon 14, padding 12.
  - Contenu des illustrations :
    - étape 1 : champ lien factice, séparateur « ou », zone de dépôt en pointillés ;
    - étape 2 : badge « Transcription » pulsé en `--vx-live`, trois mini-étapes dont celle du milieu en indéterminé, deux lignes squelettes horodatées ;
    - étape 3 : pastilles de téléchargement TXT, PDF, SRT, VTT, MP3, et bouton « Copier le texte ».
- **Ce que tu récupères** : section distincte, placée après Fonctionnement, fond `--vx-surface`. En-tête : H2 et sous-titre « Trois livrables pour chaque vidéo, à copier ou télécharger. ».
  - Grille de 3 colonnes `repeat(auto-fit, minmax(min(100%, 250px), 1fr))`, `gap: 16px`.
  - Chaque carte : fond `--vx-bg`, filet `--vx-line`, rayon 20, padding 20, `gap: 14px`. Elle contient, dans l'ordre :
    1. **les icônes de fichier, en haut** ;
    2. le titre H3 en Unbounded 600 19 px ;
    3. la description 14 px.
  - Icône de fichier : pastille avec filet `--vx-line-strong`, rayon 10, padding `4px 10px 4px 6px`. Elle contient un SVG de 22 × 26 (page à coin plié, bandeau coloré portant le format en JetBrains Mono 700) suivi de l'extension (`.txt`…) en mono 500 12 px.
  - Couleurs des bandeaux : TXT en `--vx-text-2`, PDF en `--vx-error`, SRT et VTT en `--vx-live`, MP3 en `--vx-accent`.
  - Cartes :
    - Transcription horodatée (TXT, PDF) ;
    - Sous-titres (SRT, VTT) ;
    - Fichier audio (MP3).
- **Pour qui ?** (`#usages`) : en-tête avec le H2 et le sous-titre « Trois usages, un même point de départ : un lien ou un fichier. ».
  - Grille de 3 colonnes `repeat(auto-fit, minmax(min(100%, 250px), 1fr))`, `gap: 16px`.
  - Chaque carte (`<article>`, fond `--vx-surface`, rayon 20, ombre `--vx-shadow`, filet `--vx-line-strong` au survol) contient :
    1. une scène animée décorative de 176 px (`aria-hidden`, fond `--vx-surface-2`, rayon 14, marge de 12 px) ;
    2. un surtitre mono 12 px en accent (« 01 · Sous-titres », « 02 · Citations », « 03 · Fiches produit ») ;
    3. un titre H3 en Unbounded 600 17 px ;
    4. une description 14 px ;
    5. des pastilles de formats utiles.
  - **Créateurs de contenu** : vignette vidéo (trame de points, bouton lecture, étiquette YouTube 12:03, pastille « .srt »). Trois sous-titres avec leur code temporel SRT se succèdent en bas (`vx-cap`, 7,5 s, délais négatifs de 0 / −5 / −2,5 s).
  - **Marketeurs** : champ de recherche où « centrée » se tape (`vx-type`, steps(8)). Un surlignage `--vx-mark` descend jusqu'à la 3ᵉ ligne (`vx-scan`), puis la pastille « Citation copiée · 00:19 » apparaît (`vx-pop`). Cycle de 6 s.
  - **E-commerçants** : transcription squelette « Démo · 03:42 », flèche animée (`vx-flow`), puis une fiche produit dont les lignes se dessinent (`vx-grow`, délais échelonnés de 0,3 s, `backwards`) et une pastille « FAQ · 4 Q ».
  - Textes courts des scènes : `white-space: nowrap` et `text-overflow: ellipsis`, pour ne jamais casser une pilule.
- **Tarifs** : trois cartes Gratuit, Pro, Pack (rayon 20, padding 20, fond `--vx-bg`). Pro a un contour de 2 px `--vx-accent` et la mention « Recommandé » en mono 12 px accent. Prix en Unbounded 600 28 px (`[prix]`), suffixe 13 px discret. Liste avec coches `--vx-success`. Bouton de 40 px (principal pour Pro, secondaire sinon). Note : « Prix et quotas provisoires. » **Pas de tableau à largeur minimale** : sur mobile, les cartes s'empilent.
- **Questions fréquentes** : `<details>`/`<summary>` natifs, summary de 48 px min, Onest 600 15 px, signe « + » en mono, réponse 14 px.
- **Appel final** : H2 « Ta prochaine vidéo, en texte. » et bouton principal de 44 px « Coller un lien » (ancre vers `#hero-lien`).
- **Pied de page** : « © Voxia » et les liens CGU, Confidentialité, Contact (13 px).

### 2. Design system (`designs/Voxia Nocturne - Design System.dc.html`)
Ce fichier sert de référence pour tous les composants et états, dans les deux thèmes :
- palette avec ratios de contraste calculés en direct ;
- typographie, icônes, composants clés ;
- micro-animations, avec un bouton qui simule `reduced-motion` ;
- maquettes accueil et **résultat et export** en desktop et en mobile ;
- **les cinq états de création et de suivi** ;
- bloc des variables CSS.

Note : dans ce fichier, les maquettes de héros et de tarifs sont des ébauches antérieures. **Pour le héros, la landing (écran 1) fait foi.**

**Résultat et export** (`livewire/partials/lecture-transcription.blade.php`) :
- En-tête : badge de statut, titre (Unbounded 22 px), métadonnées en mono 12 px.
- Desktop : colonne média (lecteur YouTube en iframe, ou lecteur audio habillé + onde), puis colonne texte. Cette colonne contient la recherche (« Précédent », « Suivant », compteur « 1 sur 1 »), les bascules `aria-pressed` « Horodatages » et « Suivre la lecture », la liste horodatée et la barre d'export.
- Mobile : le lecteur est collant (`position: sticky; top: 0`) au-dessus du texte. Les exports sont en grille de 3 colonnes dans un pied de page.
- Export : « Copier le texte » en bouton plein `--vx-text` / `--vx-bg`, case « Avec horodatages », puis TXT, PDF, SRT, VTT, MP3 en boutons secondaires.
- Format verrouillé par le plan : filet `--vx-line`, texte `--vx-text-3`, libellé « PDF · Pro » et note « À partir du plan Pro » reliée par `aria-describedby`.

**Cinq états** (`livewire/nouvelle-transcription.blade.php` et `livewire/suivi-transcription.blade.php`) :
1. **Vide** : champ lien et bouton principal, zone de dépôt (pointillés de 1,5 px `--vx-line-strong`, rayon 20) avec le bouton « Parcourir ».
2. **En file d'attente** : badge « En attente », titre, URL en mono, trois étapes vides. Message `role="status"` : « Tu peux fermer cette page… ».
3. **En cours** : badge pulsé « Transcription ». Barre d'envoi déterminée (`barre-envoi`) pour les fichiers. Trois étapes avec l'étape courante en segment indéterminé cyan (`progression-etapes`).
4. **En erreur** : badge « Échec », première étape en `--vx-error`. Message `role="alert"` sur fond `color-mix(--vx-error 9%)` : « Cette vidéo est privée. Aucun décompte n'a été fait. ». Bouton secondaire « Nouvelle transcription ».
5. **Terminé** : badge « Terminé », trois étapes vertes, puis le texte qui remplace le panneau sans rechargement.

Les étapes Téléchargement → Transcription → Terminé **dérivent des statuts existants**. Aucune nouvelle donnée n'est demandée au serveur. Le seul pourcentage réel est celui de l'envoi de fichier (`livewire-upload-progress`).

### 3. Écrans applicatifs (`designs/Voxia * - Nocturne.dc.html`)
Chaque maquette expose ses états dans le panneau Tweaks (thème sombre ou clair inclus). Tous les états listés sont à implémenter. Gabarit commun : en-tête de l'application (logo, navigation Tableau de bord / Historique / Compte avec `aria-current="page"`, jauge de quota `[n] / [limite]`), conteneur de 1120 px, marge latérale de 16 px.

- **Tableau de bord** (`Voxia Tableau de bord - Nocturne.dc.html`) : point d'entrée après connexion. Bloc « Nouvelle transcription » avec le lien et le fichier, puis les transcriptions récentes.
  - États du lien (`etatLien`) : vide, lecture des informations de la vidéo, aperçu (titre, durée, source) avant validation, limite atteinte (vidéo trop longue pour le plan), erreur.
  - États du fichier (`etatFichier`) : vide, envoi (barre déterminée), prêt, erreur.
  - Bandeau d'avertissement si l'email n'est pas vérifié (`emailVerifie`). Le lien collé est conservé.
  - Historique vide (`historiqueVide`) : message et invitation à coller un premier lien.
- **Transcription** (`Voxia Transcription - Nocturne.dc.html`) : remplace le résultat du design system.
  - Statuts (`statut`) : en attente, téléchargement, transcription, échec, terminé. Ce sont les cinq états, avec les étapes dérivées des statuts existants.
  - Source (`source`) : YouTube (lecteur en iframe) ou fichier (lecteur audio habillé avec onde). Barre de progression et minuteur sur une seule ligne.
  - Plan (`plan`) : en gratuit, les formats réservés affichent le cadenas et « À partir du plan Pro ».
  - Onglets du texte centrés et lisibles sur mobile. Barre d'export en `flex-wrap` : les boutons passent à la ligne sans se chevaucher.
  - `apercu` : variante de lecture publique (sans actions de compte).
- **Historique** (`Voxia Historique - Nocturne.dc.html`) : liste avec miniature de source, titre, durée, date et badge de statut.
  - Modes (`mode`) : liste, résultats de recherche, recherche sans résultat, historique vide.
  - Plan (`plan`) : en gratuit, mention de la durée de conservation.
  - Confirmation après suppression (`statutSupprimee`), message `role="status"`.
- **Compte** (`Voxia Compte - Nocturne.dc.html`) : sections Ta formule, Profil, Mot de passe, Langue de l'interface, Packs et factures, Vos données, Supprimer le compte (zone de danger en `--vx-error`).
  - Plan (`plan`) : gratuit, pro, pack.
  - Confirmation d'enregistrement (`statutEnregistre`).
  - Boutons de langue FR / EN en `aria-pressed`, sans retour à la ligne à l'intérieur d'un bouton.
- **Connexion** et **Inscription** (`Voxia Connexion - Nocturne.dc.html`, `Voxia Inscription - Nocturne.dc.html`) : carte centrée, boutons Google et Facebook, puis le formulaire email.
  - Erreurs (`erreur`) : aucune, formulaire (messages sous les champs, `aria-invalid`), échec Google, échec Facebook (`role="alert"` en haut de la carte).
  - `lienGarde` : si un visiteur a collé un lien avant de se connecter, un rappel indique que le lien sera repris après la connexion.

### 4. Back-office (Filament 4)
- Créer un thème Filament (fichier CSS compilé par Vite, `->viteTheme(...)` dans `AdminPanelProvider`) qui importe `voxia-tokens.css`.
- Il mappe la couleur primaire sur `--vx-accent`, les gris sur l'échelle indigo et la police sur Onest. Rayons à 12 px.
- Garder des tableaux denses : pas de trame ni de lueur, pas d'ombre sur les lignes.

## Interactions et comportements
- **Thème.** Sombre par défaut. Clair si `data-theme="light"` sur `<html>`, ou si `prefers-color-scheme: light` et qu'aucun attribut n'est posé. Un sélecteur manuel serait une fonctionnalité nouvelle, à arbitrer séparément.
- **Survol.** Bouton principal : `filter: brightness(1.1)`. Bouton secondaire : contour et texte passent en `--vx-accent-text`. Liens : `--vx-accent-text`, puis `--vx-text` au survol. Transitions de 150 ms.
- **Focus.** Anneau de 2 px `--vx-focus`, décalé de 2 px. En sombre, cyan : il reste visible sur un bouton vert.
- **Validation du lien** (côté client, avant Livewire) :
  - champ vide → « Colle le lien d'une vidéo YouTube ou TikTok. » ;
  - domaine qui n'est ni YouTube ni TikTok → « Ce lien n'est ni YouTube ni TikTok. Pour un autre fichier, dépose-le directement. ».

  L'erreur s'efface à la saisie. Si les messages serveur actuels existent déjà, ils priment.
- **Horodatages.** Bouton « Lire à partir de mm:ss » : un événement Alpine `lecteur-aller` positionne le lecteur.
- **Animations** (toutes sous `prefers-reduced-motion`) :
  - **onde** : `scaleY` de 0,4 à 1, 1,6 s, ease-in-out, décalage de −0,1 s par barre, active seulement pendant la lecture ;
  - **point direct** : opacité de 1 à 0,25, 1,6 s ;
  - **étape en cours** : segment de 40 % qui traverse la piste, 1,6 s, `cubic-bezier(0.65, 0, 0.35, 1)` ;
  - **apparition du texte** : mot à mot, 400 ms, 60 ms d'écart, **dans l'aperçu de l'accueil uniquement** ;
  - **rail des étapes** (`vx-line`), **scènes d'usage** (`vx-cap`, `vx-scan`, `vx-pop`, `vx-type`, `vx-grow`, `vx-flow`) : keyframes dans `voxia-tokens.css`. Pour toutes ces animations, l'état de repos (sans animation) est l'état final lisible : sous-titre visible, ligne surlignée, fiche complète.
  - **bandeau défilant et effet de frappe de l'accueil actuel** : à retirer.

## État
Aucun nouvel état serveur. Côté Alpine : `p` (progression d'envoi), `enLecture` et `t` (lecteur), ouverture du menu mobile. Le suivi continue de rafraîchir toutes les 3 s comme aujourd'hui.

## Jetons de design
Source : `tokens/voxia-tokens.css`. Intégration Tailwind : `tokens/app.css`. Les noms de classes existants (`bg-fond`, `text-texte`, `border-filet`, `rounded-bouton`…) sont conservés.

| Jeton | Sombre (défaut) | Clair | Usage |
|---|---|---|---|
| `--vx-bg` | #0D0B1A | #F6F5FF | Fond |
| `--vx-surface` | #15122A | #FFFFFF | Panneaux |
| `--vx-surface-2` | #1D1938 | #ECEAFB | Surface élevée, pistes |
| `--vx-line` | #2C2750 | #DDDAF2 | Filet décoratif |
| `--vx-line-strong` | #6E68A0 | #7C77A6 | Contours de champ (≥ 3:1) |
| `--vx-text` | #EEEBFF | #14112B | Texte principal |
| `--vx-text-2` | #C0BAE6 | #423D66 | Secondaire |
| `--vx-text-3` | #9690C4 | #5C5785 | Discret |
| `--vx-accent` | #C8F25A | #4A6A00 | Bouton principal, onde lue |
| `--vx-on-accent` | #0D0B1A | #FFFFFF | Texte sur accent |
| `--vx-accent-text` | #C8F25A | #4A6A00 | Liens, mots d'accent |
| `--vx-mark` | #2A2152 | #EEF8C8 | Ligne en lecture |
| `--vx-mark-time` | #4FD1E0 | #3E5700 | Horodatage de la ligne en lecture |
| `--vx-success` | #4FE0A6 | #157A4F | Terminé |
| `--vx-warning` | #FFC266 | #8A5A00 | Avertissement |
| `--vx-error` | #FF7A7A | #C0262D | Erreur |
| `--vx-focus` | #4FD1E0 | #4A6A00 | Focus |
| `--vx-live` | #4FD1E0 | #0E7C8A | En direct |
| `--vx-wave-idle` | #3A3468 | #CFCBEA | Onde non lue |

**Contrastes vérifiés :**
- texte sur fond : 16,6:1 en sombre, 17,4:1 en clair ;
- texte discret ≥ 5,6:1 dans les deux thèmes ;
- texte du bouton principal : 15,1:1 en sombre, 6,3:1 en clair ;
- ligne surlignée : 16,5:1 en clair ;
- liens accent en clair : 5,3:1 sur `--vx-surface-2`.

Attention : en clair, accent (olive) et succès (vert) sont proches. Les badges de statut portent donc toujours un libellé, et la couleur seule ne doit jamais signaler un état.

**Typographie** (Google Fonts, licence OFL, à auto-héberger en woff2, sous-ensemble latin, `font-display: swap`) :
- Onest 400/500/600/700 : texte, 15 px / 1,6 ;
- Unbounded 600 : titres uniquement, jamais sous 17 px ;
- JetBrains Mono 400/500 : horodatages (`tabular-nums`), formats, URL, métadonnées.

| Rôle | Taille | Graisse | Interligne | Approche |
|---|---|---|---|---|
| H1 | clamp(22px, 3.9vw, 44px) | Unbounded 600 | 1,15 | −0,035em |
| H2 | clamp(22px, 3vw, 30px) | Unbounded 600 | 1,15 | −0,03em |
| Prix | 28px | Unbounded 600 | 1 | — |
| Titre de carte | 16px | Onest 600 | 1,6 | — |
| Label de champ | 15px | Onest 600 | 1,6 | — |
| Corps | 15px | Onest 400 | 1,6 | — |
| Texte d'appui | 16px | Onest 400 | 1,6 | — |
| Petit | 14px | Onest 400 | 1,5 | — |
| Aide | 13px | Onest 400 | 1,5 | — |
| Donnée | 12px | JetBrains Mono 400/500 | 1,5 | — |

**Rayons** : boutons et champs 12 px, panneaux et cartes 20 px, badges et pastilles 999 px.

**Espacements** (px) : 2, 4, 6, 8, 10, 12, 14, 16, 18, 20, 24, 28, 32, 48, 64. Sections : 64 px vertical. Héros : 32 px en haut, 48 px en bas.

**Hauteurs** : boutons standard 44 px, boutons du héros 54 px, boutons de carte 40 px (40 px n'est toléré que sur desktop), bouton de langue 36 px (à passer à 44 px sur mobile).

**Ombres** :
- `--vx-shadow` : en sombre, `inset 0 0 0 1px rgb(255 255 255 / .02), 0 16px 40px -18px rgb(0 0 0 / .8)` ;
- `--vx-shadow-hero` : en sombre, `0 30px 60px -24px rgb(0 0 0 / .85)`.

## Ressources
- **Icônes** : Tabler Icons (MIT) ou Lucide (ISC), trait de 1,6 à 1,8 px, 16 à 22 px, extrémités rondes, `currentColor`. Celles utilisées : lien, envoi, lecture, pause, recherche, copier, télécharger, coche cerclée, alerte cerclée, cadenas, fichier-lecture. Un composant `<x-icone nom="…">` avec SVG en ligne suffit.
- **Logos YouTube et TikTok** : SVG en ligne, en couleurs de marque, uniquement pour identifier la source (pastille du héros, miniatures d'historique). À remplacer par les fichiers officiels des kits de marque des plateformes.
- **Logo Voxia** : provisoire (5 barres arrondies). Aucun fichier de logo n'a été fourni.
- Aucune image ni illustration. Pas de témoignages, de chiffres clients ou de logos clients.

## Fichiers
- `designs/Voxia Landing - B Nocturne.dc.html` : landing, fait foi pour le héros et l'accueil.
- `designs/Voxia Nocturne - Design System.dc.html` : jetons, composants, animations, résultat, cinq états.
- `designs/Voxia Tableau de bord - Nocturne.dc.html`, `Voxia Transcription - Nocturne.dc.html`, `Voxia Historique - Nocturne.dc.html`, `Voxia Compte - Nocturne.dc.html`, `Voxia Connexion - Nocturne.dc.html`, `Voxia Inscription - Nocturne.dc.html` : écrans applicatifs.
- `designs/Voxia Audit.dc.html` : audit de l'existant (points faibles à corriger).
- `designs/support.js` : moteur nécessaire pour ouvrir les maquettes.
- `tokens/voxia-tokens.css` : variables CSS des deux thèmes, keyframes, règle reduced-motion.
- `tokens/app.css` : `@theme inline` pour Tailwind v4, utilitaires `titre-h1`, `titre-h2`, `fond-heros`.
- `blade-reference/` : bouton, champ, badge-statut, panneau, horodatage, onde-audio, progression-etapes, barre-envoi, lecteur-audio.

## Ordre de mise en œuvre conseillé
1. Ajouter `voxia-tokens.css` et remplacer le bloc `@theme` de `resources/css/app.css`. Remplacer les couleurs codées en dur (`#1B1C1A` → `bg-surlignage`, `#4A4B47` → `bg-onde-repos`, voile de dialogue → `--vx-overlay`).
2. Charger les polices et retirer Geist et Instrument Serif. Retirer `uppercase` et `tracking-*` des libellés en mono.
3. Mettre à jour les composants Blade d'après `blade-reference/`, en conservant leurs props actuelles.
4. Landing, puis tarifs (cartes empilées en mobile), puis connexion et inscription, tableau de bord, transcription (cinq états), historique et compte.
5. Thème Filament.
6. Vérifier : axe ou Lighthouse sur chaque page, navigation clavier, `prefers-reduced-motion`, largeur de 320 px sans défilement horizontal, interface EN.
