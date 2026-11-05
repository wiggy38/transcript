<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Réécriture (V2) : une sortie par format (article, newsletter, post LinkedIn, thread X). La clé unique
     * devient (transcription, fonctionnalité, format) ; le résumé garde format = '' et reste unique.
     * « parametres » porte le ton et la longueur choisis.
     */
    public function up(): void
    {
        Schema::table('ai_outputs', function (Blueprint $table) {
            $table->string('format', 20)->default('')->after('fonctionnalite');
            $table->json('parametres')->nullable()->after('langue');
        });

        Schema::table('ai_outputs', function (Blueprint $table) {
            $table->dropUnique(['transcription_id', 'fonctionnalite']);
            $table->unique(['transcription_id', 'fonctionnalite', 'format']);
        });
    }

    public function down(): void
    {
        Schema::table('ai_outputs', function (Blueprint $table) {
            $table->dropUnique(['transcription_id', 'fonctionnalite', 'format']);
            $table->unique(['transcription_id', 'fonctionnalite']);
        });

        Schema::table('ai_outputs', function (Blueprint $table) {
            $table->dropColumn(['format', 'parametres']);
        });
    }
};
