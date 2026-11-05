// Polices de la direction « Nocturne », auto-hébergées via fontsource : aucun appel vers Google.
// Onest (texte, variable), Unbounded 600 (titres), JetBrains Mono 400/500 (données).
import '@fontsource-variable/onest/wght.css';
import '@fontsource/unbounded/latin-600.css';
import '@fontsource/jetbrains-mono/latin-400.css';
import '@fontsource/jetbrains-mono/latin-500.css';

// Envoi de fichiers en morceaux (écran Nouvelle transcription). Alpine est fourni par Livewire.
import televersement from './televersement.js';
// Lecteur et texte horodaté d'une transcription terminée (cahier F2).
import lecteurTranscription from './lecteur-transcription.js';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('televersement', televersement);
    window.Alpine.data('lecteurTranscription', lecteurTranscription);
});
