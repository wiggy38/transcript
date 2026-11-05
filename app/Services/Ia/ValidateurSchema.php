<?php

namespace App\Services\Ia;

/**
 * Validation d'une valeur décodée contre un sous-ensemble du schéma JSON : type, anyOf, enum,
 * properties, required, additionalProperties (false), items. Renvoie la première erreur, ou null.
 */
final class ValidateurSchema
{
    /** @param  array<string, mixed>  $schema */
    public function erreur(mixed $valeur, array $schema, string $chemin = '$'): ?string
    {
        if (isset($schema['anyOf'])) {
            foreach ($schema['anyOf'] as $option) {
                if ($this->erreur($valeur, $option, $chemin) === null) {
                    return null;
                }
            }

            return "{$chemin} : aucune option ne correspond";
        }

        if (isset($schema['enum']) && ! in_array($valeur, $schema['enum'], true)) {
            return "{$chemin} : valeur hors liste";
        }

        if (isset($schema['type']) && ! $this->typeCorrespond($valeur, $schema['type'])) {
            return "{$chemin} : type attendu {$schema['type']}";
        }

        if (($schema['type'] ?? null) === 'object') {
            return $this->erreurObjet($valeur, $schema, $chemin);
        }

        if (($schema['type'] ?? null) === 'array' && isset($schema['items'])) {
            foreach ($valeur as $index => $element) {
                $erreur = $this->erreur($element, $schema['items'], "{$chemin}[{$index}]");

                if ($erreur !== null) {
                    return $erreur;
                }
            }
        }

        return null;
    }

    /** @param  array<string, mixed>  $schema */
    private function erreurObjet(mixed $valeur, array $schema, string $chemin): ?string
    {
        $proprietes = $schema['properties'] ?? [];

        foreach ($schema['required'] ?? [] as $cle) {
            if (! array_key_exists($cle, $valeur)) {
                return "{$chemin} : « {$cle} » manquant";
            }
        }

        if (($schema['additionalProperties'] ?? true) === false) {
            foreach (array_keys($valeur) as $cle) {
                if (! array_key_exists($cle, $proprietes)) {
                    return "{$chemin} : « {$cle} » non attendu";
                }
            }
        }

        foreach ($proprietes as $cle => $sousSchema) {
            if (array_key_exists($cle, $valeur)) {
                $erreur = $this->erreur($valeur[$cle], $sousSchema, "{$chemin}.{$cle}");

                if ($erreur !== null) {
                    return $erreur;
                }
            }
        }

        return null;
    }

    private function typeCorrespond(mixed $valeur, string $type): bool
    {
        return match ($type) {
            'object' => is_array($valeur) && ($valeur === [] || ! array_is_list($valeur)),
            'array' => is_array($valeur) && array_is_list($valeur),
            'string' => is_string($valeur),
            'integer' => is_int($valeur),
            'number' => is_int($valeur) || is_float($valeur),
            'boolean' => is_bool($valeur),
            'null' => $valeur === null,
            default => false,
        };
    }
}
