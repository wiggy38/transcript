// Polices Google Fonts (Roboto, Roboto Mono), auto-hébergées via fontsource : aucun appel vers Google.
// Latin et latin étendu, chacun chargé seulement si la page en a besoin (unicode-range).
import '@fontsource/roboto/latin-400.css';
import '@fontsource/roboto/latin-500.css';
import '@fontsource/roboto/latin-700.css';
import '@fontsource/roboto/latin-ext-400.css';
import '@fontsource/roboto/latin-ext-500.css';
import '@fontsource/roboto/latin-ext-700.css';
import '@fontsource/roboto-mono/latin-400.css';
import '@fontsource/roboto-mono/latin-500.css';
import '@fontsource/roboto-mono/latin-ext-400.css';
import '@fontsource/roboto-mono/latin-ext-500.css';

// Envoi de fichiers en morceaux (écran Nouvelle transcription). Alpine est fourni par Livewire.
import televersement from './televersement.js';
// Lecteur et texte horodaté d'une transcription terminée (cahier F2).
import lecteurTranscription from './lecteur-transcription.js';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('televersement', televersement);
    window.Alpine.data('lecteurTranscription', lecteurTranscription);
});
