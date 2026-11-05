<?php

namespace App\Services\Resume;

/**
 * Schéma JSON de la sortie du résumé (sorties structurées : output_config.format). Le modèle ne renvoie
 * jamais d'horodatage : il renvoie le numéro du paragraphe, et le serveur lit l'horodatage en base.
 * Les bornes (nombre de points, de phrases) ne sont pas dans le schéma : ValidateurResume les vérifie.
 */
final class SchemaResume
{
    /** @return array<string, mixed> */
    public static function schema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['resume', 'points_cles', 'chapitres'],
            'properties' => [
                'resume' => ['type' => 'string'],
                'points_cles' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['texte', 'paragraphe'],
                        'properties' => [
                            'texte' => ['type' => 'string'],
                            'paragraphe' => ['type' => 'integer'],
                        ],
                    ],
                ],
                'chapitres' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['titre', 'paragraphe', 'resume'],
                        'properties' => [
                            'titre' => ['type' => 'string'],
                            'paragraphe' => ['type' => 'integer'],
                            'resume' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
