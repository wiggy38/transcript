// Envoi d'un fichier importé en morceaux (cahier F1, 2 Go). Chaque morceau part dans sa propre
// requête : la limite de taille de PHP ne s'applique qu'au morceau, et un envoi interrompu
// repart du début avec un nouvel identifiant. Composant Alpine « televersement », fourni à
// la vue nouvelle-transcription ; $wire est le composant Livewire qui reçoit le fichier fini.

const extension = (nom) => (nom.split('.').pop() || '').toLowerCase();

function identifiantEnvoi() {
    // UUID v4 à partir de crypto.getRandomValues : disponible aussi hors HTTPS (réseau local).
    const octets = crypto.getRandomValues(new Uint8Array(16));
    octets[6] = (octets[6] & 0x0f) | 0x40;
    octets[8] = (octets[8] & 0x3f) | 0x80;
    const hex = [...octets].map((o) => o.toString(16).padStart(2, '0')).join('');

    return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
}

function envoyerMorceau(url, jeton, donnees, surProgression) {
    return new Promise((resolve, reject) => {
        const requete = new XMLHttpRequest();
        requete.open('POST', url);
        requete.setRequestHeader('Accept', 'application/json');
        requete.setRequestHeader('X-CSRF-TOKEN', jeton);

        requete.upload.onprogress = (evenement) => {
            if (evenement.lengthComputable) {
                surProgression(evenement.loaded);
            }
        };

        requete.onload = () => {
            let corps = {};

            try {
                corps = JSON.parse(requete.responseText);
            } catch (_) {
                corps = {};
            }

            if (requete.status >= 200 && requete.status < 300) {
                resolve(corps);
            } else {
                reject(new Error(corps.message || ''));
            }
        };

        requete.onerror = () => reject(new Error(''));

        const formulaire = new FormData();

        Object.entries(donnees).forEach(([cle, valeur]) => {
            if (valeur instanceof Blob) {
                formulaire.append(cle, valeur, 'morceau.part');
            } else {
                formulaire.append(cle, valeur);
            }
        });

        requete.send(formulaire);
    });
}

export default function televersement($wire, config) {
    return {
        etat: 'repos',
        progression: 0,
        erreur: '',
        survol: false,

        async choisir(fichier) {
            if (! fichier || this.etat === 'envoi') {
                return;
            }

            this.erreur = '';

            if (! config.extensions.includes(extension(fichier.name))) {
                return this.echouer(config.messages.format);
            }

            if (fichier.size === 0) {
                return this.echouer(config.messages.vide);
            }

            if (fichier.size > config.tailleMaxOctets) {
                return this.echouer(config.messages.trop_gros);
            }

            this.etat = 'envoi';
            this.progression = 0;

            const envoi = identifiantEnvoi();
            const total = Math.max(1, Math.ceil(fichier.size / config.morceauOctets));
            const jeton = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

            try {
                let reponse = {};

                for (let index = 0; index < total; index++) {
                    const debut = index * config.morceauOctets;

                    reponse = await envoyerMorceau(config.url, jeton, {
                        envoi,
                        index,
                        total,
                        taille: fichier.size,
                        nom: fichier.name,
                        morceau: fichier.slice(debut, debut + config.morceauOctets),
                    }, (chargé) => {
                        this.progression = Math.min(99, Math.round(((debut + chargé) / fichier.size) * 100));
                    });
                }

                if (! reponse.termine) {
                    throw new Error(config.messages.echec);
                }

                this.progression = 100;
                this.etat = 'repos';

                await $wire.fichierTermine(envoi, reponse.nom, fichier.name);
            } catch (erreur) {
                this.echouer(erreur.message || config.messages.echec);
            }
        },

        echouer(message) {
            this.etat = 'erreur';
            this.erreur = message;
        },

        libelleProgression() {
            return config.messages.progression.replace(':pourcent', String(this.progression));
        },
    };
}
