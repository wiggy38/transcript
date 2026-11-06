<?php

namespace App\Services\Traduction;

use App\Exceptions\ErreurTraduction;

/**
 * Contrôle d'alignement. Le modèle renvoie, pour un morceau, une liste (identifiant, texte) : elle doit
 * contenir exactement les identifiants envoyés, une fois chacun, dans le même ordre, avec un texte non vide.
 * Une fusion, une coupe, un ajout ou une perte de segment ne passent pas ce contrôle.
 */
final class ValidateurTraduction
{
    /**
     * @param  mixed  $structure  sortie décodée du modèle
     * @param  list<string>  $identifiants  identifiants envoyés, dans l'ordre
     * @return array<string, string> texte traduit par identifiant
     *
     * @throws ErreurTraduction
     */
    public function morceau(mixed $structure, array $identifiants): array
    {
        if (! is_array($structure) || ! isset($structure['segments']) || ! is_array($structure['segments'])) {
            throw new ErreurTraduction(ErreurTraduction::STRUCTURE);
        }

        $segments = $structure['segments'];

        if (count($segments) !== count($identifiants)) {
            throw new ErreurTraduction(ErreurTraduction::NOMBRE_SEGMENTS);
        }

        $traduits = [];

        foreach ($segments as $index => $segment) {
            $id = $segment['id'] ?? null;
            $texte = $segment['texte'] ?? null;

            $attendu = $identifiants[$index] ?? null;

            if ($id !== $attendu) {
                // Un identifiant absent, dupliqué ou déplacé fait échouer le morceau : aucun segment n'est deviné.
                throw new ErreurTraduction(in_array($id, $identifiants, true) ? ErreurTraduction::ORDRE : ErreurTraduction::IDENTIFIANT);
            }

            if (! is_string($texte) || trim($texte) === '') {
                throw new ErreurTraduction(ErreurTraduction::TEXTE_VIDE);
            }

            $traduits[$id] = trim($texte);
        }

        return $traduits;
    }

    /**
     * Contrôle global, après tous les morceaux : chaque segment d'origine a exactement une traduction.
     *
     * @param  list<array{id: string}>  $segments
     * @param  array<string, string>  $traduits
     *
     * @throws ErreurTraduction
     */
    public function complet(array $segments, array $traduits): void
    {
        if (count($traduits) !== count($segments)) {
            throw new ErreurTraduction(ErreurTraduction::NOMBRE_SEGMENTS);
        }

        foreach ($segments as $segment) {
            if (! array_key_exists($segment['id'], $traduits)) {
                throw new ErreurTraduction(ErreurTraduction::IDENTIFIANT);
            }
        }
    }
}
