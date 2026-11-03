// Lecteur d'une transcription terminée (cahier F2). Composant Alpine « lecteurTranscription », fourni
// à la vue livewire/partials/lecture-transcription. Un clic sur un horodatage lance la lecture à ce
// moment. Le paragraphe en cours est surligné et la page le suit, sauf quand l'utilisateur fait
// défiler lui-même : le bouton « Suivre la lecture » reprend alors le suivi.
// Source YouTube : API IFrame officielle. Autres sources : lecteur audio HTML sur le MP3.

const NAVIGATION_CLAVIER = new Set(['ArrowUp', 'ArrowDown', 'PageUp', 'PageDown', 'Home', 'End', ' ']);
const MARGE_PX = 16;
const POSITION_CIBLE = 0.3; // le paragraphe suivi est placé au tiers haut de la zone visible
const DELAI_COPIE_MS = 2500;

let apiYoutube = null;

function chargerApiYoutube() {
    if (window.YT && window.YT.Player) {
        return Promise.resolve(window.YT);
    }

    if (apiYoutube === null) {
        apiYoutube = new Promise((resolve, reject) => {
            const precedent = window.onYouTubeIframeAPIReady;

            window.onYouTubeIframeAPIReady = () => {
                if (typeof precedent === 'function') {
                    precedent();
                }
                resolve(window.YT);
            };

            const script = document.createElement('script');
            script.src = 'https://www.youtube.com/iframe_api';
            script.async = true;
            script.onerror = () => {
                apiYoutube = null;
                reject(new Error('api-youtube'));
            };
            document.head.append(script);
        });
    }

    return apiYoutube;
}

/** Même format que App\Support\Horodatage : mm:ss, ou h:mm:ss au-delà d'une heure. */
export function formaterTemps(ms) {
    const total = Math.floor((Number.isFinite(ms) ? Math.max(0, ms) : 0) / 1000);
    const heures = Math.floor(total / 3600);
    const minutes = String(Math.floor((total % 3600) / 60)).padStart(2, '0');
    const secondes = String(total % 60).padStart(2, '0');

    return heures > 0 ? `${heures}:${minutes}:${secondes}` : `${minutes}:${secondes}`;
}

/**
 * Index du dernier paragraphe commençant à « ms » ou avant (recherche dichotomique).
 * « debuts » est trié par ordre croissant. Renvoie -1 avant le premier paragraphe.
 */
export function indexParagraphe(debuts, ms) {
    let bas = 0;
    let haut = debuts.length - 1;
    let resultat = -1;

    while (bas <= haut) {
        const milieu = (bas + haut) >> 1;

        if (debuts[milieu] <= ms) {
            resultat = milieu;
            bas = milieu + 1;
        } else {
            haut = milieu - 1;
        }
    }

    return resultat;
}

const memoReplie = new Map();

/**
 * Texte pour la recherche : minuscules et sans accents. Chaque caractère donne un seul caractère,
 * donc les positions trouvées dans le texte replié valent dans le texte affiché.
 */
function replier(texte) {
    let sortie = '';

    for (let i = 0; i < texte.length; i++) {
        const caractere = texte[i];

        if (!memoReplie.has(caractere)) {
            const base = caractere.normalize('NFD').charAt(0).toLowerCase();
            memoReplie.set(caractere, base.length === 1 ? base : caractere);
        }

        sortie += memoReplie.get(caractere);
    }

    return sortie;
}

/** Réécrit un paragraphe avec ses résultats en <mark>. Le texte passe par textContent, jamais par innerHTML. */
function ecrireSurlignage(noeud, texte, passages, courant) {
    const fragment = document.createDocumentFragment();
    let curseur = 0;

    for (const passage of passages) {
        if (passage.debut > curseur) {
            fragment.append(texte.slice(curseur, passage.debut));
        }

        const marque = document.createElement('mark');
        marque.className = 'rounded-sm bg-accent/25 px-0.5 text-texte data-[courant=true]:bg-accent data-[courant=true]:text-fond';
        marque.textContent = texte.slice(passage.debut, passage.fin);

        if (passage === courant) {
            marque.dataset.courant = 'true';
        }

        fragment.append(marque);
        curseur = passage.fin;
    }

    if (curseur < texte.length) {
        fragment.append(texte.slice(curseur));
    }

    noeud.replaceChildren(fragment);
}

export default function lecteurTranscription(config) {
    // Données non réactives (éléments du DOM, textes d'origine, résultats) : gardées hors d'Alpine.
    let paragraphes = [];
    let debuts = [];
    let textes = [];
    let repliees = null;
    let passages = [];
    let passagesParParagraphe = new Map();
    let paragraphesSurlignes = new Set();
    let lecteurYoutube = null;
    let intervalleYoutube = null;
    let minuterieCopie = null;

    const reduit = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const noeudTexte = (index) => paragraphes[index].querySelector('[data-texte]');

    return {
        source: config.source,
        idVideo: config.idVideo,
        dureeMs: config.dureeMs,
        libelles: config.libelles,
        temps: 0,
        lecture: false,
        actif: -1,
        suivre: true,
        horodatagesVisibles: true,
        recherche: '',
        nombreResultats: 0,
        position: -1,
        messageRecherche: '',
        messageCopie: '',
        erreurLecteur: false,

        init() {
            paragraphes = Array.from(this.$refs.liste?.children ?? []);
            debuts = paragraphes.map((paragraphe) => Number(paragraphe.dataset.debutMs));
            textes = paragraphes.map((paragraphe) => paragraphe.querySelector('[data-texte]').textContent);

            if (this.source === 'youtube') {
                this.initYoutube();
            }

            this.synchroniser();
        },

        async initYoutube() {
            try {
                const YT = await chargerApiYoutube();

                lecteurYoutube = new YT.Player(this.$refs.youtube, {
                    videoId: this.idVideo,
                    width: '100%',
                    height: '100%',
                    playerVars: { playsinline: 1, rel: 0, origin: window.location.origin },
                    events: {
                        onReady: (evenement) => {
                            evenement.target.getIframe().setAttribute('title', config.libelles.lecteur);
                            intervalleYoutube ??= setInterval(() => this.synchroniser(), 250);
                        },
                        onStateChange: (evenement) => {
                            this.lecture = evenement.data === YT.PlayerState.PLAYING;
                            this.synchroniser();
                        },
                        onError: () => {
                            this.erreurLecteur = true;
                        },
                    },
                });
            } catch (_) {
                this.erreurLecteur = true;
            }
        },

        /** Position actuelle en millisecondes, lue sur le lecteur réellement présent. */
        positionMs() {
            if (lecteurYoutube && typeof lecteurYoutube.getCurrentTime === 'function') {
                return lecteurYoutube.getCurrentTime() * 1000;
            }

            return (this.$refs.audio?.currentTime ?? 0) * 1000;
        },

        synchroniser() {
            const ms = this.positionMs();
            this.temps = ms;
            this.suivreLecture(ms);
        },

        /** Clic sur un horodatage : lecture à ce moment, à la milliseconde près. */
        aller(ms) {
            this.suivre = true;
            this.temps = ms;

            if (lecteurYoutube) {
                lecteurYoutube.seekTo(ms / 1000, true);
                lecteurYoutube.playVideo();
            } else if (this.$refs.audio) {
                this.$refs.audio.currentTime = ms / 1000;
                Promise.resolve(this.$refs.audio.play()).catch(() => {});
            }

            this.suivreLecture(ms);
        },

        suivreLecture(ms) {
            const index = indexParagraphe(debuts, ms);

            if (index === this.actif) {
                return;
            }

            if (this.actif >= 0) {
                paragraphes[this.actif]?.removeAttribute('data-actif');
                paragraphes[this.actif]?.removeAttribute('aria-current');
            }

            this.actif = index;

            if (index >= 0) {
                paragraphes[index].setAttribute('data-actif', 'true');
                paragraphes[index].setAttribute('aria-current', 'true');

                if (this.suivre) {
                    this.defiler(paragraphes[index]);
                }
            }
        },

        /**
         * Fait défiler la page vers l'élément seulement s'il sort de la zone visible, et à vitesse
         * douce : le paragraphe se place au tiers haut. « forcer » ignore cette condition (recherche).
         */
        defiler(element, forcer = false) {
            const bandeHaute = MARGE_PX;
            const bandeBasse = window.innerHeight - MARGE_PX;
            const rect = element.getBoundingClientRect();

            if (! forcer && rect.top >= bandeHaute && rect.bottom <= bandeBasse) {
                return;
            }

            const cible = bandeHaute + (bandeBasse - bandeHaute) * POSITION_CIBLE;

            window.scrollBy({ top: rect.top - cible, behavior: reduit() ? 'auto' : 'smooth' });
        },

        /**
         * Interaction de l'utilisateur avec le défilement (molette, tactile, touches de navigation,
         * barre de défilement) : le suivi s'arrête. Le clic sur un horodatage le réactive.
         */
        surInteraction(evenement) {
            if (evenement.type === 'keydown') {
                const cible = evenement.target.tagName;

                if (! NAVIGATION_CLAVIER.has(evenement.key) || ['INPUT', 'TEXTAREA', 'SELECT'].includes(cible)) {
                    return;
                }
            }

            // Clic dans la barre de défilement verticale : à droite de la zone de contenu.
            if (evenement.type === 'pointerdown' && evenement.clientX < document.documentElement.clientWidth) {
                return;
            }

            this.suivre = false;
        },

        basculerSuivre() {
            this.suivre = ! this.suivre;

            if (this.suivre && this.actif >= 0) {
                this.defiler(paragraphes[this.actif]);
            }
        },

        basculerHorodatages() {
            this.horodatagesVisibles = ! this.horodatagesVisibles;
        },

        formater(ms) {
            return formaterTemps(ms);
        },

        /* Recherche */

        rechercher() {
            const terme = replier(this.recherche.trim());

            passages = [];
            passagesParParagraphe = new Map();

            if (terme !== '') {
                repliees ??= textes.map((texte) => replier(texte));

                repliees.forEach((texte, index) => {
                    let position = texte.indexOf(terme);

                    while (position !== -1) {
                        const passage = { index, debut: position, fin: position + terme.length };
                        passages.push(passage);

                        if (! passagesParParagraphe.has(index)) {
                            passagesParParagraphe.set(index, []);
                        }
                        passagesParParagraphe.get(index).push(passage);

                        position = texte.indexOf(terme, position + terme.length);
                    }
                });
            }

            this.nombreResultats = passages.length;
            this.position = passages.length > 0 ? 0 : -1;
            this.dessiner();

            if (this.position >= 0) {
                this.defiler(paragraphes[passages[0].index], true);
            }

            this.mettreAJourMessage();
        },

        /** Affiche les résultats dans les paragraphes concernés, et restaure les autres. */
        dessiner() {
            const courant = this.position >= 0 ? passages[this.position] : null;
            const nouveaux = new Set(passagesParParagraphe.keys());

            for (const index of paragraphesSurlignes) {
                if (! nouveaux.has(index)) {
                    noeudTexte(index).textContent = textes[index];
                }
            }

            for (const index of nouveaux) {
                ecrireSurlignage(noeudTexte(index), textes[index], passagesParParagraphe.get(index), courant);
            }

            paragraphesSurlignes = nouveaux;
        },

        suivant() {
            this.deplacer(1);
        },

        precedent() {
            this.deplacer(-1);
        },

        deplacer(pas) {
            if (passages.length === 0) {
                return;
            }

            this.position = (this.position + pas + passages.length) % passages.length;
            this.dessiner();
            this.defiler(paragraphes[passages[this.position].index], true);
            this.mettreAJourMessage();
        },

        effacerRecherche() {
            this.recherche = '';
            this.rechercher();
        },

        mettreAJourMessage() {
            if (this.recherche.trim() === '') {
                this.messageRecherche = '';
            } else if (passages.length === 0) {
                this.messageRecherche = this.libelles.aucun;
            } else {
                this.messageRecherche = this.libelles.sur
                    .replace(':position', String(this.position + 1))
                    .replace(':total', String(passages.length));
            }
        },

        /* Copie */

        async copier() {
            try {
                await navigator.clipboard.writeText(textes.join('\n\n'));
                this.messageCopie = this.libelles.copieOk;
            } catch (_) {
                this.messageCopie = this.libelles.copieEchec;
            }

            clearTimeout(minuterieCopie);
            minuterieCopie = setTimeout(() => {
                this.messageCopie = '';
            }, DELAI_COPIE_MS);
        },
    };
}
