<?php

namespace App\Services\Reecriture;

use App\Enums\FormatReecriture;

/**
 * Schémas JSON des sorties structurées, un par format (output_config.format). Les bornes (mots,
 * caractères, nombre d'éléments) ne sont pas dans le schéma : ValidateurReecriture les vérifie.
 */
final class SchemaReecriture
{
    /** @return array<string, mixed> */
    public static function pour(FormatReecriture $format): array
    {
        return match ($format) {
            FormatReecriture::Article => self::article(),
            FormatReecriture::Newsletter => self::newsletter(),
            FormatReecriture::LinkedIn => self::linkedin(),
            FormatReecriture::Thread => self::thread(),
        };
    }

    /** @return array<string, mixed> */
    private static function article(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['titre', 'introduction', 'intertitres', 'conclusion'],
            'properties' => [
                'titre' => ['type' => 'string'],
                'introduction' => ['type' => 'string'],
                'intertitres' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['titre', 'paragraphes'],
                        'properties' => [
                            'titre' => ['type' => 'string'],
                            'paragraphes' => ['type' => 'array', 'items' => ['type' => 'string']],
                        ],
                    ],
                ],
                'conclusion' => ['type' => 'string'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function newsletter(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['objet', 'apercu', 'accroche', 'corps', 'appel_action'],
            'properties' => [
                'objet' => ['type' => 'string'],
                'apercu' => ['type' => 'string'],
                'accroche' => ['type' => 'string'],
                'corps' => ['type' => 'array', 'items' => ['type' => 'string']],
                'appel_action' => ['type' => 'string'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function linkedin(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['accroche', 'paragraphes'],
            'properties' => [
                'accroche' => ['type' => 'string'],
                'paragraphes' => ['type' => 'array', 'items' => ['type' => 'string']],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function thread(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['messages'],
            'properties' => [
                'messages' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['texte'],
                        'properties' => ['texte' => ['type' => 'string']],
                    ],
                ],
            ],
        ];
    }
}
