<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Les cinq parties d'un script. Une partie absente reste en ligne avec
     * detectee = false et des bornes nulles : elle n'est jamais inventée.
     */
    public function up(): void
    {
        Schema::create('script_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('script_analysis_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->string('type', 20);
            $table->unsignedInteger('debut_ms')->nullable();
            $table->unsignedInteger('fin_ms')->nullable();
            $table->text('texte')->nullable();
            $table->boolean('detectee')->default(false);

            $table->unique(['script_analysis_id', 'type']);
            $table->unique(['script_analysis_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('script_parts');
    }
};
