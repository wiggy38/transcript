// Polices auto-hébergées (fontsource) : aucun appel à un CDN de polices.
// Latin et latin étendu, chacun chargé seulement si la page en a besoin (unicode-range).
import '@fontsource/geist/latin-400.css';
import '@fontsource/geist/latin-500.css';
import '@fontsource/geist/latin-600.css';
import '@fontsource/geist/latin-700.css';
import '@fontsource/geist/latin-ext-400.css';
import '@fontsource/geist/latin-ext-500.css';
import '@fontsource/geist/latin-ext-600.css';
import '@fontsource/geist/latin-ext-700.css';
import '@fontsource/geist-mono/latin-400.css';
import '@fontsource/geist-mono/latin-500.css';
import '@fontsource/geist-mono/latin-ext-400.css';
import '@fontsource/geist-mono/latin-ext-500.css';
// Titres et mots surlignés : Bricolage Grotesque, variable (graisse et axe optique).
import '@fontsource-variable/bricolage-grotesque/wght.css';

// Envoi de fichiers en morceaux (écran Nouvelle transcription). Alpine est fourni par Livewire.
import televersement from './televersement.js';
// Lecteur et texte horodaté d'une transcription terminée (cahier F2).
import lecteurTranscription from './lecteur-transcription.js';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('televersement', televersement);
    window.Alpine.data('lecteurTranscription', lecteurTranscription);
});
