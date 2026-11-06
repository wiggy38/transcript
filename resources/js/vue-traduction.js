// Vue côte à côte d'une traduction (V2). Le lecteur diffuse la position (événement « voxia-temps ») ; le segment
// qui la contient est signalé pour surligner l'original et la traduction à la fois. Alpine est fourni par Livewire.

/**
 * Index du segment en cours à « ms », ou -1 entre deux segments.
 * « debuts » et « fins » sont triés par ordre croissant, de même longueur (recherche dichotomique).
 */
export function segmentEnCours(debuts, fins, ms) {
    let bas = 0;
    let haut = debuts.length - 1;
    let trouve = -1;

    while (bas <= haut) {
        const milieu = (bas + haut) >> 1;

        if (debuts[milieu] <= ms) {
            trouve = milieu;
            bas = milieu + 1;
        } else {
            haut = milieu - 1;
        }
    }

    return trouve >= 0 && ms < fins[trouve] ? trouve : -1;
}

export default function vueTraduction(config) {
    return {
        debuts: config.debuts,
        fins: config.fins,
        actif: -1,

        surveiller(ms) {
            const index = segmentEnCours(this.debuts, this.fins, Number(ms) || 0);

            if (index !== this.actif) {
                this.actif = index;
            }
        },
    };
}
