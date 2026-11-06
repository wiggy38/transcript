<?php

namespace App\Services\Traduction;

/**
 * Sortie structurée d'un morceau : un élément par segment, identifiant et texte traduit. Les identifiants
 * attendus forment une énumération, construite pour chaque morceau ; les horodatages n'y figurent pas,
 * ils sont recopiés côté serveur.
 */
final class SchemaTraduction
{
    /**
     * @param  list<string>  $identifiants
     * @return array<string, mixed>
     */
    public static function pour(array $identifiants): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['segments'],
            'properties' => [
                'segments' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['id', 'texte'],
                        'properties' => [
                            'id' => ['type' => 'string', 'enum' => array_values($identifiants)],
                            'texte' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
